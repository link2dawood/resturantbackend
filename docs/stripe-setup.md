# Stripe Setup (Phase 4 — Payments)

The code for subscriptions is complete, but these steps require the **Stripe
dashboard** and **cannot be automated** — do them once per environment (use
**Test mode** for staging/dev).

## 1. API keys
Stripe Dashboard → **Developers → API keys**. Copy into `.env`:

```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
CASHIER_CURRENCY=usd
```

## 2. Product + recurring price
Stripe Dashboard → **Product catalog → Add product**.
- Add a **recurring** price, **Monthly**, in your currency (e.g. $99.00/month).
- Copy the **Price ID** (`price_...`) into `.env`:

```
STRIPE_PRICE_ID=price_...
SUBSCRIPTION_PLAN_NAME="Pro"
SUBSCRIPTION_MONTHLY_AMOUNT=9900   # cents; keep in sync with the price (for MRR reporting)
```

> Billing is anchored to the **1st of each month** with a **prorated first
> charge** — this is handled in code (`SubscriptionService`), not in the price config.

## 3. Webhook endpoint
Stripe Dashboard → **Developers → Webhooks → Add endpoint**.
- URL: `https://YOUR_DOMAIN/stripe/webhook`
- Events to send (minimum):
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`
- Copy the **Signing secret** (`whsec_...`) into `.env`:

```
STRIPE_WEBHOOK_SECRET=whsec_...
```

Local testing: `stripe listen --forward-to localhost:8000/stripe/webhook`.

## 4. Dunning / Smart Retries
Stripe Dashboard → **Settings → Billing → Subscriptions and emails → Manage failed
payments**. Enable **Smart Retries** and the retry schedule. Our app emails the
customer on each `invoice.payment_failed`; Stripe handles the retry cadence and
cancels the subscription after the final failure (which locks the workspace out).

## 5. Customer Portal
Stripe Dashboard → **Settings → Billing → Customer portal**. Enable it and allow
**update payment method**, **view invoices**, and **cancel subscription**. The app
links to it from `/billing` → "Manage subscription & invoices".

## 6. (Optional) Stripe-sent invoices/receipts
We send our own receipt on `invoice.payment_succeeded`. If you also want Stripe's
native emailed invoices/receipts, enable them under **Settings → Billing → Invoices**
and **Settings → Emails**.

## 7. Run migrations
```
php artisan migrate
```
Adds Cashier's `stripe_id`/`pm_*` columns, the `subscriptions` and
`subscription_items` tables (the trial columns came from the trial-system migration).
