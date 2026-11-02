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
use App\Models\Customers_Model;

/**
 * Privacy Controller
 *
 * (CI4 port of the CI3 application/controllers/Privacy.php)
 */
class Privacy extends BaseController {
    protected Customers_Model $customers_model;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->customers_model = new Customers_Model;
    }

    /**
     * Remove all customer data (including appointments from the system).
     */
    public function ajax_delete_personal_information()
    {
        try
        {
            $customer_token = $this->request->getPost('customer_token');

            if (empty($customer_token))
            {
                throw new \InvalidArgumentException('Invalid customer token value provided.');
            }

            $customer_id = cache()->get('customer-token-' . $customer_token);

            if (empty($customer_id))
            {
                throw new \InvalidArgumentException('Customer ID could not be found, please reload the page '
                    . 'and try again.');
            }

            $this->customers_model->delete($customer_id);

            $response = [
                'success' => TRUE
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
