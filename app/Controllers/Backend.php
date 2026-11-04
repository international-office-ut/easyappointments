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

use App\Libraries\Timezones;
use App\Models\Admins_Model;
use App\Models\Appointments_Model;
use App\Models\Customers_Model;
use App\Models\Identities_Model;
use App\Models\Providers_Model;
use App\Models\Roles_Model;
use App\Models\Secretaries_Model;
use App\Models\Services_Model;
use App\Models\Settings_Model;
use App\Models\User_Model;
use CodeIgniter\Database\MigrationRunner;
use Exception;

/**
 * Backend Controller
 *

 */
class Backend extends BaseController {
    protected Appointments_Model $appointments_model;
    protected Providers_Model $providers_model;
    protected Services_Model $services_model;
    protected Customers_Model $customers_model;
    protected Settings_Model $settings_model;
    protected Roles_Model $roles_model;
    protected User_Model $user_model;
    protected Secretaries_Model $secretaries_model;
    protected Admins_Model $admins_model;
    protected Timezones $timezones;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->appointments_model = new Appointments_Model;
        $this->providers_model = new Providers_Model;
        $this->services_model = new Services_Model;
        $this->customers_model = new Customers_Model;
        $this->settings_model = new Settings_Model;
        $this->roles_model = new Roles_Model;
        $this->user_model = new User_Model;
        $this->secretaries_model = new Secretaries_Model;
        $this->admins_model = new Admins_Model;
        $this->timezones = new Timezones;
    }

    /**
     * Display the main backend page.
     *
     * This method displays the main backend page. All users login permission can view this page which displays a
     * calendar with the events of the selected provider or service. If a user has more privileges he will see more
     * menus at the top of the page.
     *
     * @param string $appointment_hash Appointment edit dialog will appear when the page loads (default '').
     *
     * @throws Exception
     */
    public function index($appointment_hash = '')
    {
        session()->set('dest_url', site_url('backend/index' . (! empty($appointment_hash) ? '/' . $appointment_hash : '')));

        if ( ! $this->has_privileges(PRIV_APPOINTMENTS))
        {
            return;
        }

        $calendar_view_query_param = $this->request->getGet('view');

        $user_id = session()->get('user_id');

        $user = $this->user_model->get_user($user_id);

        $view = [];
        $view['base_url'] = ea_config('base_url');
        $view['page_title'] = lang('calendar');
        $view['user_display_name'] = $this->user_model->get_user_display_name(session()->get('user_id'));
        $view['active_menu'] = PRIV_APPOINTMENTS;
        $view['date_format'] = $this->settings_model->get_setting('date_format');
        $view['time_format'] = $this->settings_model->get_setting('time_format');
        $view['first_weekday'] = $this->settings_model->get_setting('first_weekday');
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $view['require_phone_number'] = $this->settings_model->get_setting('require_phone_number');
        $view['available_providers'] = $this->providers_model->get_available_providers();
        $view['available_services'] = $this->services_model->get_available_services();
        $view['customers'] = $this->customers_model->get_batch();
        $view['calendar_view'] = ! empty($calendar_view_query_param) ? $calendar_view_query_param : $user['settings']['calendar_view'];
        $view['timezones'] = $this->timezones->to_array();
        $view['language'] = service('language')->getLocale();
        $this->set_user_data($view);

        if (session()->get('role_slug') === DB_SLUG_SECRETARY)
        {
            $secretary = $this->secretaries_model->get_row(session()->get('user_id'));
            $view['secretary_providers'] = $secretary['providers'];
        }
        else
        {
            $view['secretary_providers'] = [];
        }

        $results = $this->appointments_model->get_batch(['hash' => $appointment_hash]);

        if ($appointment_hash !== '' && count($results) > 0)
        {
            $appointment = $results[0];

            // Providers and secretaries must not open the appointment dialog of a foreign
            // provider, even when the appointment hash is known to them somehow.
            $this->assert_appointment_access($appointment);

            $appointment['customer'] = $this->customers_model->get_row($appointment['id_users_customer']);
            $view['edit_appointment'] = $appointment; // This will display the appointment edit dialog on page load.
        }
        else
        {
            $view['edit_appointment'] = NULL;
        }

        return view('backend/header', $view)
            . view('backend/calendar', $view)
            . view('backend/footer', $view);
    }

    /**
     * Make sure the session role is allowed to open the given appointment record.
     *
     * @param array $appointment The appointment row to be opened through its hash.
     */
    protected function assert_appointment_access(array $appointment): void
    {
        if ( ! (new Identities_Model)->provider_in_scope(
            session()->get('role_slug'),
            (int) session()->get('user_id'),
            $appointment['id_users_provider']))
        {
            header('Location: ' . site_url('user/no_privileges'));
            exit;
        }
    }

    /**
     * Check whether current user is logged in and has the required privileges to view a page.
     *
     * The backend page requires different privileges from the users to display pages. Not all pages are available to
     * all users. For example secretaries should not be able to edit the system users.
     *
     * @param string $page This argument must match the roles field names of each section (eg "appointments", "users"
     * ...).
     * @param bool $redirect If the user has not the required privileges (either not logged in or insufficient role
     * privileges) then the user will be redirected to another page. Set this argument to FALSE when using ajax (default
     * true).
     *
     * @return bool Returns whether the user has the required privileges to view the page or not. If the user is not
     * logged in then he will be prompted to log in. If he hasn't the required privileges then an info message will be
     * displayed.
     */
    protected function has_privileges($page, $redirect = TRUE)
    {
        // Check if user is logged in.
        $user_id = session()->get('user_id');

        if ($user_id == FALSE)
        {
            // User not logged in, display the login view.
            if ($redirect)
            {
                header('Location: ' . site_url('user/login'));
            }
            return FALSE;
        }

        // Check if the user has the required privileges for viewing the selected page.
        $role_slug = session()->get('role_slug');

        $role_privileges = db_connect()->table('roles')->where(['slug' => $role_slug])->get()->getRowArray();

        if ($role_privileges[$page] < PRIV_VIEW)
        {
            // User does not have the permission to view the page.
            if ($redirect)
            {
                header('Location: ' . site_url('user/no_privileges'));
            }
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Set the user data in order to be available at the view and js code.
     *
     * @param array $view Contains the view data.
     */
    protected function set_user_data(&$view)
    {
        $view['user_id'] = session()->get('user_id');
        $view['user_email'] = session()->get('user_email');
        $view['timezone'] = session()->get('timezone');
        $view['role_slug'] = session()->get('role_slug');
        $view['privileges'] = $this->roles_model->get_privileges(session()->get('role_slug'));
    }

    /**
     * Display the backend customers page.
     *
     * In this page the user can manage all the customer records of the system.
     */
    public function customers()
    {
        session()->set('dest_url', site_url('backend/customers'));

        if ( ! $this->has_privileges(PRIV_CUSTOMERS))
        {
            return;
        }

        $view['base_url'] = ea_config('base_url');
        $view['page_title'] = lang('customers');
        $view['user_display_name'] = $this->user_model->get_user_display_name(session()->get('user_id'));
        $view['active_menu'] = PRIV_CUSTOMERS;
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $view['date_format'] = $this->settings_model->get_setting('date_format');
        $view['time_format'] = $this->settings_model->get_setting('time_format');
        $view['first_weekday'] = $this->settings_model->get_setting('first_weekday');
        $view['require_phone_number'] = $this->settings_model->get_setting('require_phone_number');
        $view['customers'] = $this->customers_model->get_batch();
        $view['available_providers'] = $this->providers_model->get_available_providers();
        $view['available_services'] = $this->services_model->get_available_services();
        $view['timezones'] = $this->timezones->to_array();

        if (session()->get('role_slug') === DB_SLUG_SECRETARY)
        {
            $secretary = $this->secretaries_model->get_row(session()->get('user_id'));
            $view['secretary_providers'] = $secretary['providers'];
        }
        else
        {
            $view['secretary_providers'] = [];
        }

        $this->set_user_data($view);

        return view('backend/header', $view)
            . view('backend/customers', $view)
            . view('backend/footer', $view);
    }

    /**
     * Displays the backend services page.
     *
     * Here the admin user will be able to organize and create the services that the user will be able to book
     * appointments in frontend.
     *
     * NOTICE: The services that each provider is able to service is managed from the backend services page.
     */
    public function services()
    {
        session()->set('dest_url', site_url('backend/services'));

        if ( ! $this->has_privileges(PRIV_SERVICES))
        {
            return;
        }

        $view['base_url'] = ea_config('base_url');
        $view['page_title'] = lang('services');
        $view['user_display_name'] = $this->user_model->get_user_display_name(session()->get('user_id'));
        $view['active_menu'] = PRIV_SERVICES;
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $view['date_format'] = $this->settings_model->get_setting('date_format');
        $view['time_format'] = $this->settings_model->get_setting('time_format');
        $view['first_weekday'] = $this->settings_model->get_setting('first_weekday');
        $view['services'] = $this->services_model->get_batch();
        $view['categories'] = $this->services_model->get_all_categories();
        $view['timezones'] = $this->timezones->to_array();
        $view['working_plan'] = $this->settings_model->get_setting('company_working_plan');
        $this->set_user_data($view);

        return view('backend/header', $view)
            . view('backend/services', $view)
            . view('backend/footer', $view);
    }

    /**
     * Display the backend users page.
     *
     * In this page the admin user will be able to manage the system users. By this, we mean the provider, secretary and
     * admin users. This is also the page where the admin defines which service can each provider provide.
     */
    public function users()
    {
        session()->set('dest_url', site_url('backend/users'));

        if ( ! $this->has_privileges(PRIV_USERS))
        {
            return;
        }

        $view['base_url'] = ea_config('base_url');
        $view['page_title'] = lang('users');
        $view['user_display_name'] = $this->user_model->get_user_display_name(session()->get('user_id'));
        $view['active_menu'] = PRIV_USERS;
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $view['date_format'] = $this->settings_model->get_setting('date_format');
        $view['time_format'] = $this->settings_model->get_setting('time_format');
        $view['first_weekday'] = $this->settings_model->get_setting('first_weekday');
        $view['admins'] = $this->admins_model->get_batch();
        $view['providers'] = $this->providers_model->get_batch();
        $view['secretaries'] = $this->secretaries_model->get_batch();

        // The service list carries the category fields, so the provider services
        // tab can group the entries by category (the booking page does the same).
        $view['services'] = db_connect()->table('services')
            ->select('services.*, service_categories.name AS category_name, '
                . 'service_categories.id AS category_id')
            ->join('service_categories',
                'service_categories.id = services.id_service_categories', 'left')
            ->orderBy('services.name ASC')
            ->get()
            ->getResultArray();

        $view['working_plan'] = $this->settings_model->get_setting('company_working_plan');
        $view['timezones'] = $this->timezones->to_array();
        $view['working_plan_exceptions'] = '{}';
        $this->set_user_data($view);

        return view('backend/header', $view)
            . view('backend/users', $view)
            . view('backend/footer', $view);
    }

    /**
     * Display the user/system settings.
     *
     * This page will display the user settings (name, password etc). If current user is an administrator, then he will
     * be able to make change to the current Easy!Appointment installation (core settings like company name, book
     * timeout etc).
     */
    public function settings()
    {
        session()->set('dest_url', site_url('backend/settings'));

        if ( ! $this->has_privileges(PRIV_SYSTEM_SETTINGS, FALSE)
            && ! $this->has_privileges(PRIV_USER_SETTINGS))
        {
            return;
        }

        $user_id = session()->get('user_id');

        $view['base_url'] = ea_config('base_url');
        $view['page_title'] = lang('settings');
        $view['user_display_name'] = $this->user_model->get_user_display_name($user_id);
        $view['active_menu'] = PRIV_SYSTEM_SETTINGS;
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $view['date_format'] = $this->settings_model->get_setting('date_format');
        $view['first_weekday'] = $this->settings_model->get_setting('first_weekday');
        $view['time_format'] = $this->settings_model->get_setting('time_format');
        $view['role_slug'] = session()->get('role_slug');
        $view['user_settings'] = $this->user_model->get_user($user_id);
        $view['timezones'] = $this->timezones->to_array();

        // Users without system-settings privileges may only edit their own user settings, so
        // the system settings (like the API token) must never appear in the page source.
        $system_settings = $this->has_privileges(PRIV_SYSTEM_SETTINGS, FALSE)
            ? $this->settings_model->get_settings()
            : [];

        // The API token hash must not reach the page source and the token widget works
        // exclusively through its dedicated endpoints.
        $system_settings = array_values(array_filter($system_settings,
            static fn(array $setting): bool => $setting['name'] !== 'api_token'));

        $view['system_settings'] = $system_settings;

        // book_advance_timeout preview
        $book_advance_timeout = $this->settings_model->get_setting('book_advance_timeout');
        $hours = floor($book_advance_timeout / 60);
        $minutes = $book_advance_timeout % 60;
        $view['book_advance_timeout_preview'] = sprintf('%02d:%02d', $hours, $minutes);

        $this->set_user_data($view);

        return view('backend/header', $view)
            . view('backend/settings', $view)
            . view('backend/footer', $view);
    }

    /**
     * This method will update the installation to the latest available version in the server.
     *
     * IMPORTANT: The code files must exist in the server, this method will not fetch any new files but will update
     * the database schema.
     *
     * This method can be used either by loading the page in the browser or by an ajax request. But it will answer with
     * JSON encoded data.
     */
    public function update()
    {
        try
        {
            if ( ! $this->has_privileges(PRIV_SYSTEM_SETTINGS, TRUE))
            {
                throw new Exception('You do not have the required privileges for this task!');
            }

            $migrations = new MigrationRunner(config(\Config\Migrations::class));

            $migrations->latest();

            $view = ['success' => TRUE];
        }
        catch (Exception $exception)
        {
            $view = ['success' => FALSE, 'exception' => $exception->getMessage()];
        }

        return view('general/update', $view);
    }
}
