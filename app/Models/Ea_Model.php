<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

namespace App\Models;

use CodeIgniter\BaseModel;

/**
 * Easy!Appointments model base: ports the CI3 EA_Model to CI4.
 *
 * Deliberately extends BaseModel (not Model): the CI3 model methods carry
 * names like insert()/update() that would collide with the CRUD API of the
 * CI4 Model class, so each EA model keeps its own methods and uses
 * $this->db->table(...) query-builder chains directly.
 */
class Ea_Model extends BaseModel {
    /**
     * EA_Model constructor.
     */
    public function __construct()
    {
        $this->db = db_connect();
    }
}
