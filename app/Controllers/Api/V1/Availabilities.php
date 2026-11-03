<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

namespace App\Controllers\Api\V1;

use Exception;
use App\Libraries\Availability;
use App\Models\Appointments_Model;
use App\Models\Providers_Model;
use App\Models\Services_Model;

/**
 * Availabilities Controller (API v1)
 *

 */
class Availabilities extends API_V1_Controller {
    protected Appointments_Model $appointments_model;
    protected Providers_Model $providers_model;
    protected Services_Model $services_model;
    protected Availability $availability;

    /**
     * Class Constructor
     */
    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->appointments_model = new Appointments_Model;
        $this->providers_model = new Providers_Model;
        $this->services_model = new Services_Model;
        $this->availability = new Availability;
    }

    /**
     * GET API Method
     *
     * Provide the "providerId", "serviceId" and "date" GET parameters to get the availabilities for a specific date.
     * If no "date" was provided then the current date will be used.
     */
    public function get()
    {
        try
        {
            $provider_id = $this->request->getGet('providerId');

            $service_id = $this->request->getGet('serviceId');

            $date = $this->request->getGet('date');

            if ( ! $date)
            {
                $date = date('Y-m-d');
            }

            $provider = $this->providers_model->get_row($provider_id);

            $service = $this->services_model->get_row($service_id);

            $available_hours = $this->availability->get_available_hours($date, $service, $provider);

            return $this->response
                ->setContentType('application/json')
                ->setBody(json_encode($available_hours));
        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }
}
