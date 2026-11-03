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

use Exception;
use App\Libraries\Rate_limiter;
use App\Models\Settings_Model;
use App\Models\User_Model;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Deployment;
use EA\Engine\Notifications\Email as EmailClient;
use EA\Engine\Types\Email;
use EA\Engine\Types\Text;

/**
 * User Controller
 *
 * Handles the login, SSO, password reset and temporary admin access flows.
 */
class User extends BaseController {
    protected Settings_Model $settings_model;
    protected User_Model $user_model;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->settings_model = new Settings_Model;
        $this->user_model = new User_Model;
        $this->db = db_connect();
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
     * Display the login page.
     *
     * @throws Exception
     */
    public function login()
    {
        $deployment = config(Deployment::class);

        $view['base_url'] = ea_config('base_url');

        $view['dest_url'] = session()->get('dest_url');

        if ( ! $view['dest_url'])
        {
            $view['dest_url'] = site_url('backend');
        }

        // Messages of redirects from the temporary access and password reset flows.
        $view['login_message'] = session()->getFlashdata('login_message');

        // SSO-only deployments hide the password login and offer the temporary
        // administrator access instead, so admins can still log in without the SSO.
        $view['admin_access_mode'] = (bool) $deployment->shib_enabled && ! (bool) $deployment->shib_password_login;

        $view['company_name'] = $this->settings_model->get_setting('company_name');

        // When the role-picker was requested through the SSO flow, the candidates of the
        // authenticated identity get rendered server-side (no credentials required).
        $candidates = json_decode((string) (session()->get('login_candidates') ?? ''), TRUE);
        $view['shib_candidates'] = ($this->request->getGet('multi') === '1' && is_array($candidates))
            ? $candidates
            : [];

        $view['shib_error'] = (bool) session()->getFlashdata('shib_error');
        $view['shib_enabled'] = (bool) $deployment->shib_enabled;
        $view['password_login'] = $this->password_login_allowed();

        return view('user/login', $view);
    }

    /**
     * Start the Shibboleth SSO login by passing the browser through the service provider.
     */
    public function shib()
    {
        $deployment = config(Deployment::class);

        if ( ! $deployment->shib_enabled)
        {
            return redirect()->to('user/login');
        }

        header('Location: ' . $deployment->shib_login_path . '?target='
            . rawurlencode(site_url('user/shib_return')));
        exit;
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
        $deployment = config(Deployment::class);

        if ( ! $deployment->shib_enabled)
        {
            return redirect()->to('user/login');
        }

        // The attribute values are only read from the unprefixed environment, because a plain
        // HTTP header (e.g. "mail: ...") can never spoof such a value: mod_shib exports the
        // attributes without a prefix, while nginx deployments forward them explicitly through
        // fastcgi_param directives (see docs/security-ops.md).
        $attribute = static function (string $name): ?string
        {
            return $_SERVER[$name] ?? NULL;
        };

        // A real service provider session always carries both the application id and the
        // unique session id of the SSO request.
        if (empty($attribute('Shib-Application-ID')) || empty($attribute('Shib-Session-ID')))
        {
            session()->setFlashdata('shib_error', TRUE);
            return redirect()->to('user/login');
        }

        $email = $attribute($deployment->shib_attr_email);

        if ($email === NULL || $email === '')
        {
            log_message('info', 'Shibboleth login skipped: the email attribute is not present.');
            session()->setFlashdata('shib_error', TRUE);
            return redirect()->to('user/login');
        }

        $matches = $this->user_model->get_users_by_email($email);

        if (count($matches) === 1)
        {
            $this->establish_session($matches[0], TRUE);

            return redirect()->to('backend');
        }

        if (count($matches) > 1)
        {
            // The identity matches several role rows: cache the verified candidates and let
            // the role picker of the login page render them (same list as the password flow).
            session()->set('login_candidates', json_encode($matches));

            return redirect()->to('user/login?multi=1');
        }

        log_message('info', 'Shibboleth login failed: no user account for "' . $email . '".');
        session()->setFlashdata('shib_error', TRUE);
        return redirect()->to('user/login');
    }

    /**
     * Write the session data of a verified login (password or Shibboleth) and harden it.
     *
     * @param array $user_data Session payload of the matched user row.
     * @param bool $sso When TRUE the login came through an identity provider (logout triggers SLO).
     */
    protected function establish_session(array $user_data, bool $sso = FALSE): void
    {
        session()->regenerate(TRUE);

        if ($sso)
        {
            $user_data['shib_sso'] = TRUE;
        }

        session()->set($user_data);
    }

    /**
     * Display the logout page.
     */
    public function logout()
    {
        $deployment = config(Deployment::class);

        $sso_logout = (bool) $deployment->shib_enabled && (bool) session()->get('shib_sso');

        session()->remove('user_id');
        session()->remove('user_email');
        session()->remove('role_slug');
        session()->remove('username');
        session()->remove('login_candidates');
        session()->remove('dest_url');
        session()->remove('shib_sso');

        // Destroy the whole session record, so that a leaked session cookie cannot outlive
        // the logout (a fresh login would regenerate the session id anyway).
        session()->destroy();

        if ($sso_logout)
        {
            // Hand the session destruction over to the service provider, so that the IdP
            // Single Logout terminates the SSO session of the user, and return to the login.
            header('Location: ' . $deployment->shib_logout_path
                . '?return=' . rawurlencode(site_url('user/login')));
            exit;
        }

        $view['base_url'] = ea_config('base_url');
        $view['company_name'] = $this->settings_model->get_setting('company_name');

        return view('user/logout', $view);
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
        $deployment = config(Deployment::class);

        return ! $deployment->shib_enabled || $deployment->shib_password_login;
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
            return redirect()->to('user/login');
        }

        $view['base_url'] = ea_config('base_url');
        $view['company_name'] = $this->settings_model->get_setting('company_name');

        return view('user/forgot_password', $view);
    }

    /**
     * Display the "not authorized" page.
     * @throws Exception
     */
    public function no_privileges()
    {
        $view['base_url'] = ea_config('base_url');
        $view['company_name'] = $this->settings_model->get_setting('company_name');

        return view('user/no_privileges', $view);
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
            else if ( ! $this->request->getPost('username') || ! $this->request->getPost('password'))
            {
                throw new Exception('Invalid credentials given!');
            }

            $client_key = 'login|client|' . $this->request->getIPAddress();
            $user_key = 'login|user|' . $this->request->getPost('username') . '|' . $this->request->getIPAddress();

            $user_data = (Rate_limiter::has_reached_limit($client_key, 5)
                || Rate_limiter::has_reached_limit($user_key, 5))
                    ? NULL
                    : $this->user_model->check_login($this->request->getPost('username'), $this->request->getPost('password'));

            if (is_array($user_data) && ! empty($user_data['multi']))
            {
                // The login name belongs to an identity with multiple roles: cache the verified
                // candidates and let the user pick the role to continue with.
                session()->set('login_candidates', json_encode($user_data['candidates']));

                Rate_limiter::clear($client_key);
                Rate_limiter::clear($user_key);

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

                Rate_limiter::clear($client_key);
                Rate_limiter::clear($user_key);

                $response = AJAX_SUCCESS;
            }
            else
            {
                Rate_limiter::register_attempt($client_key, 600);
                Rate_limiter::register_attempt($user_key, 600);

                $response = AJAX_FAILURE;
            }
        }
        catch (Exception $exception)
        {
            $this->response->setStatusCode(500);

            $response = $this->ajax_exception($exception);
        }

        return $this->response->setContentType('application/json')->setBody(json_encode($response));
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
            $candidates = json_decode((string) (session()->get('login_candidates') ?? ''), TRUE);

            if (empty($candidates) || ! is_array($candidates))
            {
                throw new Exception('No login candidates in the current session.');
            }

            $user_id = (int) $this->request->getPost('user_id');

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
            session()->remove('login_candidates');

            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $this->response->setStatusCode(500);

            $response = $this->ajax_exception($exception);
        }

        return $this->response->setContentType('application/json')->setBody(json_encode($response));
    }

    /**
     * Request the link that either sets a new password or, in SSO-only deployments, provides
     * a temporary administrator login.
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
            $deployment = config(Deployment::class);
            $admin_only = (bool) $deployment->shib_enabled && ! (bool) $deployment->shib_password_login;

            if ( ! $this->password_login_allowed() && ! $admin_only)
            {
                log_message('info', 'Password recovery attempt while no recovery flow is available.');
                $response = AJAX_FAILURE;
            }
            else if ( ! $this->request->getPost('username') || ! $this->request->getPost('email'))
            {
                throw new Exception('You must enter a valid username and email address in '
                    . 'order to request an account access link!');
            }

            $recovery_key = 'recovery|client|' . $this->request->getIPAddress();

            if (Rate_limiter::has_reached_limit($recovery_key, 3))
            {
                $token = FALSE;
            }
            else
            {
                Rate_limiter::register_attempt($recovery_key, 1800);

                // SSO-only deployments grant temporary administrator access instead of a
                // password reset, because regular passwords would not be accepted there.
                $token = $this->user_model->create_access_token(
                    $this->request->getPost('username'),
                    $this->request->getPost('email'),
                    $admin_only,
                    $admin_only ? 'admin_access' : 'password_reset',
                    $admin_only ? 900 : 1800
                );
            }

            // Record the request outcome, so that administrators can trace break-glass
            // activations and probing attempts of their own accounts.
            log_message('info', 'Reset request from "' . $this->request->getIPAddress() . '" for username "'
                . $this->request->getPost('username') . '" and email "' . $this->request->getPost('email') . '": '
                . ($token !== FALSE ? 'matched, ' . ($admin_only ? 'admin access' : 'password reset')
                    . ' link sent' : 'no matching account'));

            if ($token !== FALSE)
            {
                $email = $this->buildEmailClient();

                $company_settings = [
                    'company_name' => $this->settings_model->get_setting('company_name'),
                    'company_link' => $this->settings_model->get_setting('company_link'),
                    'company_email' => $this->settings_model->get_setting('company_email')
                ];

                if ($admin_only)
                {
                    $link = site_url('user/access/' . rawurlencode($token));

                    $email->send_admin_access(new Text($link), new Email($this->request->getPost('email')),
                        $company_settings);
                }
                else
                {
                    $link = site_url('user/reset_password/' . rawurlencode($token));

                    $email->send_password_reset(new Text($link), new Email($this->request->getPost('email')),
                        $company_settings);
                }
            }

            // The response is the same for every input, so that callers cannot enumerate the
            // existing accounts.
            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $this->response->setStatusCode(500);

            $response = $this->ajax_exception($exception);
        }

        return $this->response->setContentType('application/json')->setBody(json_encode($response));
    }

    /**
     * Display the password set page of a password reset link.
     *
     * The token gets passed between the requests in a hidden field, so that resubmissions
     * after an invalid password still work.
     *
     * @param string $token The plaintext token of the emailed link.
     */
    public function reset_password(string $token = '')
    {
        $access_key = 'access|client|' . $this->request->getIPAddress();

        if (Rate_limiter::has_reached_limit($access_key, 20))
        {
            session()->setFlashdata('login_message', lang('password_reset_token_invalid'));
            return redirect()->to('user/login');
        }

        Rate_limiter::register_attempt($access_key, 1800);

        $user_data = $this->user_model->get_access_token_rows($token, 'password_reset');

        if (empty($user_data))
        {
            log_message('info', 'Invalid or expired password reset link was used from "'
                . $this->request->getIPAddress() . '".');

            session()->setFlashdata('login_message', lang('password_reset_token_invalid'));
            return redirect()->to('user/login');
        }

        $view = [
            'base_url' => ea_config('base_url'),
            'company_name' => $this->settings_model->get_setting('company_name'),
            'token' => $token,
            'invalid' => session()->getFlashdata('password_reset_invalid')
        ];

        return view('user/reset_password', $view);
    }

    /**
     * Apply the password of a submitted password reset form.
     */
    public function ajax_reset_password()
    {
        $user_rows = NULL;

        try
        {
            $access_key = 'access|client|' . $this->request->getIPAddress();

            if (Rate_limiter::has_reached_limit($access_key, 20))
            {
                throw new Exception(lang('password_reset_token_invalid'));
            }

            Rate_limiter::register_attempt($access_key, 1800);

            $token = (string) $this->request->getPost('token');
            $password = (string) $this->request->getPost('password');
            $password_confirm = (string) $this->request->getPost('password_confirm');

            if ($password === '' || $password !== $password_confirm)
            {
                throw new Exception(lang('passwords_mismatch'));
            }

            $user_rows = $this->user_model->apply_password_reset($token, $password, 'password_reset');

            if (empty($user_rows))
            {
                throw new Exception(lang('password_reset_token_invalid'));
            }

            if (count($user_rows) > 1)
            {
                // The identity holds multiple roles: cache the verified rows and let the user
                // pick the role to continue with.
                session()->set('login_candidates', json_encode($user_rows));

                return redirect()->to('user/login?multi=1');
            }

            $this->establish_session($user_rows[0]);
        }
        catch (Exception $exception)
        {
            session()->setFlashdata('password_reset_invalid', TRUE);

            return redirect()->to('user/reset_password/' . rawurlencode((string) $this->request->getPost('token')));
        }

        log_message('info', 'Password reset applied for user #' . $user_rows[0]['id']
            . ' (' . $user_rows[0]['role_slug'] . ') from "' . $this->request->getIPAddress() . '".');

        return redirect()->to('backend');
    }

    /**
     * Provide the temporary administrator access of an emailed link (SSO-only deployments).
     *
     * @param string $token The plaintext token of the emailed link.
     */
    public function access(string $token = '')
    {
        $access_key = 'access|client|' . $this->request->getIPAddress();

        if (Rate_limiter::has_reached_limit($access_key, 20))
        {
            log_message('info', 'Admin access rate limit reached for "'
                . $this->request->getIPAddress() . '".');

            session()->setFlashdata('login_message', lang('password_reset_token_invalid'));
            return redirect()->to('user/login');
        }

        Rate_limiter::register_attempt($access_key, 1800);

        $user_data = $this->user_model->get_access_token_rows($token, 'admin_access');

        if (empty($user_data))
        {
            log_message('info', 'Invalid or expired admin access link was used from "'
                . $this->request->getIPAddress() . '".');

            session()->setFlashdata('login_message', lang('password_reset_token_invalid'));
            return redirect()->to('user/login');
        }

        // Multiple role rows can technically carry the admin token, even though the token
        // creation restricts the matching to admins: the first row is sufficient.
        $this->establish_session($user_data[0]);

        log_message('info', 'Temporary admin access granted to user #' . $user_data[0]['id']
            . ' (' . $user_data[0]['role_slug'] . ') from "' . $this->request->getIPAddress() . '".');

        return redirect()->to('backend');
    }
}
