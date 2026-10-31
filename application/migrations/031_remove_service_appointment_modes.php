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
 * Class Migration_remove_service_appointment_modes
 *
 * The appointment mode is a provider decision (providers own the online / on-premise
 * distinction together with their own weekday overrides), so the service level value and
 * the per-service weekday overrides are removed. The provider-level columns stay.
 */
class Migration_remove_service_appointment_modes extends CI_Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ($this->db->table_exists('service_weekday_modes'))
        {
            $this->dbforge->drop_table('service_weekday_modes', TRUE);
        }

        if ($this->db->field_exists('appointment_mode', 'services'))
        {
            $this->dbforge->drop_column('services', 'appointment_mode');
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if ( ! $this->db->field_exists('appointment_mode', 'services'))
        {
            $this->dbforge->add_column('services', [
                'appointment_mode' => [
                    'type' => 'VARCHAR',
                    'constraint' => '16',
                    'null' => TRUE,
                    'default' => NULL,
                ]
            ]);
        }

        if ( ! $this->db->table_exists('service_weekday_modes'))
        {
            $this->db->query('CREATE TABLE `' . $this->db->dbprefix('service_weekday_modes') . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_services` INT UNSIGNED NOT NULL,
                `weekday` INT NOT NULL,
                `mode` VARCHAR(16) NOT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `' . $this->db->dbprefix('fk_service_weekday_modes_services') . '`
                    FOREIGN KEY (`id_services`)
                    REFERENCES ' . $this->db->dbprefix('services') . ' (`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
        }
    }
}
