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
 * Class Migration_Add_service_location_overrides
 *
 * Adds the service location overrides (JSON, service-scoped):
 *
 * - `location_weekday_modes` — list of time windows per weekday: {"1": [{"from": "08:00", "to": "13:00",
 *   "location": "Raum A"}]}
 * - `location_date_overrides` — list of time windows per ISO date: {"2026-11-20": [...]}
 *
 * Both override the default `services.location` value (highest priority: date window, then weekday
 * window, then the default location).
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Add_service_location_overrides extends CI_Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! $this->db->field_exists('location_weekday_modes', 'services'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('services') . '`
                    ADD COLUMN `location_weekday_modes` TEXT NULL DEFAULT NULL AFTER `location`;
            ');
        }

        if ( ! $this->db->field_exists('location_date_overrides', 'services'))
        {
            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('services') . '`
                    ADD COLUMN `location_date_overrides` TEXT NULL DEFAULT NULL AFTER `location_weekday_modes`;
            ');
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $columns = ['location_date_overrides', 'location_weekday_modes'];

        foreach ($columns as $column)
        {
            if ($this->db->field_exists($column, 'services'))
            {
                $this->db->query('
                    ALTER TABLE `' . $this->db->dbprefix('services') . '`
                        DROP COLUMN `' . $column . '`;
                ');
            }
        }
    }
}
