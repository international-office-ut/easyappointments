<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.3.0
 * ---------------------------------------------------------------------------- */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


/**
 * (CI4 port of the CI3 application/migrations/010_add_time_format_setting.php)
 *
 * Class Migration_Add_time_format_setting
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea010AddTimeFormatSetting extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $this->db->table('settings')->insert([
            'name' => 'time_format',
            'value' => 'regular'
        ]);
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->db->table('settings')->where(['name' => 'time_format'])->delete();
    }
}
