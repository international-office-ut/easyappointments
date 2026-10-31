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
 * The identity contract for any write path (existing or future ones) is:
 * - A password write on a single row must ensure that every sibling row of the identity can
 *   use the same credentials, so password writes are cascaded through sync_password() and
 *   the update orchestrator sync_after_update().
 * - An email or login name rename on one row propagates to the sibling rows
 *   (sync_email_and_login_name()), otherwise the identity silently splits into two persons.
 * - New role rows of an existing identity inherit the credentials at insert time
 *   (attach_credentials()), a password must only be requested when the email is new.
 * - The password scheme stays per-row (own salt, per-row hash), only the plaintext value is
 *   shared.
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
            ->where('roles.slug !=', DB_SLUG_CUSTOMER)
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
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('user_settings.username', $username)
            ->where('roles.slug !=', DB_SLUG_CUSTOMER)
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
     * Ensure that a new role row without a password belongs to an existing identity.
     *
     * A passwordless row is only allowed when the same person (same email address) already
     * holds a row in another role, so that the credentials can be inherited at insert time.
     *
     * @param string $email Email of the prospective new row.
     * @param string $role_slug Role of the prospective new row.
     *
     * @return void
     *
     * @throws Exception When the email matches no role row and no password is provided.
     */
    public function assert_passwordless_row(string $email, string $role_slug): void
    {
        if ( ! $this->exists_in_other_role($email, $role_slug))
        {
            throw new Exception('The user password cannot be empty for new users.');
        }
    }

    /**
     * Keep the sibling rows of an identity in sync after a role row update.
     *
     * @param array $updated_user The values that are written to the users row (id + email).
     * @param string|null $old_email Email of the row before the update (NULL skips the email cascade).
     * @param array|null $old_settings Settings of the row before the update.
     * @param array $new_settings Settings that are written to the row.
     * @param string|null $new_password New plaintext password that is written to the row.
     *
     * @return void
     */
    public function sync_after_update(array $updated_user, ?string $old_email, ?array $old_settings,
        array $new_settings, ?string $new_password): void
    {
        $old_login_name = $old_settings['username'] ?? NULL;
        $new_login_name = $new_settings['username'] ?? ($old_login_name ?? NULL);

        if ($old_email !== NULL
            && ! empty($updated_user['email'])
            && $updated_user['email'] !== $old_email)
        {
            $this->sync_email_and_login_name(
                $old_email,
                $updated_user['email'],
                $new_login_name,
                $old_login_name,
                (int)$updated_user['id']
            );
        }
        else if ($old_login_name !== NULL
            && $new_login_name !== $old_login_name)
        {
            // A login name rename without an email change keeps the identity intact too, the
            // siblings must not keep the old login name.
            $this->sync_email_and_login_name(
                $updated_user['email'] ?? $old_email ?? '',
                NULL,
                $new_login_name,
                $old_login_name,
                (int)$updated_user['id']
            );
        }

        if ($new_password !== NULL)
        {
            $this->sync_password($updated_user['email'] ?? '', $new_password, (int)$updated_user['id']);
        }
    }

    /**
     * Providers whose records the given session role may read, edit or delete.
     *
     * Access is centralized here, because every endpoint that touches provider-bound
     * records needs the same answer: providers only get their own rows, secretaries the
     * rows of their bound providers and admins every row.
     *
     * @param string|NULL $role_slug The role slug of the session (requests without a usable
     * session get an empty scope instead of an unrestricted one).
     * @param int|NULL $user_id The user record id of the session.
     *
     * @return array|NULL Returns the provider ids in scope (empty when the session carries
     * no provider or secretary role), or NULL when all providers are in scope (admin and
     * other unrestricted roles).
     */
    public function providers_in_scope(?string $role_slug, ?int $user_id): ?array
    {
        if ($role_slug === NULL || ! $user_id)
        {
            return []; // No usable session, so the request gets no provider scope (fail closed).
        }

        if ($role_slug === DB_SLUG_PROVIDER)
        {
            return [$user_id];
        }

        if ($role_slug === DB_SLUG_SECRETARY)
        {
            $this->load->model('secretaries_model');

            return $this->secretaries_model->get_row($user_id)['providers'];
        }

        return NULL;
    }

    /**
     * Determine whether the given session role may work with a specific provider row.
     */
    public function provider_in_scope(?string $role_slug, ?int $user_id, $provider_id): bool
    {
        $provider_scope = $this->providers_in_scope($role_slug, $user_id);

        if ($provider_scope === NULL)
        {
            return TRUE;
        }

        return in_array((int) $provider_id, array_map('intval', $provider_scope), TRUE);
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
            ->where('roles.slug !=', DB_SLUG_CUSTOMER)
            ->where('users.id !=', $except_user_id ?? 0)
            ->get()
            ->num_rows();
    }
}
