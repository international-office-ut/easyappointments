<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


/**
 * Class Migration_Add_calendar_view_setting
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea006AddCalendarViewSetting extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! in_array('calendar_view', array_column($this->db->getFieldData('user_settings'), 'name'), TRUE))
        {
            $fields = [
                'calendar_view' => [
                    'type' => 'VARCHAR',
                    'constraint' => '32',
                    'default' => 'default'
                ]
            ];

            $this->forge->addColumn('user_settings', $fields);

            $this->db->table('user_settings')->update(['calendar_view' => 'default']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if (in_array('calendar_view', array_column($this->db->getFieldData('user_settings'), 'name'), TRUE))
        {
            $this->forge->dropColumn('user_settings', 'calendar_view');
        }
    }
}
