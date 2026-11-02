<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

// Bootstrap Easy!Appointments PHPUnit Tests
//
// The engine layer carries no framework dependencies, so the PHPUnit boot
// only wires the composer autoloader (the CI4 classes are not exercised here).

require_once __DIR__ . '/../../vendor/autoload.php';
