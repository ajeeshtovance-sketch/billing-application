# API Testing Guide - Postman & curl Setup

## Option 1: Using Postman

### Step 1: Import Collection

1. Download [POSTMAN_COLLECTION.json](./POSTMAN_COLLECTION.json)
2. Open Postman
3. Click **Import** → Select the file → **Import**

### Step 2: Set Base URL Variable

1. Click **Variables** tab (top-right)
2. Find `base_url` variable
3. Set Current Value: `https://testbillapi.eazycutz.com`
4. Save

### Step 3: Login and Get Token

1. Go to **Authentication** → **Login** request
2. Click **Send**
3. Copy the `access_token` from response
4. Go to **Variables** tab
5. Find `jwt_token` variable
6. Paste token in Current Value
7. Save

### Step 4: Test All Endpoints

- All requests will now use your JWT token automatically
- Click on any request → **Send**
- Token expires in 1 hour → Re-login and update token

---

## Option 2: Using curl (Command Line)

### Step 1: Login

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

### Step 2: Save Token as Variable

**Windows PowerShell:**

```powershell
$TOKEN = "paste_your_token_here"
```

**Linux/Mac (bash):**

```bash
TOKEN="paste_your_token_here"
```

### Step 3: Make API Request

```bash
curl -X 'GET' \
  'https://testbillapi.eazycutz.com/api/v1/auth/me' \
  -H 'Authorization: Bearer '"$TOKEN"
```

### Step 4: Repeat for any other endpoint

All examples are in [CURL_EXAMPLES.md](./CURL_EXAMPLES.md)

---

## Quick Test - Verify Setup

### Postman

1. Click **Authentication** → **Validate Token** → **Send**
2. You should get response: `{"valid": true, "message": "Token is valid", ...}`

### curl

```bash
curl -X 'POST' \
  'https://testbillapi.eazycutz.com/api/v1/auth/validate-token' \
  -H 'Authorization: Bearer YOUR_TOKEN'
```

**Success Response:**

```json
{
    "valid": true,
    "message": "Token is valid",
    "user_id": 6,
    "user_name": "Demo User 3",
    "user_email": "demo3@test.com",
    "expires_in_seconds": 3600,
    "expires_in_minutes": 60
}
```

---

## Complete API Workflow Example

### Scenario: Create Invoice for Customer

**Postman:**

1. **Customers** → **Create Customer** → Send (get customer_id)
2. **Products** → **Add Product** → Send (get product_id)
3. **Invoices** → **Create Invoice** → Modify body with IDs → Send
4. **Invoices** → **Get Invoice** → Send (verify creation)

**curl:**

```bash
# 1. Create Customer
curl -X 'POST' 'https://testbillapi.eazycutz.com/api/v1/customers' \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"John","email":"john@test.com",...}'

# 2. Create Product
curl -X 'POST' 'https://testbillapi.eazycutz.com/api/v1/products' \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Product","sku":"SKU-001",...}'

# 3. Create Invoice
curl -X 'POST' 'https://testbillapi.eazycutz.com/api/v1/invoices' \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"customer_id":1,"line_items":[{"item_id":1,...}],...}'

# 4. Get Invoice
curl -X 'GET' 'https://testbillapi.eazycutz.com/api/v1/invoices/1' \
  -H "Authorization: Bearer $TOKEN"
```

---

## Troubleshooting

### Error: "Unauthenticated"

❌ **Problem:** Missing or invalid Authorization header
✅ **Solution:**

- Make sure you include: `-H 'Authorization: Bearer YOUR_TOKEN'`
- Ensure token is not expired (expires in 1 hour)
- Login again to get fresh token

### Error: "Token not provided"

❌ **Problem:** Authorization header is missing
✅ **Solution:**

- Postman: Check variables are set correctly
- curl: Verify `$TOKEN` variable is set

### Error: "Token is invalid"

❌ **Problem:** Token is malformed or was manually altered
✅ **Solution:**

- Login again with `/auth/login`
- Copy entire `access_token` value
- Don't modify the token

### Error: "Validation failed"

❌ **Problem:** Missing required fields in request body
✅ **Solution:**

- Check request body has all required fields
- Refer to examples in CURL_EXAMPLES.md or Postman collection

---

## Available Users for Testing

| Username                | Password | Email                   |
| ----------------------- | -------- | ----------------------- |
| demo3                   | password | demo3@test.com          |
| admin@billing.test      | password | admin@billing.test      |
| superadmin@billing.test | password | superadmin@billing.test |
| john@example.com        | password | john@example.com        |

---

## Files in This Project

1. **POSTMAN_COLLECTION.json** - Import this into Postman
2. **CURL_EXAMPLES.md** - All curl command examples
3. **API_DOCUMENTATION.md** - Full API documentation
4. **API_AUTHENTICATION.md** - Authentication details (this file)

---

## Next Steps

1. Choose your preferred method (Postman or curl)
2. Set up authentication following the steps above
3. Test the `/auth/me` endpoint to verify setup
4. Start testing other endpoints
5. Refer to CURL_EXAMPLES.md or Postman collection for all endpoint examples

Good luck! 🚀
