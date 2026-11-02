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
 * (CI4 port of the CI3 application/migrations/008_add_service_attendants_number.php)
 *
 * Class Migration_Add_service_attendants_number
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea008AddServiceAttendantsNumber extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! in_array('attendants_number', array_column($this->db->getFieldData('services'), 'name'), TRUE))
        {
            $fields = [
                'attendants_number' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'default' => '1',
                    'after' => 'availabilities_type'
                ]
            ];

            $this->forge->addColumn('services', $fields);
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if (in_array('attendants_number', array_column($this->db->getFieldData('services'), 'name'), TRUE))
        {
            $this->forge->dropColumn('services', 'attendants_number');
        }
    }
}
