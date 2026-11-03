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
 * Class Migration_Remove_google_sync
 *
 * Removes the Google Calendar sync feature from the database schema: the provider sync settings,
 * the calendar event ids and the Google Analytics setting.
 *
 * @property CI_DB_query_builder $db
 */
class Migration_Ea024RemoveGoogleSync extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $drop_settings_columns = $this->db->tableExists('user_settings')
            && in_array('google_sync', array_column($this->db->getFieldData('user_settings'), 'name'), TRUE);

        if ($drop_settings_columns)
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
                    DROP COLUMN `google_sync`, DROP COLUMN `google_token`, DROP COLUMN `google_calendar`,
                    DROP COLUMN `sync_past_days`, DROP COLUMN `sync_future_days`;
            ');
        }

        if ($this->db->tableExists('appointments') && in_array('id_google_calendar', array_column($this->db->getFieldData('appointments'), 'name'), TRUE))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('appointments') . '`
                    DROP COLUMN `id_google_calendar`;
            ');
        }

        if ($this->db->tableExists('unavailables') && in_array('id_google_calendar', array_column($this->db->getFieldData('unavailables'), 'name'), TRUE))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('unavailables') . '`
                    DROP COLUMN `id_google_calendar`;
            ');
        }

        if ($this->db->tableExists('settings'))
        {
            $this->db->table('settings')->where(['name' => 'google_analytics_code'])->delete();
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if ($this->db->tableExists('user_settings') && ! in_array('google_sync', array_column($this->db->getFieldData('user_settings'), 'name'), TRUE))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
                    ADD COLUMN `google_sync` TINYINT(4) NULL DEFAULT NULL AFTER `zoom_link`,
                    ADD COLUMN `google_token` TEXT NULL DEFAULT NULL AFTER `google_sync`,
                    ADD COLUMN `google_calendar` VARCHAR(128) NULL DEFAULT NULL AFTER `google_token`,
                    ADD COLUMN `sync_past_days` INT(11) NULL DEFAULT \'5\' AFTER `google_calendar`,
                    ADD COLUMN `sync_future_days` INT(11) NULL DEFAULT \'5\' AFTER `sync_past_days`;
            ');
        }

        if ($this->db->tableExists('appointments') && ! in_array('id_google_calendar', array_column($this->db->getFieldData('appointments'), 'name'), TRUE))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('appointments') . '`
                    ADD COLUMN `id_google_calendar` TEXT NULL DEFAULT NULL;
            ');
        }

        if ($this->db->tableExists('unavailables') && ! in_array('id_google_calendar', array_column($this->db->getFieldData('unavailables'), 'name'), TRUE))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('unavailables') . '`
                    ADD COLUMN `id_google_calendar` TEXT NULL DEFAULT NULL;
            ');
        }

        if ($this->db->tableExists('settings'))
        {
            $this->db->table('settings')->insert([
                'name' => 'google_analytics_code',
                'value' => NULL
            ]);
        }
    }
}
