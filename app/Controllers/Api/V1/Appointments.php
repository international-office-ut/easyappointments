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
use App\Libraries\Notifications;
use App\Models\Appointments_Model;
use App\Models\Customers_Model;
use App\Models\Providers_Model;
use App\Models\Services_Model;
use App\Models\Settings_Model;
use EA\Engine\Api\V1\Parsers\Appointments as AppointmentsParser;
use EA\Engine\Api\V1\Request;
use EA\Engine\Api\V1\Response;
use EA\Engine\Types\NonEmptyText;

/**
 * Appointments Controller (API v1)
 *
 * (CI4 port of the CI3 application/controllers/api/v1/Appointments.php)
 */
class Appointments extends API_V1_Controller {
    /**
     * Appointments Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\Appointments
     */
    protected $parser;

    protected Appointments_Model $appointments_model;
    protected Services_Model $services_model;
    protected Providers_Model $providers_model;
    protected Customers_Model $customers_model;
    protected $settings_model;
    protected Notifications $notifications;

    /**
     * Class Constructor
     */
    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->appointments_model = new Appointments_Model;
        $this->services_model = new Services_Model;
        $this->providers_model = new Providers_Model;
        $this->customers_model = new Customers_Model;
        $this->settings_model = new Settings_Model;
        $this->notifications = new Notifications;
        $this->parser = new AppointmentsParser;
    }

    /**
     * GET API Method
     *
     * @param int $id Optional (null), the record ID to be returned.
     */
    public function get($id = NULL)
    {
        try
        {
            $where = [
                'is_unavailable' => FALSE
            ];

            if ($id !== NULL)
            {
                $where['id'] = $id;
            }

            $appointments = $this->appointments_model->get_batch($where, NULL, NULL, NULL, array_key_exists('aggregates', $_GET));

            if ($id !== NULL && count($appointments) === 0)
            {
                $this->throw_record_not_found();
            }

            $response = new Response($appointments);

            $response->encode($this->parser)
                ->search()
                ->sort()
                ->paginate()
                ->minimize()
                ->singleEntry($id)
                ->output();

        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }

    /**
     * POST API Method
     */
    public function post()
    {
        try
        {
            // Insert the appointment to the database.
            $request = new Request();
            $appointment = $request->get_body();
            $this->parser->decode($appointment);

            if (isset($appointment['id']))
            {
                unset($appointment['id']);
            }

            // Generate end_datetime based on service duration if this field is not defined
            if ( ! isset($appointment['end_datetime']))
            {
                $service = $this->services_model->get_row($appointment['id_services']);

                if (isset($service['duration']))
                {
                    $end_datetime = new \DateTime($appointment['start_datetime']);
                    $end_datetime->add(new \DateInterval('PT' . $service['duration'] . 'M'));
                    $appointment['end_datetime'] = $end_datetime->format('Y-m-d H:i:s');
                }
            }

            $id = $this->appointments_model->add($appointment);

            $appointment = $this->appointments_model->get_row($id);
            $service = $this->services_model->get_row($appointment['id_services']);
            $provider = $this->providers_model->get_row($appointment['id_users_provider']);
            $customer = $this->customers_model->get_row($appointment['id_users_customer']);
            $settings = [
                'company_name' => $this->settings_model->get_setting('company_name'),
                'company_email' => $this->settings_model->get_setting('company_email'),
                'company_link' => $this->settings_model->get_setting('company_link'),
                'date_format' => $this->settings_model->get_setting('date_format'),
                'time_format' => $this->settings_model->get_setting('time_format')
            ];

            $this->notifications->notify_appointment_saved($appointment, $service, $provider, $customer, $settings, FALSE);

            // Fetch the new object from the database and return it to the client.
            $batch = $this->appointments_model->get_batch(['id' => $id]);
            $response = new Response($batch);
            $status = new NonEmptyText('201 Created');
            $response->encode($this->parser)->singleEntry(TRUE)->output($status);
        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }

    /**
     * PUT API Method
     *
     * @param int $id The record ID to be updated.
     */
    public function put($id)
    {
        try
        {
            // Update the appointment record.
            $batch = $this->appointments_model->get_batch(['id' => $id]);

            if ($id !== NULL && count($batch) === 0)
            {
                $this->throw_record_not_found();
            }

            $request = new Request();
            $updated_appointment = $request->get_body();
            $base_appointment = $batch[0];
            $this->parser->decode($updated_appointment, $base_appointment);
            $updated_appointment['id'] = $id;
            $id = $this->appointments_model->add($updated_appointment);

            $service = $this->services_model->get_row($updated_appointment['id_services']);
            $provider = $this->providers_model->get_row($updated_appointment['id_users_provider']);
            $customer = $this->customers_model->get_row($updated_appointment['id_users_customer']);
            $settings = [
                'company_name' => $this->settings_model->get_setting('company_name'),
                'company_email' => $this->settings_model->get_setting('company_email'),
                'company_link' => $this->settings_model->get_setting('company_link'),
                'date_format' => $this->settings_model->get_setting('date_format'),
                'time_format' => $this->settings_model->get_setting('time_format')
            ];

            $this->notifications->notify_appointment_saved($updated_appointment, $service, $provider, $customer, $settings, TRUE);


            // Fetch the updated object from the database and return it to the client.
            $batch = $this->appointments_model->get_batch(['id' => $id]);
            $response = new Response($batch);
            $response->encode($this->parser)->singleEntry($id)->output();
        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }

    /**
     * DELETE API Method
     *
     * @param int $id The record ID to be deleted.
     */
    public function delete($id)
    {
        try
        {
            $appointment = $this->appointments_model->get_row($id);
            $service = $this->services_model->get_row($appointment['id_services']);
            $provider = $this->providers_model->get_row($appointment['id_users_provider']);
            $customer = $this->customers_model->get_row($appointment['id_users_customer']);
            $settings = [
                'company_name' => $this->settings_model->get_setting('company_name'),
                'company_email' => $this->settings_model->get_setting('company_email'),
                'company_link' => $this->settings_model->get_setting('company_link'),
                'date_format' => $this->settings_model->get_setting('date_format'),
                'time_format' => $this->settings_model->get_setting('time_format')
            ];

            $this->appointments_model->delete($id);

            $this->notifications->notify_appointment_deleted($appointment, $service, $provider, $customer, $settings);

            $response = new Response([
                'code' => 200,
                'message' => 'Record was deleted successfully!'
            ]);

            $response->output();
        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }
}
