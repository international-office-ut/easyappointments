<?php defined('BASEPATH') or exit('No direct script access allowed');

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

/**
 * User Model
 *
 * Contains current user's methods.
 *
 * @package Models
 */
class User_model extends EA_Model {
    /**
     * User_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('timezones');
        $this->load->helper('general');
        $this->load->helper('string');
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
        $user = $this->db->get_where('users', ['id' => $user_id])->row_array();
        $user['settings'] = $this->db->get_where('user_settings', ['id_users' => $user_id])->row_array();
        unset($user['settings']['id_users']);
        return $user;
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

        // Prepare user password (hash).
        if (isset($user_settings['password']))
        {
            $salt = $this->db->get_where('user_settings', ['id_users' => $user['id']])->row()->salt;
            $user_settings['password'] = hash_password($salt, $user_settings['password']);
        }

        if ( ! $this->db->update('users', $user, ['id' => $user['id']]))
        {
            return FALSE;
        }

        if ( ! $this->db->update('user_settings', $user_settings, ['id_users' => $user['id']]))
        {
            return FALSE;
        }

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
        $settings_rows = $this->db
            ->select("{$this->db->dbprefix('user_settings')}.id_users, username, salt, password")
            ->from('user_settings')
            ->join('users', 'users.id = user_settings.id_users', 'inner')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('user_settings.username', $username)
            ->where('roles.slug !=', DB_SLUG_CUSTOMER)
            ->get()
            ->result_array();

        if (empty($settings_rows))
        {
            return NULL;
        }

        $matches = [];

        foreach ($settings_rows as $user_settings)
        {
            if (empty($user_settings['salt']))
            {
                continue;
            }

            $password_hash = hash_password($user_settings['salt'], $password);

            if ($password_hash !== $user_settings['password'])
            {
                continue;
            }

            $user = $this->db->get_where('users', ['id' => $user_settings['id_users']])->row_array();

            if (empty($user))
            {
                continue;
            }

            $role = $this->db->get_where('roles', ['id' => $user['id_roles']])->row_array();

            if (empty($role))
            {
                continue;
            }

            $default_timezone = $this->timezones->get_default_timezone();

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
        $user = $this->db->get_where('user_settings', ['username' => $username])->row_array();
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

        $user = $this->db->get_where('users', ['id' => $user_id])->row_array();

        return $user['first_name'] . ' ' . $user['last_name'];
    }

    /**
     * If the given arguments correspond to an existing user record, generate a new
     * password and send it with an email.
     *
     * @param string $username User's username.
     * @param string $email User's email.
     *
     * @return string|bool Returns the new password on success or FALSE on failure.
     */
    public function regenerate_password($username, $email)
    {
        $result = $this->db
            ->select('users.id')
            ->from('users')
            ->join('user_settings', 'user_settings.id_users = users.id', 'inner')
            ->where('users.email', $email)
            ->where('user_settings.username', $username)
            ->get();

        if ($result->num_rows() == 0)
        {
            return FALSE;
        }

        // Create a new password and send it with an email to the given email address.
        $new_password = random_string('alnum', 12);

        // Reset the password on every record of the identity (same email and login name):
        // one person, one password, no matter how many role rows they hold. Each row keeps
        // its own salt, so the hash gets computed separately per row.
        foreach ($result->result_array() as $row)
        {
            $user_id = $row['id'];
            $salt = $this->db->get_where('user_settings', ['id_users' => $user_id])->row()->salt;
            $hash_password = hash_password($salt, $new_password);
            $this->db->update('user_settings', ['password' => $hash_password], ['id_users' => $user_id]);
        }

        return $new_password;
    }

    /**
     * Get the timezone of a user.
     *
     * @param int $id Database ID of the user.
     *
     * @return string|null
     */
    public function get_user_timezone($id)
    {
        $row = $this->db->get_where('users', ['id' => $id])->row_array();

        return $row ? $row['timezone'] : NULL;
    }
}
