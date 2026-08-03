# TPQ Administration System - Backend

Backend untuk sistem administrasi Taman Pendidikan Al-Qur'an (TPQ) yang dibangun menggunakan Laravel.

Backend berfungsi sebagai REST API yang menangani proses autentikasi, pengolahan data, validasi, business logic, dan komunikasi dengan database MySQL.

Frontend aplikasi dikembangkan menggunakan Next.js dan berkomunikasi dengan backend melalui API.

## Tech Stack

* Laravel
* PHP
* MySQL
* Laravel Sanctum
* Eloquent ORM
* REST API

## Features

* Authentication
* Role dan hak akses pengguna
* Manajemen santri
* Manajemen guru
* Manajemen kelas
* Absensi santri
* Keuangan SPP
* Manajemen aset
* Notifikasi
* Dashboard
* Laporan
* Validasi data
* Protected API

## Project Structure

Struktur utama backend:

```text
tpq-backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   └── Models/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── config/
├── resources/
├── storage/
├── .env
├── composer.json
└── ...
```

`app/Http/Controllers/` berisi controller yang menangani request dari client.

`app/Models/` berisi model yang digunakan untuk berinteraksi dengan database.

`database/migrations/` digunakan untuk mengatur struktur database.

`database/seeders/` digunakan untuk menyediakan data awal apabila diperlukan.

`routes/api.php` berisi route API yang digunakan oleh frontend.

## Requirements

* PHP
* Composer
* MySQL
* Git

Versi PHP dan Laravel mengikuti versi yang digunakan pada project.

## Installation

Clone repository:

```bash
git clone https://github.com/USERNAME/tpq-backend.git
cd tpq-backend
```

Install dependency:

```bash
composer install
```

Buat file environment:

```bash
copy .env.example .env
```

Linux/macOS:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

## Database

Buat database MySQL, kemudian sesuaikan konfigurasi pada `.env`.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tpq_database
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migration:

```bash
php artisan migrate
```

Jika project menggunakan seeder:

```bash
php artisan db:seed
```

atau:

```bash
php artisan migrate --seed
```

## Running the Application

Jalankan server Laravel:

```bash
php artisan serve
```

Backend akan tersedia pada:

```text
http://127.0.0.1:8000
```

API:

```text
http://127.0.0.1:8000/api
```

## Authentication

Authentication menggunakan Laravel Sanctum.

Endpoint yang membutuhkan autentikasi dilindungi menggunakan middleware:

```php
auth:sanctum
```

Dengan mekanisme tersebut, request ke endpoint tertentu harus berasal dari pengguna yang sudah terautentikasi.

## API

API digunakan oleh frontend Next.js untuk mengambil dan mengelola data.

Beberapa kelompok data yang tersedia meliputi:

* Authentication
* Santri
* Guru
* Kelas
* Absensi
* Keuangan SPP
* Aset
* Notifikasi
* Laporan

Daftar route API dapat dilihat pada:

```text
routes/api.php
```

Untuk melihat seluruh route Laravel:

```bash
php artisan route:list
```

## CORS

Karena frontend dan backend berjalan sebagai aplikasi yang terpisah, backend menggunakan konfigurasi CORS untuk mengatur request dari frontend.

Contoh saat development:

```text
Frontend
http://localhost:3000

        ↓

Backend
http://127.0.0.1:8000
```

Konfigurasi CORS disesuaikan dengan origin frontend yang digunakan.

## Database

Database menggunakan MySQL dan dikelola melalui Laravel Eloquent dan migration.

Struktur database berada pada:

```text
database/migrations/
```

Data awal dapat ditambahkan melalui:

```text
database/seeders/
```

## Security

Beberapa bagian keamanan yang diterapkan pada backend:

* Authentication menggunakan Laravel Sanctum
* Pembatasan akses menggunakan middleware
* Validasi data sebelum diproses
* Eloquent ORM untuk interaksi database
* Konfigurasi CORS
* Environment variable untuk konfigurasi yang bersifat sensitif

File `.env` tidak disertakan dalam repository dan tidak boleh digunakan untuk menyimpan credential pada repository publik.

## Useful Commands

Clear cache:

```bash
php artisan optimize:clear
```

Melihat route:

```bash
php artisan route:list
```

Migration:

```bash
php artisan migrate
```

Migration dan seeder:

```bash
php artisan migrate --seed
```

Menjalankan server:

```bash
php artisan serve
```

## Frontend

Frontend aplikasi dikembangkan menggunakan Next.js.

Repository frontend:

```text
tpq-frontend
```

Alur komunikasi aplikasi:

```text
Next.js
   │
   │ REST API
   ▼
Laravel
   │
   ▼
MySQL
```

## Author

Wong Sepele
