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
 * (CI4 port of the CI3 application/migrations/018_add_timezone_to_users.php)
 *
 * Class Migration_Add_timezone_to_users
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea018AddTimezoneToUsers extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! in_array('timezone', array_column($this->db->getFieldData('users'), 'name'), TRUE))
        {
            $fields = [
                'timezone' => [
                    'type' => 'VARCHAR',
                    'constraint' => '256',
                    'default' => 'UTC',
                    'after' => 'notes'
                ]
            ];

            $this->forge->addColumn('users', $fields);
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->forge->dropColumn('users', 'timezone');
    }
}
