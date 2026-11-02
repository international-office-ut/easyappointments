<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Class Migration_Ea032ConvertSchemaToUtf8Mb4
 *
 * Upgrades the whole schema from the legacy 3-byte utf8 (utf8mb3) encoding of
 * the CI3 era to utf8mb4, so that 4-byte unicode code points (e.g. emojis) can
 * be stored in all text columns. The 3-byte requirement was carried over from
 * the old Easy!Appointments stack and is now treated as a property of the
 * database server.
 */
class Migration_Ea032ConvertSchemaToUtf8Mb4 extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $charset = 'utf8mb4';
        $collation = 'utf8mb4_general_ci';

        $tables = $this->db->query('
            SELECT `TABLE_NAME`
            FROM `information_schema`.`TABLES`
            WHERE `TABLE_SCHEMA` = DATABASE()
                AND `TABLE_TYPE` = \'BASE TABLE\'
                AND `TABLE_COLLATION` IS NOT NULL
                AND `TABLE_COLLATION` NOT LIKE \'utf8mb4_%\'
        ')
            ->getResultArray();

        foreach ($tables as $table)
        {
            // Rerunning the statement after a failed conversion is harmless, so
            // the operator can just retry the migration after fixing the cause.
            $this->db->query('
                ALTER TABLE `' . $table['TABLE_NAME'] . '`
                CONVERT TO CHARACTER SET ' . $charset . '
                COLLATE ' . $collation . '
            ');
        }

        // Align the database default with the table encodings, so that new
        // tables (older MariaDB servers default to latin1) are created in the
        // application encoding.
        $database = $this->db->query('
            SELECT `DEFAULT_CHARACTER_SET_NAME`
            FROM `information_schema`.`SCHEMATA`
            WHERE `SCHEMA_NAME` = DATABASE()
        ')
            ->getRowArray();

        if ($database['DEFAULT_CHARACTER_SET_NAME'] !== $charset)
        {
            $this->db->query('
                ALTER DATABASE `' . $this->db->getDatabase() . '`
                CHARACTER SET ' . $charset . '
                COLLATE ' . $collation . '
            ');
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $charset = 'utf8';
        $collation = 'utf8_general_ci';

        $this->db->query('
            ALTER DATABASE `' . $this->db->getDatabase() . '`
            CHARACTER SET ' . $charset . '
            COLLATE ' . $collation . '
        ');

        $tables = $this->db->query('
            SELECT `TABLE_NAME`
            FROM `information_schema`.`TABLES`
            WHERE `TABLE_SCHEMA` = DATABASE()
                AND `TABLE_TYPE` = \'BASE TABLE\'
                AND `TABLE_COLLATION` IS NOT NULL
                AND `TABLE_COLLATION` NOT LIKE \'utf8mb3_%\'
        ')
            ->getResultArray();

        foreach ($tables as $table)
        {
            $this->db->query('
                ALTER TABLE `' . $table['TABLE_NAME'] . '`
                CONVERT TO CHARACTER SET ' . $charset . '
                COLLATE ' . $collation . '
            ');
        }
    }
}
