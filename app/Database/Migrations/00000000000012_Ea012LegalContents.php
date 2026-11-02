<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.3.2
 * ---------------------------------------------------------------------------- */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


/**
 * (CI4 port of the CI3 application/migrations/012_legal_contents.php)
 *
 * Class Migration_Create_consents_table
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea012LegalContents extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $this->db->table('settings')->insert([
            'name' => 'display_cookie_notice',
            'value' => '0'
        ]);

        $this->db->table('settings')->insert([
            'name' => 'cookie_notice_content',
            'value' => 'Cookie notice content.'
        ]);

        $this->db->table('settings')->insert([
            'name' => 'display_terms_and_conditions',
            'value' => '0'
        ]);

        $this->db->table('settings')->insert([
            'name' => 'terms_and_conditions_content',
            'value' => 'Terms and conditions content.'
        ]);

        $this->db->table('settings')->insert([
            'name' => 'display_privacy_policy',
            'value' => '0'
        ]);

        $this->db->table('settings')->insert([
            'name' => 'privacy_policy_content',
            'value' => 'Privacy policy content.'
        ]);

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => TRUE
            ],
            'created' => [
                'type' => 'TIMESTAMP',
                'null' => TRUE
            ],
            'modified' => [
                'type' => 'TIMESTAMP',
                'null' => TRUE
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE,
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '512',
                'null' => TRUE,
            ],
            'ip' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE,
            ],
        ]);

        $this->forge->addKey('id', TRUE);

        $this->forge->createTable('consents', TRUE, ['engine' => 'InnoDB']);
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->db->table('settings')->where([
            'name' => 'display_cookie_notice'
        ])->delete();

        $this->db->table('settings')->where([
            'name' => 'cookie_notice_content'
        ])->delete();

        $this->db->table('settings')->where([
            'name' => 'display_terms_and_conditions'
        ])->delete();

        $this->db->table('settings')->where([
            'name' => 'terms_and_conditions_content'
        ])->delete();

        $this->db->table('settings')->where([
            'name' => 'display_privacy_policy'
        ])->delete();

        $this->db->table('settings')->where([
            'name' => 'privacy_policy_content'
        ])->delete();

        $this->forge->dropTable('consents');
    }
}
