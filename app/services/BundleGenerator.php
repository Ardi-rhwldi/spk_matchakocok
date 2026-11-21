<?php
// app/services/BundleGenerator.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/MatrixCalculator.php';

class BundleGenerator {
    private PDO $db;
    private MatrixCalculator $calculator;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->calculator = new MatrixCalculator($this->db);
    }

    /**
     * Generate seluruh kemungkinan kombinasi 2 menu dan 3 menu dari produk minuman aktif
     */
    public function generateCombinations(float $defaultDiscountNominal = 2000): array {
        $stmt = $this->db->query("SELECT product_id as id, product_name as nama_produk, price as harga, hpp_manual FROM products WHERE is_active = 1 AND category_id = 1 ORDER BY product_id ASC");
        $products = $stmt->fetchAll();
        $prodCount = count($products);

        $existingBundles = $this->getExistingBundleItemSignatures();

        $candidates = [];
        $candIndex = 1;

        // 1. Kombinasi 2 menu
        for ($i = 0; $i < $prodCount; $i++) {
            for ($j = $i + 1; $j < $prodCount; $j++) {
                $combo = [$products[$i], $products[$j]];
                $candidates[] = $this->buildCandidateDetail($combo, $candIndex, $defaultDiscountNominal, $existingBundles);
                $candIndex++;
            }
        }

        // 2. Kombinasi 3 menu
        for ($i = 0; $i < $prodCount; $i++) {
            for ($j = $i + 1; $j < $prodCount; $j++) {
                for ($k = $j + 1; $k < $prodCount; $k++) {
                    $combo = [$products[$i], $products[$j], $products[$k]];
                    $candidates[] = $this->buildCandidateDetail($combo, $candIndex, $defaultDiscountNominal, $existingBundles);
                    $candIndex++;
                }
            }
        }

        return $candidates;
    }

    private function buildCandidateDetail(array $combo, int $index, float $defaultDiscount, array $existingBundles): array {
        $prodIds = [];
        $names = [];
        $hargaNormal = 0.0;
        $itemsForHpp = [];

        foreach ($combo as $p) {
            $prodIds[] = (int)$p['id'];
            $names[] = $p['nama_produk'];
            $hargaNormal += (float)$p['harga'];
            $itemsForHpp[] = [
                'product_id' => (int)$p['id'],
                'qty' => 1,
                'hpp_manual' => $p['hpp_manual']
            ];
        }

        sort($prodIds);
        $signature = implode('-', $prodIds);
        $isExisting = isset($existingBundles[$signature]);

        $namaPaket = "Paket " . implode(" + ", array_map(function($n) {
            return str_replace(' Matcha', '', $n);
        }, $names));

        $diskon = $defaultDiscount;
        $hargaPromosi = max(0, $hargaNormal - $diskon);
        $hppTotal = $this->calculator->calculateBundleHpp($itemsForHpp);
        $marginAwal = $hargaPromosi - $hppTotal;

        // Hitung frekuensi bersama pada data kasir riil
        $freq = $this->calculator->calculateCoPurchaseFrequency($prodIds, '2026-01-01 00:00:00', '2030-12-31 23:59:59');

        return [
            'code' => sprintf('GEN-%02d', $index),
            'nama_paket' => $namaPaket,
            'combo_size' => count($combo),
            'product_ids' => $prodIds,
            'items' => $combo,
            'signature' => $signature,
            'is_existing' => $isExisting,
            'existing_bundle_id' => $existingBundles[$signature] ?? null,
            'harga_normal' => $hargaNormal,
            'diskon' => $diskon,
            'harga_promosi' => $hargaPromosi,
            'hpp_total' => $hppTotal,
            'margin_awal' => $marginAwal,
            'frekuensi_bersama' => $freq
        ];
    }

    private function getExistingBundleItemSignatures(): array {
        $sql = "
            SELECT bundle_id, GROUP_CONCAT(product_id ORDER BY product_id ASC SEPARATOR '-') as signature
            FROM bundle_items
            GROUP BY bundle_id
        ";
        if (Database::getActiveDriver() === 'sqlite') {
            $sql = "
                SELECT bundle_id, group_concat(product_id) as signature
                FROM (
                    SELECT bundle_id, product_id 
                    FROM bundle_items 
                    ORDER BY bundle_id, product_id ASC
                )
                GROUP BY bundle_id
            ";
        }
        $rows = $this->db->query($sql)->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            $sig = str_replace(',', '-', $r['signature']);
            $map[$sig] = (int)$r['bundle_id'];
        }
        return $map;
    }
}
