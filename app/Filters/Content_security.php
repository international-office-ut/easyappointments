<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Content Security Policy filter — the single runtime source of the CSP header.
 *
 * The policy needs to change with every request because every inline <script> element is
 * nonced (csp_nonce() helper), so the header could not live in the static .htaccess anymore.
 * All other security headers remain in the .htaccess (see b71f46b9).
 */
class Content_security implements FilterInterface {
    public function before(RequestInterface $request, $arguments = NULL)
    {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = NULL)
    {
        // CLIRequest objects (php spark routes, php public/index.php) have no
        // HTTP transport, so the secure scheme check applies to web requests only.
        $is_secure = $request instanceof \CodeIgniter\HTTP\IncomingRequest
            && $request->isSecure();

        $response->setHeader('Content-Security-Policy', static::policy($is_secure));

        // Dynamic HTML must never be served from a heuristic browser cache: stale
        // documents lose their inline elements against a hardened policy and
        // render broken. Static assets keep their long caching (the cache busting
        // token covers them), only the PHP-generated responses get no-store.
        $response->setHeader('Cache-Control', 'no-store, must-revalidate');
    }

    protected function policy(bool $is_secure): string
    {
        return
            "default-src 'self'; "
            . "script-src 'self' 'nonce-" . csp_nonce() . "'; "
            . "worker-src 'self' blob: data:; "
            // FullCalendar v6 injects its compiled styles through runtime style
            // elements (via a csp-nonce-aware injector, see the backend calendar
            // view). Engines disagree between page parsing and injection timing,
            // so the version-stable content hashes of the bundled style blocks
            // are allowed as a fallback. On a library update check the
            // csp-report log and renew the hashes (or move to v7 css files).
            . "style-src 'self' 'nonce-" . csp_nonce() . "' 'sha256-GsQC5AaXpdCaKTyWbxBzn7nitfp0Otwn7I/zu0rUKOs=' 'sha256-47DEQpj8HBSa+/TImW+5JCeuQeRkm5NMpJWZG3hSuFU='; "
            . "img-src 'self' data:; "
            . "font-src 'self' data:; "
            . "connect-src 'self'; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'; "
            . "object-src 'none'; "
            . 'report-uri ' . rtrim(site_url('csp-report'), '/')
            // Only enforce upgrades on TLS connections: on the plain-HTTP development
            // stacks (spark serve, docker compose) the directive would upgrade every
            // same-origin subresource to a HTTPS address that nothing provides.
            . ($is_secure ? '; upgrade-insecure-requests' : '');
    }
}
