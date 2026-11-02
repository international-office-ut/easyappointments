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

namespace App\Controllers;

use CodeIgniter\Router\RouteCollection;

/**
 * Route smoke endpoint used only during the CI4 cutover QA: reports what the
 * router resolved for a GET path passed via a query parameter.
 */
class RouteSmoke extends BaseController {
    public function index()
    {
        $path = $this->request->getGet('for') ?? '/';

        /** @var RouteCollection */
        $routes = service('routes');

        $probe = $routes->getRegisteredControllers('*');
        $probeRoutes = array_keys($routes->getRoutes('GET'));
        $routing = config(\Config\Routing::class);

        $this->response->setContentType('text/plain');
        $this->response->setBody(var_export([
            'path' => $path,
            'defaultNamespace' => $routing->defaultNamespace,
            'autoRoute' => $routing->autoRoute,
            'registered' => array_slice($probeRoutes, 0, 12),
            'controllerExists' => class_exists('\App\Controllers\Appointments'),
        ], true));

        return $this->response;
    }
}
