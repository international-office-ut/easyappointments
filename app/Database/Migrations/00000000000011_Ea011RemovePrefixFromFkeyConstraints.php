<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.3.0
 * ---------------------------------------------------------------------------- */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


/**
 * (CI4 port of the CI3 application/migrations/011_remove_prefix_from_fkey_constraints.php)
 *
 * Class Migration_Remove_prefix_from_foreign_keys
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea011RemovePrefixFromFkeyConstraints extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        // Drop table constraints.
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

        // Add table constraints again without the "ea" prefix.
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('appointments') . '`
            ADD CONSTRAINT `appointments_users_customer` FOREIGN KEY (`id_users_customer`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
            ADD CONSTRAINT `appointments_services` FOREIGN KEY (`id_services`) REFERENCES `' . $this->db->prefixTable('services') . '` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
            ADD CONSTRAINT `appointments_users_provider` FOREIGN KEY (`id_users_provider`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('secretaries_providers') . '`
            ADD CONSTRAINT `secretaries_users_secretary` FOREIGN KEY (`id_users_secretary`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
            ADD CONSTRAINT `secretaries_users_provider` FOREIGN KEY (`id_users_provider`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('services') . '`
            ADD CONSTRAINT `services_service_categories` FOREIGN KEY (`id_service_categories`) REFERENCES `' . $this->db->prefixTable('service_categories') . '` (`id`)
            ON DELETE SET NULL
            ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('services_providers') . '`
            ADD CONSTRAINT `services_providers_users_provider` FOREIGN KEY (`id_users`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
            ADD CONSTRAINT `services_providers_services` FOREIGN KEY (`id_services`) REFERENCES `' . $this->db->prefixTable('services') . '` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('users') . '`
            ADD CONSTRAINT `users_roles` FOREIGN KEY (`id_roles`) REFERENCES `' . $this->db->prefixTable('roles') . '` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
            ADD CONSTRAINT `user_settings_users` FOREIGN KEY (`id_users`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE');
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        // Drop table constraints.
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('appointments') . '` DROP FOREIGN KEY `appointments_services`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('appointments') . '` DROP FOREIGN KEY `appointments_users_customer`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('appointments') . '` DROP FOREIGN KEY `appointments_users_provider`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('secretaries_providers') . '` DROP FOREIGN KEY `secretaries_users_secretary`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('secretaries_providers') . '` DROP FOREIGN KEY `secretaries_users_provider`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('services_providers') . '` DROP FOREIGN KEY `services_providers_users_provider`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('services_providers') . '` DROP FOREIGN KEY `services_providers_services`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('services') . '` DROP FOREIGN KEY `services_service_categories`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('users') . '` DROP FOREIGN KEY `users_roles`');
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('user_settings') . '` DROP FOREIGN KEY `user_settings_users`');

        // Add table constraints again.
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('appointments') . '`
            ADD CONSTRAINT `' . $this->db->prefixTable('appointments') . '_ibfk_2` FOREIGN KEY (`id_users_customer`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `' . $this->db->prefixTable('appointments') . '_ibfk_3` FOREIGN KEY (`id_services`) REFERENCES `' . $this->db->prefixTable('services') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `' . $this->db->prefixTable('appointments') . '_ibfk_4` FOREIGN KEY (`id_users_provider`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('secretaries_providers') . '`
            ADD CONSTRAINT `fk_' . $this->db->prefixTable('secretaries_providers') . '_1` FOREIGN KEY (`id_users_secretary`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `fk_' . $this->db->prefixTable('secretaries_providers') . '_2` FOREIGN KEY (`id_users_provider`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('services') . '`
            ADD CONSTRAINT `' . $this->db->prefixTable('services') . '_ibfk_1` FOREIGN KEY (`id_service_categories`) REFERENCES `' . $this->db->prefixTable('service_categories') . '` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('services_providers') . '`
            ADD CONSTRAINT `' . $this->db->prefixTable('services_providers') . '_ibfk_1` FOREIGN KEY (`id_users`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `' . $this->db->prefixTable('services_providers') . '_ibfk_2` FOREIGN KEY (`id_services`) REFERENCES `' . $this->db->prefixTable('services') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('users') . '`
            ADD CONSTRAINT `' . $this->db->prefixTable('users') . '_ibfk_1` FOREIGN KEY (`id_roles`) REFERENCES `' . $this->db->prefixTable('roles') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
            ADD CONSTRAINT `' . $this->db->prefixTable('user_settings') . '_ibfk_1` FOREIGN KEY (`id_users`) REFERENCES `' . $this->db->prefixTable('users') . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
    }
}
