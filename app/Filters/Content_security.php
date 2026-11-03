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
    }

    protected function policy(bool $is_secure): string
    {
        return
            "default-src 'self'; "
            . "script-src 'self' 'nonce-" . csp_nonce() . "'; "
            . "worker-src 'self' blob: data:; "
            . "style-src 'self' 'unsafe-inline'; "
            . "style-src-attr 'unsafe-inline'; "
            . "img-src 'self' data:; "
            . "font-src 'self'; "
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
