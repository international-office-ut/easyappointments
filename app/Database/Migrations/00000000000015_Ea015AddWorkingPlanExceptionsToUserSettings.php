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


/**
 * (CI4 port of the CI3 application/migrations/015_add_working_plan_exceptions_to_user_settings.php)
 *
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
        if ( ! $this->db->field_exists('working_plan_exceptions', 'user_settings'))
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
        if ( ! $this->db->field_exists('working_plan_exceptions', 'user_settings'))
        {
            $this->forge->dropColumn('user_settings', 'working_plan_exceptions');
        }
    }
}
