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

use CodeIgniter\Database\Migration;


/**
 * (CI4 port of the CI3 application/migrations/022_add_appointment_types_support.php)
 *
 * Class Migration_Add_appointment_types_support
 *
 * Adds the appointment mode configuration: a per-service default mode, per-service weekday
 * overrides and provider-level settings (default mode and the Zoom join link that replaced
 * the mobile-number workaround).
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea022AddAppointmentTypesSupport extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! in_array('appointment_mode', array_column($this->db->getFieldData('services'), 'name'), TRUE))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('services') . '`
                    ADD COLUMN `appointment_mode` VARCHAR(16) NULL DEFAULT NULL AFTER `location`;
            ');
        }

        if ( ! in_array('appointment_mode', array_column($this->db->getFieldData('user_settings'), 'name'), TRUE))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
                    ADD COLUMN `appointment_mode` VARCHAR(16) NULL DEFAULT NULL AFTER `calendar_view`;
            ');
        }

        if ( ! in_array('zoom_link', array_column($this->db->getFieldData('user_settings'), 'name'), TRUE))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
                    ADD COLUMN `zoom_link` VARCHAR(512) NULL DEFAULT NULL AFTER `appointment_mode`;
            ');
        }

        if ( ! $this->db->tableExists('service_weekday_modes'))
        {
            $this->db->query('
                CREATE TABLE `' . $this->db->prefixTable('service_weekday_modes') . '` (
                    `id_services` INT(11) NOT NULL,
                    `weekday` TINYINT(3) UNSIGNED NOT NULL,
                    `mode` VARCHAR(16) NOT NULL,
                    PRIMARY KEY (`id_services`, `weekday`),
                    CONSTRAINT `' . $this->db->prefixTable('fk_service_weekday_modes_services') . '`
                        FOREIGN KEY (`id_services`) REFERENCES `' . $this->db->prefixTable('services') . '` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE
                )
                ENGINE = InnoDB;
            ');
        }

        // Map the current behavior onto the new mode values, so that existing services keep
        // working exactly as before (the location value was the mode encoding so far).
        $this->db->query('
            UPDATE `' . $this->db->prefixTable('services') . '`
                SET `appointment_mode` = \'' . APPOINTMENT_MODE_ONLINE . '\'
                WHERE `location` = \'Zoom\';
        ');

        $this->db->query('
            UPDATE `' . $this->db->prefixTable('services') . '`
                SET `appointment_mode` = \'' . APPOINTMENT_MODE_ON_PREMISE . '\'
                WHERE `location` IS NOT NULL AND `location` != \'\' AND `location` != \'Zoom\';
        ');
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->db->query('
            DROP TABLE IF EXISTS `' . $this->db->prefixTable('service_weekday_modes') . '`;
        ');

        if (in_array('zoom_link', array_column($this->db->getFieldData('user_settings'), 'name'), TRUE))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
                    DROP COLUMN `zoom_link`;
            ');
        }

        if (in_array('appointment_mode', array_column($this->db->getFieldData('user_settings'), 'name'), TRUE))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
                    DROP COLUMN `appointment_mode`;
            ');
        }

        if (in_array('appointment_mode', array_column($this->db->getFieldData('services'), 'name'), TRUE))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('services') . '`
                    DROP COLUMN `appointment_mode`;
            ');
        }
    }
}
