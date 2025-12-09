# Economic Data API

API untuk mengambil data indikator ekonomi dari FRED (Federal Reserve Economic Data) menggunakan Laravel 11 dan autentikasi OAuth2 Passport.

## 📋 Fitur

-   OAuth2 Client Credentials Grant (Machine-to-Machine / Host-to-Host)
-   4 Endpoint utama untuk data ekonomi
-   Data real-time dari FRED API
-   Dokumentasi Swagger/OpenAPI
-   Custom middleware untuk validasi token
-   Response caching untuk performa lebih baik

## 🚀 Instalasi

### 1. Clone Repository

git clone https://github.com/rifkstwan/API-.git
cd API-

### 2. Install Dependencies

composer install

### 3. Konfigurasi Environment

cp .env.example .env
php artisan key:generate

### 4. Konfigurasi Database

Edit file `.env`:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=economic_data_api
DB_USERNAME=root
DB_PASSWORD=

### 5. Tambahkan FRED API Key

Edit file `.env`:
FRED_API_KEY=your_fred_api_key_here

Dapatkan API key gratis di: https://fred.stlouisfed.org/docs/api/api_key.html

### 6. Jalankan Migrasi & Setup Passport

php artisan migrate
php artisan passport:install
php artisan passport:client --client

### 7. Generate Swagger Documentation

php artisan l5-swagger:generate

### 8. Jalankan Server

php artisan serve
API akan berjalan di: `http://localhost:8000`

## 📖 Dokumentasi

### Swagger UI

Akses dokumentasi interaktif Swagger di:
http://localhost:8000/api/documentation

### Flow Diagram

Lihat dokumentasi lengkap flow dan arsitektur sistem di:

-   [DOCUMENTATION.md](DOCUMENTATION.md)

## 🔑 Autentikasi

### 1. Mendapatkan Access Token

curl -X POST http://localhost:8000/oauth/token
-H "Content-Type: application/json"
-d '{
"grant_type": "client_credentials",
"client_id": "YOUR_CLIENT_ID",
"client_secret": "YOUR_CLIENT_SECRET"
}'

**Response:**
{
"token_type": "Bearer",
"expires_in": 31536000,
"access_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}

### 2. Menggunakan Token

Sertakan token di header setiap request:
Authorization: Bearer YOUR_ACCESS_TOKEN

## 📡 API Endpoints

### 1. Economic Indicators

Mendapatkan indikator ekonomi terkini.

**Endpoint:**
GET /api/economic-indicators

**Response:**
{
"success": true,
"message": "Economic indicators retrieved successfully",
"data": {
"category": "Economic Indicators",
"timestamp": "2025-12-10T03:00:00+00:00",
"data": [
{
"indicator": "Gdp",
"value": 30485.729,
"unit": "Billions of Dollars",
"date": "2025-04-01",
"series_id": "GDP"
}
]
}
}

**Indikator yang tersedia:**

-   GDP (Gross Domestic Product)
-   Inflation (CPI)
-   Unemployment Rate
-   Consumer Confidence

### 2. Interest Rates

Mendapatkan data suku bunga.

**Endpoint:**
GET /api/interest-rates

**Suku bunga yang tersedia:**

-   Federal Funds Rate
-   Treasury 10-year
-   Mortgage 30-year
-   Prime Rate

### 3. Market Indicators

Mendapatkan indikator pasar keuangan.

**Endpoint:**
GET /api/market-indicators

**Indikator yang tersedia:**

-   S&P 500 Index
-   US Dollar Index
-   Oil Price (WTI)

### 4. Custom Report

Generate laporan kustom dengan indikator dan periode tertentu.

**List Indikator Tersedia:**
GET /api/custom-report/available-indicators

**Generate Report:**
POST /api/custom-report

**Request Body:**
{
"indicators": ["gdp", "inflation", "sp500"],
"start_date": "2024-01-01",
"end_date": "2025-12-31"
}

**Contoh cURL:**
curl -X POST http://localhost:8000/api/custom-report
-H "Authorization: Bearer YOUR_ACCESS_TOKEN"
-H "Content-Type: application/json"
-d '{
"indicators": ["gdp", "inflation", "sp500"],
"start_date": "2024-01-01",
"end_date": "2025-12-31"
}'

## 🛠️ Teknologi yang Digunakan

| Komponen     | Teknologi                            |
| ------------ | ------------------------------------ |
| Framework    | Laravel 11                           |
| Autentikasi  | Laravel Passport (OAuth2)            |
| Dokumentasi  | L5-Swagger (OpenAPI 3.0)             |
| External API | FRED (Federal Reserve Economic Data) |
| Database     | MySQL                                |
| Cache        | Laravel Cache (File/Redis)           |
| PHP Version  | 8.2+                                 |
## 📂 Struktur Project

### File dan Folder Penting

| Lokasi | File/Folder | Deskripsi |
|--------|-------------|-----------|
| **Controllers** | | |
| `app/Http/Controllers/` | `EconomicIndicatorController.php` | Handler untuk endpoint indikator ekonomi (GDP, Inflation, dll) |
| `app/Http/Controllers/` | `InterestRateController.php` | Handler untuk endpoint suku bunga |
| `app/Http/Controllers/` | `MarketIndicatorController.php` | Handler untuk endpoint indikator pasar |
| `app/Http/Controllers/` | `CustomReportController.php` | Handler untuk endpoint custom report |
| **Middleware** | | |
| `app/Http/Middleware/` | `CheckClientToken.php` | Custom middleware validasi OAuth2 token |
| **Config** | | |
| `config/` | `auth.php` | Konfigurasi autentikasi Passport |
| `config/` | `services.php` | Konfigurasi FRED API key |
| `config/` | `l5-swagger.php` | Konfigurasi dokumentasi Swagger |
| **Routes** | | |
| `routes/` | `api.php` | Definisi semua API endpoints |
| **Documentation** | | |
| `storage/api-docs/` | `api-docs.json` | File Swagger documentation |
| Root | `README.md` | Dokumentasi utama project |
| Root | `DOCUMENTATION.md` | Flow diagram dan arsitektur sistem |

### Struktur Folder Utama

- **app/Http/Controllers/** - Berisi 4 controller untuk API endpoints
- **app/Http/Middleware/** - Custom middleware untuk autentikasi
- **config/** - File konfigurasi (auth, services, swagger)
- **routes/** - Route definitions
- **storage/api-docs/** - Swagger documentation files


### Penjelasan Struktur

| Path                                       | Deskripsi                                     |
| ------------------------------------------ | --------------------------------------------- |
| `app/Http/Controllers/`                    | Berisi 4 controller utama untuk API endpoints |
| `app/Http/Middleware/CheckClientToken.php` | Custom middleware untuk validasi OAuth2 token |
| `config/auth.php`                          | Konfigurasi autentikasi Passport              |
| `config/services.php`                      | Konfigurasi FRED API key                      |
| `config/l5-swagger.php`                    | Konfigurasi dokumentasi Swagger               |
| `routes/api.php`                           | Definisi semua API routes                     |
| `storage/api-docs/api-docs.json`           | File Swagger documentation yang ter-generate  |

## 👥 Tim Pengembang

| No  | Nama               | NIM      |
| --- | ------------------ | --------- |
| 1   | Arya Yudha Bathara | Developer |
| 2   | Nama Anggota 2     | Developer |
| 3   | Nama Anggota 3     | Developer |
| 4   | Nama Anggota 4     | Developer |

## 📝 Lisensi

Proyek ini dibuat untuk keperluan pendidikan dalam rangka UAS mata kuliah **Pengembangan Aplikasi Bisnis**.
