<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


/**
 * (CI4 port of the CI3 application/migrations/030_add_password_reset_token_index.php)
 *
 * Class Migration_add_password_reset_token_index
 *
 * The access token lookups filter by the hash column, so the index keeps the link pages
 * fast when the user count grows.
 */
class Migration_Ea030AddPasswordResetTokenIndex extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
            ADD KEY `password_reset_token_hash` (`password_reset_token_hash`)');
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->db->query('ALTER TABLE `' . $this->db->prefixTable('user_settings') . '`
            DROP KEY `password_reset_token_hash`');
    }
}
