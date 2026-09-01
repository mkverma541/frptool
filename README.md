# frptool

PHP API backend for Realme/Oppo service tools (RCSM, TOOLSHUB, O+ sign flows).

**Not a Windows `.exe`** — this repo is the **server** that desktop tools call over HTTP.

## Quick start

1. Read **[INSTRUCTIONS.md](INSTRUCTIONS.md)** (full setup for Windows VPS and Cursor).
2. Copy `config.php.example` → parent folder as `config.php` (see INSTRUCTIONS).
3. Import `schema.sql` into MySQL.
4. Point Apache document root or virtual host at this folder’s parent so `/api/` and `/crypto/` URLs work.

## Folders

| Path | Purpose |
|------|---------|
| `api/tools/` | MsmDownloadTool RCSM login + sign |
| `api/platform/` | TOOLSHUB platform login |
| `api/sign/` | O+ sign + login |
| `api/gsm/` | Static model/region JSON |
| `api/flash/` | Firmware version proxy |
| `crypto/cert/` | O+ certificate upgrade by region |

## Related (not in repo)

- **Realme OTP Tool** (`RealmeOtp.exe`) — separate vendor app; backend is `otterpulse.com`, not this API.
- Tool download: get from your vendor; RAR password was `R` (do not commit binaries).
