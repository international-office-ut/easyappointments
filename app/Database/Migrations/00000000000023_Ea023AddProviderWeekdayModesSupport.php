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
 * (CI4 port of the CI3 application/migrations/023_add_provider_weekday_modes_support.php)
 *
 * Class Migration_Add_provider_weekday_modes_support
 *
 * Adds the per-provider weekday appointment mode overrides (stored as a JSON map of weekday
 * indexes to the corresponding appointment mode values).
 *
 * @property CI_DB_query_builder $db
 */
class Migration_Ea023AddProviderWeekdayModesSupport extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! $this->db->field_exists('weekday_modes', 'user_settings'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
                    ADD COLUMN `weekday_modes` TEXT NULL DEFAULT NULL AFTER `zoom_link`;
            ');
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if ($this->db->field_exists('weekday_modes', 'user_settings'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
                    DROP COLUMN `weekday_modes`;
            ');
        }
    }
}
