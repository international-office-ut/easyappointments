<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.1.0
 * ---------------------------------------------------------------------------- */

/**
 * Installation Controller
 *
 * This controller will handle the installation procedure of Easy!Appointments.
 *
 * @package Controllers
 */
class Installation extends EA_Controller {
    /**
     * Installation constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('admins_model');
        $this->load->model('settings_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('customers_model');
        $this->load->library('migration');
        $this->load->helper('installation');
        $this->load->helper('string');
    }

    /**
     * Display the installation page.
     */
    public function index()
    {
        if (is_app_installed())
        {
            redirect('appointments');
            return;
        }

        $this->assert_valid_installation_origin();

        $this->load->view('general/installation', [
            'base_url' => config('base_url')
        ]);
    }

    /**
     * Installs Easy!Appointments on the server.
     */
    public function ajax_install()
    {
        try
        {
            if (is_app_installed())
            {
                return;
            }

            $this->assert_valid_installation_origin();

            $admin = $this->input->post('admin');
            $company = $this->input->post('company');

            if ( ! $this->migration->current())
            {
                throw new Exception($this->migration->error_string());
            }

            // Insert admin
            $admin['timezone'] = 'UTC';
            $admin['settings']['username'] = $admin['username'];
            $admin['settings']['password'] = $admin['password'];
            $admin['settings']['notifications'] = TRUE;
            $admin['settings']['calendar_view'] = CALENDAR_VIEW_DEFAULT;
            unset($admin['username'], $admin['password']);
            $admin['id'] = $this->admins_model->add($admin);

            $this->session->set_userdata('user_id', $admin['id']);
            $this->session->set_userdata('user_email', $admin['email']);
            $this->session->set_userdata('role_slug', DB_SLUG_ADMIN);
            $this->session->set_userdata('timezone', $admin['timezone']);
            $this->session->set_userdata('username', $admin['settings']['username']);

            // Save company settings
            $this->settings_model->set_setting('company_name', $company['company_name']);
            $this->settings_model->set_setting('company_email', $company['company_email']);
            $this->settings_model->set_setting('company_link', $company['company_link']);

            // Service
            $service_id = $this->services_model->add([
                'name' => 'Service',
                'duration' => '30',
                'price' => '0',
                'currency' => '',
                'availabilities_type' => 'flexible',
                'attendants_number' => '1'
            ]);

            // Provider
            $this->providers_model->add([
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.org',
                'phone_number' => '+1 (000) 000-0000',
                'services' => [
                    $service_id
                ],
                'settings' => [
                    'username' => 'janedoe',
                    'password' => 'janedoe',
                    'working_plan' => $this->settings_model->get_setting('company_working_plan'),
                    'notifications' => TRUE,
                    'calendar_view' => CALENDAR_VIEW_DEFAULT,
                ],
            ]);

            // Customer
            $this->customers_model->add([
                'first_name' => 'James',
                'last_name' => 'Doe',
                'email' => 'james@example.org',
                'phone_number' => '+1 (000) 000-0000',
            ]);

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
     * Verify that the installation request originates from a permitted address.
     *
     * The installation routine grants administrative privileges, therefore requests from a
     * foreign network must be rejected, even while the application is not yet installed.
     * Loopback and private LAN addresses are always allowed, the "INSTALL_ALLOWED_RANGES"
     * configuration value can whitelist additional ranges (CIDR notation), e.g. for public
     * servers that must be configured through their own web interface.
     */
    protected function assert_valid_installation_origin(): void
    {
        $ip_address = $this->input->ip_address();

        $is_local = preg_match('/^(127\.|10\.|192\.168\.|169\.254\.|172\.(1[6-9]|2[0-9]|3[01])\.|::1$|f[cd][0-9a-f]{0,2}:|fe80:)/i',
            $ip_address);

        if ($is_local)
        {
            return;
        }

        foreach (defined('\Config::INSTALL_ALLOWED_RANGES')
            ? (array) Config::INSTALL_ALLOWED_RANGES : [] as $range)
        {
            if ($this->ip_in_cidr($ip_address, $range))
            {
                return;
            }
        }

        show_error(
            'Installation requests are only allowed from local addresses (loopback or LAN). '
            . 'Install the application from the server (e.g. via SSH), point a local proxy at '
            . 'the request or whitelist your address in the "INSTALL_ALLOWED_RANGES" value of '
            . 'the "config.php" file.', 403);
    }

    /**
     * Determine whether an IP address falls into a CIDR range.
     *
     * @param string $ip_address The IP address to be checked (v4 or v6).
     * @param string $range The CIDR range, e.g. "203.0.113.0/24" or "2001:db8::/32".
     *
     * @return bool Returns whether the address is inside the range.
     */
    protected function ip_in_cidr(string $ip_address, string $range): bool
    {
        if (strpos($range, '/') === FALSE)
        {
            $range .= strpos($ip_address, ':') !== FALSE ? '/128' : '/32';
        }

        [$subnet, $mask] = explode('/', $range, 2);

        $address_binary = @inet_pton($ip_address);
        $subnet_binary = @inet_pton($subnet);

        if ($address_binary === FALSE || $subnet_binary === FALSE
            || strlen($address_binary) !== strlen($subnet_binary)
            || strlen($address_binary) * 8 < $mask)
        {
            return FALSE;
        }

        $mask_bytes = (int) floor($mask / 8);
        $mask_bits = $mask % 8;

        if ($mask_bytes > 0
            && substr_compare($address_binary, $subnet_binary, 0, $mask_bytes) !== 0)
        {
            return FALSE;
        }

        if ($mask_bits > 0)
        {
            $mask_byte = (0xFF << (8 - $mask_bits)) & 0xFF;

            return (ord($address_binary[$mask_bytes]) & $mask_byte)
                === (ord($subnet_binary[$mask_bytes]) & $mask_byte);
        }

        return TRUE;
    }
}
