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
 * Language QA inside the real spark boot: reports locator hits and the
 * all() dictionary size for the CI3-key bridge.
 */
class LanguageQa extends BaseCommand {
    protected $group = 'Testing';
    protected $name = 'ea:lang-qa';
    protected $usage = 'ea:lang-qa';
    protected $description = 'Checks the CI3-key language bridge (EA cutover QA).';

    public function run(array $params)
    {
        $service = service('language')->setLocale('english');

        CLI::write('locale: ' . $service->getLocale());

        $hits = service('locator')->search('Language/english/Translations.php');
        CLI::write('locator hits: ' . count($hits));

        CLI::write('page_title: ' . $service->getLine('page_title'));
        CLI::write('all: ' . count($service->all()));
        CLI::write('backend_section: ' . $service->getLine('backend_section'));
    }
}
