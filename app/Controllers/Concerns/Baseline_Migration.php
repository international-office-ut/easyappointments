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

namespace App\Controllers\Concerns;

use CodeIgniter\Database\MigrationRunner;

/**
 * Baseline migration stamp used by both the CLI migration command and the
 * installer: marks the entire CI3 chain as applied on the CI4 migration
 * history, so that transitioned (CI3-migrated) databases do not replay the
 * DDL of the already-migrated schema.
 */
trait Baseline_Migration {
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
     */
    protected function archive_legacy_history(\CodeIgniter\Database\BaseConnection $db): void
    {
        $source = $db->prefixTable('migrations');
        $target = $db->prefixTable('migrations_c3_backup');

        if ($db->tableExists($target))
        {
            $target .= '_' . date('YmdHis');
        }

        $db->query('RENAME TABLE `' . $source . '` TO `' . $target . '`');
    }

    /**
     * Baseline migration stamp.
     *
     * @param MigrationRunner $migrations Runner bound to the default DB group.
     *
     * @return void
     */
    protected function baseline_stamp(MigrationRunner $migrations): void
    {
        // A fresh, NON-shared connection: the CI3-shaped tracker gets archived
        // right before this stamp, and the shared connection would otherwise
        // answer with the pre-archive table cache during the same process.
        $db = db_connect(null, false);

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