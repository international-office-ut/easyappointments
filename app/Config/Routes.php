<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Ported from the CI3 application/config/routes.php as part of the CI4
// cutover. Session-based controllers resolve through the legacy "auto
// routing" convention (controller/method/parameter) to keep every booking
// page URL byte-identical; only the explicit table below needs ports.

$routes->setDefaultController('Appointments');
$routes->set404Override('Errors::error404');

// Provider calendar feed: the token lives in the URL path (never in a query string).
$routes->get('ical/(:any)', 'Ical::fetch/$1');

/*
| -------------------------------------------------------------------------
| REST API ROUTING
| -------------------------------------------------------------------------
| The following routes point the API calls into the correct controller
| callback methods and define the HTTP verbs used for each operation.
|
*/

$resources = [
    'appointments',
    'unavailabilities',
    'customers',
    'services',
    'categories',
    'admins',
    'providers',
    'secretaries',
];

foreach ($resources as $resource)
{
    $routes->post('api/v1/' . $resource, '\App\Controllers\Api\V1\\' . ucfirst($resource) . '::post');
    $routes->put('api/v1/' . $resource . '/(:num)', '\App\Controllers\Api\V1\\' . ucfirst($resource) . '::put/$1');
    $routes->delete('api/v1/' . $resource . '/(:num)', '\App\Controllers\Api\V1\\' . ucfirst($resource) . '::delete/$1');
    $routes->get('api/v1/' . $resource, '\App\Controllers\Api\V1\\' . ucfirst($resource) . '::get');
    $routes->get('api/v1/' . $resource . '/(:num)', '\App\Controllers\Api\V1\\' . ucfirst($resource) . '::get/$1');
}

$routes->get('api/v1/settings', '\App\Controllers\Api\V1\Settings::get');
$routes->get('api/v1/settings/(:any)', '\App\Controllers\Api\V1\Settings::get/$1');
$routes->put('api/v1/settings/(:any)', '\App\Controllers\Api\V1\Settings::put/$1');
$routes->delete('api/v1/settings/(:any)', '\App\Controllers\Api\V1\Settings::delete/$1');

$routes->get('api/v1/availabilities', '\App\Controllers\Api\V1\Availabilities::get');
