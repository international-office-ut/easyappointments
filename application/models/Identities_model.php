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

/**
 * Identities Model
 *
 * Handles the shared-credential identity of a person across role records (admin / provider /
 * secretary): one email, one login name and one password are shared between rows that belong
 * to the same person, while each row keeps its own role-specific settings.
 *
 * @package Models
 */
class Identities_model extends EA_Model {
    /**
     * Fetch the rows that belong to the identity (users sharing the email).
     *
     * @param string $email Email of the identity.
     * @param int|null $except_user_id Exclude the provided user record id.
     *
     * @return array Returns an array with the sibling users (including the role slug and
     * the user_settings username).
     */
    public function sibling_rows(string $email, ?int $except_user_id = NULL): array
    {
        $query = $this->db
            ->select('users.*, roles.slug AS role_slug, user_settings.username AS login_name, '
                . 'user_settings.salt, user_settings.password')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->join('user_settings', 'user_settings.id_users = users.id', 'left')
            ->where('users.email', $email)
            ->order_by('users.id', 'ASC');

        if ($except_user_id)
        {
            $query->where('users.id !=', $except_user_id);
        }

        return $query->get()->result_array();
    }

    /**
     * Check whether the provided username may be used for the provided identity.
     *
     * A duplicate username is only allowed when the other user row that already carries it
     * shares the same email (they belong to the same person).
     *
     * @param string $username Candidate login name.
     * @param string $email Email of the identity the username belongs to.
     * @param int|null $user_id User record id of the saved row (NULL when inserting).
     *
     * @return bool Returns whether the username is acceptable for the identity.
     */
    public function validate_username(string $username, string $email, ?int $user_id = NULL): bool
    {
        $rows = $this->db
            ->select('users.email')
            ->from('user_settings')
            ->join('users', 'users.id = user_settings.id_users', 'inner')
            ->where('user_settings.username', $username)
            ->where('users.id !=', $user_id ?? 0)
            ->get()
            ->result_array();

        // No other row carries the username, so there is no conflict.
        if (count($rows) === 0)
        {
            return TRUE;
        }

        // Duplicates must only exist inside the same identity (same email address on all rows).
        foreach ($rows as $row)
        {
            if (strtolower($row['email']) !== strtolower($email))
            {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Sync a new plaintext password to all sibling rows of the identity.
     *
     * Each row keeps its own salt, so the password gets hashed separately for every row.
     *
     * @param string $email Email of the identity.
     * @param string $plaintext_password New plaintext password.
     * @param int|null $except_user_id Exclude the provided user record id.
     *
     * @return void
     */
    public function sync_password(string $email, string $plaintext_password, ?int $except_user_id = NULL): void
    {
        $siblings = $this->sibling_rows($email, $except_user_id);

        foreach ($siblings as $sibling)
        {
            if (empty($sibling['salt']))
            {
                $salt = generate_salt();
            }
            else
            {
                $salt = $sibling['salt'];
            }

            $this->db
                ->where('id_users', $sibling['id'])
                ->update('user_settings', [
                    'salt' => $salt,
                    'password' => hash_password($salt, $plaintext_password)
                ]);
        }
    }

    /**
     * Sync an email and/ or login name change to all sibling rows of the identity.
     *
     * The other rows of the identity must not keep stale credentials, so a rename on one row
     * is propagated to the rows that used the old values.
     *
     * @param string $old_email Old identity email used to find the sibling rows.
     * @param string|null $new_email New identity email (NULL leaves the email unchanged).
     * @param string|null $new_login_name New identity login name (NULL leaves the name unchanged).
     * @param string|null $old_login_name Old identity login name that should be replaced.
     * @param int|null $except_user_id Exclude the provided user record id.
     *
     * @return void
     */
    public function sync_email_and_login_name(
        string $old_email,
        ?string $new_email = NULL,
        ?string $new_login_name = NULL,
        ?string $old_login_name = NULL,
        ?int $except_user_id = NULL
    ): void
    {
        $siblings = $this->sibling_rows($old_email, $except_user_id);

        foreach ($siblings as $sibling)
        {
            if ($new_email && strtolower($sibling['email']) !== strtolower($new_email))
            {
                $this->db->where('id', $sibling['id'])->update('users', ['email' => $new_email]);
            }

            if ($new_login_name && $old_login_name && $sibling['login_name'] === $old_login_name)
            {
                $this->db->where('id_users', $sibling['id'])->update('user_settings',
                    ['username' => $new_login_name]);
            }
        }
    }

    /**
     * Replace the credentials of a new row with the ones of the existing identity.
     *
     * When a new role record is attached to an existing identity (same email on another role),
     * the new row does not need its own password: the salt and hash of the existing sibling
     * row are copied, so that the login works for both rows from the first login onwards.
     *
     * @param array $settings Settings of the new role row (passed by value, mutated below).
     * @param string $email Email used to find the existing identity.
     *
     * @return array Returns the mutated settings array.
     */
    public function attach_credentials(array $settings, string $email): array
    {
        $siblings = $this->sibling_rows($email);

        if (empty($siblings))
        {
            return $settings;
        }

        $primary = $siblings[0];

        if ( ! empty($primary['salt']) && ! empty($primary['password']))
        {
            $settings['salt'] = $primary['salt'];
            $settings['password'] = $primary['password'];
        }

        if ( ! empty($primary['login_name']) && empty($settings['username']))
        {
            $settings['username'] = $primary['login_name'];
        }

        return $settings;
    }

    /**
     * Determine whether an email is already used by another role row.
     *
     * @param string $email Email of the prospective new row.
     * @param string $role_slug Role of the existing table (e.g. DB_SLUG_PROVIDER).
     * @param int|null $except_user_id Exclude the provided user record id.
     *
     * @return bool Returns whether the email is used by another role.
     */
    public function exists_in_other_role(string $email, string $role_slug, ?int $except_user_id = NULL): bool
    {
        return (bool) $this->db
            ->select('*')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email', $email)
            ->where('roles.slug !=', $role_slug)
            ->where('users.id !=', $except_user_id ?? 0)
            ->get()
            ->num_rows();
    }
}
