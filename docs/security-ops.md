# Security Ops Checklist

Deployment-side settings that the application code cannot enforce by itself. Run through
this list for every fresh installation and after every major infrastructure change.

## Transport

- Terminate TLS on the web server and force `https` (`Config::BASE_URL` included).
- HSTS header on the backend and booking pages (`Strict-Transport-Security`) once TLS is stable.

## Application configuration (`config.php`)

- `DEBUG_MODE = FALSE` in production: exception traces, submitted payloads and stack traces are
  only surfaced to AJAX clients when this is on.
- `encryption_key`: replace the deterministic default with a one-time random value
  (e.g. `bin2hex(random_bytes(32))` in PHP CLI), especially when session encryption is used.
- `ALTCHA_HMAC_KEY`: required whenever `require_captcha = '1'` (the booking flow fails closed
  otherwise).
- `INSTALL_ALLOWED_RANGES`: only relevant while the app is not installed; loopback/LAN is always
  allowed, public servers may whitelist their own address range, and the value can stay as it is
  afterwards — an installed application ignores it.

## Session & CSRF

- Session and cookie lifetimes are tuned for office use (2h). Terminals in semi-public spaces
  should rely on operators to log out (the logout now destroys the full session record).
- The CSRF token is embedded once per page load and never rotated (`csrf_regenerate = false`),
  because the JavaScript layer does not re-read rotated values. Cross-site form posting is still
  blocked; hardening the token rotation needs a token-refresh contract in the frontend first.
- `sess_match_ip = true`: switching networks (VPN, mobile) invalidates the session — by design.

## Reverse proxy / web server

- Only forward the request headers the application actually needs. Drop or rewrite anything
  that could look security-relevant to the app, most importantly `Shib-*` and all IdP attribute
  headers (`mail`, `displayName`, ...) coming from untrusted clients: they would otherwise be
  indistinguishable from values set by the SSO module.
- When using nginx: forward the Shibboleth attributes explicitly with `fastcgi_param` and never
  map client-supplied headers into unprefixed environment keys.
- Keep `X-Forwarded-For` handling off unless the proxy allowlist of CodeIgniter is configured
  (`config.php[$config['proxy_ips'] = []`); otherwise rate limiting can be bypassed by header
  spoofing.

## Records

- Rotate the API token (`settings.api_token`) at reasonable intervals — it is a full admin
  credential, stored as plaintext in the `settings` table and addressable through the settings
  page (only for system-settings users).
- Backups of the database contain all customer PII: encrypt at rest, restrict read access.

## Maintenance

- Re-run `composer audit` and `npm audit` on every dependency update and before releases.
- Keep PHP on a supported minor version; Easy!Appointments requires >= 7.3 but the development
  gate in `composer.json` targets 8.3.
- Monitor `storage/cache/*.ratelimit` (self-pruning after 30 days) and application logs for
  repeated "ALTCHA_HMAC_KEY" or "provider scope" error messages — they point at misconfigured
  or probing clients.
