# SPK Bundling Matcha Kocok - Metode ELECTRE

Sistem Pendukung Keputusan Pemilihan Paket Promosi Bundling Menu Menggunakan Metode ELECTRE (Elimination Et Choix Traduisant la Realité).

**Studi Kasus:** Matcha Kocok Cidahu, Kabupaten Sukabumi  
**Penyusun:** Ardi Rihwaldi (NIM 2411070009), Program Studi Informatika, STIKOM El Rahma Bogor  
**Platform:** PHP 8.x, MySQL/MariaDB (dengan auto-fallback SQLite), Bootstrap 5, Chart.js

---

## 📌 7 Modul Sistem (Sesuai PRD)

1. **Modul 1: Login dan Manajemen Pengguna**
   - Autentikasi dengan `password_hash` (BCrypt).
   - Pengaturan peran: **Pemilik** (akses penuh & penentu bobot/analisis) dan **Admin** (pengelola data master).
   - Sesi timeout otomatis setelah tidak aktif 120 menit.
   - Fitur ganti kata sandi mandiri.

2. **Modul 2: Data Master & Impor Penjualan**
   - **Menu & Topping:** CRUD produk, status aktif, dan HPP otomatis.
   - **Bahan Baku:** CRUD 11 jenis bahan baku, stok fisik, stok minimum, dan harga per unit.
   - **Resep & HPP:** Pengaturan 32 komposisi takaran bahan per cup minuman; peringatan jika menu/topping belum memiliki resep (HPP = 0).
   - **Riwayat Transaksi:** 709 transaksi operasional kasir (10 Feb – 9 Jul 2026) dengan filter periode tanggal.
   - **Impor Penjualan:** Impor berkas CSV dengan pemetaan kolom otomatis, penolakan baris tanggal salah, invoice duplikat, dan menu tidak dikenal.

3. **Modul 3: Manajemen Alternatif Paket Bundling**
   - Pembuatan paket manual (2 sampai 3 menu varian, diskon nominal atau persen).
   - **Generator Otomatis:** Membentuk seluruh 20 kombinasi (10 kombinasi 2 menu & 10 kombinasi 3 menu).
   - Pencegahan duplikasi susunan item yang sama persis.
   - Tampilan frekuensi dibeli bersamaan (*co-purchase frequency*) langsung dari data riil transaksi kasir.
   - Penandaan aktif/nonaktif keikutsertaan dalam analisis ELECTRE.

4. **Modul 4: Pengaturan Kriteria dan Bobot**
   - Kelola 7 kriteria keputusan ($C_1$ sampai $C_7$).
   - Validasi ketat total bobot harus tepat **1,00**.
   - Pengaturan rentang tanggal transaksi kasir yang dipakai sebagai basis data analisis.
   - Pencatatan riwayat perubahan bobot (*criteria history*) beserta catatan alasan.

5. **Modul 5: Perhitungan ELECTRE**
   - Pembentukan Matriks Keputusan ($X$) secara otomatis dari data resep, stok, dan riwayat transaksi.
   - Fasilitas koreksi manual nilai matriks dengan catatan alasan.
   - Tahapan matematis berurutan:
     1. Matriks Keputusan $X$
     2. Normalisasi Matriks $R$ ($r_{ij} = x_{ij} / \sqrt{\sum x_{kj}^2}$)
     3. Pembobotan Matriks $V$ ($v_{ij} = w_j \times r_{ij}$)
     4. Himpunan & Matriks Concordance $C$ ($c_{kl}$) serta Ambang Batas $\bar{c}$
     5. Himpunan & Matriks Discordance $D$ ($d_{kl}$) serta Ambang Batas $\bar{d}$
     6. Matriks Dominan Concordance $F$ dan Discordance $G$
     7. Matriks Agregat Dominan $E$ ($E_{kl} = F_{kl} \times G_{kl}$)
     8. Skor Bersih (*Net Score* = Outranking - Dominated) dan Peringkat.
   - Aturan *tie-break*: jika skor bersih sama, diurutkan berdasarkan $C_1$ (margin keuntungan) tertinggi, lalu $C_2$.

6. **Modul 6: Laporan Hasil Keputusan & Rekomendasi**
   - Rekomendasi paket nomor 1 terpilih dengan narasi alasan lengkap (kriteria pendukung utama dan paket yang didominasi).
   - Tabel pemeringkatan seluruh alternatif paket promosi.
   - Ekspor laporan ke **Excel (CSV)**.
   - Cetak laporan resmi (**Print PDF**) lengkap dengan lembar tanda tangan pemilik dan admin.
   - **Simulasi Proyeksi Laba:** Perhitungan estimasi omzet, HPP, dan laba bersih bulanan bila paket terjual $N$ unit/bulan.

7. **Modul 7: Dashboard SPK**
   - Rekomendasi paket promosi nomor 1 terbaru dan tanggal analisis.
   - Ringkasan bobot kriteria aktif.
   - Grafik tren omzet bulanan dan produk minuman matcha terlaris (Chart.js).
   - Peringatan stok bahan baku yang menipis (khususnya Selai Blueberry).

---

## 🧮 7 Kriteria Keputusan & Formula Perhitungan

| Kode | Kriteria | Sifat | Bobot | Cara Menghitung Sesuai PRD |
| :--- | :--- | :--- | :---: | :--- |
| **C1** | Margin Keuntungan Paket | Benefit | 0,25 | $\text{Harga Promosi} - \text{HPP Total Paket}$ |
| **C2** | Frekuensi Dibeli Bersamaan | Benefit | 0,20 | Jumlah transaksi kasir yang memuat seluruh menu penyusun paket |
| **C3** | Volume Penjualan Menu | Benefit | 0,15 | Total cup terjual seluruh menu penyusun pada periode |
| **C4** | Ketersediaan Stok Bahan | Benefit | 0,10 | $\min(\text{stok bahan} / \text{kebutuhan bahan per paket})$ |
| **C5** | Harga Bundling Promosi | Cost | 0,10 | Harga promosi paket yang ditawarkan ke konsumen |
| **C6** | Tren Penjualan Menu | Benefit | 0,10 | $\% \text{ perubahan kuantitas}: \frac{Q_{\text{paruh 2}} - Q_{\text{paruh 1}}}{Q_{\text{paruh 1}}} \times 100\%$ |
| **C7** | Kompleksitas Produksi | Cost | 0,10 | Jumlah jenis bahan baku unik pada gabungan resep paket |

---

## 🔑 Akun Login Bawaan

| Peran | Username | Kata Sandi | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Pemilik Usaha** | `pemilik` | `pemilik123` | Akses penuh seluruh modul, analisis ELECTRE, dan penentu bobot |
| **Admin Toko** | `admin` | `admin123` | Modul 1 sampai 4 (Data master, stok, resep, impor penjualan) |

---

## 🚀 Panduan Menjalankan Sistem

### Menggunakan XAMPP:
1. Pastikan folder proyek berada di `C:\xampp\htdocs\spk_matchakocok`.
2. Buka **XAMPP Control Panel**, lalu aktifkan **Apache** dan **MySQL**.
3. Buka browser dan akses alamat:
   ```
   http://localhost/spk_matchakocok/
   ```
4. Database dan data riil 709 transaksi otomatis diinisialisasi pada akses pertama (atau via `http://localhost/spk_matchakocok/install.php`).
5. Masuk dengan akun `pemilik` / `pemilik123`.

### Menggunakan Built-in PHP Server:
Jika ingin menjalankan langsung tanpa XAMPP web server:
```powershell
cd C:\xampp\htdocs\spk_matchakocok
php -S localhost:8000
```
Lalu buka browser di `http://localhost:8000/`.

