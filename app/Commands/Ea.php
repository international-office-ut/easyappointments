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

use App\Controllers\Console as Console_Controller;
use CodeIgniter\CLI\BaseCommand;

/**
 * Easy!Appointments console passthrough for `spark`.
 *
 * The CI3 CLI controller keeps working through `php index.php console <cmd>`;
 * this command gives the same surface to `spark`:
 *
 *   php spark ea:install
 *   php spark ea:migrate [fresh]
 *   php spark ea:seed
 *   php spark ea:backup [path]
 *   php spark ea:maintain [dry]
 *   php spark ea:help
 */
class Ea extends BaseCommand {
    protected $group = 'Easy!Appointments';
    protected $name = 'ea';
    protected $usage = 'ea [install|migrate|seed|backup|maintain|help] [arguments]';
    protected $description = 'Runs the EA console (maintenance, backup, migration and install flows).';

    public function run(array $params)
    {
        // Route the sub-command params into the same expectations the CI3
        // controller derived from $GLOBALS['argv'] (index.php, console, cmd...).
        $command = array_shift($params) ?? 'help';

        $argv_backup = $GLOBALS['argv'];
        $GLOBALS['argv'] = array_merge([$_SERVER['argv'][0] ?? 'spark', 'console', $command], $params);

        // The console controller runs standalone in the spark context: the CI4
        // controller init wiring happens here (request/response/logger trio).
        $console = new Console_Controller;
        $console->initController(service('request'), service('response'), service('logger'));

        switch ($command)
        {
            case 'install':  $console->install(); break;
            case 'migrate':  $console->migrate($params[0] ?? ''); break;
            case 'seed':     $console->seed(); break;
            case 'backup':   $console->backup(); break;
            case 'maintain': $console->maintain(); break;
            case 'help':
            default:         $console->help(); break;
        }

        $GLOBALS['argv'] = $argv_backup;
    }
}
