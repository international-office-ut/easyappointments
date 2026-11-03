<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


/**
 * Class Migration_Modify_sync_period_columns
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea021ModifySyncPeriodColumns extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $fields = [
            'sync_past_days' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => TRUE,
                'default' => '30'
            ],
            'sync_future_days' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => TRUE,
                'default' => '90'
            ],
        ];

        $this->forge->modifyColumn('user_settings', $fields);

        $this->db->table('user_settings')->where('sync_past_days', '5')
            ->update(['sync_past_days' => '30']);

        $this->db->table('user_settings')->where('sync_future_days', '5')
            ->update(['sync_future_days' => '90']);
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $fields = [
            'sync_past_days' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => TRUE,
                'default' => '5'
            ],
            'sync_future_days' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => TRUE,
                'default' => '5'
            ],
        ];

        $this->forge->modifyColumn('user_settings', $fields);

        $this->db->table('user_settings')->where('sync_past_days', '30')
            ->update(['sync_past_days' => '5']);

        $this->db->table('user_settings')->where('sync_future_days', '90')
            ->update(['sync_future_days' => '5']);
    }
}
