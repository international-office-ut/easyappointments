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
 * Class Migration_Add_record_retention_setting
 *
 * Adds the data retention setting used by the maintenance cleanup (privilege: records like
 * appointments, customers and unavailables older than the configured months get removed).
 *
 * @property CI_DB_query_builder $db
 */
class Migration_Add_record_retention_setting extends CI_Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $rows = $this->db->get_where('settings', ['name' => 'record_retention_months'])->num_rows();

        if ($rows === 0)
        {
            $this->db->insert('settings', ['name' => 'record_retention_months', 'value' => '3']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->db->delete('settings', ['name' => 'record_retention_months']);
    }
}
