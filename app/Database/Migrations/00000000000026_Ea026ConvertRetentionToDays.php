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
 * (CI4 port of the CI3 application/migrations/026_convert_retention_to_days.php)
 *
 * Class Migration_Convert_retention_to_days
 *
 * Converts the data retention setting from months to days for a finer retention control:
 * the months value gets multiplied by 30 and the days setting replaces the months row.
 *
 * @property CI_DB_query_builder $db
 */
class Migration_Ea026ConvertRetentionToDays extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! $this->db->tableExists('settings'))
        {
            return;
        }

        // The days row already wins (poor-man idempotency check).
        $days_row = $this->db->table('settings')->where(['name' => 'record_retention_days'])->get()->getRow();

        if ($days_row)
        {
            return;
        }

        $months_row = $this->db->table('settings')->where(['name' => 'record_retention_months'])->get()->getRow();

        $days = ($months_row && ! empty($months_row->value)) ? ((int)$months_row->value * 30) : 90;

        $this->db->table('settings')->insert([
            'name' => 'record_retention_days',
            'value' => (string) $days
        ]);

        $this->db->table('settings')->where(['name' => 'record_retention_months'])->delete();
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if ( ! $this->db->tableExists('settings'))
        {
            return;
        }

        $days_row = $this->db->table('settings')->where(['name' => 'record_retention_days'])->get()->getRow();

        $days = ($days_row && ! empty($days_row->value)) ? (int)$days_row->value : 90;

        $this->db->table('settings')->where(['name' => 'record_retention_days'])->delete();

        $months_row = $this->db->table('settings')->where(['name' => 'record_retention_months'])->get()->getRow();

        if ( ! $months_row)
        {
            $this->db->table('settings',
                ['name' => 'record_retention_months', 'value' => (string) floor($days / 30)]);
        }
    }
}
