# Billing App API Documentation

## Overview

RESTful API for the Billing Application. All protected endpoints require **JWT Bearer token** authentication.

**Base URL:** `http://your-domain/api/v1`

---

## Authentication (JWT)

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

### Dashboard Summary (PDF: Total Sales, Paid, Unpaid, Cancelled)
```http
GET /api/v1/dashboard/summary?period=month
Authorization: Bearer {token}
```

**Query params:** `period` = today | week | month | year

**Response:**
```json
{
  "total_sales": 15000.00,
  "paid": 10000.00,
  "unpaid": 5000.00,
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

| Table | Purpose |
|-------|---------|
| organizations | Store/tenant (SaaS) |
| users | Users with organization_id, role |
| categories | Product categories |
| items | Products with stock, low_stock_alert |
| customers | Customers |
| quotations | Quotations (Move to Invoice) |
| quotation_line_items | Quotation line items |
| delivery_challans | Delivery Challan (Move to Invoice) |
| delivery_challan_line_items | DC line items |
| invoices | Invoices |
| invoice_line_items | Invoice line items |
| credit_notes | Credit Notes (Refund status) |
| credit_note_line_items | CN line items |
| payment_methods | Cash, Card, UPI, etc. |
| payments | Payment records |
| purchases | Purchase list |
| purchase_line_items | Purchase line items |
| expenses | Expense tracking |
| stock_movements | Stock movement log |
| number_sequences | Invoice/Quote/DC numbering |

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
