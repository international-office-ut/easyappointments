<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

namespace App\Controllers\Api\V1;

use Exception;
use App\Models\User_Model;

/**
 * CI3-shaped authorization bridge for the CI4 stack.
 *
 * The EA\Engine\Api\V1\Authorization client keeps its CI3-style touch points
 * ($bridge->load->model('user_model') and $bridge->user_model), so that both
 * stacks work with the same engine code. Under CI4 the bridge resolves those
 * calls onto the App\Models classes.
 */
class Api_Auth_Bridge {
    /**
     * @var object An object that carries the CI3-style "load" surface.
     */
    public $load;

    /**
     * Api_Auth_Bridge constructor.
     */
    public function __construct()
    {
        $this->load = new class {
            public function __get($name)
            {
                return NULL;
            }

            public function model(string $name)
            {
                // CI4 resolves models through the bridge's magic properties — the
                // call only exists for CI3 parity here.
            }
        };
    }

    /**
     * Resolve the models the engine authorization touches (magic property).
     */
    public function __get($name)
    {
        if ($name === 'user_model')
        {
            static $user_model = NULL;

            if ($user_model === NULL)
            {
                $user_model = new User_Model;
            }

            return $user_model;
        }

        return NULL;
    }
}
