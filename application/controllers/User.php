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
use EA\Engine\Types\Email;
use EA\Engine\Types\NonEmptyText;

/**
 * User Controller
 *
 * @package Controllers
 */
class User extends EA_Controller {
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('settings_model');
        $this->load->model('user_model');
    }

    /**
     * Default Method
     *
     * The default method will redirect the browser to the user/login URL.
     */
    public function index()
    {
        header('Location: ' . site_url('user/login'));
    }

    /**
     * Rate limiting paths point into the storage cache directory.
     *
     * @param string $key Unique key of the rate limit record.
     * @return string
     */
    protected function rate_limit_path(string $key): string
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'storage', 'cache', sha1($key) . '.ratelimit']);
    }

    /**
     * Returns the rate limit record of the provided key, removing it if it is expired or corrupted.
     *
     * @param string $key Unique key of the rate limit record.
     * @return array|null
     */
    protected function rate_limit_read(string $key): ?array
    {
        $path = $this->rate_limit_path($key);
        $contents = @file_get_contents($path);

        if ($contents === FALSE)
        {
            return NULL;
        }

        $record = json_decode($contents, TRUE);

        if ( ! is_array($record) || ! isset($record['hits'], $record['expires']))
        {
            @unlink($path);

            return NULL;
        }

        if ($record['expires'] < time())
        {
            @unlink($path);

            return NULL;
        }

        return $record;
    }

    /**
     * Returns whether the provided key has reached its attempts limit.
     *
     * @param string $key Unique key of the rate limit record.
     * @param int $max_attempts Maximum attempts allowed within the window.
     * @return bool
     */
    protected function rate_limit_has_reached_limit(string $key, int $max_attempts): bool
    {
        $record = $this->rate_limit_read($key);

        return $record !== NULL && $record['hits'] >= $max_attempts;
    }

    /**
     * Registers a new attempt for the provided key, so that the limit gets closer.
     *
     * @param string $key Unique key of the rate limit record.
     * @param int $window Attempts window in seconds.
     */
    protected function rate_limit_register_attempt(string $key, int $window): void
    {
        $record = $this->rate_limit_read($key);

        if ($record === NULL)
        {
            $record = [
                'hits' => 0,
                'expires' => time() + $window
            ];

            // A new rate limit record is a good moment to clean up forgotten records of
            // other keys, so that the cache directory cannot grow without bounds.
            $this->rate_limit_prune();
        }

        $record['hits']++;

        @file_put_contents($this->rate_limit_path($key), json_encode($record), LOCK_EX);
    }

    /**
     * Removes all stale rate limit records from the storage cache directory.
     */
    protected function rate_limit_prune(): void
    {
        $files = @glob(dirname($this->rate_limit_path('prune')) . '/*.ratelimit');

        if ($files === FALSE)
        {
            return;
        }

        foreach ($files as $file)
        {
            if (@filemtime($file) < time() - 30 * DAY)
            {
                @unlink($file);
            }
        }
    }

    /**
     * Removes the rate limit record of the provided key.
     *
     * @param string $key Unique key of the rate limit record.
     */
    protected function rate_limit_clear(string $key): void
    {
        @unlink($this->rate_limit_path($key));
    }

    /**
     * Display the login page.
     *
     * @throws Exception
     */
    public function login()
    {
        $view['base_url'] = config('base_url');
        $view['dest_url'] = $this->session->userdata('dest_url');

        if ( ! $view['dest_url'])
        {
            $view['dest_url'] = site_url('backend');
        }

        $view['company_name'] = $this->settings_model->get_setting('company_name');

        // When the role-picker was requested through the SSO flow, the candidates of the
        // authenticated identity get rendered server-side (no credentials required).
        $candidates = json_decode((string) $this->session->userdata('login_candidates'), TRUE);
        $view['shib_candidates'] = ($this->input->get('multi') === '1' && is_array($candidates))
            ? $candidates
            : [];

        $view['shib_error'] = (bool) $this->session->flashdata('shib_error');
        $view['shib_enabled'] = (bool) Config::SHIB_ENABLED;
        $view['password_login'] = $this->password_login_allowed();

        $this->load->view('user/login', $view);
    }

    /**
     * Start the Shibboleth SSO login by passing the browser through the service provider.
     */
    public function shib()
    {
        if ( ! Config::SHIB_ENABLED)
        {
            redirect('user/login');
        }

        redirect(Config::SHIB_LOGIN_PATH . '?target=' . rawurlencode(site_url('user/shib_return')));
    }

    /**
     * Landing page of the completed Shibboleth login.
     *
     * The identities are matched by email (or eppn, depending on the SHIB_ATTR_* configuration),
     * so that no additional password or provisioning data is required. The attributes are only
     * trusted when the request really passed the Shibboleth service provider.
     */
    public function shib_return()
    {
        if ( ! Config::SHIB_ENABLED)
        {
            redirect('user/login');
        }

        $attribute = static function (string $name): ?string
        {
            return $_SERVER[$name]
                ?? $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))]
                ?? NULL;
        };

        if (empty($attribute('Shib-Application-ID')))
        {
            $this->session->set_flashdata('shib_error', TRUE);
            redirect('user/login');
        }

        $email = $attribute(Config::SHIB_ATTR_EMAIL);

        if ($email === NULL || $email === '')
        {
            log_message('info', 'Shibboleth login skipped: the email attribute is not present.');
            $this->session->set_flashdata('shib_error', TRUE);
            redirect('user/login');
        }

        $matches = $this->user_model->get_users_by_email($email);

        if (count($matches) === 1)
        {
            $this->establish_session($matches[0], TRUE);

            redirect('backend');
        }

        if (count($matches) > 1)
        {
            // The identity matches several role rows: cache the verified candidates and let
            // the role picker of the login page render them (same list as the password flow).
            $this->session->set_userdata('login_candidates', json_encode($matches));

            redirect('user/login?multi=1');
        }

        log_message('info', 'Shibboleth login failed: no user account for "' . $email . '".');
        $this->session->set_flashdata('shib_error', TRUE);
        redirect('user/login');
    }

    /**
     * Write the session data of a verified login (password or Shibboleth) and harden it.
     *
     * @param array $user_data Session payload of the matched user row.
     * @param bool $sso When TRUE the login came through an identity provider (logout triggers SLO).
     */
    protected function establish_session(array $user_data, bool $sso = FALSE): void
    {
        $this->session->sess_regenerate(TRUE);

        if ($sso)
        {
            $user_data['shib_sso'] = TRUE;
        }

        $this->session->set_userdata($user_data);
    }

    /**
     * Display the logout page.
     */
    public function logout()
    {
        $sso_logout = (bool) Config::SHIB_ENABLED && (bool) $this->session->userdata('shib_sso');

        $this->session->unset_userdata('user_id');
        $this->session->unset_userdata('user_email');
        $this->session->unset_userdata('role_slug');
        $this->session->unset_userdata('username');
        $this->session->unset_userdata('login_candidates');
        $this->session->unset_userdata('dest_url');
        $this->session->unset_userdata('shib_sso');

        // Destroy the whole session record, so that a leaked session cookie cannot outlive
        // the logout (a fresh login would regenerate the session id anyway).
        $this->session->sess_destroy();

        if ($sso_logout)
        {
            // Hand the session destruction over to the service provider, so that the IdP
            // Single Logout terminates the SSO session of the user, and return to the login.
            redirect(Config::SHIB_LOGOUT_PATH . '?return=' . rawurlencode(site_url('user/login')));
        }

        $view['base_url'] = config('base_url');
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $this->load->view('user/logout', $view);
    }

    /**
     * Returns whether the username/password login is usable with the current configuration.
     *
     * The password check only dies completely when the SSO is active: with the SHIB_ENABLED
     * switch off, password login stays available no matter what (fail-open), so that a
     * misconfigured installation never locks all users (and admins) out.
     */
    protected function password_login_allowed(): bool
    {
        return ! Config::SHIB_ENABLED || Config::SHIB_PASSWORD_LOGIN;
    }

    /**
     * Display the "forgot password" page.
     * @throws Exception
     */
    public function forgot_password()
    {
        if ( ! $this->password_login_allowed())
        {
            // A new password would never be accepted by the disabled password login.
            redirect('user/login');
        }

        $view['base_url'] = config('base_url');
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $this->load->view('user/forgot_password', $view);
    }

    /**
     * Display the "not authorized" page.
     * @throws Exception
     */
    public function no_privileges()
    {
        $view['base_url'] = config('base_url');
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $this->load->view('user/no_privileges', $view);
    }

    /**
     * Check whether the user has entered the correct login credentials.
     *
     * The session data of a logged in user are the following:
     *   - 'user_id'
     *   - 'user_email'
     *   - 'role_slug'
     *   - 'dest_url'
     */
    public function ajax_check_login()
    {
        try
        {
            if ( ! $this->password_login_allowed())
            {
                log_message('info', 'Password login attempt while "SHIB_PASSWORD_LOGIN" is disabled.');
                $response = AJAX_FAILURE;
            }
            else if ( ! $this->input->post('username') || ! $this->input->post('password'))
            {
                throw new Exception('Invalid credentials given!');
            }

            $client_key = 'login|client|' . $this->input->ip_address();
            $user_key = 'login|user|' . $this->input->post('username') . '|' . $this->input->ip_address();

            $user_data = ($this->rate_limit_has_reached_limit($client_key, 5)
                || $this->rate_limit_has_reached_limit($user_key, 5))
                    ? NULL
                    : $this->user_model->check_login($this->input->post('username'), $this->input->post('password'));

            if (is_array($user_data) && ! empty($user_data['multi']))
            {
                // The login name belongs to an identity with multiple roles: cache the verified
                // candidates and let the user pick the role to continue with.
                $this->session->set_userdata('login_candidates', json_encode($user_data['candidates']));

                $rate_limit_clear = method_exists($this, 'rate_limit_clear') ? 'rate_limit_clear' : '';

                if ($rate_limit_clear)
                {
                    $this->rate_limit_clear($client_key);
                    $this->rate_limit_clear($user_key);
                }

                $response = [
                    'multi' => TRUE,
                    'candidates' => array_map(function ($candidate) {
                        return [
                            'user_id' => $candidate['user_id'],
                            'role_slug' => $candidate['role_slug'],
                        ];
                    }, $user_data['candidates'])
                ];
            }
            else if ($user_data)
            {
                $this->establish_session($user_data); // Save data on user's session and harden it.

                $this->rate_limit_clear($client_key);
                $this->rate_limit_clear($user_key);

                $response = AJAX_SUCCESS;
            }
            else
            {
                $this->rate_limit_register_attempt($client_key, 600);
                $this->rate_limit_register_attempt($user_key, 600);

                $response = AJAX_FAILURE;
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
     * Continue the login with one of the cached role candidates.
     *
     * The candidates got stored in the session after the password was verified, so this
     * endpoint only needs to match the provided user id against that safe list.
     *
     * Required POST Parameters:
     *
     * - int $_POST['user_id'] Selected candidate user record id.
     */
    public function ajax_select_login_role()
    {
        try
        {
            $candidates = json_decode((string) $this->session->userdata('login_candidates'), TRUE);

            if (empty($candidates) || ! is_array($candidates))
            {
                throw new Exception('No login candidates in the current session.');
            }

            $user_id = (int) $this->input->post('user_id');

            $user_data = NULL;

            foreach ($candidates as $candidate)
            {
                if ((int) $candidate['user_id'] === $user_id)
                {
                    $user_data = $candidate;
                    break;
                }
            }

            if (empty($user_data))
            {
                throw new Exception('The selected role is not part of the verified login candidates.');
            }

            $this->establish_session($user_data);
            $this->session->unset_userdata('login_candidates');

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
     * Regenerate a new password for the current user, only if the username and
     * email address given correspond to an existing user in db.
     *
     * Required POST Parameters:
     *
     * - string $_POST['username'] Username to be validated.
     * - string $_POST['email'] Email to be validated.
     */
    public function ajax_forgot_password()
    {
        try
        {
            if ( ! $this->password_login_allowed())
            {
                log_message('info', 'Password recovery attempt while "SHIB_PASSWORD_LOGIN" is disabled.');
                $response = AJAX_FAILURE;
            }
            else if ( ! $this->input->post('username') || ! $this->input->post('email'))
            {
                throw new Exception('You must enter a valid username and email address in '
                    . 'order to get a new password!');
            }

            $recovery_key = 'recovery|client|' . $this->input->ip_address();

            if ($this->rate_limit_has_reached_limit($recovery_key, 3))
            {
                $new_password = FALSE;
            }
            else
            {
                $this->rate_limit_register_attempt($recovery_key, 1800);

                $new_password = $this->user_model->regenerate_password(
                    $this->input->post('username'),
                    $this->input->post('email')
                );
            }

            if ($new_password != FALSE)
            {
                $this->config->load('email');

                $email = new EmailClient($this, $this->config->config);

                $company_settings = [
                    'company_name' => $this->settings_model->get_setting('company_name'),
                    'company_link' => $this->settings_model->get_setting('company_link'),
                    'company_email' => $this->settings_model->get_setting('company_email')
                ];

                $email->send_password(new NonEmptyText($new_password), new Email($this->input->post('email')),
                    $company_settings);
            }

            $response = $new_password != FALSE ? AJAX_SUCCESS : AJAX_FAILURE;
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
