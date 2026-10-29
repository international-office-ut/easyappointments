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
 * Class Migration_Convert_retention_to_days
 *
 * Converts the data retention setting from months to days for a finer retention control:
 * the months value gets multiplied by 30 and the days setting replaces the months row.
 *
 * @property CI_DB_query_builder $db
 */
class Migration_Convert_retention_to_days extends CI_Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! $this->db->table_exists('settings'))
        {
            return;
        }

        $days = (int)$this->settings_model->get_setting('record_retention_days');

        if ( ! empty($days))
        {
            return;
        }

        $months = (int)$this->settings_model->get_setting('record_retention_months');

        if ( ! empty($months))
        {
            $days = $months * 30;
        }
        else
        {
            $days = 90;
        }

        $this->db->insert('settings', ['name' => 'record_retention_days', 'value' => (string) $days]);

        $this->db->delete('settings', ['name' => 'record_retention_months']);
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $days = (int)$this->db->get_where('settings', ['name' => 'record_retention_days'])->row()->value;

        if ($this->db->table_exists('settings'))
        {
            $this->db->delete('settings', ['name' => 'record_retention_days']);

            if ( ! empty($days))
            {
                $this->db->insert('settings',
                    ['name' => 'record_retention_months', 'value' => (string) floor($days / 30)]);
            }
        }
    }
}
