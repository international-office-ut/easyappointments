# Shibboleth SSO

> This page explains how to enable single sign-on with a Shibboleth Identity Provider (for example, the identity federation of a university) so that staff members can log into the backend without being assigned shared passwords.

### Introduction

Easy!Appointments integrates with the **native Shibboleth Service Provider (SP)** instead of using a PHP SAML library: the SP daemon (`shibd`) performs all of the SAML work at the web-server level, and the application simply reads the released attributes (mail address, display name) inside the login endpoint.

Because of that, no additional PHP or Composer dependency is required — the flow only works when the SP runs beside the application. A local username/password login remains available at any time.

### Prerequisites

- The native Shibboleth Service Provider must be installed on the web server. On Debian/Ubuntu this is a single package that brings the `shibd` daemon and the Apache module:

  ```
  sudo apt-get install libapache2-mod-shib
  sudo a2enmod shib2
  sudo systemctl enable --now shibd
  ```

- The `Config` class of `config.php` must contain the SSO constants (present in `config-sample.php`):

  ```
  const SHIB_ENABLED     = TRUE;
  const SHIB_ATTR_EMAIL  = 'mail';          // or 'eppn' (released by the IdP)
  const SHIB_ATTR_NAME   = 'displayName';   // optional, '' to skip
  const SHIB_LOGIN_PATH  = '/Shibboleth.sso/Login';
  const SHIB_LOGOUT_PATH = '/Shibboleth.sso/Logout';
  ```
  `SHIB_ATTR_EMAIL` picks the IdP attribute that identifies the person. Only use `eppn` when the IdP does not release `mail`; both values must match the email column of the Easy!Appointments user records.

### Service Provider Configuration (`/etc/shibboleth/shibboleth2.xml`)

- Register the virtual host as the handler host and define the SP entity ID (a URI that belongs to you, e.g. `https://appointments.example.org/entityid`):

  ```xml
  <ApplicationDefaults entityID="https://appointments.example.org/entityid"
                       REMOTE_USER="eppn uid persistent-id targeted-id">
      <Sessions lifetime="28800" timeout="3600" checkAddress="false"
                handler="https://appointments.example.org/Shibboleth.sso" handlerSSL="true">

          <SSO entityID="https://idp.example.org/idp/shibboleth">
              SAML2
          </SSO>

          <MetadataProvider type="XML" path="/etc/shibboleth/metadata/idp-metadata.xml" />

          <!-- Required for the logout chain, so that Easy!Appointments logouts
               also terminate the IdP session. -->
          <Logout>SAML2</Logout>

      </Sessions>

      <Host name="appointments.example.org"/>
  </ApplicationDefaults>
  ```

- Sponsor a server key pair for production with `/etc/shibboleth/keygen.sh` and store the IdP metadata under `/etc/shibboleth/metadata/`.

### Apache Virtual Host

The SP handlers must be reachable and the login-return endpoint must establish an SP session before the application reads the attributes:

```apache
<Location /Shibboleth.sso>
    SetHandler shib
</Location>

<Location /index.php/user/shib_return>
    AuthType shibboleth
    ShibRequestSetting requireSession true
    Require shib-session
</Location>
```

The login button of Easy!Appointments sends the browser to `<SHIB_LOGIN_PATH>?target=<return URL>`, where the SP establishes the session and sends the user back with the attributes in place.

### Attribute Release

The identity provider administrators must release the following attributes to the SP entity ID:

- `mail` (or `eppn` as fallback) — login identifier
- `displayName` — optional, not required by the login

`/etc/shibboleth/attribute-map.xml` must map them to the same names (the entries are commented out by default in the upstream template):

```xml
<Attribute name="urn:oid:0.9.2342.19200300.100.1.3" id="mail"/>
<Attribute name="urn:oid:1.3.6.1.4.1.5923.1.1.1.6" id="eppn"/>
<Attribute name="urn:oid:2.16.840.1.113730.3.1.241" id="displayName"/>
```

When PHP runs through FPM double-check that the attributes really reach the application: log in once and inspect a `phpinfo()` page for the `Shib-Application-ID` and `Shib-*` variables. With `ProxyPassMatch`/`ProxyFCGI` transports, environment variables sometimes stop at Apache. Information about the session (all released attributes of the current session) is also available at `https://appointments.example.org/Shibboleth.sso/Session`.

### Application Behavior

- The login page shows a "Login with Shibboleth" button that starts the SP flow (`user/shib`).
- The return endpoint (`user/shib_return`) matches the released identifier against the email addresses of admin, provider and secretary rows. Customer records are never login candidates: customers book through the public booking page and confirm appointments with the hash link from their notification mail.
- A staff row matching the email signs in directly. When the same person has several role rows (shared identity), the role picker appears and the row can be chosen without entering credentials again.
- **Unmatched identities are rejected** with a notice on the login page: unlike your organization, Easy!Appointments does not expect accounts to be created on first login. Create the user rows with the regular email in the backend, and the SSO login will find them right afterwards.
- The logout of an SSO session redirects through `<SHIB_LOGOUT_PATH>` so that the IdP **Single Logout** (SLO) also terminates the IdP session — closing the browser alone never ends an IdP session.
- The local username/password stay functional; both login paths regenerate the PHP session ID after a successful login.

### Password Login

`SHIB_PASSWORD_LOGIN` (default `TRUE`) decides whether the username/password login remains available:

- `TRUE` — both methods are offered, Shibboleth is simply the convenient one.
- `FALSE` — password-based logins are rejected on the endpoint, the login page shows the Shibboleth button only, and the "Forgot your password?" flow is disabled as well (a newly generated password would never be accepted by the disabled login).

The switches are fail-open on purpose: with `SHIB_ENABLED = FALSE` the password login stays enabled even when `SHIB_PASSWORD_LOGIN = FALSE`, so that a pair of wrong constants never locks every administrator out of a running installation. Invalid combinations are logged to the application log.

### Testing Checklist

- `systemctl status shibd` and `https://appointments.example.org/Shibboleth.sso/Status` report a healthy SP.
- The login page shows the Shibboleth button; click it and confirm that the IdP login and the return to the backend work.
- A user with provider + secretary rows sees the role picker and can choose a role from the identity provider.
- Login with an unknown email address shows the rejection notice on the login page.
- The "Log out" button destroys the SP session and redirects through the IdP SLO; a new login attempt prompts the IdP for the credentials again.
- When `SHIB_PASSWORD_LOGIN` is `FALSE`, a direct POST to `user/ajax_check_login` is rejected and the login page shows no credentials form anymore.

*This document applies to Easy!Appointments v1.4.2.*

[Back](readme.md)
