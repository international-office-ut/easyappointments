<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.4.2
 * ---------------------------------------------------------------------------- */

/**
 * Class Migration_Add_provider_calendar_tokens
 *
 * Adds the provider calendar feed tokens: each provider may own a single subscription token
 * that gets stored hashed, so that the iCal feed URL can never be reconstructed from the
 * database contents.
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Add_provider_calendar_tokens extends CI_Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ( ! $this->db->table_exists('provider_calendar_tokens'))
        {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'auto_increment' => TRUE
                ],
                'id_users' => [
                    'type' => 'BIGINT',
                    'constraint' => 20
                ],
                'token_hash' => [
                    'type' => 'VARCHAR',
                    'constraint' => '64'
                ],
                'created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP',
                'last_used_at DATETIME NULL DEFAULT NULL'
            ]);

            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('token_hash');
            $this->dbforge->add_key('id_users');
            $this->dbforge->create_table('provider_calendar_tokens', TRUE, ['engine' => 'InnoDB']);

            $this->db->query('
                ALTER TABLE `' . $this->db->dbprefix('provider_calendar_tokens') . '`
                  ADD CONSTRAINT `fk_' . $this->db->dbprefix('provider_calendar_tokens') . '_1`
                  FOREIGN KEY (`id_users`) REFERENCES `' . $this->db->dbprefix('users') . '` (`id`)
                  ON DELETE CASCADE ON UPDATE CASCADE;

            ');
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if ($this->db->table_exists('provider_calendar_tokens'))
        {
            $this->dbforge->drop_table('provider_calendar_tokens', TRUE);
        }
    }
}
