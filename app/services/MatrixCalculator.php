<?php
// app/services/MatrixCalculator.php
require_once __DIR__ . '/../config/database.php';

class MatrixCalculator {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Hitung nilai matriks keputusan X untuk semua kandidat paket yang aktif/dipilih
     *
     * @param string $startDate Tanggal awal analisis (Y-m-d)
     * @param string $endDate Tanggal akhir analisis (Y-m-d)
     * @param array|null $bundleIds Filter bundle ID tertentu
     * @return array [alternatives, criteria, matrix_x, periods]
     */
    public function buildDecisionMatrix(string $startDate, string $endDate, ?array $bundleIds = null): array {
        // 1. Ambil Kriteria
        $stmtCrit = $this->db->query("SELECT * FROM criteria ORDER BY urutan ASC");
        $criteriaRows = $stmtCrit->fetchAll();
        $criteria = [];
        foreach ($criteriaRows as $row) {
            $criteria[$row['kode']] = [
                'id' => (int)$row['id'],
                'code' => $row['kode'],
                'name' => $row['nama'],
                'type' => $row['tipe'],
                'weight' => (float)$row['bobot'],
                'keterangan' => $row['keterangan']
            ];
        }

        // 2. Ambil Paket Bundling
        if (!empty($bundleIds)) {
            $inClause = implode(',', array_map('intval', $bundleIds));
            $sqlB = "SELECT * FROM bundles WHERE id IN ($inClause) ORDER BY id ASC";
        } else {
            $sqlB = "SELECT * FROM bundles WHERE ikut_analisis = 1 ORDER BY id ASC";
        }
        $bundles = $this->db->query($sqlB)->fetchAll();

        // 3. Ambil Item Produk untuk setiap bundle
        $stmtItems = $this->db->prepare("
            SELECT bi.bundle_id, bi.product_id, bi.qty, p.product_name, p.price, p.hpp_manual
            FROM bundle_items bi
            JOIN products p ON bi.product_id = p.product_id
            WHERE bi.bundle_id = ?
        ");

        // 4. Hitung Periode Paruh Awal dan Paruh Akhir untuk C6
        $startDt = new DateTime($startDate);
        $endDt   = new DateTime($endDate);
        $diffDays = $startDt->diff($endDt)->days + 1;
        $halfDays = (int)floor($diffDays / 2);

        $midDt1 = clone $startDt;
        if ($halfDays > 0) {
            $midDt1->modify('+' . ($halfDays - 1) . ' days');
        }
        $firstHalfStart = $startDate . ' 00:00:00';
        $firstHalfEnd   = $midDt1->format('Y-m-d') . ' 23:59:59';

        $midDt2 = clone $midDt1;
        $midDt2->modify('+1 day');
        $secondHalfStart = $midDt2->format('Y-m-d') . ' 00:00:00';
        $secondHalfEnd   = $endDate . ' 23:59:59';

        $fullStart = $startDate . ' 00:00:00';
        $fullEnd   = $endDate . ' 23:59:59';

        $alternatives = [];
        $matrixX = [];

        foreach ($bundles as $b) {
            $bId = (int)$b['id'];
            $stmtItems->execute([$bId]);
            $items = $stmtItems->fetchAll();

            $productIds = [];
            $itemQtys = [];
            foreach ($items as $it) {
                $productIds[] = (int)$it['product_id'];
                $itemQtys[(int)$it['product_id']] = (int)$it['qty'];
            }

            // --- C1: Margin Keuntungan Paket (Benefit) ---
            $hppBundle = $this->calculateBundleHpp($items);
            $hargaPromosi = (float)$b['harga_promosi'];
            $c1_margin = $hargaPromosi - $hppBundle;

            // --- C2: Frekuensi Dibeli Bersamaan (Benefit) ---
            $c2_freq = $this->calculateCoPurchaseFrequency($productIds, $fullStart, $fullEnd);

            // --- C3: Volume Penjualan Menu Penyusun (Benefit) ---
            $c3_volume = $this->calculateTotalSalesVolume($productIds, $fullStart, $fullEnd);

            // --- C4: Ketersediaan Stok Bahan Baku (Benefit) ---
            $c4_stock = $this->calculateStockAvailability($items);

            // --- C5: Harga Bundling Promosi (Cost) ---
            $c5_price = $hargaPromosi;

            // --- C6: Tren Penjualan Menu Penyusun (Benefit) ---
            $c6_trend = $this->calculateSalesTrend($productIds, $firstHalfStart, $firstHalfEnd, $secondHalfStart, $secondHalfEnd);

            // --- C7: Kompleksitas Produksi (Cost) ---
            $c7_complexity = $this->calculateRecipeComplexity($productIds);

            $matrixX[$bId] = [
                'C1' => round($c1_margin, 4),
                'C2' => round($c2_freq, 4),
                'C3' => round($c3_volume, 4),
                'C4' => round($c4_stock, 4),
                'C5' => round($c5_price, 4),
                'C6' => round($c6_trend, 4),
                'C7' => round($c7_complexity, 4),
            ];

            $alternatives[$bId] = [
                'id' => $bId,
                'code' => $b['kode_paket'],
                'name' => $b['nama_paket'],
                'harga_normal' => (float)$b['harga_normal'],
                'harga_promosi' => $hargaPromosi,
                'hpp_total' => $hppBundle,
                'items' => $items,
            ];
        }

        return [
            'alternatives' => $alternatives,
            'criteria' => $criteria,
            'matrix_x' => $matrixX,
            'periods' => [
                'start' => $startDate,
                'end' => $endDate,
                'first_half' => [$firstHalfStart, $firstHalfEnd],
                'second_half' => [$secondHalfStart, $secondHalfEnd],
            ]
        ];
    }

    /**
     * Hitung HPP Paket dari total resep masing-masing item
     */
    public function calculateBundleHpp(array $items): float {
        $totalHpp = 0.0;
        foreach ($items as $it) {
            $pId = (int)$it['product_id'];
            $qty = (int)$it['qty'];

            // Jika ada HPP manual (misal topping)
            if (!empty($it['hpp_manual']) && (float)$it['hpp_manual'] > 0) {
                $totalHpp += ((float)$it['hpp_manual'] * $qty);
                continue;
            }

            // Hitung dari resep asli kasir: recipes.quantity * raw_materials.price_per_unit
            $stmtR = $this->db->prepare("
                SELECT r.quantity, rm.price_per_unit
                FROM recipes r
                JOIN raw_materials rm ON r.material_id = rm.material_id
                WHERE r.product_id = ?
            ");
            $stmtR->execute([$pId]);
            $recipes = $stmtR->fetchAll();

            $itemHpp = 0.0;
            foreach ($recipes as $r) {
                $itemHpp += ((float)$r['quantity'] * (float)$r['price_per_unit']);
            }

            if ($itemHpp == 0 && !empty($it['hpp_manual'])) {
                $itemHpp = (float)$it['hpp_manual'];
            }

            $totalHpp += ($itemHpp * $qty);
        }
        return $totalHpp;
    }

    /**
     * Hitung C2: Frekuensi dibeli bersamaan
     */
    public function calculateCoPurchaseFrequency(array $productIds, string $startDateTime, string $endDateTime): int {
        if (empty($productIds)) return 0;
        $inProds = implode(',', array_map('intval', $productIds));
        $requiredCount = (int)count($productIds);

        $sql = "
            SELECT COUNT(*) FROM (
                SELECT t.transaction_id
                FROM transactions t
                JOIN transaction_details td ON t.transaction_id = td.transaction_id
                WHERE t.transaction_date >= ? AND t.transaction_date <= ?
                  AND td.product_id IN ($inProds)
                GROUP BY t.transaction_id
                HAVING COUNT(DISTINCT td.product_id) = $requiredCount
            ) sub
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDateTime, $endDateTime]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Hitung C3: Volume penjualan total seluruh menu penyusun
     */
    public function calculateTotalSalesVolume(array $productIds, string $startDateTime, string $endDateTime): int {
        if (empty($productIds)) return 0;
        $inProds = implode(',', array_map('intval', $productIds));

        $sql = "
            SELECT COALESCE(SUM(td.quantity), 0)
            FROM transactions t
            JOIN transaction_details td ON t.transaction_id = td.transaction_id
            WHERE t.transaction_date >= ? AND t.transaction_date <= ?
              AND td.product_id IN ($inProds)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDateTime, $endDateTime]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Hitung C4: Ketersediaan stok bahan baku
     * min(current_stock / kebutuhan bahan per paket)
     */
    public function calculateStockAvailability(array $items): float {
        $materialUsage = [];
        $materialStock = [];

        foreach ($items as $it) {
            $pId = (int)$it['product_id'];
            $bundleQty = (int)$it['qty'];

            $stmtR = $this->db->prepare("
                SELECT r.material_id, r.quantity, rm.current_stock
                FROM recipes r
                JOIN raw_materials rm ON r.material_id = rm.material_id
                WHERE r.product_id = ?
            ");
            $stmtR->execute([$pId]);
            $recipes = $stmtR->fetchAll();

            foreach ($recipes as $r) {
                $mId = (int)$r['material_id'];
                $need = (float)$r['quantity'] * $bundleQty;
                if (!isset($materialUsage[$mId])) {
                    $materialUsage[$mId] = 0;
                    $materialStock[$mId] = (float)$r['current_stock'];
                }
                $materialUsage[$mId] += $need;
            }
        }

        if (empty($materialUsage)) {
            return 999.0;
        }

        $minCapacity = PHP_FLOAT_MAX;
        foreach ($materialUsage as $mId => $needed) {
            if ($needed > 0) {
                $cap = $materialStock[$mId] / $needed;
                if ($cap < $minCapacity) {
                    $minCapacity = $cap;
                }
            }
        }

        return ($minCapacity === PHP_FLOAT_MAX) ? 0.0 : $minCapacity;
    }

    /**
     * Hitung C6: Tren Penjualan Menu Penyusun
     */
    public function calculateSalesTrend(array $productIds, string $h1Start, string $h1End, string $h2Start, string $h2End): float {
        $q1 = $this->calculateTotalSalesVolume($productIds, $h1Start, $h1End);
        $q2 = $this->calculateTotalSalesVolume($productIds, $h2Start, $h2End);

        if ($q1 == 0) {
            return ($q2 > 0) ? 100.0 : 0.0;
        }

        return (($q2 - $q1) / $q1) * 100.0;
    }

    /**
     * Hitung C7: Kompleksitas Produksi
     * Jumlah bahan baku unik pada resep gabungan paket
     */
    public function calculateRecipeComplexity(array $productIds): int {
        if (empty($productIds)) return 0;
        $inProds = implode(',', array_map('intval', $productIds));

        $sql = "
            SELECT COUNT(DISTINCT material_id)
            FROM recipes
            WHERE product_id IN ($inProds)
        ";
        return (int)$this->db->query($sql)->fetchColumn();
    }
}
