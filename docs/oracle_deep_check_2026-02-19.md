# Oracle Deep Check: `C##website_v1`

Snapshot date: **2026-02-19**  
Host: `localhost:1521/orcl`  
Verified current user: `C##WEBSITE_V1`

## 1) Core Objects

### Tables (with live counts)

| Table | Rows |
|---|---:|
| ALERT_STOCKS | 30 |
| CLIENTS | 521 |
| CLIENT_TYPE | 4 |
| EMPLOYEES | 20 |
| FORM_CONTOL | 0 |
| GROUP_USER | 0 |
| INVOICES | 522 |
| INVOICE_DETAILS | 1044 |
| JOBS | 12 |
| PERMISSION_GROUP | 0 |
| PRODUCTS | 30 |
| PRODUCT_TYPE | 8 |
| PURCHASES | 8 |
| PURCHASE_DETAILS | 14 |
| SUPPLIERS | 20 |
| USERS | 0 |

### Views

- `FUTURE_PRODUCTS`
- `MONTHLY_SALES`
- `TOTALAMOUNTBYINVOICENO`
- `TOTALSALEDETAIL`
- `VEMP_JOB`
- `V_INVOICES`
- `V_INVOICE_DETAILS`
- `analyst_products`

### Triggers

- `SALE_ADD` (`INVOICE_DETAILS`, `BEFORE INSERT`) updates stock and sets line price from `PRODUCTS.SELL_PRICE`.
- `SALE_UPDATE` (`INVOICE_DETAILS`, `BEFORE UPDATE`) adjusts stock when qty/product changes.
- `SALE_DELETE` (`INVOICE_DETAILS`, `AFTER DELETE`) restores stock.
- `ALERT_STOCKS` (`PRODUCTS`, `INSERT OR UPDATE`) maintains stock alert records.
- `ADD_PURCHASE_QTY` / `ADD_PURCHASE_UNITCOST` on `PURCHASE_DETAILS`.
- `CHECK_SALARY` on `EMPLOYEES`.

### Identity Columns

- `CLIENTS.CLIENT_NO` -> `ISEQ$$_99967`
- `INVOICES.INVOICE_NO` -> `ISEQ$$_99973`
- Additional identities exist on `CLIENT_TYPE`, `PRODUCT_TYPE`, and other admin tables.

## 2) E-Commerce Data Model Validation

Validated as storefront-ready:

- Product catalog: `PRODUCTS` + `PRODUCT_TYPE`
- Customer data: `CLIENTS` + `CLIENT_TYPE`
- Orders: `INVOICES`
- Order lines: `INVOICE_DETAILS`
- Sales analytics: `V_INVOICES`, `MONTHLY_SALES`
- Inventory safety: stock triggers + `ALERT_STOCKS`

## 3) Constraints & Rules Relevant to Checkout

- `INVOICES.CLIENT_NO` FK -> `CLIENTS.CLIENT_NO`
- `INVOICES.EMPLOYEE_ID` FK -> `EMPLOYEES.EMPLOYEE_ID`
- `INVOICE_DETAILS.INVOICE_NO` FK -> `INVOICES.INVOICE_NO`
- `INVOICE_DETAILS.PRODUCT_NO` FK -> `PRODUCTS.PRODUCT_NO`
- `INVOICE_DETAILS` PK is composite: `(INVOICE_NO, PRODUCT_NO)`
- `CLIENTS.PHONE` unique and `CLIENTS.CLIENT_NAME` unique

## 4) Runtime Checks Completed

- Connected directly with `oci_connect('C##website_v1', '123', '//localhost:1521/orcl')`.
- Inserted `INVOICES` and `INVOICE_DETAILS` inside a transaction and confirmed:
  - identity retrieval works via `CURRVAL`
  - trigger-set line price is populated
  - stock decrements correctly
  - transaction rollback restores state

## 5) Notable Findings

- `search_emp` procedure is not present in `C##website_v1`.
- Existing employee search UI was adapted to fallback to direct `EMPLOYEES` queries when procedure call fails.
