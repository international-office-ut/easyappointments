<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

namespace App\Models;

use EA\Engine\Security\Passwords;

use Exception;

use App\Libraries\Timezones;

/**
 * User Model
 *
 * Contains current user's methods.
 */
class User_Model extends Ea_Model {
    /**
     * User_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns the user from the database for the "settings" page.
     *
     * @param int $user_id User record id.
     *
     * @return array Returns an array with user data.
     */
    public function get_user($user_id)
    {
        $user = $this->db->table('users')->where(['id' => $user_id])->get()->getRowArray();
        $user['settings'] = $this->db->table('user_settings')->where(['id_users' => $user_id])->get()->getRowArray();

        // The credentials never belong into view data (e.g. the settings page globals), the
        // password flows read them directly from the database when they are needed.
        unset(
            $user['settings']['id_users'],
            $user['settings']['salt'],
            $user['settings']['password']
        );

        return $user;
    }

    /**
     * Returns the backend user rows (admins, providers, secretaries) that carry the email of
     * an SSO login.
     *
     * Customers are excluded because they authenticate through the public booking page, so
     * their records are not login candidates.
     *
     * @param string $email Email address of the SSO login.
     *
     * @return array Returns a list of session payloads, one per matched role row.
     */
    public function get_users_by_email(string $email): array
    {
        $rows = $this->db->table('users')
            ->select('users.id, users.email, users.timezone, roles.slug AS role_slug')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email', $email)
            ->where('roles.slug !=', DB_SLUG_CUSTOMER)
            ->orderBy('users.id', 'ASC')
            ->get()
            ->getResultArray();

        $default_timezone = (new Timezones)->get_default_timezone();

        foreach ($rows as $index => $row)
        {
            $rows[$index] = [
                'user_id' => (int)$row['id'],
                'user_email' => $row['email'],
                'username' => '',
                'timezone' => isset($row['timezone']) ? $row['timezone'] : $default_timezone,
                'role_slug' => $row['role_slug'],
            ];
        }

        return $rows;
    }

    /**
     * This method saves the user record into the database (used in backend settings page).
     *
     * @param array $user Contains the current users data.
     *
     * @return bool Returns the operation result.
     */
    public function save_user($user)
    {
        $user_settings = $user['settings'];
        $user_settings['id_users'] = $user['id'];
        unset($user['settings']);

        // The settings page works with the sanitized rows without credentials (see get_user),
        // so the old values are read directly from the database for the identity cascade.
        $old_row = $this->db->table('users')->where(['id' => $user['id']])->get()->getRowArray();
        $old_settings = $this->db->table('user_settings')->where(['id_users' => $user['id']])->get()->getRowArray();

        $new_password = $user_settings['password'] ?? NULL;

        if ($new_password !== NULL)
        {
            $user_settings['salt'] = '';
            $user_settings['password'] = Passwords::hash($new_password);
        }

        if ( ! $this->db->table('users')->where(['id' => $user['id']])->update($user))
        {
            return FALSE;
        }

        if ( ! $this->db->table('user_settings')->where(['id_users' => $user['id']])->update($user_settings))
        {
            return FALSE;
        }

        // Identity cascade: a password change on the settings page of one role row must be
        // usable for the accounts of the same identity too (same email + login name).
        (new Identities_Model)->sync_after_update($user, $old_row['email'], $old_settings,
            $user_settings, $new_password);

        return TRUE;
    }

    /**
     * Performs the check of the given user credentials.
     *
     * The login name may now be shared between role rows inside the same identity (same
     * email), so the credentials are verified against every row that carries the provided
     * login name and the matching rows are returned for the role selection.
     *
     * @param string $username Given user's name.
     * @param string $password Given user's password (not hashed yet).
     *
     * @return array|null Returns the session data of the matched user, a candidate list when
     * the login name resolves into multiple rows (key 'multi') or NULL on failure.
     */
    public function check_login($username, $password)
    {
        $settings_rows = $this->db->table('user_settings')
            ->select($this->db->prefixTable('user_settings') . ".id_users, username, salt, password")
            ->join('users', 'users.id = user_settings.id_users', 'inner')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('user_settings.username', $username)
            ->where('roles.slug !=', DB_SLUG_CUSTOMER)
            ->get()
            ->getResultArray();

        if (empty($settings_rows))
        {
            return NULL;
        }

        $matches = [];

        foreach ($settings_rows as $user_settings)
        {
            if ( ! Passwords::verify($password, $user_settings['salt'], $user_settings['password']))
            {
                continue;
            }

            $user = $this->db->table('users')->where(['id' => $user_settings['id_users']])->get()->getRowArray();

            if (empty($user))
            {
                continue;
            }

            $role = $this->db->table('roles')->where(['id' => $user['id_roles']])->get()->getRowArray();

            if (empty($role))
            {
                continue;
            }

            $default_timezone = (new Timezones)->get_default_timezone();

            $matches[] = [
                'user_id' => $user['id'],
                'user_email' => $user['email'],
                'username' => $username,
                'timezone' => isset($user['timezone']) ? $user['timezone'] : $default_timezone,
                'role_slug' => $role['slug'],
            ];
        }

        if (count($matches) === 0)
        {
            return NULL;
        }

        if (count($matches) === 1)
        {
            return reset($matches);
        }

        // Multiple roles match the identity: the customer chooses the role to open.
        return ['multi' => TRUE, 'candidates' => $matches];
    }

    /**
     * Retrieve user's salt from database.
     *
     * @param string $username This will be used to find the user record.
     *
     * @return string Returns the salt db value.
     */
    public function get_salt($username)
    {
        $user = $this->db->table('user_settings')->where(['username' => $username])->get()->getRowArray();
        return ($user) ? $user['salt'] : '';
    }

    /**
     * Get the given user's display name (first + last name).
     *
     * @param int $user_id The given user record id.
     *
     * @return string Returns the user display name.
     *
     * @throws Exception If $user_id argument is invalid.
     */
    public function get_user_display_name($user_id)
    {
        if ( ! is_numeric($user_id))
        {
            throw new Exception ('Invalid argument given: ' . $user_id);
        }

        $user = $this->db->table('users')->where(['id' => $user_id])->get()->getRowArray();

        return $user['first_name'] . ' ' . $user['last_name'];
    }

    /**
     * Store a single-use access token for the user identity that matches the given
     * credentials and return its plaintext value.
     *
     * The token hash gets stored in every role row of the matched identity, so any of the
     * identity rows can consume it later. A locked SSO-only deployment (password login
     * disabled) can additionally be restricted to administrator rows, which keeps the
     * break-glass access alive while regular users must use the SSO.
     *
     * @param string $username User's username.
     * @param string $email User's email.
     * @param bool $admin_only Whether only administrator rows may be matched.
     * @param string $type The flow that gets authorized (a password reset or a temporary
     * admin access).
     * @param int $lifetime Token lifetime in seconds.
     *
     * @return string|bool Returns the plaintext token or FALSE when no match was found.
     */
    public function create_access_token(string $username, string $email, bool $admin_only, string $type, int $lifetime)
    {
        $builder = $this->db->table('users')
            ->select('users.id')
            ->join('user_settings', 'user_settings.id_users = users.id', 'inner')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email', $email)
            ->where('user_settings.username', $username);

        if ($admin_only)
        {
            $builder = $builder->where('roles.slug', DB_SLUG_ADMIN);
        }

        $query = $builder->get();

        if ($query->getNumRows() == 0)
        {
            return FALSE;
        }

        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expires = time() + $lifetime;

        foreach ($query->getResultArray() as $row)
        {
            $this->db->table('user_settings')->where(['id_users' => $row['id']])->update([
                'password_reset_token_hash' => $token_hash,
                'password_reset_expires' => $expires,
                'password_reset_type' => $type
            ]);
        }

        return $token;
    }

    /**
     * Find the role rows of a matching and unconsumed access token.
     *
     * @param string $token The plaintext token as it was stored in the link.
     * @param string $type Only rows with this token type match.
     *
     * @return array Returns the matching role rows (empty when the token is unknown,
     * expired or already used).
     */
    public function get_access_token_rows(string $token, string $type): array
    {
        $token_hash = hash('sha256', $token);

        $query = $this->db->table('user_settings')
            ->where('password_reset_token_hash', $token_hash)
            ->where('password_reset_type', $type)
            ->where('password_reset_expires >', time())
            ->get();

        $rows = $query->getResultArray();

        if (empty($rows))
        {
            return [];
        }

        // Select the user role rows that belong to the verified token rows, so the response
        // carries complete session payloads and not the settings rows only.
        $user_ids = array_column($rows, 'id_users');

        return $this->db->table('users')
            ->select('users.id, users.email, users.timezone, roles.slug AS role_slug')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->whereIn('users.id', $user_ids)
            ->get()->getResultArray();
    }

    /**
     * Consume the access token of the given type and set the new password for the whole
     * identity, but only when the token is valid.
     *
     * @param string $token The plaintext token as it was stored in the link.
     * @param string $new_password The new password to be set.
     * @param string $type Only tokens of this type can be consumed.
     *
     * @return array|bool Returns the affected user rows or FALSE when the token is invalid.
     */
    public function apply_password_reset(string $token, string $new_password, string $type)
    {
        $rows = $this->get_access_token_rows($token, $type);

        if (empty($rows))
        {
            return FALSE;
        }

        // One person one password: the identity sync writes the new password to every role
        // row of the same identity, each with its own salt (or a fresh salt when missing).
        (new Identities_Model)->sync_password($rows[0]['email'], $new_password);

        // The token is single-use, so it gets consumed everywhere, even on identity rows
        // that were outside the original match set.
        $this->db->table('user_settings')
            ->where('password_reset_token_hash', hash('sha256', $token))
            ->update([
                'password_reset_token_hash' => NULL,
                'password_reset_expires' => NULL,
                'password_reset_type' => NULL
            ]);

        return $rows;
    }
}
