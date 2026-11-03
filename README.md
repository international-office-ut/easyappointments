# Easy!Appointments

Easy!Appointments is an open source web scheduler that helps you setup your
business with a clear and stylish approach, using the power of PHP and
AJAX. It provides you with appointment scheduling, customer management and
calendar syncing via iCal feeds.

This fork runs on **CodeIgniter 4** (the CI3 stack was removed at the
cutover): `public/index.php` front controller, `app/` application layer,
`spark` CLI and the `engine/` business-logic seam that carries no framework
coupling.

`docs/ci4-config-mapping.md` keeps the CI3→CI4 configuration mapping as a
reference for upgrade paths; `docs/security-ops.md` carries the ops
checklist.

## Requirements

- PHP >= 8.3 (with `mbstring`, `intl`, `gd`, `mysqli` / `pdo_mysql`).
- MySQL 5.7 / newer or MariaDB compatible.
- Apache (mod_rewrite, mod_headers) or Nginx.

## Quick start (CI4)

1. Copy this repo to your web root, write-protect nothing that the
   `writable/` and `storage/` directories need, and make both writable.
2. Copy `.env.example` to `.env` and set:
   - `database.default.hostname`, `database.default.database`,
     `database.default.username`, `database.default.password`
   - `database.default.DBPrefix` = `'ea_'` (the CI3 overhead prefix, parity
     with the legacy deployment)
   - `app.baseURL`
   The CI3-style secrets (`ALTCHA_HMAC_KEY`, `SMTP_*`, `SHIB_*`) keep their
   legacy names — see the `.env.example` EA block.
3. Set your web root to `public/` (Apache `DocumentRoot` / Nginx `root`).
4. Fresh install: open the browser at the base URL and follow the installer;
   the CI4 migrations run automatically.
5. Existing installation (transitioned from CI3): the installer also handles
   this case — the migration runner baselines the CI3 schema chain instead of
   replaying it.

## CLI

Both CLI surfaces run the same console actions:

```sh
php spark ea migrate [fresh]
php spark ea:seed
php spark ea install
php spark ea backup [path]
php spark ea maintain [dry]
```

The `ea` command also supports the dotted forms (`ea:migrate` and friends:
same subcommands, one handler).

## Local development

```sh
composer install
npm install
npm start          # JS/CSS watch cycle, builds into public/assets/ directly
```

The `package.json` dependencies are only the framework for the frontend-toolchain and the version pins
for the manually-vendored bundles in `public/assets/ext/` — the production runtime never uses npm,
it runs exclusively on the composer stack and those committed assets.

For the SSO (Shibboleth) smoke without an IdP, use the mock-header router:

```sh
SHIB_MOCK=1 SHIB_MOCK_MAIL=you@example.de SHIB_MOCK_NAME='You' \
    php -S localhost:8080 tools/shib_mock_router.php
```

## Tests

```sh
composer test    # PHPUnit over the engine/ business layer
```

## Production notes

- PHP 8.3 is the floor; deploy with `CI_ENVIRONMENT=production`.
- Security response headers deploy at the server layer (`.htaccess` ships the
  rules) so that they apply to errors and CORS-free responses too.
- `docs/security-ops.md` carries the Shibboleth + hardening checklist.
