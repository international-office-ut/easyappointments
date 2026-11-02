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
 * (CI4 port of the CI3 application/migrations/019_add_display_any_provider_setting.php)
 *
 * Class Migration_Add_display_any_provider_setting
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea019AddDisplayAnyProviderSetting extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $this->db->table('settings')->insert([
            'name' => 'display_any_provider',
            'value' => '1'
        ]);
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->db->table('settings')->where([
            'name' => 'display_any_provider'
        ])->delete();
    }
}
