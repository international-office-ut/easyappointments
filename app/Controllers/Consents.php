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

            $response = [
                'message' => $exception->getMessage(),
                'trace' => ea_config('debug') ? $exception->getTrace() : []
            ];
        }

        return $this->response->setContentType('application/json')->setBody(json_encode($response));
    }
}
