<img width="1578" height="806" alt="image" src="https://github.com/user-attachments/assets/f4ad5a3e-f677-441f-98c2-637c0af2dc33" />

-----

# 📊 Laporan Kustom SLiMS: Statistik Pengunjung & Transaksi per Jam

Repositori ini berisi sebuah laporan kustom untuk SLiMS (Senayan Library Management System) yang menampilkan data statistik pengunjung dan transaksi sirkulasi dalam basis per jam.

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

    Di dalam berkas `customs_report_list.inc.php`, temukan baris komentar `/* Custom reports list */`. Tambahkan kode berikut tepat di bawahnya untuk mendaftarkan laporan baru:

    ```php
    $menu[] = array(__('Statistik Pengunjung dan Transaksi (per jam)'), MWB.'reporting/customs/visitor_transaction_hour.php', __('Statistik Pengunjung dan Transaksi (per jam)'));
    ```

-----

## ➡️ Penggunaan

Setelah instalasi berhasil, laporan baru akan tersedia di dalam sistem SLiMS Anda.

1.  **Login** ke area admin SLiMS.
2.  Navigasi ke menu **Pelaporan (Reporting)**.
3.  Laporan baru bernama **"Statistik Pengunjung dan Transaksi (per jam)"** akan muncul dalam daftar laporan yang tersedia.

-----

## 📝 Catatan

Implementasi ini dirancang sebagai modifikasi langsung pada berkas inti SLiMS. Upaya untuk mengemasnya sebagai *plugin* mandiri telah diuji namun masih menghasilkan beberapa galat (*error*). Oleh karena itu, metode instalasi manual ini adalah yang direkomendasikan saat ini.
