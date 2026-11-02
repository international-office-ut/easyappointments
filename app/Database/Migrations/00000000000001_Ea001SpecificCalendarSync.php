<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


/**
 * (CI4 port of the CI3 application/migrations/001_specific_calendar_sync.php)
 *
 * Class Migration_Specific_calendar_sync
 *
 * Notice: This first migration got altered to include the creation of the initial database structure so that external
 * SQL are not required.
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea001SpecificCalendarSync extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'book_datetime' => [
                'type' => 'DATETIME',
                'null' => TRUE,
            ],
            'start_datetime' => [
                'type' => 'DATETIME',
                'null' => TRUE,
            ],
            'end_datetime' => [
                'type' => 'DATETIME',
                'null' => TRUE,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => TRUE,
            ],
            'hash' => [
                'type' => 'TEXT',
                'null' => TRUE,
            ],
            'is_unavailable' => [
                'type' => 'TINYINT',
                'constraint' => '4',
                'default' => '0'
            ],
            'id_users_provider' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
                'null' => TRUE
            ],
            'id_users_customer' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
                'null' => TRUE
            ],
            'id_services' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
                'null' => TRUE
            ],
            'id_google_calendar' => [
                'type' => 'TEXT',
                'null' => TRUE
            ],
        ]);
        $this->forge->addKey('id', TRUE);
        $this->forge->addKey('id_users_provider');
        $this->forge->addKey('id_users_customer');
        $this->forge->addKey('id_services');
        $this->forge->createTable('appointments', TRUE, ['engine' => 'InnoDB']);

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE
            ],
            'is_admin' => [
                'type' => 'TINYINT',
                'constraint' => '4',
                'null' => TRUE
            ],
            'appointments' => [
                'type' => 'INT',
                'constraint' => '4',
                'null' => TRUE
            ],
            'customers' => [
                'type' => 'INT',
                'constraint' => '4',
                'null' => TRUE
            ],
            'services' => [
                'type' => 'INT',
                'constraint' => '4',
                'null' => TRUE
            ],
            'users' => [
                'type' => 'INT',
                'constraint' => '4',
                'null' => TRUE
            ],
            'system_settings' => [
                'type' => 'INT',
                'constraint' => '4',
                'null' => TRUE
            ],
            'user_settings' => [
                'type' => 'INT',
                'constraint' => '4',
                'null' => TRUE
            ],
        ]);
        $this->forge->addKey('id', TRUE);
        $this->forge->createTable('roles', TRUE, ['engine' => 'InnoDB']);

        $this->forge->addField([
            'id_users_secretary' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
            ],
            'id_users_provider' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
            ]
        ]);
        $this->forge->addKey('id_users_secretary', TRUE);
        $this->forge->addKey('id_users_provider', TRUE);
        $this->forge->createTable('secretaries_providers', TRUE, ['engine' => 'InnoDB']);

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE
            ],
            'duration' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => TRUE
            ],
            'price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => TRUE
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => TRUE
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => TRUE
            ],
            'id_service_categories' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
                'null' => TRUE
            ],
        ]);
        $this->forge->addKey('id', TRUE);
        $this->forge->addKey('id_service_categories');
        $this->forge->createTable('services', TRUE, ['engine' => 'InnoDB']);

        $this->forge->addField([
            'id_users' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
            ],
            'id_services' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
            ]
        ]);
        $this->forge->addKey('id_users', TRUE);
        $this->forge->addKey('id_services', TRUE);
        $this->forge->createTable('services_providers', TRUE, ['engine' => 'InnoDB']);

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => TRUE
            ],
        ]);
        $this->forge->addKey('id', TRUE);
        // NOTE: the upstream CI3 chain carried a stray "add_key('id_service_categories')"
        // here. The column does not exist in this table (it belongs to "services"), CI3
        // silently produces a broken "KEY ()" block for it and every fresh install of
        // that chain fails on this statement. Dropped in the CI4 port accordingly.
        $this->forge->createTable('service_categories', TRUE, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '512',
                'null' => TRUE
            ],
            'value' => [
                'type' => 'LONGTEXT',
                'null' => TRUE
            ],
        ]);
        $this->forge->addKey('id', TRUE);
        $this->forge->createTable('settings', TRUE, ['engine' => 'InnoDB']);

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => '512',
                'null' => TRUE
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '512',
                'null' => TRUE
            ],
            'mobile_number' => [
                'type' => 'VARCHAR',
                'constraint' => '128',
                'null' => TRUE
            ],
            'phone_number' => [
                'type' => 'VARCHAR',
                'constraint' => '128',
                'null' => TRUE
            ],
            'address' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE
            ],
            'city' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE
            ],
            'state' => [
                'type' => 'VARCHAR',
                'constraint' => '128',
                'null' => TRUE
            ],
            'zip_code' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => TRUE
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => TRUE
            ],
            'id_roles' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE
            ],
        ]);
        $this->forge->addKey('id', TRUE);
        $this->forge->addKey('id_roles');
        $this->forge->createTable('users', TRUE, ['engine' => 'InnoDB']);

        $this->forge->addField([
            'id_users' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => TRUE,
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => '512',
                'null' => TRUE
            ],
            'salt' => [
                'type' => 'VARCHAR',
                'constraint' => '512',
                'null' => TRUE
            ],
            'working_plan' => [
                'type' => 'TEXT',
                'null' => TRUE
            ],
            'notifications' => [
                'type' => 'TINYINT',
                'constraint' => '4',
                'null' => TRUE
            ],
            'google_sync' => [
                'type' => 'TINYINT',
                'constraint' => '4',
                'null' => TRUE
            ],
            'google_token' => [
                'type' => 'TEXT',
                'null' => TRUE
            ],
            'google_calendar' => [
                'type' => 'VARCHAR',
                'constraint' => '128',
                'null' => TRUE
            ],
            'sync_past_days' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => TRUE,
                'default' => '5'
            ],
            'sync_future_days' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => TRUE,
                'default' => '5'
            ],
        ]);
        $this->forge->addKey('id_users', TRUE);
        $this->forge->createTable('user_settings', TRUE, ['engine' => 'InnoDB']);

        $this->db->query('
            ALTER TABLE `' . $this->db->prefixTable('appointments') . '`
              ADD CONSTRAINT `' . $this->db->prefixTable('appointments') . '_ibfk_2` FOREIGN KEY (`id_users_customer`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              ADD CONSTRAINT `' . $this->db->prefixTable('appointments') . '_ibfk_3` FOREIGN KEY (`id_services`) REFERENCES `' . $this->db->prefixTable('services') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              ADD CONSTRAINT `' . $this->db->prefixTable('appointments') . '_ibfk_4` FOREIGN KEY (`id_users_provider`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ');

        $this->db->query('
            ALTER TABLE `' . $this->db->prefixTable('secretaries_providers') . '`
              ADD CONSTRAINT `fk_' . $this->db->prefixTable('secretaries_providers') . '_1` FOREIGN KEY (`id_users_secretary`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              ADD CONSTRAINT `fk_' . $this->db->prefixTable('secretaries_providers') . '_2` FOREIGN KEY (`id_users_provider`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ');

        $this->db->query('
            ALTER TABLE `' . $this->db->prefixTable('services') . '`
              ADD CONSTRAINT `' . $this->db->prefixTable('services') . '_ibfk_1` FOREIGN KEY (`id_service_categories`) REFERENCES `' . $this->db->prefixTable('service_categories') . '` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
        ');

        $this->db->query('
            ALTER TABLE `' . $this->db->prefixTable('services_providers') . '`
              ADD CONSTRAINT `' . $this->db->prefixTable('services_providers') . '_ibfk_1` FOREIGN KEY (`id_users`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              ADD CONSTRAINT `' . $this->db->prefixTable('services_providers') . '_ibfk_2` FOREIGN KEY (`id_services`) REFERENCES `' . $this->db->prefixTable('services') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ');

        $this->db->query('
            ALTER TABLE `' . $this->db->prefixTable('users') . '`
              ADD CONSTRAINT `' . $this->db->prefixTable('users') . '_ibfk_1` FOREIGN KEY (`id_roles`) REFERENCES `' . $this->db->prefixTable('roles') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ');

        $this->db->query('
            ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
              ADD CONSTRAINT `' . $this->db->prefixTable('user_settings') . '_ibfk_1` FOREIGN KEY (`id_users`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

        ');

        $this->db->table('roles')->insert([
            'name' => 'Administrator',
            'slug' => 'admin',
            'is_admin' => TRUE,
            'appointments' => 15,
            'customers' => 15,
            'services' => 15,
            'users' => 15,
            'system_settings' => 15,
            'user_settings' => 15,
        ]);

        $this->db->table('roles')->insert([
            'name' => 'Provider',
            'slug' => 'provider',
            'is_admin' => FALSE,
            'appointments' => 15,
            'customers' => 15,
            'services' => 0,
            'users' => 0,
            'system_settings' => 0,
            'user_settings' => 15,
        ]);

        $this->db->table('roles')->insert([
            'name' => 'Customer',
            'slug' => 'customer',
            'is_admin' => FALSE,
            'appointments' => 0,
            'customers' => 0,
            'services' => 0,
            'users' => 0,
            'system_settings' => 0,
            'user_settings' => 0,
        ]);

        $this->db->table('roles')->insert([
            'name' => 'Secretary',
            'slug' => 'secretary',
            'is_admin' => FALSE,
            'appointments' => 15,
            'customers' => 15,
            'services' => 0,
            'users' => 0,
            'system_settings' => 0,
            'user_settings' => 15,
        ]);

        $this->db->table('settings')->insert([
            'name' => 'company_working_plan',
            'value' => '{"monday":{"start":"09:00","end":"18:00","breaks":[{"start":"14:30","end":"15:00"}]},"tuesday":{"start":"09:00","end":"18:00","breaks":[{"start":"14:30","end":"15:00"}]},"wednesday":{"start":"09:00","end":"18:00","breaks":[{"start":"14:30","end":"15:00"}]},"thursday":{"start":"09:00","end":"18:00","breaks":[{"start":"14:30","end":"15:00"}]},"friday":{"start":"09:00","end":"18:00","breaks":[{"start":"14:30","end":"15:00"}]},"saturday":{"start":"09:00","end":"18:00","breaks":[{"start":"14:30","end":"15:00"}]},"sunday":{"start":"09:00","end":"18:00","breaks":[{"start":"14:30","end":"15:00"}]}}'
        ]);

        $this->db->table('settings')->insert([
            'name' => 'book_advance_timeout',
            'value' => '30'
        ]);
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('appointments') . '` DROP FOREIGN KEY `' . $this->db->prefixTable('appointments') . '_ibfk_2`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('appointments') . '` DROP FOREIGN KEY `' . $this->db->prefixTable('appointments') . '_ibfk_3`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('appointments') . '` DROP FOREIGN KEY `' . $this->db->prefixTable('appointments') . '_ibfk_4`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('secretaries_providers') . '` DROP FOREIGN KEY `fk_' . $this->db->prefixTable('secretaries_providers') . '_1`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('secretaries_providers') . '` DROP FOREIGN KEY `fk_' . $this->db->prefixTable('secretaries_providers') . '_2`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('services_providers') . '` DROP FOREIGN KEY `' . $this->db->prefixTable('services_providers') . '_ibfk_1`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('services_providers') . '` DROP FOREIGN KEY `' . $this->db->prefixTable('services_providers') . '_ibfk_2`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('services') . '` DROP FOREIGN KEY `' . $this->db->prefixTable('services') . '_ibfk_1`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('users') . '` DROP FOREIGN KEY `' . $this->db->prefixTable('users') . '_ibfk_1`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('user_settings') . '` DROP FOREIGN KEY `' . $this->db->prefixTable('user_settings') . '_ibfk_1`');

        $this->forge->dropTable('appointments');
        $this->forge->dropTable('roles');
        $this->forge->dropTable('secretaries_providers');
        $this->forge->dropTable('services');
        $this->forge->dropTable('service_categories');
        $this->forge->dropTable('services_providers');
        $this->forge->dropTable('settings');
        $this->forge->dropTable('user_settings');
        $this->forge->dropTable('users');
    }
}
