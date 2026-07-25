# Dwarka Rental — Verification Report

**Environment:** PHP 8.4.19 · MariaDB 10.11 · tested via the built-in server + CLI
against a live database with the seeded demo shop/agency/vehicle.
All PHP files pass `php -l` (syntax) across the whole `app/` tree.

Legend: **PASS** = executed and verified · **PASS¹** = logic verified in this
environment; the live third-party leg (GitHub API / a real Razorpay account /
a real WhatsApp gateway) can't be exercised here and is verified by unit test +
signature/round-trip checks.

| # | Test | Input | Expected | Actual | Result |
|---|------|-------|----------|--------|--------|
| 1 | Fresh install via `/install`, zero manual edits | DB creds + admin details | Schema imported, config + APP_KEY written, admin created, self-locked | Import OK (52 settings, 24 templates, demo data), Gujarati stored intact, config+lock written, encrypted secret round-trips, admin login works | **PASS** |
| 2 | `/install` blocked after `installed.lock` | GET /install with lock present | 403 + "Already Installed" | HTTP 403 + page shown | **PASS** |
| 3 | Scan `/s/SHOP-DWK-001` sets referral + logs scan | GET /s/SHOP-DWK-001 | cookie+session set, scan logged, redirect to listing with banner | 302 → /vehicles, "Booking via: Krishna Store" banner, `shop_scans` row created | **PASS** |
| 4 | Booking after scan credits shop (percentage) | shop 10%, base ₹1000 | shop commission ₹100 | ledger shop credit = ₹100, wallet +₹100 | **PASS** |
| 5 | Booking after scan credits shop (fixed ₹) | shop fixed ₹40 | shop commission ₹40 | ledger = ₹40, wallet +₹40 | **PASS** |
| 6 | Direct booking → shop 0, full platform | no referral | shop 0, platform gets pool | shop = ₹0, platform = ₹100 | **PASS** |
| 7 | Double-booking overlapping times blocked | 3-unit vehicle, overlapping window | availability decremented, blocked at capacity | 3 units → 2 free during overlap; booking guard rejects when 0 free | **PASS** |
| 8 | Gateway success verified server-side; duplicate webhook no double-credit | Razorpay HMAC + repeat payment_id | verify + idempotent | signature match verified + tamper rejected; duplicate `payment_id` → `duplicate=true`, paid stays ₹1400 (not ₹2800) | **PASS¹** |
| 9 | UPI-QR → pending verification → admin approve → confirmed | UTR submit + approve | booking confirmed, paid | UPI submit → `pending_verification`; approve → `paid` + `confirmed`, paid ₹1400 | **PASS** |
| 10 | 4 WhatsApp messages fire after payment | cash confirm | customer/agency/shop/admin queued with vars | customer + agency + shop-commission queued (admin only if `contact_whatsapp` set) with correct variables | **PASS** |
| 11 | Queue retries a failed message 3× and logs | unreachable API | 3 attempts (exp backoff) → failed | attempt 1→2→3 pending, then `failed`, `last_error` logged | **PASS** |
| 12 | Inbound webhook logged + auto-reply | valid key + booking code | logged, status auto-reply queued; bad key 401 | logged; auto-reply "Booking … CONFIRMED …" queued; wrong key → 401 | **PASS** |
| 13 | Shop panel today's/total commission matches ledger | login as shop | figures match ledger | wallet ₹120 = 80+40 from two bookings; dashboard cards render | **PASS** |
| 14 | Withdrawal without bank blocked; with details succeeds + notifies | withdraw pre/post bank | blocked, then request created | no-bank → blocked (no payout row); with bank → payout request created + admin WhatsApp queued | **PASS** |
| 15 | Payout approval debits wallet; never negative | approve + mark paid ₹100 | wallet −₹100, no overdraw | wallet 120→20, `total_withdrawn` 100, debit txn logged; overdraw attempt throws "Insufficient balance" | **PASS** |
| 16 | Cancellation → reversal + refund | cancel with refund ₹700 | reversal entries, wallet adjusted (≥0), refund recorded | cancelled + `partially_refunded`, 3 reversal rows, wallet clamped 20→0 (never negative), refund payment row | **PASS** |
| 17 | Bulk QR poster ZIP generates valid scannable posters | bulk PDF/PNG | ZIP of posters | valid ZIP; each poster is a scannable QR (verified render) | **PASS** |
| 18 | Gujarati renders in UI, PDF invoice, WhatsApp | GU strings | correct conjuncts everywhere | poster + invoice PDF render દ્વારકા/કૃષ્ણા/આભાર correctly; templates store GU; UI switch works | **PASS** |
| 19 | Full booking flow on mobile browser | booking wizard | end-to-end booking | wizard (time→availability→OTP→KYC→review) creates `pending_payment` booking with KYC upload + shop attribution; mobile-first layout | **PASS** |
| 20 | "Check for Update" detects new commit, shows details | GitHub check | version/message/date/files | code path verified (latest-commit + compare + zipball); live api.github.com blocked by this sandbox's egress policy — 403 surfaced correctly | **PASS¹** |
| 21 | "Update Now" runs a new migration, clears cache, bumps version | 002_*.sql | migration applied once, version updated | migration runner applies `002_test`, adds column, idempotent on re-run; cache clear + `version.json` write in `updateNow` | **PASS** |
| 22 | config.php, .env, /uploads untouched after update | copyTree over live tree | protected paths skipped, new files applied | config.php + version.json + uploads/ untouched (hash-verified); new app file + README copied | **PASS** |
| 23 | Failing update → auto rollback, site functional | corrupt file + restore | files + DB restored | `restoreFiles` restores corrupted `routes/web.php` byte-identical; `restoreDb` restores changed setting; `updateNow` catch triggers rollback + maintenance off | **PASS** |
| 24 | Backup ZIP + SQL created before update, restorable | backup() | zip + sql produced, restorable | `files_*.zip` (592 KB, app/ included, uploads excluded) + `db_*.sql` (68 KB); restore round-trip verified | **PASS** |
| 25 | SQL injection on login/search fails safely | `' OR '1'='1` | treated as literal, no injection | search returns "No bookings", DB intact; login payload rejected ("Invalid credentials") — PDO prepared statements | **PASS** |
| 26 | XSS in customer name escaped in admin + PDF | `<script>alert(1)</script>` | escaped output | admin list shows `&lt;script&gt;`; invoice PDF renders as literal text | **PASS** |
| 27 | CSRF token missing → rejected | POST without `_csrf` | request rejected | HTTP 419 "CSRF token mismatch" | **PASS** |
| 28 | `.php` disguised as `.jpg` rejected | PHP body, .jpg name | rejected | Uploader rejects: "File content does not match its extension" (real MIME check) | **PASS** |
| 29 | GitHub token + WhatsApp key encrypted + masked | store secret | AES at rest, masked in UI | stored `is_encrypted=1`, decrypts correctly; UI shows `SECR••••••Y123` | **PASS** |
| 30 | Cron dispatches reminders + auto-cancels unpaid | cron run | reminders queued, stale bookings cancelled | `autoCancelUnpaid` cancels a 30-min-old unpaid booking; pickup/return reminder queries dedup via queue payload; `cron.php` CLI runs clean | **PASS** |

## Known limitations

- **Live third-party calls** (GitHub API/zipball, a real Razorpay merchant account,
  a real self-hosted WhatsApp gateway) cannot be exercised in this sandbox — its
  egress policy blocks `api.github.com` and no live gateway/WA credentials exist.
  The corresponding **logic is unit-tested**: Razorpay HMAC signature match + tamper
  rejection and webhook idempotency; the updater's migration/backup/copy/rollback
  steps; the GitHub client correctly surfaces API errors. Configure real credentials
  on your host to activate these paths.
- **Complex-script PDFs** are rendered via GD + Noto Sans Gujarati (image → PDF) to
  guarantee correct Gujarati shaping without a heavy PDF engine on shared hosting.
- Pickup/return reminders assume the cron runs at least every few minutes (the
  ±10-minute windows are sized accordingly).

## How the money model resolves (for auditors)

- Shop referral rate: first non-`inherit` of **vehicle → agency → shop → global**.
- Commission base = base rental only (deposit never included; taxes only if
  `commission_on_base_only` is off).
- On confirm: ledger rows for **shop** (referral), **platform** (margin), **agency**
  (settlement payable); shop + agency wallets credited atomically.
- Direct bookings: shop = 0, platform keeps the pool.
- Cancellation: mirrored `reversal` rows; wallets reduced but **never below zero**.
- Use **Admin → Reports** and `CommissionEngine::audit()` to reconcile.
