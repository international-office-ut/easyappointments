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
 * Class Migration_remove_service_appointment_modes
 *
 * The appointment mode is a provider decision (providers own the online / on-premise
 * distinction together with their own weekday overrides), so the service level value and
 * the per-service weekday overrides are removed. The provider-level columns stay.
 */
class Migration_Ea031RemoveServiceAppointmentModes extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        if ($this->db->tableExists('service_weekday_modes'))
        {
            $this->forge->dropTable('service_weekday_modes', TRUE);
        }

        if (in_array('appointment_mode', array_column($this->db->getFieldData('services'), 'name'), TRUE))
        {
            $this->forge->dropColumn('services', 'appointment_mode');
        }
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        if ( ! in_array('appointment_mode', array_column($this->db->getFieldData('services'), 'name'), TRUE))
        {
            $this->forge->addColumn('services', [
                'appointment_mode' => [
                    'type' => 'VARCHAR',
                    'constraint' => '16',
                    'null' => TRUE,
                    'default' => NULL,
                ]
            ]);
        }

        if ( ! $this->db->tableExists('service_weekday_modes'))
        {
            $this->db->query('CREATE TABLE `' . $this->db->prefixTable('service_weekday_modes') . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_services` INT UNSIGNED NOT NULL,
                `weekday` INT NOT NULL,
                `mode` VARCHAR(16) NOT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `' . $this->db->prefixTable('fk_service_weekday_modes_services') . '`
                    FOREIGN KEY (`id_services`)
                    REFERENCES ' . $this->db->prefixTable('services') . ' (`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB;');
        }
    }
}
