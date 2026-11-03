<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.3.2
 * ---------------------------------------------------------------------------- */

namespace App\Controllers;

use Exception;
use App\Models\Consents_Model;

/**
 * Consents Controller
 *
 * Handles user consent related operations.
 *
 * (CI4 port of the CI3 application/controllers/Consents.php)
 */
class Consents extends BaseController {
    protected Consents_Model $consents_model;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->consents_model = new Consents_Model;
    }

    /**
     * Save the user's consent.
     */
    public function ajax_save_consent()
    {
        try
        {
            $consent = $this->request->getPost('consent');

            // Only the consent fields are accepted, a posted "id" must never reach the
            // update path of the model.
            $consent = array_intersect_key($consent,
                array_flip(['first_name', 'last_name', 'email', 'type']));

            $consent['ip'] = $this->request->getIPAddress();

            $consent['id'] = $this->consents_model->add($consent);

            $response = [
                'success' => TRUE,
                'id' => $consent['id']
            ];
        }
        catch (Exception $exception)
        {
            $this->response->setStatusCode(500);

            $response = $this->ajax_exception($exception);
        }

        return $this->response->setContentType('application/json')->setBody(json_encode($response));
    }
}
