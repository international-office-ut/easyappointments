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
     * Baseline migration stamp.
     *
     * @param MigrationRunner $migrations Runner bound to the default DB group.
     *
     * @return void
     */
    protected function baseline_stamp(MigrationRunner $migrations): void
    {
        $db = db_connect();

        $history_table = $db->prefixTable('migrations');

        $migrations_found = $migrations->findMigrations();

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
