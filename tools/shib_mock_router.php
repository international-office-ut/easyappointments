<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Development web-server router that mocks the Shibboleth (SSO) attributes.
 *
 * The real flow reads the IdP attributes from the unprefixed $_SERVER values,
 * which cannot be set without running the IdP. This router injects them from
 * environment variables so that the SSO entry points can be smoke-tested
 * locally:
 *
 *   SHIB_MOCK=1 \
 *   SHIB_MOCK_MAIL=max.mustermann@example.de \
 *   SHIB_MOCK_NAME='Max Mustermann' \
 *   php -S localhost:8080 tools/shib_mock_router.php
 *
 * Injection is fail-closed: attributes are only set when the environment
 * variables exist. A request without production headers still looks like an
 * unauthenticated visitor to the application bootstrap.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

// Static assets are served by the built-in server directly.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($path !== '/' && is_file($root . $path))
{
    return FALSE;
}

if (getenv('SHIB_MOCK') === '1')
{
    $mail = (string) getenv('SHIB_MOCK_MAIL');
    $name = (string) getenv('SHIB_MOCK_NAME');

    if ($mail !== '')
    {
        $_SERVER['mail'] = $mail;
    }

    if ($name !== '')
    {
        $_SERVER['displayName'] = $name;
    }
}

require $root . '/index.php';
