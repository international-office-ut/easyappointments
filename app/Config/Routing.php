<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Config\Routing as BaseRouting;

/**
 * Routing configuration
 */
class Routing extends BaseRouting
{
    /**
     * For Defined Routes.
     * An array of files that contain route definitions.
     * Route files are read in order, with the first match
     * found taking precedence.
     *
     * Default: APPPATH . 'Config/Routes.php'
     *
     * @var list<string>
     */
    public array $routeFiles = [
        APPPATH . 'Config/Routes.php',
    ];

    /**
     * For Defined Routes and Auto Routing.
     * The default namespace to use for Controllers when no other
     * namespace has been specified.
     *
     * Default: 'App\Controllers'
     */
    public string $defaultNamespace = 'App\Controllers';

    /**
     * For Auto Routing.
     * The default controller to use when no other controller has been
     * specified.
     *
     * Default: 'Home'
     */
    public string $defaultController = 'Home';

    /**
     * For Defined Routes and Auto Routing.
     * The default method to call on the controller when no other
     * method has been set in the route.
     *
     * Default: 'index'
     */
    public string $defaultMethod = 'index';

    /**
     * For Auto Routing.
     * Whether to translate dashes in URIs for controller/method to underscores.
     * Primarily useful when using the auto-routing.
     *
     * Default: false
     */
    public bool $translateURIDashes = false;

    /**
     * Keeps the CI3 URL parity: every controller method stays reachable the
     * legacy way (appointments/index, user/login, backend/calendar ...).
     */
    public bool $autoRoute = true;

    /**
     * The improved auto-router refuses the CI3-style legacy paths (default
     * controller by name + "defined-route controller names" protection), so
     * the legacy AutoRouter walks the controller namespace the CI3 way.
     */
    public bool $translateUriToCamelCase = false;
}
