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
 * appointments, customers and provider blocks older than the configured days get removed).
 *
 * @property CI_DB_query_builder $db
 */
class Migration_Add_record_retention_setting extends CI_Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ($this->db->table_exists('settings')
            && $this->db->get_where('settings', ['name' => 'record_retention_days'])->num_rows() === 0)
        {
            $this->db->insert('settings', ['name' => 'record_retention_days', 'value' => '90']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if ($this->db->table_exists('settings'))
        {
            $this->db->delete('settings', ['name' => 'record_retention_days']);
        }
    }
}
