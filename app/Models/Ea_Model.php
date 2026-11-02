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

use Exception;

use CodeIgniter\Database\BaseConnection;

/**
 * Easy!Appointments model base: ports the CI3 EA_Model to CI4.
 *
 * Deliberately standalone (not extending the CI4 BaseModel/Model): the CI3
 * model methods carry names like insert()/update() and protected visibility
 * that would collide with the CRUD API of the CI4 classes, so each EA model
 * keeps its own methods and drives $this->db->table(...) chains directly.
 *
 * @property BaseConnection $db
 */
class Ea_Model {
    /**
     * @var BaseConnection
     */
    protected $db;

    /**
     * EA_Model constructor.
     */
    public function __construct()
    {
        $this->db = db_connect();
    }
}
