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

/**
 * Bootstrap smoke for the CI4 stack: boots the framework, opens a DB connection
 * and reads one row through a ported model — the fastest end-to-end check.
 */
class Smoke extends BaseCommand {
    protected $group = 'Testing';
    protected $name = 'ea:smoke';
    protected $usage = 'ea:smoke';
    protected $description = 'Runs the DB/booking smoke against the CI4 stack.';

    public function run(array $params)
    {
        $settings = new \App\Models\Settings_Model;
        CLI::write('company_name: ' . $settings->get_setting('company_name'));

        $providers = new \App\Models\Providers_Model;
        $provider_list = $providers->get_available_providers();
        CLI::write('providers: ' . count($provider_list) . ' (first: '
            . ($provider_list[0]['first_name'] ?? '?') . ')');

        $users = new \App\Models\User_Model;
        $matches = $users->check_login('administrator', 'administrator');
        CLI::write('check_login(administrator): ' . (is_array($matches) ? 'matched role '
            . $matches['role_slug'] : 'FAILED'));

        $user = $users->get_user(1);
        CLI::write('user #1 display: ' . $user['first_name'] . ' '
            . $user['last_name']);

        $appointments = new \App\Models\Appointments_Model;
        CLI::write('appointments: ' . count($appointments->get_batch()));
    }
}
