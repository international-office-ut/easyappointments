<?php

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

namespace App\Controllers;

use Exception;
use App\Controllers\Concerns\Baseline_Migration;
use App\Models\Admins_Model;
use App\Models\Customers_Model;
use App\Models\Providers_Model;
use App\Models\Services_Model;
use App\Models\Settings_Model;
use CodeIgniter\Database\MigrationRunner;
use Config\Deployment;

/**
 * Installation Controller
 *
 * This controller will handle the installation procedure of Easy!Appointments.
 *
 * (CI4 port of the CI3 application/controllers/Installation.php)
 */
class Installation extends BaseController {
    use Baseline_Migration;

    protected Admins_Model $admins_model;
    protected Settings_Model $settings_model;
    protected Services_Model $services_model;
    protected Providers_Model $providers_model;
    protected Customers_Model $customers_model;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->admins_model = new Admins_Model;
        $this->settings_model = new Settings_Model;
        $this->services_model = new Services_Model;
        $this->providers_model = new Providers_Model;
        $this->customers_model = new Customers_Model;
    }

    /**
     * Display the installation page.
     */
    public function index()
    {
        if (is_app_installed())
        {
            return redirect()->to('appointments');
        }

        $this->assert_valid_installation_origin();

        return view('general/installation', [
            'base_url' => ea_config('base_url')
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
                return $this->response->setContentType('application/json')->setBody(json_encode(AJAX_SUCCESS));
            }

            $this->assert_valid_installation_origin();

            $admin = $this->request->getPost('admin');
            $company = $this->request->getPost('company');

            $migrations = new MigrationRunner(config(\Config\Migrations::class));

            // Baseline stamp: on an existing database (installed through CI3) the
            // tables already exist, so CI4 marks the whole CI3 migration chain as
            // applied without replaying any DDL. On a fresh database the tables do
            // not exist yet and the full chain runs below.
            if (db_connect()->tableExists('appointments'))
            {
                if ($this->legacy_history_present(db_connect()))
                {
                    $this->archive_legacy_history(db_connect());

                    $migrations = new MigrationRunner(config(\Config\Migrations::class));

                    $this->baseline_stamp($migrations);
                }
                elseif (count($migrations->getHistory()) === 0)
                {
                    $this->baseline_stamp($migrations);
                }
            }

            $migrations->latest();

            // Insert admin
            $admin['timezone'] = 'UTC';
            $admin['settings']['username'] = $admin['username'];
            $admin['settings']['password'] = $admin['password'];
            $admin['settings']['notifications'] = TRUE;
            $admin['settings']['calendar_view'] = CALENDAR_VIEW_DEFAULT;
            unset($admin['username'], $admin['password']);
            $admin['id'] = $this->admins_model->add($admin);

            session()->set('user_id', $admin['id']);
            session()->set('user_email', $admin['email']);
            session()->set('role_slug', DB_SLUG_ADMIN);
            session()->set('timezone', $admin['timezone']);
            session()->set('username', $admin['settings']['username']);

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
                    // The demo provider must not log in with publicly known credentials, so the
                    // password is randomized and never surfaced (it can be reset by an admin).
                    'password' => bin2hex(random_bytes(16)),
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
            $this->response->setStatusCode(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => ea_config('debug') ? $exception->getTrace() : []
            ];
        }

        return $this->response->setContentType('application/json')->setBody(json_encode($response));
    }

    /**
     * Verify that the installation request originates from a permitted address.
     *
     * The installation routine grants administrative privileges, therefore requests from a
     * foreign network must be rejected, even while the application is not yet installed.
     * Loopback and private LAN addresses are always allowed, the "INSTALL_ALLOWED_RANGES"
     * env value can whitelist additional ranges (CIDR notation), e.g. for public
     * servers that must be configured through their own web interface.
     */
    protected function assert_valid_installation_origin(): void
    {
        $ip_address = $this->request->getIPAddress();

        $is_local = preg_match('/^(127\.|10\.|192\.168\.|169\.254\.|172\.(1[6-9]|2[0-9]|3[01])\.|::1$|f[cd][0-9a-f]{0,2}:|fe80:)/i',
            $ip_address);

        if ($is_local)
        {
            return;
        }

        foreach (config(Deployment::class)->install_allowed_ranges as $range)
        {
            if ($this->ip_in_cidr($ip_address, $range))
            {
                return;
            }
        }

        $this->response->setStatusCode(403);
        $this->response->setBody(
            'Installation requests are only allowed from local addresses (loopback or LAN). '
            . 'Install the application from the server (e.g. via SSH), point a local proxy at '
            . 'the request or whitelist your address in the "INSTALL_ALLOWED_RANGES" value of '
            . 'the env configuration.');
        $this->response->send();
        exit;
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
