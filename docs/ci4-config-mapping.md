# CI3 → CI4 configuration mapping (Phase 0 deliverable)

Authoritative inventory of every CI3 configuration key and its CI4 target.
Port each key exactly once during Phase 1; mark the checkbox in the table
column when landed.

Not yet ported: CI3 `require_once config.php` root file (the untracked
`Config` class) — every key of it carries over to `.env` + a custom
`app/Config/Deployment.php` (below).

## application/config/config.php

| CI3 key | Value/type now | CI4 target | Notes |
|---|---|---|---|
| `version` / `release_label` | "2.0.0" | `app/Config/Deployment.php` (custom) | EA-specific, keep semantics |
| `debug` = `Config::DEBUG_MODE` | bool | `.env` + `app.forceGlobalSecureRequests` n/a | CI4 debug = `CI_ENVIRONMENT=development`; reads through the logger/exception display |
| `base_url` = `Config::BASE_URL` | string | `.env` `app.baseURL` | trailing slash required |
| `index_page` = "index.php" | string | `app.indexPage` | keeps `/index.php/...` URLs working (no JS changes) |
| `uri_protocol` = AUTO | string | `app.uriProtocol` (default `REQUEST_URI`) | CI4 has the same attribute |
| `url_suffix` | "" | none | CI4 dropped URL suffixes |
| `language` / `available_languages` | arrays, HTTP-detected | `app.defaultLocale` + custom `app/Config/Deployment.php` array | EA language bootstrap logic stays custom (BaseController logic); `language_helper` loading differs in CI4 |
| `charset` = UTF-8 | string | implicit/UTF-8 | CI4 uses UTF-8 everywhere |
| `enable_hooks` + `hooks.php` | bool | `app/Config/Events.php` | only if hooks actually used |
| `subclass_prefix` = EA_ | "EA_" | none | CI4 uses namespaces instead of prefixes |
| `permitted_uri_chars` | a-z0-9 regex-ish | `app.allowedSiteURLChars`? | CI4 `App->allowedSiteURLChars` may not exist; CI4 validates per-route — port at Phase 1 |
| `allow_get_array` / `enable_query_strings` / `controller_trigger` / `function_trigger` / `directory_trigger` | bool | implicit CI4 behavior | $_GET always available; Segment-based URLs unchanged |
| `log_threshold` = 1 | int | `Logging.log.level` (CI4 `.env`) | 1 = `error`, 2 = `debug`, 3 = `info` — verify mapping per logger config |
| `log_path` = storage/logs/ | string | `app/Config/Logger.php` or `.env` `Logging.log.path` | absolute path got from `${CI_ROOT}`? CI4 default writable/logs — keep storage/logs |
| `log_date_format` | "Y-m-d H:i:s" | `Config/Logger` dateFormat | same |
| `cache_path` = storage/cache/ | string | `app/Config/Cache.php` file handler path | same directory |
| `cache_busting_token` | "ZW007" | `app/Config/Deployment.php` | consumed by `asset_helper` (custom) |
| `encryption_key` | base64(APPPATH) | none | CI4 handles keyless session; keep CI3 key only if cryptographic area used (it isn't) |
| `sess_driver` = files | string | `.env` Session.handler = "files" | |
| `sess_cookie_name` | ea_session | `.env` Session. cookieName? — CI4 attr `sessionCookieName` (4.5+) | |
| `sess_expiration` = 7200 | int | `.env` Session.expiration | |
| `sess_save_path` | storage/sessions | `.env` Session.savePath (must be writable, out of webroot) | |
| `sess_match_ip` = true | bool | `.env` Session.matchIP | SSO reality-check: VPN/proxy clients share IPs — keep true for hardening; verify Shib clients |
| `sess_time_to_update` | 300 | none | CI4 has no auto-regen timer; call `session()->regenerate()` post-login anywhere the flow rotates sessions (login requests) — verify in `User` port |
| `sess_regenerate_destroy` | false | `.env` Session.regenerateDestroy=false | same semantics |
| `cookie_prefix` / `cookie_domain` / `cookie_path` | "" / "" / "/" | `.env` cookie.* (Config\Cookie) | |
| `cookie_secure` = true | bool | `.env` cookie.secure = true | HTTPS-only deployments (incl. local via mkcert) |
| `cookie_httponly` = true | bool | `.env` cookie.httponly = true | |
| `global_xss_filtering` = true | bool | none (CI4 dropped global filtering) | ⚠ Port discipline: CI3 sanitized `$this->input->post(...)`; CI4 requires explicit `esc()` on output or `filter_var` on input — audit booking + contact form paths explicitly during Phase 3 |
| `csrf_protection` = true | bool | CI4 CSRF filter enabled in `app/Config/Filters` + `.env` `Security.tokenName` etc | |
| `csrf_token_name` = csrfToken | string | `.env` Security.tokenName | keep the SAME token name so the JS `GlobalVariables.csrfToken` contract stays intact |
| `csrf_cookie_name` = csrfCookie | string | `.env` Security.cookieName? (CI4 CSRF cookie attr) | |
| `csrf_expire` = 7200 | int | `.env` Security.expires | |
| `csrf_regenerate` = false | bool | `.env` Security.regenerate = false | Important comment: frontend re-reads fresh CSS token at page load only; keep off until the JS learns to re-read rotated values |
| `csrf_exclude_uris` = api/v1/.* | array | `app/Config/Filters::csrf excluded`— filter: csrf except `api/v1/*` | app with Bearer/Basic auth has no cookies |
| `compress_output` = false | bool | none | gzip at server layer (`.htaccess`/vhost) instead |
| `time_reference` = local | string | none | CI4 `$locale`/datetime work with explicit timezone |
| `rewrite_short_tags` | false | none | CI4 uses full PHP tags natively |
| `proxy_ips` = "" | string | `.env` app.proxyIPs | only if behind reverse proxy at the deployment |

## application/config/database.php

| CI3 key | CI4 target | Notes |
|---|---|---|
| `hostname` = `Config::DB_HOST` | `.env` `database.default.hostname` | |
| `username` / `password` | `.env` `database.default.username` / `.env` `database.default.password` | |
| `database` = `Config::DB_NAME` | `.env` `database.default.database` | |
| `dbdriver` = mysqli | `.env` `database.default.driver` = `mysqli`(or `MySQLi` class ref) | |
| `char_set` / `dbcollat` | `.env` `database.default.charSet` / `DBCollat` | match current values exactly |
| `db_debug` | `.env` `database.default.DBTDebug` n/a | CI4 shows query errors in development environment only |
| `dbprefix` | `.env` `database.default.DBPrefix` | empty in CI3 |

## application/config/email.php

| CI3 key | CI4 target |
|---|---|
| `protocol` = `Config::EMAIL_PROTOCOL` | `.env` `email.protocol` |
| `smtp_host` = `Config::SMTP_HOST` | `.env` `email.SMTPHost` |
| `smtp_user` / `smtp_pass` | `.env` `email.SMTPUser` / `.env` `email.SMTPPass` |
| `smtp_port` = `Config::SMTP_PORT` | `.env` `email.SMTPPort` |
| `smtp_crypto` = `Config::SMTP_CRYPTO` | `.env` `email.SMTPCrypto` |
| `smtp_auth` = `Config::SMTP_AUTH` | `.env` `email.SMTPAuth` |
| `useragent` = Easy!Appointments | `.env` `email.userAgent` |
| `mailtype` = html | `.env` `email.mailType` |

## application/config/routes.php

| CI3 route | CI4 `app/Config/Routes.php` |
|---|---|
| `default_controller` = appointments | `$routes->set404Override('Errors::error404'); $routes->setDefaultNamespace(...); $routes->get('/', 'Appointments::index')` (check exact index URL segments) |
| `404_override` = errors/error404 | `$routes->set404Override('Errors::error404')` |
| `ical/(:any)` = ical/fetch/$1 | `$routes->get('ical/(:any)', 'Ical::fetch/$1')` |
| `api/v1/...` resource loop | same pattern generated in a loop inside Routes.php (portable as-is) |
| Api-specific settings/availabilities routes | port literally |

## application/config/migration.php

| CI3 key | CI4 target | Notes |
|---|---|---|
| `migration_enabled` = TRUE | `.env` `database.migrateOnBoot = false` (CI4 runs migrations via CLI or migration-triggered) | keep current install flow — see below |
| `migration_version` = 31 | none | CI4 tracks in the migrations table |
| `migration_path` = application/migrations | `app/Database/Migrations` | same file naming semantics |

⚠️ Installation note: CI3 `migration.php` `migration_version` = 31 was checked with
`$this->migration->current()` in a fresh install path; Phase 5 must port the
installer migration trigger (is-app-installed check + `migration->current()`
equivalent + `existing DB` path) and verify from-scratch and upgrade paths.

## config-sample.php (`Config` class) → `.env` + `app/Config/Deployment.php`

| Config class key | CI4 home | Notes |
|---|---|---|
| `BASE_URL` | `.env app.baseURL` | replaces `base_url` |
| `DEBUG_MODE` | `.env CI_ENVIRONMENT=development|production` | |
| `ALTCHA_HMAC_KEY` | `.env` `booking.altchaHmacKey` (custom Deployment config read) | fail-closed when empty, checked in `Appointments::ajax_register_appointment` + captcha endpoint |
| `EMAIL_*` | see email.php table | |
| `SHIB_ENABLED`, `SHIB_ATTR_EMAIL`, `SHIB_ATTR_NAME`, `SHIB_PASSWORD_LOGIN`, `SHIB_LOGIN_PATH`, `SHIB_LOGOUT_PATH` | `app/Config/Deployment.php` reading the env (`shib.enabled`, `shib.attr.email` …) | keep `$_SERVER` attribute approach — Shib router script (`tools/shib_mock_router.php`) supplies them in smoke |
| `ICAL_FEED_RANGE_PAST/FUTURE`, `ICAL_FEED_INCLUDE_CUSTOMER` | `app/Config/Deployment.php` | |
| `INSTALL_ALLOWED_RANGES` | `app/Config/Deployment.php` | plus the loopback/private LAN default from `installation_helper` |
| `DB_HOST`, `DB_NAME`, `DB_USERNAME`, `DB_PASSWORD` | `.env database.default.*` | |

## Non-portability dangers (Phase 3 must handle)

1. `global_xss_filtering = true`: CI3 sanitized input at the accessor level.
   CI4 has no direct equivalent. Output-escape audit needed at every `echo` in
   the booking flow, backend calendar JS injections into templates and API v1
   responses (`Response::output()` is a raw `json_encode($this->response)` —
   `array→json` raw is safe only because `set_content_type` + `esc()`-free path
   is pure; still: audit once).
2. `MessageLanguage`/language helpers: CI4 uses services not
   `$this->load->language`; keep `set_message`/`sanitize`-like helpers with
   fallbacks until the pickers map.
3. Upstream-deprecated `Response` class: it references
   `ParsersInterface::encode(array &$response)` — after Phase 3 keep the
   non-response-binding PHP signatures (parameter-by-reference has no CI
   conflicts; just don't let any framework layer capture its references).
4. `Appointments.php` `altcha` field parsing via `$this->input->post('altcha')`
   — `$this->request->getPost('altcha')` in CI4 returns `null` for missing
   values; old `FALSE` vs `NULL` semantics with ` '' === $post_value ` — use
   `(string)` cast, already applied.
