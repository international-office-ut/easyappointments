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
 * Class Migration_Add_service_availabilities_type
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea007AddServiceAvailabilitiesType extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! in_array('availabilities_type', array_column($this->db->getFieldData('services'), 'name'), TRUE))
        {
            $fields = [
                'availabilities_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => '32',
                    'default' => 'flexible',
                    'after' => 'description'
                ]
            ];

            $this->forge->addColumn('services', $fields);

            $this->db->table('services')->update(['availabilities_type' => 'flexible']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if (in_array('availabilities_type', array_column($this->db->getFieldData('services'), 'name'), TRUE))
        {
            $this->forge->dropColumn('services', 'availabilities_type');
        }
    }
}
