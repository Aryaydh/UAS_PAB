# Dokumentasi Flow - Economic Data API

Dokumentasi lengkap mengenai alur kerja, arsitektur sistem, dan flow diagram dari Economic Data API.

---

## 1. Authentication Flow (OAuth2 Client Credentials)

Diagram berikut menunjukkan alur autentikasi menggunakan OAuth2 Client Credentials Grant:

sequenceDiagram
participant Client as Client Application
participant API as Laravel API
participant Passport as Passport OAuth2
participant DB as Database

Client->>API: POST /oauth/token<br/>(client_id, client_secret, grant_type)
API->>Passport: Validasi credentials
Passport->>DB: Cek oauth_clients
DB-->>Passport: Client ditemukan & valid
Passport->>DB: Generate & simpan access token
DB-->>Passport: Token tersimpan
Passport-->>API: Token berhasil dibuat
API-->>Client: Response: access_token, expires_in

Note over Client: Client menyimpan access token

Client->>API: GET /api/endpoint<br/>(Authorization: Bearer token)
API->>Passport: Validasi token via middleware
Passport->>DB: Cek oauth_access_tokens
DB-->>Passport: Token valid & belum expired
Passport-->>API: Token authenticated
API-->>Client: Response: Data (200 OK)

### Penjelasan Flow:

1. **Client** mengirim request ke `/oauth/token` dengan credentials
2. **Laravel API** menerima dan meneruskan ke Passport
3. **Passport** validasi credentials di database
4. Jika valid, **generate JWT token** dan simpan ke database
5. **Response** berisi `access_token` dan `expires_in`
6. **Client** gunakan token di header `Authorization: Bearer {token}`
7. Setiap request divalidasi oleh middleware

---

## 2. API Request Flow

Diagram flowchart menunjukkan alur request API dari client hingga response:

flowchart TD
Start([Client Request
GET /api/endpoint]) --> CheckAuth{Token Valid?}

CheckAuth -->|Tidak| Unauth[Return 401<br/>Unauthenticated]
CheckAuth -->|Ya| CheckCache{Data ada<br/>di Cache?}

CheckCache -->|Ya| ReturnCache[Ambil data<br/>dari Cache]
CheckCache -->|Tidak| CallFRED[Request ke<br/>FRED API]

CallFRED --> ProcessData[Process &<br/>Format Data]
ProcessData --> SaveCache[Simpan ke<br/>Cache 1 jam]
SaveCache --> ReturnSuccess

ReturnCache --> ReturnSuccess[Return 200 OK<br/>dengan Data]

Unauth --> End([End])
ReturnSuccess --> End

style Start fill:#e1f5ff
style End fill:#e1f5ff
style Unauth fill:#ffebee
style ReturnSuccess fill:#e8f5e9
text

### Penjelasan Alur:

1. Client mengirim request dengan Bearer token
2. Middleware cek validitas token
3. Jika tidak valid → return 401 Unauthorized
4. Jika valid → cek apakah data ada di cache
5. Jika ada di cache → langsung return data
6. Jika tidak → request ke FRED API
7. Process dan format data
8. Simpan ke cache selama 1 jam
9. Return response 200 OK dengan data

---

## 3. Generate Custom Report Flow

Diagram untuk endpoint POST `/api/custom-report`:

flowchart TD
Start([POST /api/custom-report]) --> ValidateToken{Token
Valid?}

ValidateToken -->|Tidak| Error401[Return 401<br/>Unauthorized]
ValidateToken -->|Ya| ValidateInput{Input<br/>Valid?}

ValidateInput -->|Tidak| Error422[Return 422<br/>Validation Error]
ValidateInput -->|Ya| InitArray[Inisialisasi<br/>reportData array]

InitArray --> LoopStart{Masih ada<br/>indicator?}

LoopStart -->|Ya| GetIndicator[Ambil indicator<br/>dari list]
GetIndicator --> RequestFRED[Request ke FRED API<br/>dengan date range]
RequestFRED --> FormatData[Format data<br/>ke struktur JSON]
FormatData --> AddToArray[Tambahkan ke<br/>reportData]
AddToArray --> LoopStart

LoopStart -->|Tidak| Success[Return 200 OK<br/>dengan Complete Report]

Error401 --> End([End])
Error422 --> End
Success --> End

style Start fill:#e1f5ff
style End fill:#e1f5ff
style Error401 fill:#ffebee
style Error422 fill:#fff3e0
style Success fill:#e8f5e9
text

### Penjelasan:

1. Validasi token OAuth2
2. Validasi input (indicators, start_date, end_date)
3. Loop untuk setiap indicator yang diminta
4. Request data ke FRED API dengan date range
5. Format dan tambahkan ke array hasil
6. Return complete report dengan semua data

---

## 4. Arsitektur Sistem

Diagram arsitektur keseluruhan sistem:

graph TB
subgraph Client["Client Layer"]
MobileApp[Mobile App]
WebApp[Web Frontend]
BackendService[Backend Service]
end

subgraph API["Laravel API Server"]
Middleware[Middleware<br/>CheckClientToken]
Controllers[Controllers<br/>- EconomicIndicator<br/>- InterestRate<br/>- MarketIndicator<br/>- CustomReport]
Cache[Cache<br/>File/Redis]
end

subgraph Database["Database Layer"]
MySQL[(MySQL Database<br/>- oauth_clients<br/>- oauth_access_tokens<br/>- users)]
end

subgraph External["External API"]
FRED[FRED API<br/>Federal Reserve<br/>Economic Data]
end

MobileApp -->|HTTPS Request<br/>Bearer Token| Middleware
WebApp -->|HTTPS Request<br/>Bearer Token| Middleware
BackendService -->|HTTPS Request<br/>Bearer Token| Middleware

Middleware --> Controllers
Controllers --> Cache
Controllers --> MySQL
Controllers -->|HTTP Request| FRED

style Client fill:#e3f2fd
style API fill:#f3e5f5
style Database fill:#fff3e0
style External fill:#e8f5e9

### Komponen:

-   **Client Layer**: Mobile app, web frontend, atau backend service lain
-   **API Server**: Laravel 11 dengan Passport OAuth2
-   **Database**: MySQL untuk menyimpan oauth tokens
-   **External API**: FRED untuk data ekonomi real-time
-   **Cache**: Menyimpan data sementara untuk performa

---

## 5. Database Schema

erDiagram
oauth_clients ||--o{ oauth_access_tokens : "has many"

oauth_clients {
bigint id PK
bigint user_id
string name
string secret
string provider
text redirect
boolean personal_access_client
boolean password_client
boolean revoked
timestamp created_at
timestamp updated_at
}

oauth_access_tokens {
string id PK
bigint user_id
bigint client_id FK
string name
text scopes
boolean revoked
timestamp created_at
timestamp updated_at
timestamp expires_at
}

---

## 6. Endpoint Summary

| Method | Endpoint                                  | Deskripsi                | Auth Required           |
| ------ | ----------------------------------------- | ------------------------ | ----------------------- |
| POST   | `/oauth/token`                            | Mendapatkan access token | ❌ (Client Credentials) |
| GET    | `/api/economic-indicators`                | Data indikator ekonomi   | ✅ Bearer Token         |
| GET    | `/api/interest-rates`                     | Data suku bunga          | ✅ Bearer Token         |
| GET    | `/api/market-indicators`                  | Data indikator pasar     | ✅ Bearer Token         |
| GET    | `/api/custom-report/available-indicators` | List indikator tersedia  | ✅ Bearer Token         |
| POST   | `/api/custom-report`                      | Generate laporan kustom  | ✅ Bearer Token         |

---

## 7. Response Structure

### Success Response (200 OK)

{
"success": true,
"message": "Data retrieved successfully",
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

### Error Response (401 Unauthorized)

{
"message": "Unauthenticated."
}

### Validation Error (422)

{
"success": false,
"message": "Validation error",
"errors": {
"indicators": ["The indicators field is required."],
"start_date": ["The start date field is required."]
}
}

---

## 8. Security Features

-   ✅ **OAuth2 Client Credentials Grant** untuk Machine-to-Machine (M2M)
-   ✅ **JWT Token** dengan expiry time
-   ✅ **Custom Middleware** untuk validasi setiap request
-   ✅ **Token Revocation** support
-   ✅ **HTTPS** recommended untuk production
-   ✅ **Rate Limiting** dapat ditambahkan (optional)

---

## 9. Caching Strategy

| Aspek              | Detail                     |
| ------------------ | -------------------------- |
| Cache Key Format   | `{category}_{series_id}`   |
| Cache Duration     | 1 jam (3600 detik)         |
| Cache Driver       | File (dapat diganti Redis) |
| Cache Invalidation | Otomatis setelah expired   |

---

## 10. Error Handling

| HTTP Code | Meaning               | Example                         |
| --------- | --------------------- | ------------------------------- |
| 200       | Success               | Data berhasil diambil           |
| 401       | Unauthorized          | Token tidak valid atau expired  |
| 422       | Validation Error      | Input request tidak valid       |
| 500       | Internal Server Error | Error dari FRED API atau server |
