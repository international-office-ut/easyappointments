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

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Router\Router;

/**
 * Router QA for the CI4 cutover: resolves a probe path through the real
 * router instance and reports the controller/method it resolves to.
 */
class RouterQa extends BaseCommand {
    protected $group = 'Testing';
    protected $name = 'ea:router-qa';
    protected $usage = 'ea:router-qa [path]';
    protected $description = 'Resolves a probe path through the real CI4 router.';

    public function run(array $params)
    {
        CLI::write('Routing config: ' . json_encode([
            'autoRoute' => config(\Config\Routing::class)->autoRoute,
            'defaultNamespace' => config(\Config\Routing::class)->defaultNamespace,
            'appFeature' => config(\Config\Feature::class)->autoRoutesImproved,
        ]));

        $probe = $params[0] ?? 'backend';

        $routes = service('routes')->loadRoutes();
        /** @var Router */
        $router = service('router', $routes, service('request'));

        try
        {
            $controller = $router->handle($probe);
            CLI::write('resolved: ' . $controller . '::' . $router->methodName()
                . ' (params: ' . json_encode($router->params()) . ')');
        }
        catch (\Throwable $exception)
        {
            CLI::write('FAILED: ' . get_class($exception) . ' — ' . $exception->getMessage());
        }
    }
}
