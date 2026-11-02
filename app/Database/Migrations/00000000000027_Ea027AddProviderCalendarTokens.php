<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.4.2
 * ---------------------------------------------------------------------------- */

namespace App\Database\Migrations;


/**
 * (CI4 port of the CI3 application/migrations/027_add_provider_calendar_tokens.php)
 *
 * Class Migration_Add_provider_calendar_tokens
 *
 * Adds the provider calendar feed tokens: each provider may own a single subscription token
 * that gets stored hashed, so that the iCal feed URL can never be reconstructed from the
 * database contents.
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea027AddProviderCalendarTokens extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! $this->db->table_exists('provider_calendar_tokens'))
        {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'auto_increment' => TRUE
                ],
                'id_users' => [
                    'type' => 'INT',
                    'constraint' => 11
                ],
                'token_hash' => [
                    'type' => 'VARCHAR',
                    'constraint' => '64'
                ],
                'created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP',
                'last_used_at DATETIME NULL DEFAULT NULL'
            ]);

            $this->forge->addKey('id', TRUE);
            $this->forge->addKey('token_hash');
            $this->forge->addKey('id_users');
            $this->forge->createTable('provider_calendar_tokens', TRUE, ['engine' => 'InnoDB']);
        }

        // Deployments that were created before the column type of "users.id" changed to
        // INT(11) have partially applied tables without the foreign key: align the types
        // and attach the constraint so that deleting providers removes their tokens too.
        $foreign_keys = $this->db->query('
            SELECT CONSTRAINT_NAME
              FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = \'' . $this->db->prefixTable('provider_calendar_tokens') . '\'
               AND COLUMN_NAME = \'id_users\'
               AND REFERENCED_TABLE_NAME IS NOT NULL
        ')->result_array();

        if ( ! empty($foreign_keys))
        {
            return;
        }

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('provider_calendar_tokens') . '`
            MODIFY `id_users` INT(11);');

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('provider_calendar_tokens') . '`
            ADD CONSTRAINT `fk_' . $this->db->prefixTable('provider_calendar_tokens') . '_1`
            FOREIGN KEY (`id_users`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE');
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if ($this->db->table_exists('provider_calendar_tokens'))
        {
            $this->forge->dropTable('provider_calendar_tokens', TRUE);
        }
    }
}
