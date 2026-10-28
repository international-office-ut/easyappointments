<?php defined('BASEPATH') or exit('No direct script access allowed');

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

/**
 * Class Migration_Add_appointment_types_support
 *
 * Adds the appointment mode configuration: a per-service default mode, per-service weekday
 * overrides and provider-level settings (default mode and the Zoom join link that replaced
 * the mobile-number workaround).
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Add_appointment_types_support extends CI_Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! $this->db->field_exists('appointment_mode', 'services'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('services') . '`
                    ADD COLUMN `appointment_mode` VARCHAR(16) NULL DEFAULT NULL AFTER `location`;
            ');
        }

        if ( ! $this->db->field_exists('appointment_mode', 'user_settings'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('user_settings') . '`
                    ADD COLUMN `appointment_mode` VARCHAR(16) NULL DEFAULT NULL AFTER `calendar_view`;
            ');
        }

        if ( ! $this->db->field_exists('zoom_link', 'user_settings'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('user_settings') . '`
                    ADD COLUMN `zoom_link` VARCHAR(512) NULL DEFAULT NULL AFTER `appointment_mode`;
            ');
        }

        if ( ! $this->db->table_exists('service_weekday_modes'))
        {
            $this->db->query('
                CREATE TABLE `' . $this->db->dbprefix('service_weekday_modes') . '` (
                    `id_services` INT(11) NOT NULL,
                    `weekday` TINYINT(3) UNSIGNED NOT NULL,
                    `mode` VARCHAR(16) NOT NULL,
                    PRIMARY KEY (`id_services`, `weekday`),
                    CONSTRAINT `' . $this->db->dbprefix('fk_service_weekday_modes_services') . '`
                        FOREIGN KEY (`id_services`) REFERENCES `' . $this->db->dbprefix('services') . '` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE
                )
                ENGINE = InnoDB DEFAULT CHARSET = utf8;
            ');
        }

        // Map the current behavior onto the new mode values, so that existing services keep
        // working exactly as before (the location value was the mode encoding so far).
        $this->db->query('
            UPDATE `' . $this->db->dbprefix('services') . '`
                SET `appointment_mode` = \'' . APPOINTMENT_MODE_ONLINE . '\'
                WHERE `location` = \'Zoom\';
        ');

        $this->db->query('
            UPDATE `' . $this->db->dbprefix('services') . '`
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
            DROP TABLE IF EXISTS `' . $this->db->dbprefix('service_weekday_modes') . '`;
        ');

        if ($this->db->field_exists('zoom_link', 'user_settings'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('user_settings') . '`
                    DROP COLUMN `zoom_link`;
            ');
        }

        if ($this->db->field_exists('appointment_mode', 'user_settings'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('user_settings') . '`
                    DROP COLUMN `appointment_mode`;
            ');
        }

        if ($this->db->field_exists('appointment_mode', 'services'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('services') . '`
                    DROP COLUMN `appointment_mode`;
            ');
        }
    }
}
