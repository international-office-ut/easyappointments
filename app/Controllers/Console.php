<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.3.2
 * ---------------------------------------------------------------------------- */

namespace App\Controllers;

use Exception;
use App\Controllers\Concerns\Baseline_Migration;
use App\Models\Admins_Model;
use App\Models\Customers_Model;
use App\Models\Maintenance_Model;
use App\Models\Providers_Model;
use App\Models\Services_Model;
use App\Models\Settings_Model;
use CodeIgniter\Database\BaseUtils;
use CodeIgniter\Database\MigrationRunner;

/**
 * Console Controller (CI4 port of the CI3 application/controllers/Console.php)
 *
 * CLI commands of Easy!Appointments, can only be executed from a terminal and not with a direct request.
 */
class Console extends BaseController {
    use Baseline_Migration;

    protected Admins_Model $admins_model;
    protected Customers_Model $customers_model;
    protected Providers_Model $providers_model;
    protected Services_Model $services_model;
    protected Settings_Model $settings_model;
    protected Maintenance_Model $maintenance_model;
    protected $db;

    public function __construct()
    {
        if ( ! is_cli())
        {
            exit('No direct script access allowed');
        }
    }

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->admins_model = new Admins_Model;
        $this->customers_model = new Customers_Model;
        $this->providers_model = new Providers_Model;
        $this->services_model = new Services_Model;
        $this->settings_model = new Settings_Model;
        $this->maintenance_model = new Maintenance_Model;
        $this->db = db_connect();
    }

    /**
     * Perform a console installation.
     *
     * Use this method to install Easy!Appointments directly from the terminal.
     *
     * Usage:
     *
     * php index.php console install
     */
    public function install()
    {
        $this->migrate('fresh');
        $this->seed();

        echo PHP_EOL . '⇾ Installation completed, login with "administrator" / "administrator".' . PHP_EOL . PHP_EOL;
    }

    /**
     * Migrate the database to the latest state.
     *
     * Use this method to upgrade an existing installation to the latest database state.
     *
     * Notice:
     *
     * Do not use this method to install the app as it will not seed the database with the initial entries (admin,
     * provider, service, settings etc). Use the UI installation page for this.
     *
     * Usage:
     *
     * php index.php console migrate
     *
     * php index.php console migrate fresh
     *
     * @param string $type
     */
    public function migrate($type = '')
    {
        $db = db_connect();

        $migrations = new MigrationRunner(config(\Config\Migrations::class));

        $message = '';

        try
        {
            // Transitioned from CI3: the schema already exists and no CI4 history
            // was written yet, so the migration chain gets baselined instead of
            // replaying the DDL (a fresh empty database runs or finishes the chain
            // below).
            if ($db->tableExists('appointments'))
            {
                // CI3-shaped tracker first: the CI4 runner cannot query it and the
                // archive step keeps it as a backup table.
                if ($this->legacy_history_present($db))
                {
                    $this->archive_legacy_history($db);

                    $migrations = new MigrationRunner(config(\Config\Migrations::class));

                    $this->baseline_stamp($migrations);

                    echo PHP_EOL
                        . '⇾ CI3 migration tracker detected: archived as "'
                        . $db->prefixTable('migrations_c3_backup') . '" and the CI3 chain '
                        . 'marked as applied (no DDL replayed).' . PHP_EOL . PHP_EOL;

                    return;
                }

                if (count($migrations->getHistory()) === 0)
                {
                    $this->baseline_stamp($migrations);

                    echo PHP_EOL
                        . '⇾ Existing schema detected: the CI3 migration chain was marked '
                        . 'as applied (no DDL replayed).' . PHP_EOL . PHP_EOL;

                    return;
                }
            }

            if ($type === 'fresh')
            {
                $migrations->version(0);
            }

            $migrations->latest();
        }
        catch (Throwable $exception)
        {
            // Database failures must reach the operator driving the command, not only the log.
            $message = 'Migration failed: ' . $exception->getMessage();
        }

        if ($message !== '')
        {
            echo PHP_EOL . '⇾ ' . $message . PHP_EOL . PHP_EOL;

            return;
        }

        // CI4 migration runner is silent on success, so the resulting state is echoed for
        // the console user.
        echo PHP_EOL
            . '⇾ Migrations are up to date. (CI4 migrations table tracks per-file versions)' . PHP_EOL
            . PHP_EOL;
    }

    /**
     * Seed the database with test data.
     *
     * Use this method to add test data to your database
     *
     * Usage:
     *
     * php index.php console seed
     */
    public function seed()
    {
        // Settings
        $this->settings_model->set_setting('company_name', 'Company Name');
        $this->settings_model->set_setting('company_email', 'info@example.org');
        $this->settings_model->set_setting('company_link', 'https://example.org');

        // Admin
        $this->admins_model->add([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.org',
            'phone_number' => '+1 (000) 000-0000',
            'settings' => [
                'username' => 'administrator',
                'password' => 'administrator',
                'notifications' => TRUE,
                'calendar_view' => CALENDAR_VIEW_DEFAULT
            ],
        ]);

        // Service
        $service_id = $this->services_model->add([
            'name' => 'Service',
            'duration' => '30',
            'price' => '0',
            'currency' => '',
            'availabilities_type' => 'flexible',
            'attendants_number' => '1'
        ]);

        // Provider
        $this->providers_model->add([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.org',
            'phone_number' => '+1 (000) 000-0000',
            'services' => [
                $service_id
            ],
            'settings' => [
                'username' => 'janedoe',
                'password' => 'janedoe',
                'working_plan' => $this->settings_model->get_setting('company_working_plan'),
                'notifications' => TRUE,
                'calendar_view' => CALENDAR_VIEW_DEFAULT
            ],
        ]);

        // Customer
        $this->customers_model->add([
            'first_name' => 'James',
            'last_name' => 'Doe',
            'email' => 'james@example.org',
            'phone_number' => '+1 (000) 000-0000',
        ]);
    }

    /**
     * Create a backup file.
     *
     * Use this method to backup your Easy!Appointments data.
     *
     * Usage:
     *
     * php index.php console backup
     *
     * php index.php console backup /path/to/backup/folder
     *
     * @throws Exception
     */
    public function backup()
    {
        $path = isset($GLOBALS['argv'][3]) ? $GLOBALS['argv'][3] : ROOTPATH . 'storage/backups';

        if ( ! file_exists($path))
        {
            throw new Exception('The backup path does not exist: ' . $path);
        }

        if ( ! is_writable($path))
        {
            throw new Exception('The backup path is not writable: ' . $path);
        }

        /** @var BaseUtils */
        $utils = db_connect();

        $contents = $utils->backup();

        $filename = 'easyappointments-backup-' . date('Y-m-d-His') . '.gz';

        file_put_contents(rtrim($path, '/') . '/' . $filename, gzencode($contents));
    }

    /**
     * Trigger the maintenance cleanup based on the record retention period.
     *
     * Appointments, customers without appointments and provider blocks (unavailable periods)
     * that are older than the retention period get removed to keep the record set within the
     * data retention policy. Use the optional "dry" argument to preview without deleting.
     *
     * Usage:
     *
     * php index.php console maintain
     * php index.php console maintain dry
     */
    public function maintain()
    {
        $dry_run = isset($GLOBALS['argv'][3]) && in_array($GLOBALS['argv'][3], ['dry', '--dry-run']);

        $days = $this->retention_days();

        $appointments = $this->maintenance_model->purge_appointments($days, $dry_run);
        $provider_blocks = $this->maintenance_model->purge_provider_blocks($days, $dry_run);
        $customers = $this->maintenance_model->purge_customers_without_appointments($dry_run);

        $mode = $dry_run ? 'DRY RUN' : 'CLEANUP';

        echo PHP_EOL
            . '⇾ Maintenance ' . $mode . ' (retention period: ' . $days . ' days)' . PHP_EOL
            . '  Appointments: ' . $appointments . PHP_EOL
            . '  Provider blocks: ' . $provider_blocks . PHP_EOL
            . '  Customers: ' . $customers . PHP_EOL . PHP_EOL;
    }

    /**
     * Resolve the data retention period in days.
     *
     * Older installations stored the period in months; the value gets converted once on
     * the fly in case the days setting does not exist yet.
     *
     * @return int Returns the retention period in days.
     */
    protected function retention_days(): int
    {
        $days = (int)$this->settings_model->get_setting('record_retention_days');

        if (empty($days))
        {
            $days = (int)$this->settings_model->get_setting('record_retention_months');

            $days = (empty($days)) ? 90 : $days * 30;
        }

        return max(0, $days);
    }

    /**
     * Show help information about the console capabilities.
     *
     * Use this method to see the available commands.
     *
     * Usage:
     *
     * php index.php console help
     */
    public function help()
    {
        $help = [
            '',
            'Easy!Appointments ' . ea_config('version'),
            '',
            'Usage:',
            '',
            '⇾ php index.php console [command] [arguments]',
            '',
            'Commands:',
            '',
            '⇾ php index.php console migrate',
            '⇾ php index.php console migrate fresh',
            '⇾ php index.php console seed',
            '⇾ php index.php console install',
            '⇾ php index.php console backup',
            '⇾ php index.php console maintain',
            '',
            '',
        ];

        echo implode(PHP_EOL, $help);
    }
}
