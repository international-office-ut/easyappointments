<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v2.1.0
 * ---------------------------------------------------------------------------- */

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\MigrationRunner;

/**
 * One-time migration cutover command for CI3-era databases. The automatic
 * legacy handling inside the installer and the regular migration command
 * was intentionally removed (2.1.0): CI3 databases must run this command
 * explicitly before any of the CI4-native tools.
 */
class Migrate_legacy extends BaseCommand {
    protected $group = 'Database';
    protected $name = 'ea:migrate-legacy';
    protected $usage = 'ea:migrate-legacy';
    protected $description = 'Cutover a CI3 database: archives the legacy migration tracker and baseline-stamps the CI4 chain.';

    public function run(array $params)
    {
        $db = db_connect();

        if ( ! $this->legacy_history_present($db))
        {
            if (count((new MigrationRunner(config(\Config\Migrations::class)))->getHistory()) > 0)
            {
                CLI::write('Nothing to do: this database already carries a CI4 migration history.',
                    'yellow');

                return;
            }

            CLI::error('No CI3-shaped migration tracker was found. Run "ea:migrate" for '
                . 'a CI4-native database instead.');

            return;
        }

        $backup_table = $this->archive_legacy_history($db);

        $this->baseline_stamp();

        CLI::write('⇾ CI3 migration tracker archived as "' . $backup_table . '" and the CI3 '
            . 'migration chain marked as applied (no DDL replayed).', 'green');

        CLI::write('Now run "ea:migrate" or deploy the application as usual.');
    }

    /**
     * Detect the CI3-shaped migration tracker (single "version" bigint column,
     * no CI4 columns): transitioned databases carry it and the CI4 runner
     * cannot read it.
     */
    protected function legacy_history_present(\CodeIgniter\Database\BaseConnection $db): bool
    {
        if ( ! $db->tableExists('migrations'))
        {
            return FALSE;
        }

        $columns = array_map(
            static fn ($field): string => strtolower((string) ($field->name ?? '')),
            $db->getFieldData($db->prefixTable('migrations'))
        );

        return in_array('version', $columns, TRUE)
            && ! in_array('batch', $columns, TRUE);
    }

    /**
     * Archive the CI3-shaped tracker (non-destructive rename; the backup name
     * gets a suffix when a previous backup already exists).
     *
     * @return string Target table name of the archive.
     */
    protected function archive_legacy_history(\CodeIgniter\Database\BaseConnection $db): string
    {
        $source = $db->prefixTable('migrations');
        $target = $db->prefixTable('migrations_c3_backup');

        if ($db->tableExists($target))
        {
            $target .= '_' . date('YmdHis');
        }

        $db->query('RENAME TABLE `' . $source . '` TO `' . $target . '`');

        return $target;
    }

    /**
     * Baseline migration stamp: marks the entire CI3 chain as applied on the
     * CI4 migration history, so the transitioned database does not replay
     * the DDL of the already-migrated schema.
     */
    protected function baseline_stamp(): void
    {
        // A fresh, NON-shared connection: the CI3-shaped tracker gets archived
        // right before this stamp, and the shared connection would otherwise
        // answer with the pre-archive table cache during the same process.
        $db = db_connect(NULL, FALSE);

        $runner = new MigrationRunner(config(\Config\Migrations::class), $db);

        $runner->ensureTable();

        $history_table = $db->prefixTable('migrations');

        $migrations_found = $runner->findMigrations();

        if ($migrations_found === [])
        {
            return;
        }

        $batch_next = ((int) ($db->table($history_table)
            ->selectMax('batch')
            ->get()
            ->getRow()
            ->batch ?? 0)) + 1;

        foreach ($migrations_found as $row)
        {
            $db->table($history_table)->insert([
                'version'   => $row->version,
                'class'     => $row->class,
                'group'     => $row->group ?? 'default',
                'namespace' => $row->namespace ?? 'App',
                'time'      => time(),
                'batch'     => $batch_next,
            ]);
        }
    }
}
