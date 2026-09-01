# FRP Tool — Instructions for Cursor & Developers

Read this file first when opening the project in **Cursor on Windows**. It explains architecture, setup, testing, and what is **not** included in the repo.

---

## 1. What this project is

| Component | In this repo? | Description |
|-----------|---------------|-------------|
| **PHP API server** | ✅ Yes (`api/`, `crypto/`) | Proxy between Windows service tools and Realme/Oppo upstream servers |
| **Windows `.exe` tool** | ❌ No | MsmDownloadTool, TOOLSHUB, or Realme OTP Tool — separate downloads |
| **Marketing website** | ❌ No | Not required for MVP |
| **OAuth** | ❌ Not used | Auth = RSA/AES + OTP/license via HTTP POST |

### Architecture

```
Windows tool (.exe on customer PC)
        │  HTTPS POST (encrypted)
        ▼
Your VPS (this repo: api/ + crypto/)
        │  OTP check, token swap, MySQL
        ▼
Upstream: realmeservice.com, sgsmpro.com, regional sign servers
```

### Separate product: Realme OTP Tool

If you have **RealmeOtp.exe** (from realmeotp.com):

- It does **NOT** use this `api/` code.
- Its backend (from static analysis) is **`https://otterpulse.com`**:
  - `https://otterpulse.com/rn/oneclick.php`
  - `https://otterpulse.com/verify.php`
- Test OTP example: `XP2J37J6XL1M829` (validates on vendor server only).
- RAR archive password was **`R`**; inner app is **`RealmeOtp1.0.exe`**.

Do not confuse Realme OTP Tool with this PHP API stack.

---

## 2. Repository layout

```
frptool/                    ← git clone root
├── README.md
├── INSTRUCTIONS.md         ← this file
├── config.php.example      ← template (copy to parent — see below)
├── schema.sql              ← MySQL tables + sample rows
├── .gitignore
├── api/
│   ├── tools/              ← RCSM login/sign (MsmDownloadTool)
│   ├── platform/           ← TOOLSHUB login
│   ├── sign/               ← O+ login + sign
│   ├── gsm/                ← static JSON (models, regions)
│   ├── flash/              ← firmware proxy
│   ├── questionnaire/      ← upstream proxy
│   ├── tool/plugin/        ← upstream proxy
│   └── event/trace/        ← upstream proxy
└── crypto/
    └── cert/
        └── upgrade.php     ← O+ certs by region (needs MySQL)
```

Each API subfolder has `.htaccess` (Apache rewrite: hide `.php` extension).

---

## 3. Critical: `config.php` location

PHP files load config with:

```php
$config = include('../../../config.php');
```

From `api/platform/konak.php` that resolves to **three levels above** the subfolder, i.e. **parent of the git clone root**.

### Windows (XAMPP) example

```
C:\xampp\htdocs\
├── config.php              ← COPY config.php.example HERE
└── frptool\                ← git clone
    ├── api\
    └── crypto\
```

Apache URL: `http://localhost/frptool/api/...`  
Config path from `api/platform/`: `../../../config.php` → `C:\xampp\htdocs\config.php` ✅

### Linux VPS example

```
/var/www/html/
├── config.php              ← copy here
└── frptool/
    ├── api/
    └── crypto/
```

Edit `config.php` values: MySQL user/pass/db, `verify_url` (your public site URL).

**Never commit real `config.php` or passwords to git.**

---

## 4. Windows local setup (step by step)

### 4.1 Install stack

1. Install **XAMPP** (Apache + PHP + MySQL) or **Laragon**.
2. Enable PHP extensions in `php.ini`: `openssl`, `curl`, `mysqli`.
3. Enable Apache `mod_rewrite`.

### 4.2 Clone and configure

```powershell
cd C:\xampp\htdocs
git clone https://github.com/mkverma541/frptool.git
copy frptool\config.php.example config.php
notepad C:\xampp\htdocs\config.php
```

Set MySQL password and `verify_url` (e.g. `http://localhost` for local tests).

### 4.3 Database

```powershell
cd C:\xampp\mysql\bin
mysql -u root -p < C:\xampp\htdocs\frptool\schema.sql
```

Or use phpMyAdmin → Import `schema.sql`.

Update `cotp` table with a test OTP if using sign login flow:

```sql
UPDATE cotp SET otp = 'YOUR_TEST_OTP' WHERE id = 1;
```

### 4.4 Test API without tool (browser/curl)

These work with DB + config only:

| URL | Expected |
|-----|----------|
| `http://localhost/frptool/api/gsm/getModelNameAll.php` | JSON phone list |
| `http://localhost/frptool/api/gsm/getMdmArea.php` | JSON regions |
| `http://localhost/frptool/crypto/cert/upgrade.php` | JSON certificates (region from `actived_server` id=4) |

If these fail: check Apache running, `config.php` path, MySQL `actived_server` row.

### 4.5 Test with Windows service tool

Tools expect hostnames like `rcsm-in.realmeservice.com`. For lab testing, point hosts file to your PC/server:

```
C:\Windows\System32\drivers\etc\hosts

127.0.0.1   rcsm-in.realmeservice.com
```

Then configure Apache vhost so those paths map to `frptool/api/tools/...` (advanced — often easier on VPS with real domain).

**MVP on Windows:** Start with static endpoints + DB; full tool test usually needs VPS + domain + valid upstream credentials in `servers` table.

---

## 5. API endpoints reference

### `api/tools/` — MsmDownloadTool (RCSM)

| File | Method | Auth |
|------|--------|------|
| `login.php`, `apilogin.php` | POST | RSA `s_msg` + OTP via `otp.sgsmpro.com/otp_api.php` |
| `sign.php` | POST | RSA + MD5 sign |
| `loginmtk.php` | GET/POST | Dev/test, hardcoded creds |

User-Agent expected: `MsmDownloadTool-V2.0.71-rcsm`

Upstream: `https://rcsm-{cn,in,eu}.realmeservice.com/api/tools/login|sign`

### `api/platform/` — TOOLSHUB

| File | Method | Auth |
|------|--------|------|
| `login.php` | POST JSON | AES-256-GCM + `deviceId` header + OTP via `{verify_url}/otp_eu.php` |

Uses MySQL: `actived_server`, `servers`, `tokens`.

### `api/sign/` — O+ sign tool

| File | Method | Auth |
|------|--------|------|
| `login.php` | POST JSON `toolCode` | OTP via `otp.sgsmpro.com/otp_eu.php` |
| `sign.php` | POST JSON | Token swap + tickets from `pub.sgsmpro.com` |

Regional sign servers (from DB region): India / Europe / Other IPs in `sign.php`.

### `crypto/cert/upgrade.php`

Returns `cert4Encrypt` / `cert4Sign` JSON by region (`actived_server.id = 4`).

---

## 6. External dependencies (runtime)

| Service | Used for |
|---------|----------|
| `otp.sgsmpro.com` | OTP validation (tools + sign login) |
| `pub.sgsmpro.com` | Tickets, RealmeNew API proxy |
| `rcsm-*.realmeservice.com` | Official RCSM login/sign |
| Regional sign IPs in `sign.php` | Device signing |
| MySQL localhost | tokens, servers, cotp, devdata |

**To run independently of sgsmpro:** replace OTP URLs in PHP with your own `verify.php` endpoints (future task).

---

## 7. MySQL tables (summary)

| Table | Purpose |
|-------|---------|
| `actived_server` | Active region/server (row `id=4` used everywhere) |
| `servers` | Realme login username/password/mac |
| `tokens` | generated_token ↔ original_token swap |
| `cotp` | OTP for sign login (row `id=1`) |
| `devdata` | Sign operation logs |

Full DDL: `schema.sql`

---

## 8. MVP checklist

- [ ] Clone repo on Windows
- [ ] Copy `config.php.example` → `../config.php` (parent of clone)
- [ ] Import `schema.sql`
- [ ] Apache + PHP mysqli/openssl/curl enabled
- [ ] Test `gsm/getModelNameAll.php` in browser
- [ ] Test `crypto/cert/upgrade.php`
- [ ] Fill `servers` with valid upstream credentials (required for login proxy)
- [ ] Replace or keep sgsmpro OTP URLs depending on license source
- [ ] Point tool hosts/DNS to your server (production)
- [ ] Realme OTP Tool (`RealmeOtp.exe`) — separate test on `otterpulse.com`, not this API

---

## 9. For Cursor AI — how to work on this repo

When user asks to fix, deploy, or extend:

1. **Scope:** Server-side PHP only unless user adds a Go/C# client.
2. **Config:** Never hardcode secrets; use `config.php` in parent directory.
3. **Paths:** `konak.php` in each module loads DB via `../../../config.php`.
4. **Deploy pattern:** Same as USB Port Share — scp to VPS, chown `www-data`, Apache docroot.
5. **Do not commit:** `config.php`, `.exe`, `.rar`, logs, `.env`.
6. **Two products:** This API ≠ RealmeOtp.exe (otterpulse.com backend).
7. **OAuth:** Not used; do not add unless requested.
8. **Common tasks:**
   - Add own OTP API → new PHP files + change `$url` in `tools/login.php`, `sign/login.php`, `platform/login.php`
   - Admin panel → new folder `admin/` (like USBPortShare project)
   - Fix region → update `actived_server` and `crypto/cert/upgrade.php` branches

---

## 10. Troubleshooting

| Problem | Fix |
|---------|-----|
| `Connection failed` MySQL | Check `config.php` credentials; MySQL service running |
| `include config.php failed` | File must be **parent of clone**, not inside `frptool/` |
| Blank / 500 error | Enable `display_errors` in PHP; check Apache error log |
| OTP always fails | sgsmpro URL or your `verify_url/otp_eu.php` not implemented |
| cert/upgrade empty | Insert `actived_server` row id=4 with region India/Europe/Other |
| Tool cannot connect | hosts/DNS not pointing to your server; need valid TLS in prod |

---

## 11. Legal note

Service tools may only be used for **legal device repair** with customer proof of ownership. This documentation is for technical setup only.

---

## 12. Links

- GitHub: `https://github.com/mkverma541/frptool`
- Realme OTP marketing site (separate tool): `https://realmeotp.com/`
- SGSM reference ecosystem (some OTP URLs in code): `https://sgsmpro.com/`

---

*Last updated: project initial push — adjust this file when OTP server or deploy path changes.*
