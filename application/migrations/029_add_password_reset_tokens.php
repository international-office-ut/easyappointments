<?php defined('BASEPATH') or exit('No direct script access allowed');

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

/**
 * Class V1_5_0_Add_password_reset_tokens
 *
 * Adds the single-use access token columns that power the email link based password reset
 * and, in SSO-only deployments, the temporary admin access links.
 */
class Migration_add_password_reset_tokens extends CI_Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $fields = [
            'password_reset_token_hash' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => TRUE,
                'default' => NULL
            ],
            'password_reset_expires' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => TRUE,
                'default' => NULL
            ],
            'password_reset_type' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => TRUE,
                'default' => NULL
            ]
        ];

        $this->dbforge->add_column('user_settings', $fields);
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->dbforge->drop_column('user_settings', 'password_reset_token_hash');
        $this->dbforge->drop_column('user_settings', 'password_reset_expires');
        $this->dbforge->drop_column('user_settings', 'password_reset_type');
    }
}
