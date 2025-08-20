<img width="1578" height="806" alt="image" src="https://github.com/user-attachments/assets/f4ad5a3e-f677-441f-98c2-637c0af2dc33" />

-----

# 📊 Laporan Kustom SLiMS: Analisis Jam Sibuk (Pengunjung & Transaksi)

Repositori ini berisi laporan kustom tingkat lanjut untuk SLiMS (Senayan Library Management System). Laporan ini berfungsi untuk menganalisis jam sibuk perpustakaan berdasarkan aktivitas pengunjung dan transaksi sirkulasi, lengkap dengan perbandingan periode dan visualisasi data.

-----

## ✨ Fitur Utama

Berdasarkan antarmuka baru, laporan ini sekarang mencakup fitur-fitur berikut:

  * **📅 Filter Periode Fleksibel:**

      * Pilih rentang tanggal kustom sesuai kebutuhan.
      * Gunakan filter cepat untuk periode umum (Hari ini, Kemarin, Bulan ini, Tahun ini).

  * **🔄 Analisis Komparatif:**

      * Bandingkan data periode saat ini dengan periode sebelumnya secara langsung (misal: Bulan ini vs Bulan lalu).

  * **📈 Ringkasan KPI (Key Performance Indicator):**

      * Lihat total pengunjung dan transaksi untuk kedua periode.
      * Identifikasi jam puncak untuk pengunjung dan transaksi secara otomatis.

  * **📊 Visualisasi Data Intuitif:**

      * Tabel per jam yang menampilkan perbandingan aktivitas dengan bar grafik terintegrasi untuk pemahaman yang lebih cepat.

  * **🖨️ Opsi Lanjutan:**

      * Tersedia tombol untuk mencetak laporan langsung dari halaman.
      * Opsi untuk menampilkan data dalam bentuk grafik yang terpisah.

-----

## ⚙️ Instalasi

Untuk menginstal laporan kustom ini, ikuti langkah-langkah berikut:

1.  **📁 Salin Berkas Laporan**

    Salin berkas `visitor_transaction_hour.php` ke dalam direktori instalasi SLiMS Anda di lokasi berikut:

    ```bash
    slims/admin/modules/reporting/customs/
    ```

2.  **💻 Modifikasi Berkas Konfigurasi**

    Buka dan edit berkas `customs_report_list.inc.php` yang berada di direktori:

    ```bash
    slims/admin/modules/reporting/customs/
    ```

3.  **✍️ Tambahkan Entri Menu Laporan**

    Di dalam berkas `customs_report_list.inc.php`, temukan baris komentar `/* Custom reports list */`. Tambahkan kode berikut di bawahnya untuk mendaftarkan laporan baru. **(Nama telah diperbarui)**:

    ```php
    $menu[] = array(__('Analisis Jam Sibuk (Pengunjung & Transaksi)'), MWB.'reporting/customs/visitor_transaction_hour.php', __('Analisis jam sibuk berdasarkan pengunjung dan transaksi'));
    ```

-----

## ➡️ Penggunaan

Setelah instalasi berhasil, laporan baru akan tersedia di dalam sistem SLiMS Anda.

1.  **Login** ke area admin SLiMS.
2.  Navigasi ke menu **Pelaporan (Reporting)**.
3.  Laporan baru bernama **"Analisis Jam Sibuk (Pengunjung & Transaksi)"** akan muncul dalam daftar laporan yang tersedia.

-----

## 📝 Catatan

Implementasi ini dirancang sebagai modifikasi langsung pada berkas inti SLiMS. Upaya untuk mengemasnya sebagai *plugin* mandiri telah diuji namun masih menghasilkan beberapa galat (*error*). Oleh karena itu, metode instalasi manual ini adalah yang direkomendasikan saat ini.
