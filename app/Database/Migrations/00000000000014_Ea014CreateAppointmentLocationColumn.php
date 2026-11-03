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
 * Class Migration_Create_appointment_location_column
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea014CreateAppointmentLocationColumn extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $this->db->query('
            ALTER TABLE `' . $this->db->prefixTable('appointments') . '`
                ADD COLUMN `location` TEXT AFTER `end_datetime`; 
        ');

        $this->db->query('
            ALTER TABLE `' . $this->db->prefixTable('services') . '`
                ADD COLUMN `location` TEXT AFTER `description`; 
        ');
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        $this->db->query('
            ALTER TABLE `' . $this->db->prefixTable('appointments') . '`
                DROP COLUMN `location`; 
        ');

        $this->db->query('
            ALTER TABLE `' . $this->db->prefixTable('services') . '`
                DROP COLUMN `location`; 
        ');
    }
}
