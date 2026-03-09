# Billing API - Complete curl Examples

## Setup Variables

```bash
# Set your base URL
BASE_URL="https://testbillapi.eazycutz.com"

# Set your JWT token (get from login)
JWT_TOKEN="your_token_here"
```

---

## 1. AUTHENTICATION

### Login

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/auth/login' \
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

### Get Current User

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/auth/me' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Validate Token

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/auth/validate-token' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Logout

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/auth/logout' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Refresh Token

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/auth/refresh' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

---

## 2. CUSTOMERS

### List All Customers

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/customers' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Create New Customer

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/customers' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "address": "123 Main St",
  "city": "New York",
  "state": "NY",
  "country": "USA",
  "postal_code": "10001",
  "tax_id": "123456789",
  "payment_terms": "NET30"
}'
```

### Get Customer by ID

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/customers/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Update Customer

```bash
curl -X 'PUT' \
  '$BASE_URL/api/v1/customers/1' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "name": "John Doe Updated",
  "email": "john.updated@example.com"
}'
```

### Delete Customer

```bash
curl -X 'DELETE' \
  '$BASE_URL/api/v1/customers/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Customer Summary

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/customers/summary' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Customer Invoices

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/customers/1/invoices' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

---

## 3. PRODUCTS

### List All Products

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/products' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Create New Product

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/products' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "name": "Product A",
  "sku": "SKU-001",
  "description": "Product Description",
  "category_id": 1,
  "unit_price": 100.00,
  "quantity": 50,
  "reorder_level": 10,
  "tax_percent": 18.00
}'
```

### Get Product by ID

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/products/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Update Product

```bash
curl -X 'PUT' \
  '$BASE_URL/api/v1/products/1' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "name": "Product A Updated",
  "unit_price": 120.00
}'
```

### Delete Product

```bash
curl -X 'DELETE' \
  '$BASE_URL/api/v1/products/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Product Summary

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/products/summary' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Update Stock

```bash
curl -X 'PATCH' \
  '$BASE_URL/api/v1/products/1/stock' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "quantity": 100
}'
```

---

## 4. INVOICES

### List All Invoices

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/invoices' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Create Invoice

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/invoices' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "customer_id": 1,
  "invoice_date": "2026-03-05",
  "due_date": "2026-04-05",
  "reference_number": "INV-001",
  "notes": "Invoice notes",
  "line_items": [
    {
      "item_id": 1,
      "quantity": 5,
      "unit_price": 100.00
    }
  ]
}'
```

### Get Invoice

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/invoices/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Update Invoice

```bash
curl -X 'PUT' \
  '$BASE_URL/api/v1/invoices/1' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "due_date": "2026-04-10",
  "notes": "Updated notes"
}'
```

### Delete Invoice

```bash
curl -X 'DELETE' \
  '$BASE_URL/api/v1/invoices/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Invoice Summary

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/invoices/summary' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Cancel Invoice

```bash
curl -X 'PATCH' \
  '$BASE_URL/api/v1/invoices/1/cancel' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

---

## 5. QUOTATIONS

### List Quotations

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/quotations' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Create Quotation

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/quotations' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "customer_id": 1,
  "quotation_date": "2026-03-05",
  "valid_until": "2026-04-05",
  "reference_number": "QT-001",
  "notes": "Quotation notes",
  "line_items": [
    {
      "item_id": 1,
      "quantity": 5,
      "unit_price": 100.00
    }
  ]
}'
```

### Get Quotation

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/quotations/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Update Quotation

```bash
curl -X 'PUT' \
  '$BASE_URL/api/v1/quotations/1' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "valid_until": "2026-04-10"
}'
```

### Delete Quotation

```bash
curl -X 'DELETE' \
  '$BASE_URL/api/v1/quotations/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Convert Quotation to Invoice

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/quotations/1/convert-to-invoice' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

---

## 6. DELIVERY CHALLANS

### List Delivery Challans

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/delivery-challans' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Create Delivery Challan

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/delivery-challans' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "customer_id": 1,
  "challan_date": "2026-03-05",
  "reference_number": "DC-001",
  "notes": "Delivery challan notes",
  "line_items": [
    {
      "item_id": 1,
      "quantity": 5
    }
  ]
}'
```

### Get Delivery Challan

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/delivery-challans/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Update Delivery Challan

```bash
curl -X 'PUT' \
  '$BASE_URL/api/v1/delivery-challans/1' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "notes": "Updated delivery challan"
}'
```

### Delete Delivery Challan

```bash
curl -X 'DELETE' \
  '$BASE_URL/api/v1/delivery-challans/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Mark as Delivered

```bash
curl -X 'PATCH' \
  '$BASE_URL/api/v1/delivery-challans/1/mark-delivered' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Convert Delivery Challan to Invoice

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/delivery-challans/1/convert-to-invoice' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

---

## 7. CREDIT NOTES

### List Credit Notes

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/credit-notes' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Create Credit Note

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/credit-notes' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "customer_id": 1,
  "invoice_id": 1,
  "credit_note_date": "2026-03-05",
  "reference_number": "CN-001",
  "reason": "Defective products",
  "notes": "Credit note for returned items",
  "line_items": [
    {
      "item_id": 1,
      "quantity": 2,
      "unit_price": 100.00
    }
  ]
}'
```

### Get Credit Note

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/credit-notes/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Update Credit Note

```bash
curl -X 'PUT' \
  '$BASE_URL/api/v1/credit-notes/1' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "notes": "Updated credit note"
}'
```

### Delete Credit Note

```bash
curl -X 'DELETE' \
  '$BASE_URL/api/v1/credit-notes/1' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Mark as Refunded

```bash
curl -X 'PATCH' \
  '$BASE_URL/api/v1/credit-notes/1/mark-refund' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

---

## 8. DASHBOARD

### Net Profit

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/dashboard/net-profit?period=month' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Received Amount

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/dashboard/received-amount?period=month' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Income and Expense

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/dashboard/income-expense?period=month' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Low Stock Items

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/dashboard/low-stock-items' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### Payment Method Chart

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/dashboard/payment-method-chart?period=month' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

### CTA Links

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/dashboard/cta' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

---

## 9. BILLS (CTA)

### Create New Bill

```bash
curl -X 'POST' \
  '$BASE_URL/api/v1/bills' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer $JWT_TOKEN' \
  -d '{
  "customer_id": 1,
  "bill_date": "2026-03-05",
  "due_date": "2026-04-05",
  "reference_number": "BILL-001",
  "notes": "Bill notes",
  "line_items": [
    {
      "item_id": 1,
      "quantity": 5,
      "unit_price": 100.00
    }
  ]
}'
```

---

## Query Parameters

### Period Options

For dashboard endpoints, use `?period=` with values:

- `today` - Today's data
- `week` - Last 7 days
- `month` - Last 30 days
- `year` - Last 365 days

Example:

```bash
curl -X 'GET' \
  '$BASE_URL/api/v1/dashboard/net-profit?period=week' \
  -H 'Authorization: Bearer $JWT_TOKEN'
```

---

## Error Codes

- **200** - Success
- **201** - Created
- **400** - Bad Request (Invalid data)
- **401** - Unauthorized (Invalid or missing token)
- **404** - Not Found (Resource doesn't exist)
- **422** - Validation Error (Missing required fields)
- **500** - Server Error

---

## Test Credentials

**Username:** `demo3`
**Password:** `password`

---

## How to Use

1. **Get JWT Token:**

    ```bash
    curl -X 'POST' 'https://testbillapi.eazycutz.com/api/v1/auth/login' \
      -H 'Content-Type: application/json' \
      -d '{"username":"demo3","password":"password"}'
    ```

2. **Copy the `access_token` from response**

3. **Use token in all protected requests:**

    ```bash
    -H 'Authorization: Bearer YOUR_TOKEN_HERE'
    ```

4. **Token expires after 1 hour - call `/auth/refresh` or login again**
