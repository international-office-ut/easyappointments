<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

namespace EA\Engine\Api\V1;

use EA\Engine\Types\NonEmptyText;

/**
 * API v1 Authorization Class
 *
 * This class will handle the authorization procedure of the API.
 *
 * @deprecated
 */
class Authorization {
    /**
     * Login resolver: maps the credentials to a user row (array with "role_slug")
     * or a falsy value when the credentials do not match.
     *
     * @var callable
     */
    protected $check_login;

    /**
     * Role slug that marks the user as admin.
     *
     * @var string
     */
    protected $admin_role_slug;

    /**
     * Class Constructor
     *
     * @param callable $check_login Login resolver (a User_Model::check_login callable).
     * @param string $admin_role_slug Role slug that marks the user as admin.
     */
    public function __construct(callable $check_login, string $admin_role_slug)
    {
        $this->check_login = $check_login;
        $this->admin_role_slug = $admin_role_slug;
    }

    /**
     * Perform Basic Authentication
     *
     * @param NonEmptyText $username Admin Username
     * @param NonEmptyText $password Admin Password
     *
     * @throws Exception Throws 401-Unauthorized exception if the authentication fails.
     */
    public function basic(NonEmptyText $username, NonEmptyText $password)
    {
        self::check_admin_credentials(
            $username->get(),
            $password->get(),
            $this->check_login,
            $this->admin_role_slug
        );
    }

    /**
     * Verify a provided API bearer token against the configured one.
     *
     * The settings page only stores the SHA-256 hash of the generated token, so the
     * comparison hashes the provided value first. The comparison uses a constant-time
     * function, so that request timing does not leak any part of the configured token.
     * Both values must be present for the request to pass: an empty configured token
     * cannot disable the need for credentials here, the caller decides whether such a
     * request falls back to Basic authentication.
     *
     * @param mixed $configured_token SHA-256 hash stored in the application settings (may be NULL).
     * @param mixed $provided_token Token sent in the "Authorization: Bearer ..." header.
     * @return bool Returns TRUE only when both tokens exist and the hashes are equal.
     */
    public static function verify_bearer($configured_token, $provided_token): bool
    {
        if (empty($configured_token) || empty($provided_token))
        {
            return FALSE;
        }

        return hash_equals((string) $configured_token, hash('sha256', (string) $provided_token));
    }

    /**
     * Check login credentials against the login-check resolver and require the admin role.
     *
     * @param string $username Provided username.
     * @param string $password Provided password.
     * @param callable $check_login Resolves the credentials to a user record (array with
     * "role_slug") or a falsy value when the credentials do not match.
     * @param string $admin_role_slug Role slug that marks the user as admin.
     *
     * @return void Throws 401-Unauthorized when the credentials do not match an admin user.
     *
     * @throws Exception
     */
    public static function check_admin_credentials(string $username, string $password,
        callable $check_login, string $admin_role_slug): void
    {
        $user_data = $check_login($username, $password);

        if ( ! $user_data || $user_data['role_slug'] !== $admin_role_slug)
        {
            throw new Exception('The provided credentials do not match any admin user!', 401,
                'Unauthorized');
        }
    }
}
