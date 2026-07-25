# 🛵 Dwarka Rental — Multi-Vendor Vehicle Rental Platform

A production-ready, mobile-first web application for **Devbhoomi Dwarka (Gujarat)**.
Tourists scan a shop's printed **QR poster**, book a Bike / Activa / Scooty / Car /
Tempo Traveller / Cycle, pay online, and the referring shop automatically earns a
commission. Full **Gujarati + English** UI, WhatsApp automation, commission engine,
one-click installer, and a GitHub-based auto-updater.

Built in **PHP 8.1+ / MySQL** with a lightweight custom MVC — **no Composer, runs on
basic cPanel shared hosting**.

---

## 1. Hosting requirements

| Requirement | Minimum |
|---|---|
| PHP | 8.1+ (tested on 8.4) |
| MySQL / MariaDB | 5.7+ / 10.4+ (InnoDB, utf8mb4) |
| PHP extensions | `pdo_mysql`, `curl`, `mbstring`, `gd`, `zip`, `json`, `openssl`, `fileinfo` |
| Apache | `mod_rewrite` + `.htaccess` (`AllowOverride All`) |
| HTTPS | Required (installer forces HTTPS) |

> On Nginx, map all non-file requests to `index.php` and deny access to
> `/config`, `/backups`, `/logs`, `/migrations`, `/lib`; disable PHP execution in `/uploads`.

## 2. Upload & install

1. Create a **MySQL database + user** in cPanel and grant all privileges.
2. Upload the project files to your web root (e.g. `public_html`).
3. Ensure `config/`, `uploads/`, `backups/`, `cache/`, `logs/` are **writable** (0755).
4. Open **`https://your-domain.com/install`** and follow the wizard:
   - System check → Database (imports schema + optional demo data) → Website →
     Admin account → WhatsApp API (optional, with a live test) → Finish.
5. The installer writes `config/config.php` (with a random `APP_KEY`), creates
   `config/installed.lock`, and **locks itself**. No manual file editing anywhere.
6. Log in at **`/admin/login`** with the mobile + password you chose.

To reinstall, delete `config/installed.lock`.

## 3. Cron setup

Add a single cron job (every minute) — dispatches the WhatsApp queue, sends
pickup/return reminders, and auto-cancels unpaid bookings:

```
* * * * * php /home/USER/public_html/cron.php >> /home/USER/public_html/logs/cron.log 2>&1
```

If your host has no CLI cron, set a `cron_key` in the DB and hit
`https://your-domain.com/cron.php?cron_key=YOURKEY` from a web cron service.

## 4. Payment gateway setup

- **Admin → Settings** → toggle the methods you want (Razorpay / UPI QR / Cash).
- **Razorpay**: store `razorpay_key_id`, `razorpay_key_secret`, and
  `razorpay_webhook_secret` (encrypted). Point the Razorpay webhook to
  `https://your-domain.com/webhook/razorpay` (events: `payment.captured`).
- **UPI QR**: set each **agency's** UPI ID / QR image (Admin → Agencies).
  Customers pay and upload UTR + screenshot → admin approves under **Payments**.
- **Cash / pay at pickup**: reserves the booking; balance collected at the counter.

All payments are verified **server-side**; duplicate webhooks are idempotent.

## 5. WhatsApp setup

- **Admin → WhatsApp → Settings**: enter your self-hosted API URL, key, session,
  sender (all encrypted at rest, masked in the UI), enable sending, and set the
  daily limit / send delay. Use **Send Test Message** to verify.
- **Templates**: edit the EN + GU templates and pick variables like
  `{customer_name} {booking_code} {vehicle_name} {pickup_time} {amount}` …
- **Inbound**: point your API's inbound webhook to
  `https://your-domain.com/api/whatsapp_inbound` (verified by the inbound secret).
  Customers can reply with a booking code to get its status automatically.

## 6. GitHub auto-update setup

- **Admin → Update Manager**: enter `owner/repo`, branch, and a **Personal Access
  Token** (stored AES-256 encrypted, masked). Click **Test Connection**.
- **Check for Update** compares the latest commit with `version.json`.
- **Update Now** runs: pre-flight → **backup (files ZIP + DB SQL)** → download →
  extract to staging → copy files (**skipping `config/`, `uploads/`, `backups/`,
  `.env`, `installed.lock`, `version.json`**) → run new migrations → clear cache →
  bump `version.json`. **Any failure triggers automatic rollback** (files + DB
  restored from the backup) and the site stays online.

## 7. Roles & panels

| Role | URL | What they do |
|---|---|---|
| Super Admin / Staff | `/admin` | Everything (staff have granular permissions) |
| Shop Partner | `/shop` | Commission, wallet, withdrawal, own QR poster |
| Agency / Owner | `/agency` | Vehicles, pickups/returns, settlement, UPI QR |
| Customer | `/` | Browse, book (mobile OTP), pay, "My Bookings" |

## 8. Default credentials

There are **no hardcoded credentials** — the super-admin account is created during
installation. Shop/agency logins are created by the admin from the shop/agency edit
form (mobile = login). Change the admin password anytime (create a new staff or edit
the user in the DB if locked out).

## 9. Folder structure

```
/                    front controller (index.php), .htaccess, version.json, cron.php
/app/Core            framework (Database, Router, Auth, Session, Csrf, Crypto, Lang, …)
/app/Controllers     Admin / Shop / Agency / Front / Api controllers
/app/Services        Availability, Pricing, Commission, Wallet, Payout, Whatsapp,
                     Invoice/Statement/Image PDF, Poster, Updater, GitHubClient, …
/app/Views           admin / shop / agency / front / layouts / errors templates
/app/Middleware      AdminAuth, ShopAuth, AgencyAuth
/config              config.php (generated), installed.lock, .htaccess (deny)
/install             one-click wizard + database.sql
/migrations          001_init.sql + runner
/lib/phpqrcode       bundled pure-PHP QR library
/assets/fonts        Noto Sans Gujarati (for Gujarati posters/PDFs)
/uploads /backups /logs /cache   runtime (protected)
/lang                en.php, gu.php
```

## 10. Security

PDO prepared statements everywhere · bcrypt passwords · CSRF on every POST/AJAX ·
output escaping (XSS) · upload MIME whitelist + PHP-exec blocked in `/uploads` ·
login rate-limit & lockout · OTP 5-min expiry · secrets AES-256-GCM encrypted &
masked · `.htaccess` protection for sensitive dirs · forced HTTPS · idempotent
payment webhooks · full admin audit log · masked bank numbers.

## 11. Troubleshooting

- **Installer won't run** → delete `config/installed.lock`.
- **500 after install** → check `logs/php_errors.log`; verify DB credentials in
  `config/config.php` and that `/config` is writable.
- **Pretty URLs 404** → enable `mod_rewrite` / `AllowOverride All`; on a subfolder
  set `RewriteBase` in the root `.htaccess`.
- **WhatsApp not sending** → enable it in settings, confirm cron runs, check
  **WhatsApp → Queue** for errors.
- **Gujarati looks wrong in PDF** → ensure `assets/fonts/NotoSansGujarati.ttf` exists.
- **Update check fails** → verify the repo/token and that the token has `repo` scope.

---

_See `VERIFICATION_REPORT.md` for the full tested feature matrix._
