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

namespace App\Libraries;

use Exception;

/**
 * Email framework bridge for the CI4 stack.
 *
 * The EA\Engine\Notifications\Email client keeps its CI3-style touch points
 * ($bridge->load->helper(...), $bridge->load->view(...), $bridge->timezones)
 * so that both stacks work with the same engine code. Under CI4, the bridge
 * maps those calls onto the CI4 helpers and services.
 */
class Notifications_Bridge {
    /**
     * @var \App\Libraries\Timezones
     */
    public $timezones;

    /**
     * @var object An object that carries the CI3-style "load" surface.
     */
    public $load;

    /**
     * @var array The CI3-style email configuration map.
     */
    public $config = [];

    /**
     * Notifications_Bridge constructor.
     *
     * @param array $email_config CI3-shaped email configuration values.
     */
    public function __construct(array $email_config)
    {
        $this->timezones = new Timezones;
        $this->config = $email_config;
        $this->load = new class {
            public function __get($name)
            {
                return NULL;
            }

            public function helper(string $name)
            {
                // CI4 helpers are global, there is nothing to load.
            }

            public function view(string $view, array $data = [], $return = TRUE)
            {
                return view($view, $data);
            }
        };
    }
}
