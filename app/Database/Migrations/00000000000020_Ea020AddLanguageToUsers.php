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
 * Class Migration_Add_language_to_users
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea020AddLanguageToUsers extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! in_array('language', array_column($this->db->getFieldData('users'), 'name'), TRUE))
        {
            $fields = [
                'language' => [
                    'type' => 'VARCHAR',
                    'constraint' => '256',
                    'default' => 'english',
                    'after' => 'timezone'
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
        $this->forge->dropColumn('users', 'language');
    }
}
