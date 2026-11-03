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
 * Class Migration_Add_record_retention_setting
 *
 * Adds the data retention setting used by the maintenance cleanup (privilege: records like
 * appointments, customers and provider blocks older than the configured days get removed).
 *
 * @property CI_DB_query_builder $db
 */
class Migration_Ea025AddRecordRetentionSetting extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ($this->db->tableExists('settings')
            && $this->db->table('settings')->where(['name' => 'record_retention_days'])->countAllResults() === 0)
        {
            $this->db->table('settings')->insert([
                'name' => 'record_retention_days',
                'value' => '90'
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if ($this->db->tableExists('settings'))
        {
            $this->db->table('settings')->where(['name' => 'record_retention_days'])->delete();
        }
    }
}
