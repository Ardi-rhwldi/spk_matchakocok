<?php
// app/services/ElectreService.php

class ElectreService {

    /**
     * Jalankan algoritma ELECTRE lengkap sesuai Spesifikasi Bagian 8 PRD
     *
     * @param array $alternatives Daftar alternatif [id => ['code' => 'P01', 'name' => '...', 'c1' => ..., 'c2' => ...]]
     * @param array $criteria Daftar kriteria [kode => ['id' => 1, 'name' => '...', 'type' => 'benefit'|'cost', 'weight' => 0.25]]
     * @param array $decisionMatrix Matriks X [alt_id => [crit_code => val]]
     * @return array Hasil lengkap berupa matriks tahapan, threshold, net score, ranking, dan alasan
     * @throws InvalidArgumentException
     */
    public function calculate(array $alternatives, array $criteria, array $decisionMatrix): array {
        $m = count($alternatives);
        $n = count($criteria);

        // Validasi prasyarat: alternatif minimal 2 (FR-5.6)
        if ($m < 2) {
            throw new InvalidArgumentException("Perhitungan ELECTRE membutuhkan minimal 2 alternatif (saat ini: {$m}).");
        }

        // Validasi total bobot harus tepat 1,00 (toleransi float 0.0001) (FR-4.2, Bagian 8)
        $totalWeight = 0.0;
        foreach ($criteria as $crit) {
            $totalWeight += (float)$crit['weight'];
        }
        if (abs($totalWeight - 1.0) > 0.0001) {
            throw new InvalidArgumentException("Total bobot kriteria harus tepat 1,00 (saat ini: " . number_format($totalWeight, 4) . ").");
        }

        $altIds = array_keys($alternatives);
        $critCodes = array_keys($criteria);

        // 1. Matriks Keputusan X
        $matrixX = $decisionMatrix;

        // 2. Normalisasi Matriks R: r_ij = x_ij / sqrt(sum_i x_ij^2)
        $matrixR = [];
        $sumSquares = [];
        foreach ($critCodes as $cCode) {
            $sumSq = 0.0;
            foreach ($altIds as $aId) {
                $val = (float)($matrixX[$aId][$cCode] ?? 0);
                $sumSq += ($val * $val);
            }
            $sumSquares[$cCode] = sqrt($sumSq);
        }

        foreach ($altIds as $aId) {
            $matrixR[$aId] = [];
            foreach ($critCodes as $cCode) {
                $denom = $sumSquares[$cCode];
                $matrixR[$aId][$cCode] = ($denom > 0) ? ((float)$matrixX[$aId][$cCode] / $denom) : 0.0;
            }
        }

        // 3. Pembobotan Matriks V: v_ij = w_j * r_ij
        $matrixV = [];
        foreach ($altIds as $aId) {
            $matrixV[$aId] = [];
            foreach ($critCodes as $cCode) {
                $w = (float)$criteria[$cCode]['weight'];
                $matrixV[$aId][$cCode] = $matrixR[$aId][$cCode] * $w;
            }
        }

        // 4 & 5. Himpunan dan Indeks Concordance (C_kl, c_kl)
        // 4 & 6. Himpunan dan Indeks Discordance (D_kl, d_kl)
        $matrixC = [];
        $matrixD = [];
        $setConcordance = [];
        $setDiscordance = [];

        $sumC = 0.0;
        $sumD = 0.0;
        $pairCount = $m * ($m - 1);

        foreach ($altIds as $k) {
            $matrixC[$k] = [];
            $matrixD[$k] = [];
            $setConcordance[$k] = [];
            $setDiscordance[$k] = [];

            foreach ($altIds as $l) {
                if ($k === $l) {
                    $matrixC[$k][$l] = null;
                    $matrixD[$k][$l] = null;
                    continue;
                }

                $cSet = [];
                $dSet = [];

                // Hitung Concordance Set C_kl
                // Kriteria Benefit: v_kj >= v_lj
                // Kriteria Cost: v_kj <= v_lj
                foreach ($critCodes as $cCode) {
                    $vkj = $matrixV[$k][$cCode];
                    $vlj = $matrixV[$l][$cCode];
                    $type = strtolower($criteria[$cCode]['type'] ?? 'benefit');

                    $isConcordant = false;
                    if ($type === 'cost') {
                        if ($vkj <= ($vlj + 1e-12)) {
                            $isConcordant = true;
                        }
                    } else {
                        // Benefit
                        if ($vkj >= ($vlj - 1e-12)) {
                            $isConcordant = true;
                        }
                    }

                    if ($isConcordant) {
                        $cSet[] = $cCode;
                    } else {
                        $dSet[] = $cCode;
                    }
                }

                $setConcordance[$k][$l] = $cSet;
                $setDiscordance[$k][$l] = $dSet;

                // Indeks Concordance c_kl = sum(w_j untuk j in C_kl)
                $cVal = 0.0;
                foreach ($cSet as $cCode) {
                    $cVal += (float)$criteria[$cCode]['weight'];
                }
                $matrixC[$k][$l] = $cVal;
                $sumC += $cVal;

                // Indeks Discordance d_kl = max(|v_kj - v_lj| j in D_kl) / max(|v_kj - v_lj| seluruh j)
                $maxDiffD = 0.0;
                foreach ($dSet as $cCode) {
                    $diff = abs($matrixV[$k][$cCode] - $matrixV[$l][$cCode]);
                    if ($diff > $maxDiffD) {
                        $maxDiffD = $diff;
                    }
                }

                $maxDiffAll = 0.0;
                foreach ($critCodes as $cCode) {
                    $diff = abs($matrixV[$k][$cCode] - $matrixV[$l][$cCode]);
                    if ($diff > $maxDiffAll) {
                        $maxDiffAll = $diff;
                    }
                }

                $dVal = ($maxDiffAll > 1e-12 && count($dSet) > 0) ? ($maxDiffD / $maxDiffAll) : 0.0;
                $matrixD[$k][$l] = $dVal;
                $sumD += $dVal;
            }
        }

        // 7. Ambang Batas (Thresholds)
        // c_bar = sum(c_kl) / (m(m-1))
        // d_bar = sum(d_kl) / (m(m-1))
        $thresholdC = ($pairCount > 0) ? ($sumC / $pairCount) : 0.0;
        $thresholdD = ($pairCount > 0) ? ($sumD / $pairCount) : 0.0;

        // 8. Matriks Dominan Concordance (F), Discordance (G), dan Agregat (E)
        // F_kl = 1 jika c_kl >= c_bar, else 0
        // G_kl = 1 jika d_kl <= d_bar, else 0
        // E_kl = F_kl * G_kl
        $matrixF = [];
        $matrixG = [];
        $matrixE = [];

        foreach ($altIds as $k) {
            $matrixF[$k] = [];
            $matrixG[$k] = [];
            $matrixE[$k] = [];

            foreach ($altIds as $l) {
                if ($k === $l) {
                    $matrixF[$k][$l] = '-';
                    $matrixG[$k][$l] = '-';
                    $matrixE[$k][$l] = '-';
                    continue;
                }

                $fVal = ($matrixC[$k][$l] >= ($thresholdC - 1e-12)) ? 1 : 0;
                $gVal = ($matrixD[$k][$l] <= ($thresholdD + 1e-12)) ? 1 : 0;
                $eVal = $fVal * $gVal;

                $matrixF[$k][$l] = $fVal;
                $matrixG[$k][$l] = $gVal;
                $matrixE[$k][$l] = $eVal;
            }
        }

        // 9. Seleksi dan Peringkat (Net Score)
        // Outranking count = jumlah baris E (jumlah l di mana E_kl = 1)
        // Dominated count = jumlah kolom E (jumlah l di mana E_lk = 1)
        // Net Score = Outranking count - Dominated count
        $scores = [];
        foreach ($altIds as $k) {
            $outranking = 0;
            $dominated = 0;
            $dominatedBy = [];
            $dominates = [];

            foreach ($altIds as $l) {
                if ($k === $l) continue;

                if ($matrixE[$k][$l] === 1) {
                    $outranking++;
                    $dominates[] = $alternatives[$l]['name'];
                }
                if ($matrixE[$l][$k] === 1) {
                    $dominated++;
                    $dominatedBy[] = $alternatives[$l]['name'];
                }
            }

            $netScore = $outranking - $dominated;

            // Cari kriteria yang paling mendukung alternatif ini dibanding alternatif lainnya
            $criteriaAdvantage = [];
            foreach ($critCodes as $cCode) {
                $type = strtolower($criteria[$cCode]['type'] ?? 'benefit');
                $winCount = 0;
                foreach ($altIds as $l) {
                    if ($k === $l) continue;
                    if ($type === 'cost') {
                        if ($matrixX[$k][$cCode] <= $matrixX[$l][$cCode]) $winCount++;
                    } else {
                        if ($matrixX[$k][$cCode] >= $matrixX[$l][$cCode]) $winCount++;
                    }
                }
                $criteriaAdvantage[$cCode] = $winCount;
            }
            arsort($criteriaAdvantage);
            $topCriteriaCodes = array_slice(array_keys($criteriaAdvantage), 0, 3);
            $topCriteriaNames = [];
            foreach ($topCriteriaCodes as $c) {
                $topCriteriaNames[] = $criteria[$c]['name'] . " (" . $c . ")";
            }

            // Susun narasi alasan rekomendasi
            $alasan = "";
            if ($outranking > 0) {
                $alasan .= "Mendominasi " . $outranking . " paket (" . implode(", ", $dominates) . "). ";
            } else {
                $alasan .= "Tidak mendominasi paket lain secara mutlak. ";
            }
            if ($dominated > 0) {
                $alasan .= "Didominasi oleh " . $dominated . " paket (" . implode(", ", $dominatedBy) . "). ";
            } else {
                $alasan .= "Unggul tanpa didominasi oleh paket mana pun. ";
            }
            $alasan .= "Kriteria pendukung utama: " . implode(", ", $topCriteriaNames) . ".";

            $scores[$k] = [
                'bundle_id' => $k,
                'code' => $alternatives[$k]['code'],
                'name' => $alternatives[$k]['name'],
                'c1_margin' => (float)($matrixX[$k]['C1'] ?? 0),
                'c2_freq'   => (float)($matrixX[$k]['C2'] ?? 0),
                'outranking_count' => $outranking,
                'dominated_count'  => $dominated,
                'net_score'        => $netScore,
                'dominates_list'   => $dominates,
                'dominated_by_list'=> $dominatedBy,
                'alasan'           => $alasan,
                'status_dominasi'  => ($dominated === 0 && $outranking > 0) ? 'Dominan Kuat' : (($netScore > 0) ? 'Direkomendasikan' : (($netScore == 0) ? 'Kompetitif' : 'Kurang Direkomendasikan')),
            ];
        }

        // Peringkat disusun dari Skor Bersih tertinggi.
        // Aturan tambahan: Bila skor bersih sama, urutan ditentukan oleh nilai C1 (margin) tertinggi, lalu C2 (frekuensi beli bersamaan)
        uasort($scores, function($a, $b) {
            if ($b['net_score'] !== $a['net_score']) {
                return $b['net_score'] <=> $a['net_score'];
            }
            if (abs($b['c1_margin'] - $a['c1_margin']) > 1e-6) {
                return ($b['c1_margin'] > $a['c1_margin']) ? 1 : -1;
            }
            return ($b['c2_freq'] > $a['c2_freq']) ? 1 : -1;
        });

        $rank = 1;
        foreach ($scores as $k => &$sc) {
            $sc['rank'] = $rank++;
        }
        unset($sc);

        return [
            'matrix_x' => $matrixX,
            'matrix_r' => $matrixR,
            'matrix_v' => $matrixV,
            'matrix_c' => $matrixC,
            'matrix_d' => $matrixD,
            'threshold_c' => $thresholdC,
            'threshold_d' => $thresholdD,
            'matrix_f' => $matrixF,
            'matrix_g' => $matrixG,
            'matrix_e' => $matrixE,
            'scores' => $scores,
            'alternatives' => $alternatives,
            'criteria' => $criteria,
        ];
    }
}

