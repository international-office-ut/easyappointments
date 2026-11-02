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

// Language service QA (CI4 cutover): boots the minimal framework set and
// prints what `lang('page_title')` and ea_langs() resolve to.

require __DIR__ . '/../vendor/autoload.php';

define('APPPATH', realpath(__DIR__ . '/../app') . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
define('VENDORPATH', realpath(__DIR__ . '/../vendor') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');

require VENDORPATH . 'codeigniter4/framework/system/Common.php';

define('BASEPATH', VENDORPATH . 'codeigniter4/framework/system' . DIRECTORY_SEPARATOR);
define('COMPOSER_PATH', VENDORPATH . 'autoload.php');

$autoloader = new \CodeIgniter\Autoloader\Autoloader(COMPOSER_PATH);

require APPPATH . 'Config/Modules.php';
require APPPATH . 'Config/Routing.php';
require APPPATH . 'Config/App.php';
require APPPATH . 'Config/Deployment.php';
require APPPATH . 'Config/Services.php';
require APPPATH . 'Libraries/Ea_Language.php';
require APPPATH . 'Helpers/config_helper.php';
require APPPATH . 'Helpers/general_helper.php';

$service = Config\Services::language('english', false);

echo 'locale: ', $service->getLocale(), PHP_EOL;
echo 'locator hits:', PHP_EOL;
print_r(service('locator')->search('Language/' . $service->getLocale() . '/Translations.php'));
echo 'page_title: ', $service->getLine('page_title') ?: '(MISS)', PHP_EOL;
echo 'ea_langs: ', count($service->all()), PHP_EOL;
echo 'backend_section: ', $service->getLine('backend_section') ?: '(MISS)', PHP_EOL;
