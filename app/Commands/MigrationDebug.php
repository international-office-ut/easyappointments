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

use CodeIgniter\BaseCommands;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Forge;

/**
 * Debug command printing the exact SQL that the CI4 forge composes for a
 * create-table call of the CI3 chain (used during the CI4 migration QA).
 */
class MigrationDebug extends BaseCommand {
    protected $group = 'Testing';
    protected $name = 'ea:migration-debug';
    protected $usage = 'ea:migration-debug';
    protected $description = 'Composes the CREATE TABLE SQL for the failing migration 001 table.';

    public function run(array $params)
    {
        $conn = db_connect();

        $forge = new class($conn) extends Forge {
            public function composeCreateTable(string $table, array $attributes = []): string
            {
                return $this->_createTable($table, false, $attributes);
            }
        };

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => '20', 'unsigned' => TRUE, 'auto_increment' => TRUE],
            'name' => ['type' => 'VARCHAR', 'constraint' => '256', 'null' => TRUE],
            'description' => ['type' => 'TEXT', 'null' => TRUE],
        ]);
        $forge->addKey('id', TRUE);
        $forge->addKey('id_service_categories');

        $sql = $forge->composeCreateTable('service_categories', ['ENGINE' => 'InnoDB']);

        CLI::write($sql, 'yellow');

        return $this::EXIT_SUCCESS;
    }
}
