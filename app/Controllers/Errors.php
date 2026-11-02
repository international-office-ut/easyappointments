<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

namespace App\Controllers;

use App\Models\Settings_Model;

/**
 * Errors Controller
 *
 * (CI4 port of the CI3 application/controllers/Errors.php)
 */
class Errors extends BaseController {
    protected Settings_Model $settings_model;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->settings_model = new Settings_Model;
    }

    /**
     * Display the 404 error page.
     */
    public function index()
    {
        return $this->error404();
    }

    /**
     * Display the 404 error page.
     */
    public function error404()
    {
        $view['company_name'] = $this->settings_model->get_setting('company_name');

        return view('general/error404', $view);
    }
}
