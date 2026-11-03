<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Ported from the CI3 application/config/routes.php as part of the CI4
// cutover. Session-based controllers resolve through the legacy "auto
// routing" convention (controller/method/parameter) to keep every booking
// page URL byte-identical; only the explicit table below needs ports.
//
// CI4's improved auto-routing refuses the "default controller by name" path,
// so the root route and the appointments entry get explicit definitions.

$routes->get('/', '\App\Controllers\Appointments::index');
$routes->get('appointments', '\App\Controllers\Appointments::index');
$routes->set404Override('\App\Controllers\Errors::error404');


/*
| -------------------------------------------------------------------------
| BACKEND AREA (defined routes with session filters)
| -------------------------------------------------------------------------
| CI4 filters only attach to Defined Routes, so the backend pages and the
| AJAX endpoints get explicit entries. The controller-level privilege checks
| stay as the defense-in-depth layer.
|
*/

$routes->get('backend', '\\App\\Controllers\\Backend::index', ['filter' => 'eaBackendPage']);
$routes->get('backend/index/(:any)', '\\App\\Controllers\\Backend::index/$1', ['filter' => 'eaBackendPage']);

$backendPages = [
    'customers',
    'services',
    'users',
    'settings',
    'update',
];

foreach ($backendPages as $backendPage)
{
    $routes->get('backend/' . $backendPage, '\\App\\Controllers\\Backend::' . $backendPage,
        ['filter' => 'eaBackendPage']);
}

$backendApiMethods = [
    'ajax_get_calendar_events',
    'ajax_get_calendar_appointments',
    'ajax_save_appointment',
    'ajax_delete_appointment',
    'ajax_filter_customers',
    'ajax_save_unavailable',
    'ajax_delete_unavailable',
    'ajax_save_working_plan_exception',
    'ajax_delete_working_plan_exception',
    'ajax_save_customer',
    'ajax_delete_customer',
    'ajax_save_service',
    'ajax_delete_service',
    'ajax_filter_services',
    'ajax_save_service_category',
    'ajax_delete_service_category',
    'ajax_filter_service_categories',
    'ajax_filter_admins',
    'ajax_save_admin',
    'ajax_delete_admin',
    'ajax_filter_providers',
    'ajax_save_provider',
    'ajax_delete_provider',
    'ajax_filter_secretaries',
    'ajax_save_secretary',
    'ajax_delete_secretary',
    'ajax_save_settings',
    'ajax_get_identity_info',
    'ajax_validate_username',
    'ajax_apply_global_working_plan',
    'ajax_maintain',
    'ajax_ical_feed_state',
    'ajax_generate_ical_feed',
    'ajax_revoke_ical_feed',
];

foreach ($backendApiMethods as $backendApiMethod)
{
    $routes->add('backend_api/' . $backendApiMethod, '\\App\\Controllers\\Backend_api::' . $backendApiMethod,
        ['filter' => 'eaBackendApi']);
}

// Provider calendar feed: the token lives in the URL path (never in a query string).
$routes->get('ical/(:any)', '\App\Controllers\Ical::fetch/$1');

// CSP violation reports: browser-generated POST that never carries the CSRF token.
$routes->post('csp-report', '\App\Controllers\Csp_report::index');


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
