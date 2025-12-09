# Dokumentasi Flow - Economic Data API

Dokumentasi lengkap mengenai alur kerja, arsitektur sistem, dan flow diagram dari Economic Data API.

---

## 1. Authentication Flow

sequenceDiagram
participant Client as Client Application
participant API as Laravel API
participant DB as Database

Client->>API: POST /oauth/token
API->>DB: Validate Credentials
DB-->>API: Client Valid
API-->>Client: Return Token

Client->>API: GET /api/endpoint + Token
API->>DB: Validate Token
DB-->>API: Token Valid
API-->>Client: Return Data

---

## 2. API Request Flow

flowchart TD
Start([Client Request]) --> Auth{Token Valid?}
Auth -->|No| Error401[401 Unauthorized]
Auth -->|Yes| Cache{Data in Cache?}
Cache -->|Yes| Return1[Return Cached Data]
Cache -->|No| FRED[Call FRED API]
FRED --> Process[Process Data]
Process --> Save[Save to Cache]
Save --> Return2[Return Data]
Error401 --> End([End])
Return1 --> End
Return2 --> End

## 3. Custom Report Flow

flowchart TD
Start([POST /custom-report]) --> Token{Token Valid?}
Token -->|No| E401[401 Error]
Token -->|Yes| Input{Input Valid?}
Input -->|No| E422[422 Error]
Input -->|Yes| Loop[Loop Each Indicator]
Loop --> Request[Call FRED API]
Request --> Format[Format Data]
Format --> Add[Add to Report]
Add --> More{More Indicators?}
More -->|Yes| Loop
More -->|No| Success[200 OK Response]
E401 --> End([End])
E422 --> End
Success --> End

---

## 4. System Architecture

graph TB
subgraph Clients["Client Layer"]
Mobile[Mobile App]
Web[Web App]
Service[Backend Service]
end

subgraph API["Laravel API Server"]
Auth[OAuth2 Middleware]
Controllers[Controllers]
Cache[Cache]
end

subgraph DB["Database"]
MySQL[(MySQL)]
end

subgraph External["External"]
FRED[FRED API]
end

Mobile --> Auth
Web --> Auth
Service --> Auth
Auth --> Controllers
Controllers --> Cache
Controllers --> MySQL
Controllers --> FRED

---

## 5. API Endpoints

| Method | Endpoint                                  | Description            | Auth |
| ------ | ----------------------------------------- | ---------------------- | ---- |
| POST   | `/oauth/token`                            | Get access token       | No   |
| GET    | `/api/economic-indicators`                | Economic indicators    | Yes  |
| GET    | `/api/interest-rates`                     | Interest rates         | Yes  |
| GET    | `/api/market-indicators`                  | Market indicators      | Yes  |
| GET    | `/api/custom-report/available-indicators` | Available indicators   | Yes  |
| POST   | `/api/custom-report`                      | Generate custom report | Yes  |

---

## 6. Success Response Example

{
"success": true,
"message": "Data retrieved successfully",
"data": {
"category": "Economic Indicators",
"timestamp": "2025-12-10T04:00:00Z",
"data": [
{
"indicator": "Gdp",
"value": 30485.729,
"date": "2025-04-01"
}
]
}
}

---

## 7. Error Response Examples

### 401 Unauthorized

{
"message": "Unauthenticated."
}

### 422 Validation Error

{
"success": false,
"message": "Validation error",
"errors": {
"indicators": ["The indicators field is required"]
}
}

---

## 8. Security Features

-   ✅ OAuth2 Client Credentials Grant
-   ✅ JWT Token with expiry
-   ✅ Custom middleware validation
-   ✅ Token revocation support
-   ✅ HTTPS recommended

---

## 9. Caching Strategy

| Aspect     | Detail                   |
| ---------- | ------------------------ |
| Duration   | 1 hour (3600 seconds)    |
| Key Format | `{category}_{series_id}` |
| Driver     | File or Redis            |
| TTL        | Auto expire after 1 hour |

---

## 10. Error Handling

| HTTP Code | Meaning          | Example               |
| --------- | ---------------- | --------------------- |
| 200       | Success          | Data retrieved        |
| 401       | Unauthorized     | Invalid/expired token |
| 422       | Validation Error | Invalid input         |
| 500       | Server Error     | FRED API error        |
