# sistemperpustakaan
📚 E-Perpus: Sistem Manajemen Perpustakaan Kampus
Sistem Informasi Manajemen Perpustakaan berbasis Web yang dikembangkan dari nol menggunakan PHP Native dan arsitektur Object-Oriented Programming (OOP). Proyek ini dibangun sebagai implementasi nyata dari konsep keamanan aplikasi web (Password Hashing & Session Management) serta desain antarmuka yang modern dan responsif.

Dikembangkan oleh 
I Made Agus Satria Winanta 240040047
I Kadek Egik Dita Mahaputra 240040024

✨ Fitur Utama
🔒 Keamanan Tingkat Lanjut (Hashing): Mengamankan kredensial pengguna menggunakan algoritma password_hash() standar industri, memastikan password tidak tersimpan dalam bentuk teks biasa di database.

🛡️ Otentikasi & Otorisasi Berbasis Role (RBAC):

Admin (Pustakawan): Memiliki hak akses penuh untuk mengelola (CRUD) data katalog buku.

Mahasiswa: Memiliki hak akses khusus untuk membaca dan mengeksplorasi katalog.

📦 Manajemen Katalog Dinamis (CRUD): Fitur Create, Read, Update, dan Delete untuk manajemen inventaris buku yang terstruktur rapi menggunakan Class OOP.

🎨 Desain Modern & Responsif: Antarmuka pengguna (UI) dibangun menggunakan Bootstrap 5, terinspirasi dari tata letak perpustakaan digital modern (SLiMS-style) yang memberikan pengalaman pengguna (UX) optimal di perangkat desktop maupun mobile.

🏗️ Arsitektur Modular: Pemisahan logika kode antara koneksi database, otentikasi pengguna, dan pemrosesan data (Separation of Concerns) untuk memudahkan maintenance dan pengembangan lanjutan.

🛠️ Teknologi yang Digunakan
Back-End: PHP 8+ (Native / OOP)

Database: MySQL / MariaDB

Front-End: HTML5, CSS3, Bootstrap 5 (via CDN)

Icons: Bootstrap Icons

🚀 Panduan Instalasi (Local Development)
Ikuti langkah-langkah berikut untuk menjalankan proyek ini di komputer Anda:

Clone Repository:

Bash
git clone https://github.com/username-anda/nama-repo-anda.git
Pindahkan ke Web Server:
Pindahkan folder hasil clone ke dalam direktori web server lokal Anda (contoh: htdocs untuk XAMPP atau MAMP).

Siapkan Database:

Buka phpMyAdmin (http://localhost/phpmyadmin).

Buat database baru dengan nama db_perpustakaan.

Import file database.sql (jika Anda menyediakannya di repo) atau jalankan script pembuatan tabel dan akun default.

Konfigurasi Koneksi:
Buka file config.php dan sesuaikan kredensial database jika menggunakan konfigurasi khusus:

PHP
private $user = "root";
private $pass = ""; // Sesuaikan dengan password database Anda
Jalankan Aplikasi:
Buka browser dan akses http://localhost/nama-folder-repo-anda/index.php.

👤 Kredensial Default (Untuk Testing)
Untuk masuk ke dalam sistem saat pertama kali diinstal, Anda dapat menggunakan akun percobaan berikut:

Username: admin

Password: stikom123

Role: Admin
