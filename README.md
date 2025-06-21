📦 Investo - Sistem Manajemen Inventaris Barang

Investo adalah aplikasi web berbasis PHP yang digunakan untuk mencatat, memantau, dan mengelola data 
inventaris barang, kategori, transaksi, dan supplier. Sistem ini dirancang untuk membantu admin dan staff gudang 
dalam proses pengelolaan stok barang secara efisien dan akurat.

---

🧰 Teknologi yang Digunakan

- PHP 8.1
- MySQL (phpMyAdmin)
- HTML/CSS
- Bootstrap / Tailwind (opsional tergantung UI)
- JavaScript
- XAMPP / LAMPP sebagai server lokal

---

📁 Struktur Fitur

| File | Deskripsi |
|------|-----------|
| `dashboard.php` | Halaman utama setelah login yang menampilkan ringkasan data. |
| `barang.php` | Modul CRUD untuk data barang (stok, harga, deskripsi, gambar, dsb). |
| `kategori.php` | Mengelola kategori barang. |
| `supplier.php` | Mengelola data supplier. |
| `transaksi.php` | Mencatat transaksi masuk dan keluar barang. |
| `users.php` | Modul manajemen akun pengguna (admin & staff). |
| `profile.php` | Modul profil pengguna aktif. |
| `laporan.php` | Menampilkan dan mencetak laporan transaksi. |
| `log-aktivitas.php` | Mencatat semua aktivitas user seperti login, tambah data, edit, dll. |

---

🗄️ Struktur Database

Nama database: `investo_db`  
Tabel-tabel utama:
- `users`: Menyimpan data akun pengguna.
- `barang`: Data barang lengkap (stok, harga, gambar, dll).
- `kategori`: Kategori barang (alat tulis, elektronik, dll).
- `supplier`: Informasi pemasok barang.
- `transaksi`: Riwayat barang masuk dan keluar.
- `log_aktivitas`: Mencatat semua aktivitas pengguna untuk audit trail.

SQL tersedia di file: `investo_db.sql`

---

🔐 Fitur Keamanan

- Login multi-user (admin & staff)
- Hashing password menggunakan `bcrypt`
- Pencatatan log aktivitas untuk jejak audit
- Validasi data pada form input

---
