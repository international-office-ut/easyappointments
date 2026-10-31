<?php defined('BASEPATH') or exit('No direct script access allowed');

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

use EA\Engine\Notifications\Email as EmailClient;
use EA\Engine\Search\Fuzzy;
use EA\Engine\Types\Email;
use EA\Engine\Types\Text;

/**
 * Backend API Controller
 *
 * Contains all the backend AJAX callbacks.
 *
 * @package Controllers
 */
class Backend_api extends EA_Controller {
    /**
     * @var array
     */
    protected $privileges;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('admins_model');
        $this->load->model('appointments_model');
        $this->load->model('consents_model');
        $this->load->model('customers_model');
        $this->load->model('providers_model');
        $this->load->model('roles_model');
        $this->load->model('secretaries_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');
        $this->load->model('user_model');
        $this->load->library('ics_file');
        $this->load->library('notifications');
        $this->load->library('timezones');

        if ($this->session->userdata('role_slug'))
        {
            $this->privileges = $this->roles_model->get_privileges($this->session->userdata('role_slug'));
        }
    }

    /**
     * Provider scope of the current session role.
     *
     * Providers may only operate on their own records and secretaries only on the records of
     * their bound providers, so that foreign record ids can never be used from these sessions.
     *
     * @return array|NULL Returns the provider ids in scope or NULL when all providers are in
     * scope (admin).
     */
    protected function get_provider_scope(): ?array
    {
        $role_slug = $this->session->userdata('role_slug');

        if ($role_slug === DB_SLUG_PROVIDER)
        {
            return [$this->session->userdata('user_id')];
        }

        if ($role_slug === DB_SLUG_SECRETARY)
        {
            return $this->secretaries_model->get_row($this->session->userdata('user_id'))['providers'];
        }

        return NULL;
    }

    /**
     * Throw an exception when the provider id is not in the current session's scope.
     *
     * @param int $provider_id The provider id that is about to be accessed.
     */
    protected function assert_provider_in_scope($provider_id)
    {
        $provider_scope = $this->get_provider_scope();

        if ($provider_scope === NULL)
        {
            return;
        }

        if ( ! in_array((int) $provider_id, array_map('intval', $provider_scope), TRUE))
        {
            throw new Exception('The record does not belong to your provider scope.');
        }
    }

    /**
     * Keep only the appointment rows that belong to the current session's provider scope.
     *
     * The returned array is re-indexed, so that no filtered response can ever contain sparse
     * keys (they would break the JSON list contract of the frontend).
     *
     * @param array $rows Appointment rows to be filtered.
     *
     * @return array Returns the in-scope rows.
     */
    protected function scope_appointment_rows(array $rows): array
    {
        $provider_scope = $this->get_provider_scope();

        if ($provider_scope === NULL)
        {
            return $rows;
        }

        $scoped_rows = [];

        foreach ($rows as $row)
        {
            if (in_array((int) $row['id_users_provider'], array_map('intval', $provider_scope), TRUE))
            {
                $scoped_rows[] = $row;
            }
        }

        return $scoped_rows;
    }

    /**
     * Get Calendar Events
     *
     * This method will return all the calendar events within a specified period.
     */
    public function ajax_get_calendar_events()
    {
        try
        {
            // This endpoint would expose the whole schedule with customer data, so it must
            // never respond to requests that do not come from any logged in user.
            if ($this->privileges[PRIV_APPOINTMENTS]['view'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $user_id = $this->session->userdata('user_id');
            $role_slug = $this->session->userdata('role_slug');

            $start_date = $this->input->post('startDate') . ' 00:00:00';
            $end_date = $this->input->post('endDate') . ' 23:59:59';

            $where = [
                'start_datetime >=' => $start_date,
                'end_datetime <=' => $end_date
            ];

            // Providers may only see their own events and secretaries only the events of the
            // providers they are assigned to, so the filter needs to happen already in SQL.
            if ($role_slug === DB_SLUG_PROVIDER)
            {
                $where['id_users_provider'] = $user_id;
            }

            if ($role_slug === DB_SLUG_SECRETARY)
            {
                $providers = $this->secretaries_model->get_row($user_id)['providers'];

                // Secretaries without any provider assignment cannot be filtered with an
                // empty IN clause, so they directly get an empty response.
                if (empty($providers))
                {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'appointments' => [],
                            'unavailability_events' => []
                        ]));
                }

                $where['id_users_provider'] = $providers;
            }

            $response = [
                'appointments' => $this->appointments_model->get_batch(
                    array_merge($where, ['is_unavailable' => FALSE])),
                'unavailability_events' => $this->appointments_model->get_batch(
                    array_merge($where, ['is_unavailable' => TRUE]))
            ];

            foreach ($response['appointments'] as &$appointment)
            {
                $appointment['provider'] = $this->providers_model->get_row($appointment['id_users_provider']);
                $appointment['service'] = $this->services_model->get_row($appointment['id_services']);
                $appointment['customer'] = $this->customers_model->get_row($appointment['id_users_customer']);
            }
            unset ($appointment);
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Get the registered appointments for the given date period and record.
     *
     * This method returns the database appointments and unavailable periods for the
     * user selected date period and record type (provider or service).
     */
    public function ajax_get_calendar_appointments()
    {
        try
        {
            if ($this->privileges[PRIV_APPOINTMENTS]['view'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            if ( ! $this->input->post('filter_type'))
            {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['appointments' => []]));
                return;
            }

            if ($this->input->post('filter_type') == FILTER_TYPE_PROVIDER)
            {
                $where_id = 'id_users_provider';
            }
            else
            {
                $where_id = 'id_services';
            }

            // Get appointments
            $record_id = $this->db->escape($this->input->post('record_id'));
            $start_date = $this->db->escape($this->input->post('start_date'));
            $end_date = $this->db->escape(date('Y-m-d', strtotime($this->input->post('end_date') . ' +1 day')));

            // Providers and secretaries may not request the calendar of another provider.
            if ($this->input->post('filter_type') == FILTER_TYPE_PROVIDER)
            {
                $this->assert_provider_in_scope($this->input->post('record_id'));
            }

            $where_clause = $where_id . ' = ' . $record_id . '
                AND ((start_datetime > ' . $start_date . ' AND start_datetime < ' . $end_date . ') 
                or (end_datetime > ' . $start_date . ' AND end_datetime < ' . $end_date . ') 
                or (start_datetime <= ' . $start_date . ' AND end_datetime >= ' . $end_date . ')) 
                AND is_unavailable = 0
            ';

            // Service filters span multiple providers, so the rows must be scoped for roles
            // that are only allowed to work with a subset of the providers.
            $response['appointments'] = $this->scope_appointment_rows(
                $this->appointments_model->get_batch($where_clause));

            foreach ($response['appointments'] as &$appointment)
            {
                $appointment['provider'] = $this->providers_model->get_row($appointment['id_users_provider']);
                $appointment['service'] = $this->services_model->get_row($appointment['id_services']);
                $appointment['customer'] = $this->customers_model->get_row($appointment['id_users_customer']);
            }

            // Get unavailable periods (only for provider).
            $response['unavailables'] = [];

            if ($this->input->post('filter_type') == FILTER_TYPE_PROVIDER)
            {
                $where_clause = $where_id . ' = ' . $record_id . '
                    AND ((start_datetime > ' . $start_date . ' AND start_datetime < ' . $end_date . ') 
                    or (end_datetime > ' . $start_date . ' AND end_datetime < ' . $end_date . ') 
                    or (start_datetime <= ' . $start_date . ' AND end_datetime >= ' . $end_date . ')) 
                    AND is_unavailable = 1
                ';

                $response['unavailables'] = $this->scope_appointment_rows(
                    $this->appointments_model->get_batch($where_clause));
            }

            foreach ($response['unavailables'] as &$unavailable)
            {
                $unavailable['provider'] = $this->providers_model->get_row($unavailable['id_users_provider']);
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Save appointment changes that are made from the backend calendar page.
     */
    public function ajax_save_appointment()
    {
        try
        {
            // Save customer changes to the database.
            if ($this->input->post('customer_data'))
            {
                $customer = json_decode($this->input->post('customer_data'), TRUE);

                $required_privileges = ( ! isset($customer['id']))
                    ? $this->privileges[PRIV_CUSTOMERS]['add']
                    : $this->privileges[PRIV_CUSTOMERS]['edit'];
                if ($required_privileges == FALSE)
                {
                    throw new Exception('You do not have the required privileges for this task.');
                }

                $customer['id'] = $this->customers_model->add($customer);
            }

            // Save appointment changes to the database.
            if ($this->input->post('appointment_data'))
            {
                $appointment = json_decode($this->input->post('appointment_data'), TRUE);

                $required_privileges = ( ! isset($appointment['id']))
                    ? $this->privileges[PRIV_APPOINTMENTS]['add']
                    : $this->privileges[PRIV_APPOINTMENTS]['edit'];
                if ($required_privileges == FALSE)
                {
                    throw new Exception('You do not have the required privileges for this task.');
                }

                $manage_mode = isset($appointment['id']);

                // Existing appointments may only be changed by roles that can also work with
                // the provider of the appointment (no foreign reassignment from other roles).
                if ($manage_mode)
                {
                    $existing_appointment = $this->appointments_model->get_row($appointment['id']);

                    $this->assert_provider_in_scope($existing_appointment['id_users_provider']);
                }

                $this->assert_provider_in_scope($appointment['id_users_provider']);

                // If the appointment does not contain the customer record id, then it means that is is going to be
                // inserted. Get the customer's record ID.
                if ( ! isset($appointment['id_users_customer']))
                {
                    $appointment['id_users_customer'] = $customer['id'];
                }

                $appointment['id'] = $this->appointments_model->add($appointment);
            }

            $appointment = $this->appointments_model->get_row($appointment['id']);
            $provider = $this->providers_model->get_row($appointment['id_users_provider']);
            $customer = $this->customers_model->get_row($appointment['id_users_customer']);
            $service = $this->services_model->get_row($appointment['id_services']);

            $settings = [
                'company_name' => $this->settings_model->get_setting('company_name'),
                'company_link' => $this->settings_model->get_setting('company_link'),
                'company_email' => $this->settings_model->get_setting('company_email'),
                'date_format' => $this->settings_model->get_setting('date_format'),
                'time_format' => $this->settings_model->get_setting('time_format')
            ];

            $this->notifications->notify_appointment_saved($appointment, $service, $provider, $customer, $settings, $manage_mode);

            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Delete appointment from the database.
     *
     * This method deletes an existing appointment from the database. Once this action is finished it cannot be undone.
     * Notification emails are send to both provider and customer after the delete action is completed.
     */
    public function ajax_delete_appointment()
    {
        try
        {
            if ($this->privileges[PRIV_APPOINTMENTS]['delete'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            if ( ! $this->input->post('appointment_id'))
            {
                throw new Exception('No appointment id provided.');
            }

            // Store appointment data for later use in this method.
            $appointment = $this->appointments_model->get_row($this->input->post('appointment_id'));
            $this->assert_provider_in_scope($appointment['id_users_provider']);
            $provider = $this->providers_model->get_row($appointment['id_users_provider']);
            $customer = $this->customers_model->get_row($appointment['id_users_customer']);
            $service = $this->services_model->get_row($appointment['id_services']);

            $settings = [
                'company_name' => $this->settings_model->get_setting('company_name'),
                'company_email' => $this->settings_model->get_setting('company_email'),
                'company_link' => $this->settings_model->get_setting('company_link'),
                'date_format' => $this->settings_model->get_setting('date_format'),
                'time_format' => $this->settings_model->get_setting('time_format')
            ];

            // Delete appointment record from the database.
            $this->appointments_model->delete($this->input->post('appointment_id'));

            // Send notification emails to provider and customer.
            try
            {
                $this->config->load('email');

                $email = new EmailClient($this, $this->config->config);

                $send_provider = $this->providers_model
                    ->get_setting('notifications', $provider['id']);

                if ((bool)$send_provider === TRUE)
                {
                    $email->send_delete_appointment($appointment, $provider,
                        $service, $customer, $settings, new Email($provider['email']),
                        new Text($this->input->post('delete_reason')));
                }

                $send_customer = $this->settings_model->get_setting('customer_notifications');

                if ((bool)$send_customer === TRUE)
                {
                    $email->send_delete_appointment($appointment, $provider,
                        $service, $customer, $settings, new Email($customer['email']),
                        new Text($this->input->post('delete_reason')));
                }

                // Notify admins
                $admins = $this->admins_model->get_batch();

                foreach ($admins as $admin)
                {
                    if ( ! $admin['settings']['notifications'] === '0')
                    {
                        continue;
                    }

                    $email->send_delete_appointment($appointment, $provider,
                        $service, $customer, $settings, new Email($admin['email']),
                        new Text($this->input->post('cancel_reason')));
                }

                // Notify secretaries
                $secretaries = $this->secretaries_model->get_batch();

                foreach ($secretaries as $secretary)
                {
                    if ( ! $secretary['settings']['notifications'] === '0')
                    {
                        continue;
                    }

                    if (in_array($provider['id'], $secretary['providers']))
                    {
                        continue;
                    }

                    $email->send_delete_appointment($appointment, $provider,
                        $service, $customer, $settings, new Email($secretary['email']),
                        new Text($this->input->post('cancel_reason')));
                }
            }
            catch (Exception $exception)
            {
                $warnings[] = [
                    'message' => $exception->getMessage(),
                    'trace' => config('debug') ? $exception->getTrace() : []
                ];
            }

            if (empty($warnings))
            {
                $response = AJAX_SUCCESS;
            }
            else
            {
                $response = ['warnings' => $warnings];
            }
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Filter the customer records with the given key string.
     *
     * Outputs the search results.
     */
    public function ajax_filter_customers()
    {
        try
        {
            if ($this->privileges[PRIV_CUSTOMERS]['view'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $key = (string) $this->input->post('key');

            $limit = (int) ($this->input->post('limit') ?: 1000);

            $customers = $this->customers_model->get_batch(NULL, Fuzzy::MAX_CANDIDATES, NULL,
                'first_name ASC, last_name ASC');

            $customers = Fuzzy::filter($customers, [
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'address',
                'city',
                'zip_code',
                'notes'
            ], $key, $limit);

            foreach ($customers as &$customer)
            {
                $appointments = $this->appointments_model->get_batch(['id_users_customer' => $customer['id']]);

                // Roles with a provider scope must not see the appointment history with the
                // providers that are outside of their scope.
                $appointments = $this->scope_appointment_rows($appointments);

                foreach ($appointments as &$appointment)
                {
                    $appointment['service'] = $this->services_model->get_row($appointment['id_services']);
                    $appointment['provider'] = $this->providers_model->get_row($appointment['id_users_provider']);
                }

                $customer['appointments'] = $appointments;
            }

            $response = $customers;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Insert of update unavailable time period to database.
     */
    public function ajax_save_unavailable()
    {
        try
        {
            // Check privileges
            $unavailable = json_decode($this->input->post('unavailable'), TRUE);

            $required_privileges = ( ! isset($unavailable['id']))
                ? $this->privileges[PRIV_APPOINTMENTS]['add']
                : $this->privileges[PRIV_APPOINTMENTS]['edit'];
            if ($required_privileges == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            // Unavailable periods may point to another provider on update, so both the final
            // provider and the original record's provider need to be in scope.
            $this->assert_provider_in_scope($unavailable['id_users_provider']);

            if (isset($unavailable['id']))
            {
                $this->assert_provider_in_scope(
                    $this->appointments_model->get_row($unavailable['id'])['id_users_provider']);
            }

            $provider = $this->providers_model->get_row($unavailable['id_users_provider']);

            // Add appointment
            $unavailable['id'] = $this->appointments_model->add_unavailable($unavailable);
            $unavailable = $this->appointments_model->get_row($unavailable['id']); // fetch all inserted data

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(AJAX_SUCCESS));
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Delete an unavailable time period from database.
     */
    public function ajax_delete_unavailable()
    {
        try
        {
            if ($this->privileges[PRIV_APPOINTMENTS]['delete'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $unavailable = $this->appointments_model->get_row($this->input->post('unavailable_id'));
            $this->assert_provider_in_scope($unavailable['id_users_provider']);
            $provider = $this->providers_model->get_row($unavailable['id_users_provider']);

            // Delete unavailable
            $this->appointments_model->delete_unavailable($unavailable['id']);

            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Insert of update working plan exceptions to database.
     */
    public function ajax_save_working_plan_exception()
    {
        try
        {
            // Check privileges
            $required_privileges = $this->privileges[PRIV_USERS]['edit'];

            if ($required_privileges == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $date = $this->input->post('date');
            $working_plan_exception = $this->input->post('working_plan_exception');
            $provider_id = $this->input->post('provider_id');

            // Provider or secretary sessions may only change their own working plan exceptions.
            $this->assert_provider_in_scope($provider_id);

            $success = $this->providers_model->save_working_plan_exception($date, $working_plan_exception, $provider_id);

            if ($success)
            {
                $response = AJAX_SUCCESS;
            }
            else
            {
                $response = ['warnings' => 'Error on saving working plan exception.'];
            }
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Delete an working plan exceptions time period to database.
     */
    public function ajax_delete_working_plan_exception()
    {
        try
        {
            // Check privileges
            $required_privileges = $this->privileges[PRIV_USERS]['edit'];

            if ($required_privileges == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $date = $this->input->post('date');
            $provider_id = $this->input->post('provider_id');

            // Provider or secretary sessions may only change their own working plan exceptions.
            $this->assert_provider_in_scope($provider_id);

            $success = $this->providers_model->delete_working_plan_exception($date, $provider_id);

            if ($success)
            {
                $response = AJAX_SUCCESS;
            }
            else
            {
                $response = ['warnings' => 'Error on deleting working plan exception.'];
            }
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Save (insert or update) a customer record.
     */
    public function ajax_save_customer()
    {
        try
        {
            $customer = json_decode($this->input->post('customer'), TRUE);

            $required_privileges = ( ! isset($customer['id']))
                ? $this->privileges[PRIV_CUSTOMERS]['add']
                : $this->privileges[PRIV_CUSTOMERS]['edit'];
            if ($required_privileges == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $customer_id = $this->customers_model->add($customer);

            $response = [
                'status' => AJAX_SUCCESS,
                'id' => $customer_id
            ];
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Delete customer from database.
     */
    public function ajax_delete_customer()
    {
        try
        {
            if ($this->privileges[PRIV_CUSTOMERS]['delete'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $this->customers_model->delete($this->input->post('customer_id'));

            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Save (insert or update) service record.
     */
    public function ajax_save_service()
    {
        try
        {
            $service = json_decode($this->input->post('service'), TRUE);

            $required_privileges = ( ! isset($service['id']))
                ? $this->privileges[PRIV_SERVICES]['add']
                : $this->privileges[PRIV_SERVICES]['edit'];
            if ($required_privileges == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $service_id = $this->services_model->add($service);
            $response = [
                'status' => AJAX_SUCCESS,
                'id' => $service_id
            ];
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Delete service record from database.
     */
    public function ajax_delete_service()
    {
        try
        {
            if ($this->privileges[PRIV_SERVICES]['delete'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $result = $this->services_model->delete($this->input->post('service_id'));

            $response = $result ? AJAX_SUCCESS : AJAX_FAILURE;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Filter service records by given key string.
     */
    public function ajax_filter_services()
    {
        try
        {
            if ($this->privileges[PRIV_SERVICES]['view'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $key = (string) $this->input->post('key');

            $services = $this->services_model->get_batch(NULL, Fuzzy::MAX_CANDIDATES);

            $services = Fuzzy::filter($services, [
                'name',
                'duration',
                'price',
                'currency',
                'description'
            ], $key);

            $response = $this->services_model
                ->attach_weekday_modes($services);
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Save (insert or update) category record.
     */
    public function ajax_save_service_category()
    {
        try
        {
            $category = json_decode($this->input->post('category'), TRUE);

            $required_privileges = ( ! isset($category['id']))
                ? $this->privileges[PRIV_SERVICES]['add']
                : $this->privileges[PRIV_SERVICES]['edit'];
            if ($required_privileges == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $category_id = $this->services_model->add_category($category);

            $response = [
                'status' => AJAX_SUCCESS,
                'id' => $category_id
            ];
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Delete category record from database.
     */
    public function ajax_delete_service_category()
    {
        try
        {
            if ($this->privileges[PRIV_SERVICES]['delete'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $result = $this->services_model->delete_category($this->input->post('category_id'));

            $response = $result ? AJAX_SUCCESS : AJAX_FAILURE;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Filter services categories with key string.
     */
    public function ajax_filter_service_categories()
    {
        try
        {
            if ($this->privileges[PRIV_SERVICES]['view'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $key = (string) $this->input->post('key');

            $categories = $this->services_model->get_all_categories(NULL, Fuzzy::MAX_CANDIDATES);

            $response = Fuzzy::filter($categories, ['name', 'description'], $key);
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Filter admin records with string key.
     */
    public function ajax_filter_admins()
    {
        try
        {
            if ($this->privileges[PRIV_USERS]['view'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $key = (string) $this->input->post('key');

            $response = Fuzzy::filter($this->admins_model->get_batch(NULL, Fuzzy::MAX_CANDIDATES), [
                'first_name',
                'last_name',
                'email',
                'mobile_number',
                'phone_number',
                'address',
                'city',
                'state',
                'zip_code',
                'notes'
            ], $key);
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Save (insert or update) admin record into database.
     */
    public function ajax_save_admin()
    {
        try
        {
            $admin = json_decode($this->input->post('admin'), TRUE);

            $required_privileges = ( ! isset($admin['id']))
                ? $this->privileges[PRIV_USERS]['add']
                : $this->privileges[PRIV_USERS]['edit'];
            if ($required_privileges == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $admin_id = $this->admins_model->add($admin);

            $response = [
                'status' => AJAX_SUCCESS,
                'id' => $admin_id
            ];
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Delete an admin record from the database.
     */
    public function ajax_delete_admin()
    {
        try
        {
            if ($this->privileges[PRIV_USERS]['delete'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $result = $this->admins_model->delete($this->input->post('admin_id'));

            $response = $result ? AJAX_SUCCESS : AJAX_FAILURE;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Filter provider records with string key.
     */
    public function ajax_filter_providers()
    {
        try
        {
            if ($this->privileges[PRIV_USERS]['view'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $key = (string) $this->input->post('key');

            $response = Fuzzy::filter($this->providers_model->get_batch(NULL, Fuzzy::MAX_CANDIDATES), [
                'first_name',
                'last_name',
                'email',
                'mobile_number',
                'phone_number',
                'address',
                'city',
                'state',
                'zip_code',
                'notes'
            ], $key);
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Save (insert or update) a provider record into database.
     */
    /**
     * Return whether the provider owns an active calendar feed token (URL never returned
     * again after the generation).
     */
    public function ajax_ical_feed_state()
    {
        try
        {
            $provider_id = (int)$this->input->post('provider_id');

            if ( ! $this->is_ical_feed_allowed($provider_id))
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $response = [
                'active' => $this->providers_model->has_feed_token($provider_id)
            ];
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Generate (or rotate) the calendar feed token of a provider.
     *
     * The plaintext token is returned exactly once — the provider copies the feed URL now
     * and the app only stores the token hash afterwards.
     */
    public function ajax_generate_ical_feed()
    {
        try
        {
            $provider_id = (int)$this->input->post('provider_id');

            if ( ! $this->is_ical_feed_allowed($provider_id))
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $token = $this->providers_model->generate_feed_token($provider_id);

            $response = [
                'feed_url' => site_url('ical/' . $token . '.ics')
            ];
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Revoke the calendar feed token of a provider (the subscribed feed dies immediately).
     */
    public function ajax_revoke_ical_feed()
    {
        try
        {
            $provider_id = (int)$this->input->post('provider_id');

            if ( ! $this->is_ical_feed_allowed($provider_id))
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $this->providers_model->revoke_feed_token($provider_id);

            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Feed management is allowed for the provider itself and for users with the users-edit
     * privilege.
     */
    protected function is_ical_feed_allowed(int $provider_id): bool
    {
        $is_self = (int)$this->session->userdata('user_id') === $provider_id;

        if ($is_self)
        {
            return TRUE;
        }

        return $this->privileges[PRIV_USERS]['edit'] == FALSE ? FALSE : TRUE;
    }

    public function ajax_save_provider()
    {
        try
        {
            $provider = json_decode($this->input->post('provider'), TRUE);

            $required_privileges = ( ! isset($provider['id']))
                ? $this->privileges[PRIV_USERS]['add']
                : $this->privileges[PRIV_USERS]['edit'];
            if ($required_privileges == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            if ( ! isset($provider['settings']['working_plan']))
            {
                $provider['settings']['working_plan'] = $this->settings_model
                    ->get_setting('company_working_plan');
            }

            $provider_id = $this->providers_model->add($provider);

            $response = [
                'status' => AJAX_SUCCESS,
                'id' => $provider_id
            ];
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Trigger the maintenance cleanup based on the data retention setting.
     *
     * The routine removes appointments, unavailable periods and customers that are older than
     * the record retention period. Add the "dry" parameter when a run should only report the
     * numbers without deleting anything.
     */
    public function ajax_maintain()
    {
        try
        {
            if ($this->privileges[PRIV_SYSTEM_SETTINGS]['edit'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $this->load->model('maintenance_model');

            $dry_run = filter_var($this->input->post('dry_run'), FILTER_VALIDATE_BOOLEAN);

            $days = (int)$this->settings_model->get_setting('record_retention_days');

            if (empty($days))
            {
                $days = (int)$this->settings_model->get_setting('record_retention_months') * 30 ?: 90;
            }

            $this->db->trans_begin();

            $appointments = $this->maintenance_model->purge_appointments($days, $dry_run);
            $provider_blocks = $this->maintenance_model->purge_provider_blocks($days, $dry_run);
            $customers = $this->maintenance_model->purge_customers_without_appointments($dry_run);

            if ($dry_run)
            {
                $this->db->trans_rollback();
            }

            if ($this->db->trans_status() === FALSE && ! $dry_run)
            {
                $this->db->trans_rollback();
                throw new Exception('Could not execute the maintenance cleanup.');
            }

            if ( ! $dry_run)
            {
                $this->db->trans_complete();
            }

            $response = [
                'dry_run' => $dry_run,
                'retention_days' => $days,
                'appointments' => $appointments,
                'provider_blocks' => $provider_blocks,
                'customers' => $customers
            ];
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Delete a provider record from the database.
     */
    public function ajax_delete_provider()
    {
        try
        {
            if ($this->privileges[PRIV_USERS]['delete'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $result = $this->providers_model->delete($this->input->post('provider_id'));

            $response = $result ? AJAX_SUCCESS : AJAX_FAILURE;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Filter secretary records with string key.
     */
    public function ajax_filter_secretaries()
    {
        try
        {
            if ($this->privileges[PRIV_USERS]['view'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $key = (string) $this->input->post('key');

            $response = Fuzzy::filter($this->secretaries_model->get_batch(NULL, Fuzzy::MAX_CANDIDATES), [
                'first_name',
                'last_name',
                'email',
                'mobile_number',
                'phone_number',
                'address',
                'city',
                'state',
                'zip_code',
                'notes'
            ], $key);
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Save (insert or update) a secretary record into database.
     */
    public function ajax_save_secretary()
    {
        try
        {
            $secretary = json_decode($this->input->post('secretary'), TRUE);

            $required_privileges = ( ! isset($secretary['id']))
                ? $this->privileges[PRIV_USERS]['add']
                : $this->privileges[PRIV_USERS]['edit'];
            if ($required_privileges == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $secretary_id = $this->secretaries_model->add($secretary);

            $response = [
                'status' => AJAX_SUCCESS,
                'id' => $secretary_id
            ];
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Delete a secretary record from the database.
     */
    public function ajax_delete_secretary()
    {
        try
        {
            if ($this->privileges[PRIV_USERS]['delete'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $result = $this->secretaries_model->delete($this->input->post('secretary_id'));

            $response = $result ? AJAX_SUCCESS : AJAX_FAILURE;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Save a setting or multiple settings in the database.
     */
    public function ajax_save_settings()
    {
        try
        {
            if ($this->input->post('type') == SETTINGS_SYSTEM)
            {
                if ($this->privileges[PRIV_SYSTEM_SETTINGS]['edit'] == FALSE)
                {
                    throw new Exception('You do not have the required privileges for this task.');
                }

                $settings = json_decode($this->input->post('settings', FALSE), TRUE);

                $this->settings_model->save_settings($settings);
            }
            else
            {
                if ($this->input->post('type') == SETTINGS_USER)
                {
                    if ($this->privileges[PRIV_USER_SETTINGS]['edit'] == FALSE)
                    {
                        throw new Exception('You do not have the required privileges for this task.');
                    }

                    $settings = json_decode($this->input->post('settings'), TRUE);

                    // Users are only allowed to save their own user row, otherwise roles that
                    // hold the user-settings edit slot could take over any other account.
                    if ((int) $settings['id'] !== (int) $this->session->userdata('user_id'))
                    {
                        throw new Exception('You cannot update the settings of another user.');
                    }

                    $this->user_model->save_user($settings);

                    $this->session->set_userdata([
                        'user_email' => $settings['email'],
                        'username' => $settings['settings']['username'],
                        'timezone' => $settings['timezone'],
                    ]);
                }
            }

            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * This method checks whether the username already exists in the database.
     */
    /**
     * Return the identity information of a person that already exists under a different role.
     *
     * Used by the user management pages when adding a new role row to an existing person:
     * the form gets pre-filled with the common fields of the existing identity row, so the
     * same email/login name holder does not have to be typed in again.
     *
     * Required POST Parameters:
     *
     * - string $_POST['email'] Email address of the (prospective) identity.
     * - string $_POST['role_slug'] Role of the new row (admins/providers/secretaries).
     */
    public function ajax_get_identity_info()
    {
        try
        {
            if ($this->privileges[PRIV_USERS]['edit'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $email = (string) $this->input->post('email');
            $role_slug = (string) $this->input->post('role_slug');

            if ($email === '' || $role_slug === '')
            {
                throw new Exception('Email and role are required for the identity lookup.');
            }

            $this->load->model('identities_model');

            if ( ! $this->identities_model->exists_in_other_role($email, $role_slug))
            {
                $response = ['exists' => FALSE];
            }
            else
            {
                $sibling = $this->db
                    ->select('users.*, roles.slug AS role_slug, '
                        . 'user_settings.username AS login_name')
                    ->from('users')
                    ->join('roles', 'roles.id = users.id_roles', 'inner')
                    ->join('user_settings', 'user_settings.id_users = users.id', 'left')
                    ->where('users.email', $email)
                    ->where('roles.slug !=', $role_slug)
                    ->order_by('users.id', 'ASC')
                    ->limit(1)
                    ->get()
                    ->row_array();

                // Address and free-text fields are intentionally not disclosed: the lookup
                // only serves the pre-fill of the common identity fields.
                $response = [
                    'exists' => TRUE,
                    'user' => [
                        'first_name' => $sibling['first_name'],
                        'last_name' => $sibling['last_name'],
                        'phone_number' => $sibling['phone_number'],
                        'mobile_number' => $sibling['mobile_number'],
                        'timezone' => $sibling['timezone'],
                        'login_name' => $sibling['login_name'],
                        'role_slug' => $sibling['role_slug'],
                    ]
                ];
            }
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function ajax_validate_username()
    {
        try
        {
            // We will only use the function in the admins_model because it is sufficient for the rest user types for
            // now (providers, secretaries).
            $is_valid = $this->admins_model->validate_username(
                $this->input->post('username'),
                (string) $this->input->post('email'),
                $this->input->post('user_id')
            );

            $response = $is_valid;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Change system language for current user.
     *
     * The language setting is stored in session data and retrieved every time the user visits any of the system pages.
     */
    public function ajax_change_language()
    {
        try
        {
            // Check if language exists in the available languages.
            $found = FALSE;

            foreach (config('available_languages') as $lang)
            {
                if ($lang == $this->input->post('language'))
                {
                    $found = TRUE;
                    break;
                }
            }

            if ( ! $found)
            {
                throw new Exception('Translations for the given language does not exist (' . $this->input->post('language') . ').');
            }

            $this->session->set_userdata('language', $this->input->post('language'));
            $this->config->set_item('language', $this->input->post('language'));

            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Apply global working plan to all providers.
     */
    public function ajax_apply_global_working_plan()
    {
        try
        {
            if ($this->privileges[PRIV_SYSTEM_SETTINGS]['edit'] == FALSE)
            {
                throw new Exception('You do not have the required privileges for this task.');
            }

            $working_plan = $this->input->post('working_plan');

            $providers = $this->providers_model->get_batch();

            foreach ($providers as $provider)
            {
                $this->providers_model->set_setting('working_plan', $working_plan, $provider['id']);
            }

            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
}
