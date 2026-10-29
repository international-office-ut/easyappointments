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
 * Class Migration_Remove_google_sync
 *
 * Removes the Google Calendar sync feature from the database schema: the provider sync settings,
 * the calendar event ids and the Google Analytics setting.
 *
 * @property CI_DB_query_builder $db
 */
class Migration_Remove_google_sync extends CI_Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ($this->db->field_exists('google_sync', 'user_settings'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('user_settings') . '`
                    DROP COLUMN `google_sync`, DROP COLUMN `google_token`, DROP COLUMN `google_calendar`,
                    DROP COLUMN `sync_past_days`, DROP COLUMN `sync_future_days`;
            ');
        }

        if ($this->db->field_exists('id_google_calendar', 'appointments'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('appointments') . '`
                    DROP COLUMN `id_google_calendar`;
            ');
        }

        if ($this->db->field_exists('id_google_calendar', 'unavailables'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('unavailables') . '`
                    DROP COLUMN `id_google_calendar`;
            ');
        }

        $this->db->delete('settings', ['name' => 'google_analytics_code']);
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if ( ! $this->db->field_exists('google_sync', 'user_settings'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('user_settings') . '`
                    ADD COLUMN `google_sync` TINYINT(4) NULL DEFAULT NULL AFTER `zoom_link`,
                    ADD COLUMN `google_token` TEXT NULL DEFAULT NULL AFTER `google_sync`,
                    ADD COLUMN `google_calendar` VARCHAR(128) NULL DEFAULT NULL AFTER `google_token`,
                    ADD COLUMN `sync_past_days` INT(11) NULL DEFAULT \'5\' AFTER `google_calendar`,
                    ADD COLUMN `sync_future_days` INT(11) NULL DEFAULT \'5\' AFTER `sync_past_days`;
            ');
        }

        if ( ! $this->db->field_exists('id_google_calendar', 'appointments'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('appointments') . '`
                    ADD COLUMN `id_google_calendar` TEXT NULL DEFAULT NULL;
            ');
        }

        if ( ! $this->db->field_exists('id_google_calendar', 'unavailables'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('unavailables') . '`
                    ADD COLUMN `id_google_calendar` TEXT NULL DEFAULT NULL;
            ');
        }

        $this->db->insert('settings', ['name' => 'google_analytics_code', 'value' => NULL]);
    }
}
