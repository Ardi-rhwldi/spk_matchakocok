<?php
// app/services/ImporterService.php
require_once __DIR__ . '/../config/database.php';

class ImporterService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Impor data transaksi dari file CSV
     * Format kolom CSV yang didukung:
     * invoice_no, tanggal, product_id/product_name, qty, harga_satuan, subtotal
     *
     * @param string $filePath Path file CSV
     * @return array [success, imported_trx, imported_details, skipped_rows, errors, summary]
     */
    public function importCsv(string $filePath): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new InvalidArgumentException("File CSV tidak ditemukan atau tidak dapat dibaca.");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Gagal membuka file CSV.");
        }

        // Ambil header
        $header = fgetcsv($handle, 1000, ",");
        if (!$header) {
            fclose($handle);
            throw new InvalidArgumentException("Format file CSV kosong.");
        }

        // Normalisasi header (lowercase, trim)
        $header = array_map(function($h) {
            return strtolower(trim(str_replace(['"', "'", "\xEF\xBB\xBF"], '', $h)));
        }, $header);

        // Petakan indeks kolom
        $colMap = [
            'invoice_no' => array_search('invoice_no', $header) !== false ? array_search('invoice_no', $header) : (array_search('invoice', $header) !== false ? array_search('invoice', $header) : 0),
            'tanggal' => array_search('tanggal', $header) !== false ? array_search('tanggal', $header) : (array_search('date', $header) !== false ? array_search('date', $header) : 1),
            'product' => array_search('product_id', $header) !== false ? array_search('product_id', $header) : (array_search('produk', $header) !== false ? array_search('produk', $header) : (array_search('menu', $header) !== false ? array_search('menu', $header) : 2)),
            'qty' => array_search('qty', $header) !== false ? array_search('qty', $header) : (array_search('jumlah', $header) !== false ? array_search('jumlah', $header) : 3),
            'harga' => array_search('harga_satuan', $header) !== false ? array_search('harga_satuan', $header) : (array_search('harga', $header) !== false ? array_search('harga', $header) : 4),
        ];

        // Cache produk untuk pencocokan cepat
        $stmtP = $this->db->query("SELECT id, nama_produk, harga, hpp_manual FROM products");
        $productsById = [];
        $productsByName = [];
        while ($p = $stmtP->fetch()) {
            $productsById[$p['id']] = $p;
            $productsByName[strtolower(trim($p['nama_produk']))] = $p;
        }

        $rowNum = 1;
        $importedTrx = 0;
        $importedDetails = 0;
        $skippedRows = 0;
        $errors = [];
        $existingInvoices = [];

        // Ambil invoice yang sudah ada di database untuk validasi duplikasi (FR-2.6)
        $invRows = $this->db->query("SELECT id, invoice_no FROM transactions")->fetchAll();
        $invoiceMap = [];
        foreach ($invRows as $r) {
            $invoiceMap[$r['invoice_no']] = (int)$r['id'];
        }

        $this->db->beginTransaction();

        try {
            $stmtInsertTrx = $this->db->prepare("INSERT INTO transactions (invoice_no, tanggal, total_amount, bayar, kembali, tipe_bayar) VALUES (?, ?, ?, ?, 0, 'Tunai')");
            $stmtInsertDet = $this->db->prepare("INSERT INTO transaction_details (transaction_id, product_id, qty, harga_satuan, subtotal, hpp_satuan) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtUpdateTrx = $this->db->prepare("UPDATE transactions SET total_amount = total_amount + ?, bayar = bayar + ? WHERE id = ?");

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $rowNum++;
                if (count($data) < 3 || empty(trim($data[0] ?? ''))) {
                    continue; // Skip baris kosong
                }

                $invoiceNo = trim($data[$colMap['invoice_no']] ?? '');
                $rawDate = trim($data[$colMap['tanggal']] ?? '');
                $rawProduct = trim($data[$colMap['product']] ?? '');
                $qty = isset($colMap['qty']) && isset($data[$colMap['qty']]) ? (int)$data[$colMap['qty']] : 1;
                $rawHarga = isset($colMap['harga']) && isset($data[$colMap['harga']]) ? (float)$data[$colMap['harga']] : null;

                // 1. Validasi Tanggal (FR-2.6)
                $dateObj = DateTime::createFromFormat('Y-m-d', $rawDate) ?: DateTime::createFromFormat('d/m/Y', $rawDate);
                if (!$dateObj) {
                    $errors[] = "Baris $rowNum: Format tanggal '$rawDate' tidak valid (gunakan format YYYY-MM-DD atau DD/MM/YYYY).";
                    $skippedRows++;
                    continue;
                }
                $tanggal = $dateObj->format('Y-m-d');

                // 2. Validasi Produk Dikenal (FR-2.6)
                $matchedProduct = null;
                if (is_numeric($rawProduct) && isset($productsById[(int)$rawProduct])) {
                    $matchedProduct = $productsById[(int)$rawProduct];
                } else if (isset($productsByName[strtolower($rawProduct)])) {
                    $matchedProduct = $productsByName[strtolower($rawProduct)];
                } else {
                    // Cari partial match
                    foreach ($productsByName as $nameKey => $prodObj) {
                        if (str_contains($nameKey, strtolower($rawProduct)) || str_contains(strtolower($rawProduct), $nameKey)) {
                            $matchedProduct = $prodObj;
                            break;
                        }
                    }
                }

                if (!$matchedProduct) {
                    $errors[] = "Baris $rowNum: Menu/Produk '$rawProduct' tidak dikenal dalam master produk.";
                    $skippedRows++;
                    continue;
                }

                $prodId = (int)$matchedProduct['id'];
                $hargaSatuan = ($rawHarga !== null && $rawHarga > 0) ? $rawHarga : (float)$matchedProduct['harga'];
                $subtotal = $hargaSatuan * $qty;
                $hppSatuan = (float)($matchedProduct['hpp_manual'] ?? 0);

                // Buat atau hubungkan ke transaksi
                if (!isset($invoiceMap[$invoiceNo])) {
                    $stmtInsertTrx->execute([$invoiceNo, $tanggal, $subtotal, $subtotal]);
                    $trxId = (int)$this->db->lastInsertId();
                    $invoiceMap[$invoiceNo] = $trxId;
                    $importedTrx++;
                } else {
                    $trxId = $invoiceMap[$invoiceNo];
                    $stmtUpdateTrx->execute([$subtotal, $subtotal, $trxId]);
                }

                // Masukkan detail
                $stmtInsertDet->execute([$trxId, $prodId, $qty, $hargaSatuan, $subtotal, $hppSatuan]);
                $importedDetails++;
            }

            $this->db->commit();
            fclose($handle);

            return [
                'success' => true,
                'imported_trx' => $importedTrx,
                'imported_details' => $importedDetails,
                'skipped_rows' => $skippedRows,
                'errors' => $errors,
                'summary' => "Berhasil mengimpor $importedTrx transaksi baru ($importedDetails detail item). Baris dilewati: $skippedRows."
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            fclose($handle);
            throw $e;
        }
    }
}

