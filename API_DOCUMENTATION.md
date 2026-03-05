# Billing App API Documentation

## Overview

RESTful API for the Billing Application. All protected endpoints require **JWT Bearer token** authentication.

**Base URL:** `http://your-domain/api/v1`

---

## Quick Start - Complete Authentication Flow

### Step 1: Login to get JWT Token

```bash
curl -X 'POST' \
  'https://testbillapi.eazycutz.com/api/v1/auth/login' \
  -H 'Content-Type: application/json' \
  -d '{
  "username": "demo3",
  "password": "password"
}'
```

**Response:**

```json
{
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "bearer",
    "expires_in": 3600
}
```

### Step 2: Use Token in Protected Endpoints

Copy the `access_token` and add it to ALL subsequent requests:

```bash
curl -X 'GET' \
  'https://testbillapi.eazycutz.com/api/v1/auth/me' \
  -H 'accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'
```

**Key Points:**

- Token expires in 3600 seconds (1 hour)
- Always include `Authorization: Bearer {token}` header
- If you get 401 Unauthorized, token may be expired → login again
- Never put `JWT_SECRET` in requests - it's only for the server

---

## Authentication (JWT)

### ⚠️ IMPORTANT

- **Never use `JWT_SECRET` from `.env` in API requests**
- `JWT_SECRET` is the server's private key - only the server uses it to sign tokens
- Always use the `access_token` returned from the `/auth/login` endpoint

### Login

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@billing.test",
  "password": "password"
}
```

**Response:**

```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600
}
```

**Copy the `access_token` and use it in your requests:**

```bash
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Register (creates Organization + Admin User)

```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password",
  "organization_name": "My Store",
  "base_currency": "INR"
}
```

### Logout

```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

### Refresh Token

```http
POST /api/v1/auth/refresh
Authorization: Bearer {token}
```

### Get Current User

```http
GET /api/v1/auth/me
Authorization: Bearer {token}
```

### Validate Token (Debug Endpoint)

Use this to check if your token is valid and correctly formatted:

```http
POST /api/v1/auth/validate-token
Authorization: Bearer {token}
```

**This endpoint will:**

- ✅ Confirm if your token is valid
- ❌ Show errors if token is missing or invalid
- 🔍 Detect if you accidentally sent `JWT_SECRET` instead of token
- ⏰ Show token expiration time

**Curl example:**

```bash
curl -X 'POST' \
  'https://testbillapi.eazycutz.com/api/v1/auth/validate-token' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE'
```

### Dashboard Summary (PDF: Total Sales, Paid, Unpaid, Cancelled)

```http
GET /api/v1/dashboard/summary?period=month
Authorization: Bearer {token}
```

**Query params:** `period` = today | week | month | year

**Response:**

```json
{
    "total_sales": 15000.0,
    "paid": 10000.0,
    "unpaid": 5000.0,
    "cancelled": 0,
    "period": "month"
}
```

---

## Using the API

### Headers for Protected Routes

```
Authorization: Bearer {your_jwt_token}
Content-Type: application/json
Accept: application/json
```

### Error Responses

- **401 Unauthorized** – Invalid or expired token
- **422 Unprocessable Entity** – Validation errors
- **404 Not Found** – Resource not found

---

## Database Tables (Migrations)

| Table                       | Purpose                              |
| --------------------------- | ------------------------------------ |
| organizations               | Store/tenant (SaaS)                  |
| users                       | Users with organization_id, role     |
| categories                  | Product categories                   |
| items                       | Products with stock, low_stock_alert |
| customers                   | Customers                            |
| quotations                  | Quotations (Move to Invoice)         |
| quotation_line_items        | Quotation line items                 |
| delivery_challans           | Delivery Challan (Move to Invoice)   |
| delivery_challan_line_items | DC line items                        |
| invoices                    | Invoices                             |
| invoice_line_items          | Invoice line items                   |
| credit_notes                | Credit Notes (Refund status)         |
| credit_note_line_items      | CN line items                        |
| payment_methods             | Cash, Card, UPI, etc.                |
| payments                    | Payment records                      |
| purchases                   | Purchase list                        |
| purchase_line_items         | Purchase line items                  |
| expenses                    | Expense tracking                     |
| stock_movements             | Stock movement log                   |
| number_sequences            | Invoice/Quote/DC numbering           |

---

## Test Credentials (after seeding)

- **Email:** admin@billing.test
- **Password:** password

---

## Security

- JWT tokens expire (default: 60 min)
- Use `auth/refresh` to get a new token
- All data scoped by `organization_id` (multi-tenant)
- Password hashing via bcrypt
