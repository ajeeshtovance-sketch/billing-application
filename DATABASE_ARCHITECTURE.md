# SaaS Billing Application - Database Architecture

> **Aligned with your PDF flow** – Dashboard → Products (stock) → Sales (Invoice, Quotation, Delivery Challan) → Customers → Purchase → Expense → Reports. Includes Zoho-style structures for future Subscription/Plans.

---

## 1. PDF Flow Overview (from Flow updated.pdf)

### Sidebar Menu & Pages
| Section | Sub-pages | Key Features |
|---------|-----------|--------------|
| **Dashboard** | — | Cards (Net Profit, Received amount, Income/expense), New Bill, Add Product, Add Customer, Low Stock Items, Payment Method Chart |
| **Products** | Items List | Search, Add item, Edit, Update stock, Check low stock, Stock value |
| **Sales** | Invoice, Quotation, Delivery Challan | Create, Edit, Delete, Move to Invoice |
| **Customers** | Customer list, Detail page | Basic details, Transaction timeline with invoices |
| **Purchase** | Purchase list, Details | Category, Upload Bill, Edit/delete |
| **Expense** | Report | Add, Edit/View, Delete expense |
| **Profile** | Store details | Basic details, Billing details, Signout |

### Dashboard Summary Cards
- **Total Sales** → overall billing value in selected period (T/W/M/Y)
- **Paid** → money already collected
- **Unpaid** → pending customer payments
- **Cancelled** → reversed bills

### Core Flows (from PDF)
```
Quotation/Estimate  →  Create  →  Move Quotation to Invoice
Delivery Challan    →  Create  →  Mark as Delivered  →  Move DC to Invoice
Invoice             →  Download / Print / Share PDF  →  Edit / Delete / Cancel
Credit Note         →  Add  →  Status: Refund or No  →  Mark as REFUND
```

### Future Features (from PDF)
- Barcode scan
- Subscription Plan model
- POS system
- User management
- Online orders
- Staff Reports
- Expense stock Automation

---

## 2. Zoho Billing Flow (Reference for Future)

### Core Flow
```
Organization (tenant)
    ├── Products → Plans → Addons
    ├── Customers → Contact Persons
    ├── Quotes (draft → sent → accepted/declined) → Invoices
    ├── Estimates → Retainer Invoices
    ├── Subscriptions → Auto-generated Invoices
    ├── Invoices → Payments / Credit Notes
    └── Projects → Tasks → Time Entries → Billable to Invoice
```

### Key Zoho Features Mapped
| Feature | Tables |
|---------|--------|
| Product Catalog | products, plans, addons, items |
| Coupons | coupons, coupon_redemptions |
| Subscriptions | subscriptions, subscription_items, subscription_addons |
| Quotes & Estimates | quotes, estimates, retainer_invoices |
| Invoicing | invoices, credit_notes |
| Payments | payments, payment_links, refunds |
| Project Billing | projects, tasks, time_entries, expenses |

---

## 3. PDF Flow Core Tables (Retail / Inventory Billing)

### 3.1 Store / Organization
```sql
CREATE TABLE organizations (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name            VARCHAR(255) NOT NULL,
    legal_name      VARCHAR(255),
    tax_id          VARCHAR(50),
    address         JSONB,
    billing_address JSONB,
    billing_email   VARCHAR(255),
    phone           VARCHAR(50),
    logo_url        VARCHAR(500),
    base_currency   VARCHAR(3) DEFAULT 'INR',
    settings        JSONB DEFAULT '{}',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 3.2 Products / Items (with Stock – PDF: Items List, Low Stock)
```sql
CREATE TABLE categories (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name            VARCHAR(100) NOT NULL,
    parent_id       UUID REFERENCES categories(id),
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    category_id     UUID REFERENCES categories(id),
    name            VARCHAR(255) NOT NULL,
    sku             VARCHAR(50),
    barcode         VARCHAR(100),
    item_type       VARCHAR(20) DEFAULT 'product',  -- product, service
    description     TEXT,
    price           DECIMAL(15, 2) NOT NULL DEFAULT 0,
    cost            DECIMAL(15, 2),
    stock_quantity  DECIMAL(12, 2) DEFAULT 0,
    low_stock_alert DECIMAL(12, 2) DEFAULT 0,
    unit            VARCHAR(20) DEFAULT 'each',
    tax_rate        DECIMAL(5, 2) DEFAULT 0,
    status          VARCHAR(20) DEFAULT 'active',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_items_low_stock ON items(organization_id) 
    WHERE stock_quantity <= low_stock_alert AND low_stock_alert > 0;
```

### 3.3 Customers (PDF: Customers Table, Detail page, Transaction timeline)
```sql
CREATE TABLE customers (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name            VARCHAR(255) NOT NULL,
    company_name    VARCHAR(255),
    email           VARCHAR(255),
    phone           VARCHAR(50),
    address         JSONB,
    billing_address JSONB,
    gstin           VARCHAR(50),
    payment_terms   INT DEFAULT 30,
    notes           TEXT,
    status          VARCHAR(20) DEFAULT 'active',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 3.4 Quotations / Estimates (PDF: Quotation history → Move to Invoice)
```sql
CREATE TABLE quotations (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    quotation_number VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    status          VARCHAR(20) DEFAULT 'draft',  -- draft, sent, accepted, declined
    valid_until     DATE,
    subtotal        DECIMAL(15, 2) DEFAULT 0,
    tax_amount      DECIMAL(15, 2) DEFAULT 0,
    discount_amount DECIMAL(15, 2) DEFAULT 0,
    total           DECIMAL(15, 2) DEFAULT 0,
    notes           TEXT,
    terms           TEXT,
    invoice_id      UUID,  -- set when moved to invoice (FK to invoices - add via ALTER after invoices exists)
    created_by      UUID,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, quotation_number)
);

CREATE TABLE quotation_line_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    quotation_id    UUID NOT NULL REFERENCES quotations(id) ON DELETE CASCADE,
    item_id         UUID REFERENCES items(id),
    description     VARCHAR(500) NOT NULL,
    quantity        DECIMAL(10, 2) NOT NULL DEFAULT 1,
    unit_price      DECIMAL(15, 2) NOT NULL,
    tax_rate        DECIMAL(5, 2) DEFAULT 0,
    amount          DECIMAL(15, 2) NOT NULL,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 3.5 Delivery Challan (PDF: DC history → Create DC → Mark as Delivered → Move to Invoice)
```sql
CREATE TABLE delivery_challans (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    dc_number       VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    status          VARCHAR(20) DEFAULT 'draft',  -- draft, sent, delivered
    delivery_date   DATE,
    shipping_address JSONB,
    notes           TEXT,
    invoice_id      UUID,  -- set when moved to invoice (FK to invoices - add via ALTER after invoices exists)
    created_by      UUID,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, dc_number)
);

CREATE TABLE delivery_challan_line_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    delivery_challan_id UUID NOT NULL REFERENCES delivery_challans(id) ON DELETE CASCADE,
    item_id         UUID REFERENCES items(id),
    description     VARCHAR(500) NOT NULL,
    quantity        DECIMAL(10, 2) NOT NULL DEFAULT 1,
    unit            VARCHAR(20) DEFAULT 'each',
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 3.6 Invoices (PDF: Invoice history, Download/Print/Share, Edit/Delete/Cancel)
```sql
CREATE TABLE invoices (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    invoice_number  VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    quotation_id    UUID REFERENCES quotations(id),
    delivery_challan_id UUID REFERENCES delivery_challans(id),
    invoice_type    VARCHAR(20) DEFAULT 'standard',  -- standard, from_quotation, from_dc
    status          VARCHAR(20) DEFAULT 'draft',    -- draft, sent, paid, partial, unpaid, cancelled
    issue_date      DATE NOT NULL,
    due_date        DATE NOT NULL,
    subtotal        DECIMAL(15, 2) DEFAULT 0,
    tax_amount      DECIMAL(15, 2) DEFAULT 0,
    discount_amount DECIMAL(15, 2) DEFAULT 0,
    total           DECIMAL(15, 2) DEFAULT 0,
    amount_paid     DECIMAL(15, 2) DEFAULT 0,
    balance_due     DECIMAL(15, 2) DEFAULT 0,
    notes           TEXT,
    terms           TEXT,
    created_by      UUID,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, invoice_number)
);

CREATE TABLE invoice_line_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    invoice_id      UUID NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    item_id         UUID REFERENCES items(id),
    description     VARCHAR(500) NOT NULL,
    quantity        DECIMAL(10, 2) NOT NULL DEFAULT 1,
    unit_price      DECIMAL(15, 2) NOT NULL,
    tax_rate        DECIMAL(5, 2) DEFAULT 0,
    amount          DECIMAL(15, 2) NOT NULL,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 3.7 Credit Notes (PDF: Add CN, Status Refund or No, Mark as REFUND)
```sql
CREATE TABLE credit_notes (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    credit_note_number VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    invoice_id      UUID REFERENCES invoices(id),
    status          VARCHAR(20) DEFAULT 'open',  -- open, refunded, applied, void
    total           DECIMAL(15, 2) NOT NULL,
    balance         DECIMAL(15, 2) NOT NULL,
    refund_status   VARCHAR(20),  -- refund, no_refund
    notes           TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, credit_note_number)
);

CREATE TABLE credit_note_line_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    credit_note_id  UUID NOT NULL REFERENCES credit_notes(id) ON DELETE CASCADE,
    item_id         UUID REFERENCES items(id),
    description     VARCHAR(500) NOT NULL,
    quantity        DECIMAL(10, 2) NOT NULL DEFAULT 1,
    unit_price      DECIMAL(15, 2) NOT NULL,
    amount          DECIMAL(15, 2) NOT NULL,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 3.8 Payments (PDF: Received amount, Payment Method Chart)
```sql
CREATE TABLE payment_methods (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name            VARCHAR(50) NOT NULL,  -- cash, card, bank_transfer, upi, etc.
    is_active       BOOLEAN DEFAULT true,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE payments (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    payment_number  VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    invoice_id      UUID REFERENCES invoices(id),
    amount          DECIMAL(15, 2) NOT NULL,
    payment_method_id UUID REFERENCES payment_methods(id),
    payment_date    DATE NOT NULL,
    reference       VARCHAR(255),
    notes           TEXT,
    created_by      UUID,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, payment_number)
);
```

### 3.9 Purchase (PDF: Purchase list, Purchase details, Category, Upload Bill)
```sql
CREATE TABLE purchases (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    purchase_number VARCHAR(50) NOT NULL,
    supplier_name   VARCHAR(255),
    supplier_id     UUID,
    purchase_date   DATE NOT NULL,
    category_id     UUID REFERENCES categories(id),
    subtotal        DECIMAL(15, 2) DEFAULT 0,
    tax_amount      DECIMAL(15, 2) DEFAULT 0,
    total           DECIMAL(15, 2) DEFAULT 0,
    bill_attachment_url VARCHAR(500),
    notes           TEXT,
    created_by      UUID,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, purchase_number)
);

CREATE TABLE purchase_line_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    purchase_id     UUID NOT NULL REFERENCES purchases(id) ON DELETE CASCADE,
    item_id         UUID REFERENCES items(id),
    description     VARCHAR(500) NOT NULL,
    quantity        DECIMAL(10, 2) NOT NULL DEFAULT 1,
    unit_cost       DECIMAL(15, 2) NOT NULL,
    amount          DECIMAL(15, 2) NOT NULL,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 3.10 Expenses (PDF: Expense Report, Add/Edit/View/Delete)
```sql
CREATE TABLE expenses (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    category        VARCHAR(100),
    amount          DECIMAL(15, 2) NOT NULL,
    expense_date    DATE NOT NULL,
    description     TEXT,
    attachment_url VARCHAR(500),
    created_by      UUID,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 3.11 Stock Movements (for Update stock, Low stock)
```sql
CREATE TABLE stock_movements (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    item_id         UUID NOT NULL REFERENCES items(id) ON DELETE CASCADE,
    movement_type   VARCHAR(20) NOT NULL,  -- purchase, sale, adjustment, return
    quantity        DECIMAL(12, 2) NOT NULL,  -- + for in, - for out
    reference_type  VARCHAR(50),  -- invoice, purchase, delivery_challan
    reference_id   UUID,
    notes           TEXT,
    created_by      UUID,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 3.12 Number Sequences
```sql
CREATE TABLE number_sequences (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    sequence_type   VARCHAR(50) NOT NULL,  -- invoice, quotation, dc, payment, purchase
    prefix          VARCHAR(20),
    current_value   INT NOT NULL DEFAULT 0,
    padding         INT DEFAULT 5,
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, sequence_type)
);
```

---

## 4. PDF Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    BILLING SOFTWARE FLOW (from PDF)                           │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  DASHBOARD                                                                   │
│  Net Profit | Received | Income/Expense | New Bill | Add Product | Add Customer│
│  Low Stock Items | Payment Method Chart                                      │
│                                                                              │
│  PRODUCTS                    CUSTOMERS                                       │
│  Items List + Stock          Customer Table                                  │
│  Search | Add | Edit          Detail: Basic + Transaction timeline             │
│  Update stock | Low stock                                                    │
│       │                             │                                        │
│       └─────────────┬───────────────┘                                        │
│                     ▼                                                        │
│  SALES                                                                       │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐                     │
│  │ Quotation   │───►│   Invoice    │◄───│ Delivery    │                     │
│  │ Move to Inv │    │ Download/    │    │ Challan     │                     │
│  └─────────────┘    │ Print/Share  │    │ Mark Deliv  │                     │
│                     │ Edit/Delete/ │    │ Move to Inv │                     │
│                     │ Cancel       │    └─────────────┘                     │
│                     └──────┬──────┘                                         │
│                            │                                                │
│                     ┌──────▼──────┐    ┌─────────────┐                     │
│                     │  Payments   │    │ Credit Note  │                     │
│                     │  Received   │    │ Refund/No   │                     │
│                     └─────────────┘    └─────────────┘                     │
│                                                                              │
│  PURCHASE                    EXPENSE                    PROFILE               │
│  List | Details | Upload    Report | Add/Edit/Delete    Store | Billing      │
│  Category | Edit/Delete                                                      │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Zoho-Style Tables (for Future: Subscription, Plans, etc.)

### 5.1 Organizations (Tenant / Zoho Organization)

```sql
-- Top-level entity - each org is isolated (Zoho uses organization_id)
CREATE TABLE organizations (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name            VARCHAR(255) NOT NULL,
    legal_name      VARCHAR(255),
    tax_id          VARCHAR(50),
    address         JSONB,
    billing_email   VARCHAR(255),
    base_currency   VARCHAR(3) DEFAULT 'USD',
    fiscal_year_start INT DEFAULT 1,  -- 1=Jan, 4=Apr, etc.
    timezone        VARCHAR(50) DEFAULT 'UTC',
    logo_url        VARCHAR(500),
    settings        JSONB DEFAULT '{}',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.2 Products, Plans, Addons, Items (Zoho Product Catalog)

```sql
-- Products: main service offerings (e.g., "Web Hosting", "CRM")
CREATE TABLE products (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name            VARCHAR(255) NOT NULL,
    description     TEXT,
    email_ids       VARCHAR(500),  -- comma-separated for notifications
    redirect_url    VARCHAR(500),  -- post-subscription redirect
    status          VARCHAR(20) DEFAULT 'active',  -- active, inactive
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Items: billable line items (products, services, custom - used in plans, quotes, invoices)
CREATE TABLE items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    product_id      UUID REFERENCES products(id),
    name            VARCHAR(255) NOT NULL,
    description     TEXT,
    sku             VARCHAR(50),
    item_type       VARCHAR(30) DEFAULT 'product',  -- product, service, custom
    pricing_type    VARCHAR(30) DEFAULT 'flat',     -- flat, unit, volume, tier, package
    price           DECIMAL(15, 2) NOT NULL DEFAULT 0,
    unit            VARCHAR(20) DEFAULT 'each',
    status          VARCHAR(20) DEFAULT 'active',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Plans: subscription tiers under a product
CREATE TABLE plans (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    product_id      UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    name            VARCHAR(100) NOT NULL,
    description     TEXT,
    billing_cycle   VARCHAR(20) NOT NULL,  -- monthly, quarterly, yearly
    price           DECIMAL(15, 2) NOT NULL,
    setup_fee       DECIMAL(15, 2) DEFAULT 0,
    trial_days      INT DEFAULT 0,
    status          VARCHAR(20) DEFAULT 'active',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Plan-Item mapping (plan includes which items at what price)
CREATE TABLE plan_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    plan_id         UUID NOT NULL REFERENCES plans(id) ON DELETE CASCADE,
    item_id         UUID NOT NULL REFERENCES items(id) ON DELETE CASCADE,
    quantity        DECIMAL(10, 2) DEFAULT 1,
    price_override  DECIMAL(15, 2),  -- NULL = use item price
    UNIQUE(plan_id, item_id)
);

-- Addons: optional extras (one-time or recurring)
CREATE TABLE addons (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    product_id      UUID REFERENCES products(id),
    name            VARCHAR(255) NOT NULL,
    description     TEXT,
    type            VARCHAR(20) NOT NULL,  -- one_time, recurring
    pricing_type    VARCHAR(30) DEFAULT 'flat',  -- flat, unit, volume, tier, package
    price           DECIMAL(15, 2) NOT NULL,
    status          VARCHAR(20) DEFAULT 'active',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Which plans an addon can be attached to (NULL = all plans)
CREATE TABLE addon_plans (
    addon_id        UUID NOT NULL REFERENCES addons(id) ON DELETE CASCADE,
    plan_id         UUID NOT NULL REFERENCES plans(id) ON DELETE CASCADE,
    PRIMARY KEY (addon_id, plan_id)
);

-- Coupons (Zoho Coupons module)
CREATE TABLE coupons (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    code            VARCHAR(50) NOT NULL,
    name            VARCHAR(255),
    discount_type   VARCHAR(20) NOT NULL,  -- percentage, fixed
    discount_value  DECIMAL(15, 2) NOT NULL,
    redemption_type VARCHAR(20) DEFAULT 'one_time',  -- one_time, recurring, limited_period
    max_redemptions INT,
    expires_at      TIMESTAMPTZ,
    status          VARCHAR(20) DEFAULT 'active',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, code)
);
```

### 2.3 Customers & Contact Persons

```sql
CREATE TABLE customers (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    display_name    VARCHAR(255) NOT NULL,
    company_name    VARCHAR(255),
    email           VARCHAR(255),
    phone           VARCHAR(50),
    website         VARCHAR(255),
    tax_id          VARCHAR(50),
    billing_address JSONB,
    shipping_address JSONB,
    payment_terms   INT DEFAULT 30,
    currency        VARCHAR(3) DEFAULT 'USD',
    status          VARCHAR(20) DEFAULT 'active',
    crm_reference   VARCHAR(100),  -- Zoho CRM link
    custom_fields   JSONB DEFAULT '{}',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Contact persons (Zoho Contact-Persons API)
CREATE TABLE contact_persons (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id     UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100),
    email           VARCHAR(255),
    phone           VARCHAR(50),
    is_primary      BOOLEAN DEFAULT false,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Cards (Zoho Cards API)
CREATE TABLE cards (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id     UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
    payment_gateway VARCHAR(50),  -- stripe, paypal, etc.
    gateway_card_id VARCHAR(255),
    last_four       VARCHAR(4),
    expiry_month    INT,
    expiry_year     INT,
    is_default      BOOLEAN DEFAULT false,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Bank accounts (for ACH/direct debit)
CREATE TABLE bank_accounts (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id     UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
    last_four       VARCHAR(4),
    bank_name       VARCHAR(255),
    is_default      BOOLEAN DEFAULT false,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.4 Quotes (Zoho Quote Flow: draft → sent → accepted/declined)

```sql
CREATE TABLE quotes (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    quote_number    VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    contact_person_id UUID REFERENCES contact_persons(id),
    status          VARCHAR(20) DEFAULT 'draft',  -- draft, sent, accepted, declined
    valid_until     DATE,
    subtotal        DECIMAL(15, 2) DEFAULT 0,
    tax_amount      DECIMAL(15, 2) DEFAULT 0,
    discount_amount DECIMAL(15, 2) DEFAULT 0,
    total           DECIMAL(15, 2) DEFAULT 0,
    currency        VARCHAR(3) DEFAULT 'USD',
    notes           TEXT,
    terms           TEXT,
    template_id     UUID,
    custom_fields   JSONB DEFAULT '{}',
    created_by      UUID,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, quote_number)
);

CREATE TABLE quote_line_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    quote_id        UUID NOT NULL REFERENCES quotes(id) ON DELETE CASCADE,
    item_id         UUID REFERENCES items(id),
    description     VARCHAR(500) NOT NULL,
    quantity        DECIMAL(10, 2) NOT NULL DEFAULT 1,
    unit_price      DECIMAL(15, 2) NOT NULL,
    tax_id          UUID,
    amount          DECIMAL(15, 2) NOT NULL,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Quote comments & history (Zoho API)
CREATE TABLE quote_comments (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    quote_id        UUID NOT NULL REFERENCES quotes(id) ON DELETE CASCADE,
    user_id         UUID,
    comment         TEXT NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.5 Estimates & Retainer Invoices

```sql
-- Estimates (proposals before quote/invoice)
CREATE TABLE estimates (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    estimate_number VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    status          VARCHAR(20) DEFAULT 'draft',
    valid_until     DATE,
    subtotal        DECIMAL(15, 2) DEFAULT 0,
    tax_amount      DECIMAL(15, 2) DEFAULT 0,
    total           DECIMAL(15, 2) DEFAULT 0,
    currency        VARCHAR(3) DEFAULT 'USD',
    notes           TEXT,
    terms           TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, estimate_number)
);

CREATE TABLE estimate_line_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    estimate_id     UUID NOT NULL REFERENCES estimates(id) ON DELETE CASCADE,
    item_id         UUID REFERENCES items(id),
    description     VARCHAR(500) NOT NULL,
    quantity        DECIMAL(10, 2) NOT NULL DEFAULT 1,
    unit_price      DECIMAL(15, 2) NOT NULL,
    amount          DECIMAL(15, 2) NOT NULL,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Retainer invoices (advance payments - from estimate or direct)
CREATE TABLE retainer_invoices (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    retainer_number VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    estimate_id     UUID REFERENCES estimates(id),
    amount          DECIMAL(15, 2) NOT NULL,
    currency        VARCHAR(3) DEFAULT 'USD',
    status          VARCHAR(20) DEFAULT 'draft',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, retainer_number)
);
```

### 2.6 Invoices (Zoho Invoice Flow)

```sql
CREATE TABLE invoices (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    invoice_number  VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    contact_person_id UUID REFERENCES contact_persons(id),
    quote_id        UUID REFERENCES quotes(id),
    subscription_id UUID REFERENCES subscriptions(id),
    invoice_type    VARCHAR(20) DEFAULT 'standard',  -- standard, retainer, subscription
    status          VARCHAR(20) DEFAULT 'draft',    -- draft, sent, open, paid, partial, overdue, void, written_off
    issue_date      DATE NOT NULL,
    due_date        DATE NOT NULL,
    subtotal        DECIMAL(15, 2) DEFAULT 0,
    tax_amount      DECIMAL(15, 2) DEFAULT 0,
    discount_amount DECIMAL(15, 2) DEFAULT 0,
    total           DECIMAL(15, 2) DEFAULT 0,
    amount_paid     DECIMAL(15, 2) DEFAULT 0,
    balance_due     DECIMAL(15, 2) DEFAULT 0,
    currency        VARCHAR(3) DEFAULT 'USD',
    notes           TEXT,
    terms           TEXT,
    custom_fields   JSONB DEFAULT '{}',
    created_by      UUID,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, invoice_number)
);

CREATE TABLE invoice_line_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    invoice_id      UUID NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    item_id         UUID REFERENCES items(id),
    description     VARCHAR(500) NOT NULL,
    quantity        DECIMAL(10, 2) NOT NULL DEFAULT 1,
    unit_price      DECIMAL(15, 2) NOT NULL,
    tax_id          UUID,
    amount          DECIMAL(15, 2) NOT NULL,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Unbilled charges (Zoho Unbilled-Charges - convert to invoice later)
CREATE TABLE unbilled_charges (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    subscription_id UUID REFERENCES subscriptions(id),
    description     VARCHAR(500),
    amount          DECIMAL(15, 2) NOT NULL,
    currency        VARCHAR(3) DEFAULT 'USD',
    charge_date     DATE NOT NULL,
    status          VARCHAR(20) DEFAULT 'pending',  -- pending, invoiced
    invoice_id      UUID REFERENCES invoices(id),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Credit notes (Zoho Credit-Notes - apply to invoices)
CREATE TABLE credit_notes (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    credit_note_number VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    invoice_id      UUID REFERENCES invoices(id),
    status          VARCHAR(20) DEFAULT 'open',  -- open, void, applied
    total           DECIMAL(15, 2) NOT NULL,
    balance         DECIMAL(15, 2) NOT NULL,
    currency        VARCHAR(3) DEFAULT 'USD',
    notes           TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, credit_note_number)
);

CREATE TABLE credit_note_allocations (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    credit_note_id   UUID NOT NULL REFERENCES credit_notes(id) ON DELETE CASCADE,
    invoice_id      UUID NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    amount          DECIMAL(15, 2) NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.7 Subscriptions (Zoho Subscription Flow)

```sql
CREATE TABLE subscriptions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    subscription_number VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    contact_person_id UUID REFERENCES contact_persons(id),
    plan_id         UUID NOT NULL REFERENCES plans(id),
    product_id      UUID NOT NULL REFERENCES products(id),
    status          VARCHAR(20) DEFAULT 'active',  -- trial, active, paused, cancelled, expired
    billing_mode    VARCHAR(20) DEFAULT 'online',   -- online, offline
    billing_cycle   VARCHAR(20) NOT NULL,           -- monthly, quarterly, yearly
    billing_date    INT,                           -- day of month (1-28) for calendar billing
    current_period_start DATE NOT NULL,
    current_period_end   DATE NOT NULL,
    trial_ends_at   DATE,
    coupon_id       UUID REFERENCES coupons(id),
    sales_person_id UUID,
    reference       VARCHAR(255),
    custom_fields   JSONB DEFAULT '{}',
    cancelled_at    TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, subscription_number)
);

CREATE TABLE subscription_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    subscription_id UUID NOT NULL REFERENCES subscriptions(id) ON DELETE CASCADE,
    item_id         UUID NOT NULL REFERENCES items(id),
    quantity        DECIMAL(10, 2) NOT NULL DEFAULT 1,
    unit_price      DECIMAL(15, 2) NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE subscription_addons (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    subscription_id UUID NOT NULL REFERENCES subscriptions(id) ON DELETE CASCADE,
    addon_id        UUID NOT NULL REFERENCES addons(id) ON DELETE CASCADE,
    quantity        DECIMAL(10, 2) DEFAULT 1,
    unit_price      DECIMAL(15, 2) NOT NULL,
    addon_type      VARCHAR(20) NOT NULL,  -- one_time, recurring
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Scheduled changes (apply immediately, end of term, or specific date)
CREATE TABLE subscription_scheduled_changes (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    subscription_id UUID NOT NULL REFERENCES subscriptions(id) ON DELETE CASCADE,
    change_type     VARCHAR(50),  -- plan_change, addon_add, addon_remove, cancel
    effective_date  DATE NOT NULL,
    new_plan_id     UUID REFERENCES plans(id),
    status          VARCHAR(20) DEFAULT 'pending',
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Coupon redemptions
CREATE TABLE coupon_redemptions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    coupon_id       UUID NOT NULL REFERENCES coupons(id) ON DELETE CASCADE,
    subscription_id UUID REFERENCES subscriptions(id),
    customer_id     UUID NOT NULL REFERENCES customers(id),
    redeemed_at     TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.8 Payments, Payment Links, Refunds

```sql
CREATE TABLE payments (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    payment_number  VARCHAR(50) NOT NULL,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    amount          DECIMAL(15, 2) NOT NULL,
    currency        VARCHAR(3) DEFAULT 'USD',
    payment_mode    VARCHAR(30),  -- card, bank_transfer, cash, check, etc.
    card_id         UUID REFERENCES cards(id),
    status          VARCHAR(20) DEFAULT 'completed',
    payment_date    DATE NOT NULL,
    reference       VARCHAR(255),
    gateway_txn_id  VARCHAR(255),
    notes           TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, payment_number)
);

CREATE TABLE payment_allocations (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    payment_id      UUID NOT NULL REFERENCES payments(id) ON DELETE CASCADE,
    invoice_id      UUID NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    amount          DECIMAL(15, 2) NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Payment links (Zoho Payment-Links - shareable checkout)
CREATE TABLE payment_links (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    customer_id     UUID REFERENCES customers(id),
    invoice_id      UUID REFERENCES invoices(id),
    amount          DECIMAL(15, 2) NOT NULL,
    currency        VARCHAR(3) DEFAULT 'USD',
    link_url        VARCHAR(500),
    status          VARCHAR(20) DEFAULT 'active',  -- active, paid, cancelled
    expires_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Refunds (Zoho Refunds API)
CREATE TABLE refunds (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    payment_id      UUID REFERENCES payments(id),
    credit_note_id  UUID REFERENCES credit_notes(id),
    amount          DECIMAL(15, 2) NOT NULL,
    reason          VARCHAR(255),
    status          VARCHAR(20) DEFAULT 'completed',
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.9 Hosted Pages (Zoho Hosted-Pages - Checkout)

```sql
-- Hosted checkout pages (create subscription, update card, record payment, etc.)
CREATE TABLE hosted_pages (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    page_type       VARCHAR(50) NOT NULL,  -- subscription, update_card, invoice_payment, addon
    subscription_id UUID REFERENCES subscriptions(id),
    customer_id     UUID REFERENCES customers(id),
    invoice_id      UUID REFERENCES invoices(id),
    product_id      UUID REFERENCES products(id),
    plan_id         UUID REFERENCES plans(id),
    url_token       VARCHAR(255) UNIQUE NOT NULL,
    status          VARCHAR(20) DEFAULT 'active',
    expires_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.10 Projects, Tasks, Time Entries, Expenses

```sql
-- Projects (Zoho Projects API - project-based billing)
CREATE TABLE projects (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    customer_id     UUID NOT NULL REFERENCES customers(id),
    name            VARCHAR(255) NOT NULL,
    description     TEXT,
    billing_type    VARCHAR(30) DEFAULT 'fixed',  -- fixed, time_and_materials
    budget          DECIMAL(15, 2),
    status          VARCHAR(20) DEFAULT 'active',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE project_users (
    project_id      UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id         UUID NOT NULL,
    role            VARCHAR(50),
    PRIMARY KEY (project_id, user_id)
);

CREATE TABLE tasks (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id      UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    name            VARCHAR(255) NOT NULL,
    description     TEXT,
    billable        BOOLEAN DEFAULT true,
    hourly_rate     DECIMAL(15, 2),
    status          VARCHAR(20) DEFAULT 'open',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Time entries (Zoho Time Entries API)
CREATE TABLE time_entries (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    project_id      UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    task_id         UUID REFERENCES tasks(id),
    user_id         UUID NOT NULL,
    date            DATE NOT NULL,
    hours           DECIMAL(8, 2) NOT NULL,
    billable_hours  DECIMAL(8, 2),
    hourly_rate     DECIMAL(15, 2),
    description     TEXT,
    approval_status VARCHAR(20) DEFAULT 'pending',  -- pending, approved, rejected
    invoice_id      UUID REFERENCES invoices(id),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Expenses (Zoho Expenses API)
CREATE TABLE expenses (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    project_id      UUID REFERENCES projects(id),
    customer_id     UUID REFERENCES customers(id),
    user_id         UUID,
    expense_type    VARCHAR(50),  -- mileage, receipt, etc.
    amount          DECIMAL(15, 2) NOT NULL,
    currency        VARCHAR(3) DEFAULT 'USD',
    date            DATE NOT NULL,
    description     TEXT,
    receipt_url     VARCHAR(500),
    billable        BOOLEAN DEFAULT true,
    invoice_id      UUID REFERENCES invoices(id),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Recurring expenses (Zoho Recurring Expenses)
CREATE TABLE recurring_expenses (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    customer_id     UUID REFERENCES customers(id),
    description     VARCHAR(500),
    amount          DECIMAL(15, 2) NOT NULL,
    frequency       VARCHAR(20),  -- weekly, monthly, yearly
    next_date       DATE,
    status          VARCHAR(20) DEFAULT 'active',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.11 Tax, Settings, Reporting Tags

```sql
-- Tax (Zoho Settings - taxes, tax authorities, exemptions)
CREATE TABLE taxes (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name            VARCHAR(100) NOT NULL,
    rate            DECIMAL(5, 2) NOT NULL,
    country         VARCHAR(2),
    state           VARCHAR(50),
    is_default      BOOLEAN DEFAULT false,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE tax_authorities (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name            VARCHAR(255) NOT NULL,
    country         VARCHAR(2),
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Reporting tags (Zoho Reporting Tags - for segmentation)
CREATE TABLE reporting_tags (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name            VARCHAR(100) NOT NULL,
    type            VARCHAR(50),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE reporting_tag_options (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    reporting_tag_id UUID NOT NULL REFERENCES reporting_tags(id) ON DELETE CASCADE,
    name            VARCHAR(100) NOT NULL,
    is_default      BOOLEAN DEFAULT false,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.12 Workflows, Events, Audit

```sql
-- Workflows (Zoho automation - triggers, conditions, actions)
CREATE TABLE workflows (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name            VARCHAR(255) NOT NULL,
    trigger_entity  VARCHAR(50),  -- quote, invoice, subscription, payment
    trigger_event   VARCHAR(50),  -- created, updated, accepted, paid
    conditions      JSONB DEFAULT '[]',
    actions         JSONB DEFAULT '[]',  -- email, webhook, custom_function
    is_active       BOOLEAN DEFAULT true,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Events (Zoho Events - webhooks)
CREATE TABLE events (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID REFERENCES organizations(id),
    event_type      VARCHAR(100) NOT NULL,
    entity_type     VARCHAR(50),
    entity_id       UUID,
    payload         JSONB,
    processed_at    TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE audit_logs (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID REFERENCES organizations(id),
    user_id         UUID,
    entity_type     VARCHAR(50) NOT NULL,
    entity_id       UUID NOT NULL,
    action          VARCHAR(20) NOT NULL,
    old_values      JSONB,
    new_values      JSONB,
    ip_address      INET,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.13 Number Sequences & Exchange Rates

```sql
CREATE TABLE number_sequences (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    sequence_type   VARCHAR(50) NOT NULL,  -- invoice, quote, payment, subscription, etc.
    prefix          VARCHAR(20),
    current_value   INT NOT NULL DEFAULT 0,
    padding         INT DEFAULT 5,
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, sequence_type)
);

-- Multi-currency (Zoho supports 100+ currencies)
CREATE TABLE exchange_rates (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    from_currency   VARCHAR(3) NOT NULL,
    to_currency     VARCHAR(3) NOT NULL,
    rate            DECIMAL(18, 8) NOT NULL,
    effective_date  DATE NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(organization_id, from_currency, to_currency, effective_date)
);
```

---

## 3. Zoho Billing Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ZOHO BILLING FLOW                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  PRODUCT CATALOG                    CUSTOMERS                                 │
│  ┌──────────┐                      ┌──────────────┐                         │
│  │ Products │──┐                   │  Customers    │                         │
│  └──────────┘  │                   │  Contact      │                         │
│       │        │                   │  Persons      │                         │
│       ▼        │                   │  Cards        │                         │
│  ┌──────────┐  │                   └───────┬────────┘                         │
│  │  Plans   │──┼──────────────────────────┼──────────────────────────────┐  │
│  └──────────┘  │                          │                              │  │
│       │        │                          │                              │  │
│       ▼        │                   ┌──────▼──────┐                         │  │
│  ┌──────────┐  │                   │  Quotes     │                         │  │
│  │  Addons  │──┘                   │  draft→sent│                         │  │
│  └──────────┘                      │  →accepted │                         │  │
│       │                            └──────┬──────┘                         │  │
│       │                                   │                                │  │
│       │                            ┌──────▼──────┐                         │  │
│       │                            │  Invoices   │                         │  │
│       │                            │  Credit     │                         │  │
│       │                            │  Notes      │                         │  │
│       │                            └──────┬──────┘                         │  │
│       │                                   │                                │  │
│       │                            ┌──────▼──────┐                         │  │
│       └───────────────────────────►│  Payments   │                         │  │
│                                    │  Refunds    │                         │  │
│                                    └─────────────┘                         │  │
│                                                                              │
│  SUBSCRIPTIONS (recurring)          PROJECT BILLING                          │
│  Customer + Plan + Addons + Coupon  Projects → Tasks → Time Entries          │
│       │                            Expenses → Invoice                        │
│       ▼                                                                      │
│  Auto-generate Invoices             Estimates → Retainer Invoices            │
│  Unbilled Charges → Invoice                                                  │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Key Zoho-Specific Features

| Feature | Implementation |
|---------|----------------|
| **Prorated billing** | Calculate on `subscription_scheduled_changes` and `current_period_*` |
| **Metered billing** | Use `unbilled_charges` for usage, convert to invoice |
| **Consolidated billing** | Single invoice for customer with multiple subscriptions |
| **Anniversary billing** | `billing_date` = signup day |
| **Calendar billing** | `billing_date` = fixed day (e.g., 1st of month) |
| **Dunning** | Workflow on `invoice.status = overdue` |
| **Hosted checkout** | `hosted_pages` + payment gateway integration |

---

## 5. Indexes Summary

```sql
CREATE INDEX idx_products_org ON products(organization_id);
CREATE INDEX idx_plans_product ON plans(product_id);
CREATE INDEX idx_subscriptions_customer ON subscriptions(customer_id);
CREATE INDEX idx_subscriptions_status ON subscriptions(status);
CREATE INDEX idx_invoices_customer ON invoices(customer_id);
CREATE INDEX idx_invoices_status ON invoices(status);
CREATE INDEX idx_invoices_due_date ON invoices(due_date);
CREATE INDEX idx_quotes_status ON quotes(status);
CREATE INDEX idx_time_entries_project ON time_entries(project_id);
CREATE INDEX idx_events_processed ON events(processed_at) WHERE processed_at IS NULL;
```

---

*Aligned with Zoho Billing API & Help documentation*
