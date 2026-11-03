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
 * Class Migration_Add_working_plan_exceptions_to_user_settings
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea015AddWorkingPlanExceptionsToUserSettings extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! in_array('working_plan_exceptions', array_column($this->db->getFieldData('user_settings'), 'name'), TRUE))
        {
            $fields = [
                'working_plan_exceptions' => [
                    'type' => 'TEXT',
                    'null' => TRUE,
                    'after' => 'working_plan'
                ]
            ];

            $this->forge->addColumn('user_settings', $fields);
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if ( ! in_array('working_plan_exceptions', array_column($this->db->getFieldData('user_settings'), 'name'), TRUE))
        {
            $this->forge->dropColumn('user_settings', 'working_plan_exceptions');
        }
    }
}
