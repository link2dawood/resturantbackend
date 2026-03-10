# Part C: Online Fees (Uber, DoorDash, Grubhub) — How It Works

## 1. Process January (or Any Month) Statements

**Flow: manual upload → system parses → expenses + expected deposit**

1. **Where:** In the app, go to **Merchant Fees** → **Third-Party Platforms** (or direct: `/merchant-fees/third-party`).
2. **Upload:** Click **Import Statement**, then:
   - Choose **platform**: Grubhub, UberEats, or DoorDash
   - Choose **store**
   - Upload the **January (or any month) statement file**
3. **System does the rest:**
   - Parses the file (format depends on platform — see below)
   - Creates one **third_party_statement** record (gross sales, marketing/delivery/processing fees, net deposit)
   - Creates **expense transactions** for Marketing, Delivery, and Processing fees (under the right COA)
   - Creates **expected bank deposit** (net deposit) for reconciliation
4. **Result:** January (or that month's) online fees are in the system: visible in Third-Party Platform Costs, P&L, and bank reconciliation.

So "Process January Statements" = upload each platform's January statement file once per store; the system integrates the data as above.

---

## 2. Handle Varying Formats (Per Partner)

| Platform   | Accepted format | How it's handled |
|-----------|------------------|-------------------|
| **Grubhub** | PDF             | PDF text is extracted; amounts are found by searching for labels (e.g. "Gross Sales", "Marketing Fee", "Net Deposit"). |
| **UberEats** | CSV            | First row = headers. Columns are matched by **name** (case-insensitive). Supported names include: Date, Gross Sales / Subtotal, Marketing Fees / Commission, Delivery Fees, Processing Fees / Payment Processing, Net Deposit / Payout. All rows are aggregated into one statement. |
| **DoorDash** | CSV            | Same idea as UberEats: header-based column mapping. Names like Gross Sales / Total Sales, Marketing Fees / Platform Fee, Delivery Fees, Processing Fees, Net Deposit / Payout. Aggregated into one statement. |

**Varying formats within a platform:**  
- CSV: Any column order is fine as long as the **header names** match (or we add aliases). If a platform changes column names, we add another alias in the parser.  
- PDF: Grubhub's parser looks for common wording; if layout changes, we extend the search patterns.

---

## 3. Access Requirements: Manual vs Direct System Access

**Current design: no direct access to Uber/DoorDash/Grubhub needed.**

- Statements are provided manually (downloaded from each platform's merchant dashboard, then uploaded in our app).
- Developer does not need API keys or login to partner systems.
- Developer only needs: Laravel app access and sample statement files when a platform changes format.

**If you later want direct integration:** would require API/partner access, secure credentials, and a fetch step before parse; same data model and parsers would be reused.

---

## 4. Quick Reference: Where in the Code

- **Import API:** `App\Http\Controllers\Api\ThirdPartyImportController` — `import()`, plus `parseGrubhubPDF()`, `parseUberEatsCSV()`, `parseDoorDashCSV()`.
- **UI:** `resources/views/admin/merchant-fees/third-party.blade.php`
- **Data:** `third_party_statements` table; linked `expense_transactions` (and expected `bank_transactions`).
- **Routes:** Under `api/third-party/` (e.g. `POST api/third-party/import`).

---

## 5. Summary

| Part C requirement              | How it works |
|--------------------------------|-------------|
| **Process January statements** | Upload each platform's January statement (PDF or CSV) per store via **Third-Party Platforms**; system parses and creates statement + fee expenses + expected deposit. |
| **Handle varying formats**     | Separate parser per platform; CSV uses header-name mapping; PDF uses label search; new aliases when a platform changes. |
| **Access**                     | **Manual statements only** — no developer access to Uber/DoorDash/Grubhub; only app access and sample files for parser updates. |
