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

use App\Helpers\General_helper;
use EA\Engine\Security\Passwords;

use Exception;

/**
 * Secretaries Model (CI4 port, from the CI3 application/models/Secretaries_model.php)
 *
 * Handles the db actions that have to do with secretaries.
 */
class Secretaries_Model extends Ea_Model {
    /**
     * Secretaries_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add (insert or update) a secretary user record into database.
     *
     * @param array $secretary Contains the secretary user data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception When the secretary data are invalid (see validate() method).
     */
    public function add($secretary)
    {
        $this->validate($secretary);

        if ($this->exists($secretary) && ! isset($secretary['id']))
        {
            $secretary['id'] = $this->find_record_id($secretary);
        }

        if ( ! isset($secretary['id']))
        {
            $secretary['id'] = $this->insert($secretary);
        }
        else
        {
            $secretary['id'] = $this->update($secretary);
        }

        return (int)$secretary['id'];
    }

    /**
     * Validate secretary user data before add() operation is executed.
     *
     * @param array $secretary Contains the secretary user data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If secretary validation fails.
     */
    public function validate($secretary)
    {
        // If a record id is provided then check whether the record exists in the database.
        if (isset($secretary['id']))
        {
            $num_rows = $this->db->table('users')->where(['id' => $secretary['id']])->countAllResults();

            if ($num_rows === 0)
            {
                throw new Exception('Given secretary id does not exist in database: ' . $secretary['id']);
            }
        }

        // Validate 'providers' value data type (must be array)
        if (isset($secretary['providers']) && ! is_array($secretary['providers']))
        {
            throw new Exception('Secretary providers value is not an array.');
        }

        // Validate required fields integrity.
        if ( ! isset(
            $secretary['last_name'],
            $secretary['email'],
            $secretary['phone_number']
        ))
        {
            throw new Exception('Not all required fields are provided: ' . describe_data_fields($secretary));
        }

        // Validate secretary email address.
        if ( ! filter_var($secretary['email'], FILTER_VALIDATE_EMAIL))
        {
            throw new Exception('Invalid email address provided: ' . $secretary['email']);
        }

        // Check if username exists.
        if (isset($secretary['settings']['username']))
        {
            $user_id = (isset($secretary['id'])) ? $secretary['id'] : NULL;

            if ( ! (new Identities_Model)->validate_username($secretary['settings']['username'], $secretary['email'], $user_id))
            {
                throw new Exception ('Username already exists. Please select a different '
                    . 'username for this record.');
            }
        }

        // Validate secretary password.
        if (isset($secretary['settings']['password']))
        {
            if (strlen($secretary['settings']['password']) < MIN_PASSWORD_LENGTH)
            {
                throw new Exception('The user password must be at least '
                    . MIN_PASSWORD_LENGTH . ' characters long.');
            }
        }

        if ( ! isset($secretary['id']) && ! isset($secretary['settings']['password']))
        {
            // A new secretary row that belongs to an existing identity (same email on another
            // role) inherits the password from that identity, so an empty password is fine.
            (new Identities_Model)->assert_passwordless_row($secretary['email'], DB_SLUG_SECRETARY);
        }

        // Validate calendar view mode.
        if (isset($secretary['settings']['calendar_view']) && ($secretary['settings']['calendar_view'] !== CALENDAR_VIEW_DEFAULT
                && $secretary['settings']['calendar_view'] !== CALENDAR_VIEW_TABLE))
        {
            throw new Exception('The calendar view setting must be either "' . CALENDAR_VIEW_DEFAULT
                . '" or "' . CALENDAR_VIEW_TABLE . '", given: ' . $secretary['settings']['calendar_view']);
        }

        // When inserting a record the email address must be unique.
        $secretary_id = (isset($secretary['id'])) ? $secretary['id'] : '';

        $num_rows = $this->db->table('users')
            ->select('*')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_SECRETARY)
            ->where('users.email', $secretary['email'])
            ->where('users.id !=', $secretary_id)
            ->get()
            ->getNumRows();

        if ($num_rows > 0)
        {
            throw new Exception('Given email address belongs to another secretary record. '
                . 'Please use a different email.');
        }

        return TRUE;
    }

    /**
     * Validate Records Username
     *
     * @param string $username The secretary records username.
     * @param int $user_id The user record id.
     *
     * @return bool Returns the validation result.
     */
    public function validate_username($username, $email, $user_id = NULL)
    {
        return (new Identities_Model)->validate_username($username, $email, $user_id);
    }

    /**
     * Check whether a particular secretary record exists in the database.
     *
     * @param array $secretary Contains the secretary data. The 'email' value is required to be present at the moment.
     *
     * @return bool Returns whether the record exists or not.
     *
     * @throws Exception When the 'email' value is not present on the $secretary argument.
     */
    public function exists($secretary)
    {
        if ( ! isset($secretary['email']))
        {
            throw new Exception('Secretary email is not provided: ' . describe_data_fields($secretary));
        }

        // This method shouldn't depend on another method of this class.
        $num_rows = $this->db->table('users')
            ->select('*')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email', $secretary['email'])
            ->where('roles.slug', DB_SLUG_SECRETARY)
            ->get()->getNumRows();

        return $num_rows > 0;
    }

    /**
     * Find the database record id of a secretary.
     *
     * @param array $secretary Contains the secretary data. The 'email' value is required in order to find the record id.
     *
     * @return int Returns the record id
     *
     * @throws Exception When the 'email' value is not present on the $secretary array.
     */
    public function find_record_id($secretary)
    {
        if ( ! isset($secretary['email']))
        {
            throw new Exception('Secretary email was not provided: ' . describe_data_fields($secretary));
        }

        $result = $this->db->table('users')
            ->select('users.id')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email', $secretary['email'])
            ->where('roles.slug', DB_SLUG_SECRETARY)
            ->get();

        if ($result->getNumRows() == 0)
        {
            throw new Exception('Could not find secretary record id.');
        }

        return (int)$result->getRow()->id;
    }

    /**
     * Insert a new secretary record into the database.
     *
     * @param array $secretary Contains the secretary data.
     *
     * @return int Returns the new record id.
     *
     * @throws Exception When the insert operation fails.
     */
    protected function insert($secretary)
    {
        $providers = $secretary['providers'];
        unset($secretary['providers']);
        $new_password = $secretary['settings']['password'] ?? NULL;
        $settings = $secretary['settings'];
        unset($secretary['settings']);

        // New records must not carry a record id; the auto increment value is assigned by
        // the database and a zero or empty id would create a broken user_settings link.
        unset($secretary['id']);
        $secretary['id_roles'] = $this->get_secretary_role_id();

        if ( ! $this->db->table('users')->insert($secretary))
        {
            throw new Exception('Could not insert secretary into the database.');
        }

        $secretary['id'] = (int)$this->db->insertID();

        if (isset($settings['password']))
        {
            $settings['salt'] = '';
            $settings['password'] = Passwords::hash($settings['password']);
        }
        else
        {
            // The new row belongs to an existing identity (same email on another role), so
            // its credentials are copied from the sibling row and a new password is not
            // required at this point.
            $settings = (new Identities_Model)->attach_credentials($settings, $secretary['email']);
        }

        $this->save_providers($providers, $secretary['id']);
        $this->save_settings($settings, $secretary['id']);

        // Sync the new password to the other role rows of the same identity.
        if ($new_password !== NULL)
        {
            (new Identities_Model)->sync_password($secretary['email'], $new_password,
                (int)$secretary['id']);
        }

        return $secretary['id'];
    }

    /**
     * Get the secretary users role id.
     *
     * @return int Returns the role record id.
     */
    public function get_secretary_role_id()
    {
        return (int)$this->db->table('roles')->where(['slug' => DB_SLUG_SECRETARY])->get()->getRow()->id;
    }

    /**
     * Save a secretary handling users.
     *
     * @param array $providers Contains the provider ids that are handled by the secretary.
     * @param int $secretary_id The selected secretary record.
     *
     * @throws Exception If $providers argument is invalid.
     */
    protected function save_providers($providers, $secretary_id)
    {
        if ( ! is_array($providers))
        {
            throw new Exception('Invalid argument given $providers: ' . describe_data_fields($providers));
        }

        // Delete old connections
        $this->db->table('secretaries_providers')->where(['id_users_secretary' => $secretary_id])->delete();

        if (count($providers) > 0)
        {
            foreach ($providers as $provider_id)
            {
                $this->db->table('secretaries_providers')->insert([
                    'id_users_secretary' => $secretary_id,
                    'id_users_provider' => $provider_id
                ]);
            }
        }
    }

    /**
     * Save the secretary settings (used from insert or update operation).
     *
     * @param array $settings Contains the setting values.
     * @param int $secretary_id Record id of the secretary.
     *
     * @throws Exception If $secretary_id argument is invalid.
     * @throws Exception If $settings argument is invalid.
     */
    protected function save_settings($settings, $secretary_id)
    {
        if ( ! is_numeric($secretary_id))
        {
            throw new Exception('Invalid $secretary_id argument given:' . $secretary_id);
        }

        if (count($settings) == 0 || ! is_array($settings))
        {
            throw new Exception('Invalid $settings argument given:' . describe_data_fields($settings));
        }

        // Check if the setting record exists in db.
        $num_rows = $this->db->table('user_settings')
            ->where(['id_users' => $secretary_id])->countAllResults();

        if ($num_rows == 0)
        {
            $this->db->table('user_settings')->insert(['id_users' => $secretary_id]);
        }

        foreach ($settings as $name => $value)
        {
            $this->set_setting($name, $value, $secretary_id);
        }
    }

    /**
     * Set a secretary's setting value in the database.
     *
     * The secretary and settings record must already exist.
     *
     * @param string $setting_name The setting's name.
     * @param string $value The setting's value.
     * @param int $secretary_id The selected secretary id.
     *
     * @return bool
     */
    public function set_setting($setting_name, $value, $secretary_id)
    {
        return $this->db->table('user_settings')->where(['id_users' => $secretary_id])
            ->update([$setting_name => $value]);
    }

    /**
     * Update an existing secretary record in the database.
     *
     * @param array $secretary Contains the secretary record data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception When the update operation fails.
     */
    protected function update($secretary)
    {
        $providers = $secretary['providers'];
        unset($secretary['providers']);
        $new_password = $secretary['settings']['password'] ?? NULL;
        $settings = $secretary['settings'];
        unset($secretary['settings']);

        $old_row = $this->db->table('users')->where(['id' => $secretary['id']])->get()->getRowArray();
        $old_settings = $this->db->table('user_settings')->where(['id_users' => $secretary['id']])->get()->getRowArray();

        if (isset($settings['password']))
        {
            $settings['salt'] = '';
            $settings['password'] = Passwords::hash($settings['password']);
        }

        if ( ! $this->db->table('users')->where('id', $secretary['id'])->update($secretary))
        {
            throw new Exception('Could not update secretary record.');
        }

        $this->save_providers($providers, $secretary['id']);
        $this->save_settings($settings, $secretary['id']);

        // Identity cascade: keep the other role rows of the same person in sync.
        (new Identities_Model)->sync_after_update($secretary,
            $old_row ? $old_row['email'] : NULL, $old_settings, $settings, $new_password);

        return (int)$secretary['id'];
    }

    /**
     * Delete an existing secretary record from the database.
     *
     * @param int $secretary_id The secretary record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception When the $secretary_id is not a valid int value.
     */
    public function delete($secretary_id)
    {
        if ( ! is_numeric($secretary_id))
        {
            throw new Exception('Invalid argument type $secretary_id: ' . $secretary_id);
        }

        $num_rows = $this->db->table('users')->where(['id' => $secretary_id])->countAllResults();
        if ($num_rows == 0)
        {
            return FALSE; // Record does not exist in database.
        }

        return $this->db->table('users')->where(['id' => $secretary_id])->delete();
    }

    /**
     * Get a specific secretary record from the database.
     *
     * @param int $secretary_id The id of the record to be returned.
     *
     * @return array Returns an array with the secretary user data.
     *
     * @throws Exception When the $secretary_id is not a valid int value.
     * @throws Exception When given record id does not exist in the database.
     */
    public function get_row($secretary_id)
    {
        if ( ! is_numeric($secretary_id))
        {
            throw new Exception('$secretary_id argument is not a valid numeric value: ' . $secretary_id);
        }

        // Check if record exists
        if ($this->db->table('users')->where(['id' => $secretary_id])->countAllResults() == 0)
        {
            throw new Exception('The given secretary id does not match a record in the database.');
        }

        $secretary = $this->db->table('users')->where(['id' => $secretary_id])->get()->getRowArray();

        $secretary_providers = $this->db->table('secretaries_providers')
            ->where(['id_users_secretary' => $secretary['id']])->get()->getResultArray();

        $secretary['providers'] = [];
        foreach ($secretary_providers as $secretary_provider)
        {
            $secretary['providers'][] = $secretary_provider['id_users_provider'];
        }

        $secretary['settings'] = $this->db->table('user_settings')
            ->where(['id_users' => $secretary['id']])->get()->getRowArray();

        unset($secretary['settings']['id_users'], $secretary['settings']['salt']);

        return $secretary;
    }

    /**
     * Get a specific field value from the database.
     *
     * @param string $field_name The field name of the value to be returned.
     * @param int $secretary_id Record id of the value to be returned.
     *
     * @return string Returns the selected record value from the database.
     *
     * @throws Exception When the $field_name argument is not a valid string.
     * @throws Exception When the $secretary_id is not a valid int.
     * @throws Exception When the secretary record does not exist in the database.
     * @throws Exception When the selected field value is not present on database.
     */
    public function get_value($field_name, $secretary_id)
    {
        if ( ! is_string($field_name))
        {
            throw new Exception('$field_name argument is not a string: ' . $field_name);
        }

        if ( ! is_numeric($secretary_id))
        {
            throw new Exception('$secretary_id argument is not a valid numeric value: ' . $secretary_id);
        }

        // Check whether the secretary record exists.
        $result = $this->db->table('users')->where(['id' => $secretary_id])->get();

        if ($result->getNumRows() === 0)
        {
            throw new Exception('The record with the given id does not exist in the '
                . 'database: ' . $secretary_id);
        }

        // Check if the required field name exist in database.
        $row_data = $result->getRowArray();

        if ( ! array_key_exists($field_name, $row_data))
        {
            throw new Exception('The given $field_name argument does not exist in the database: '
                . $field_name);
        }

        return $row_data[$field_name];
    }

    /**
     * Get all, or specific secretary records from database.
     *
     * @param mixed|null $where (OPTIONAL) The WHERE clause of the query to be executed.
     * @param int|null $limit
     * @param int|null $offset
     * @param mixed|null $order_by
     *
     * @return array Returns an array with secretary records.
     */
    public function get_batch($where = NULL, $limit = NULL, $offset = NULL, $order_by = NULL)
    {
        $role_id = $this->get_secretary_role_id();

        $builder = $this->db->table('users');

        if ($where !== NULL)
        {
            $builder->where($where);
        }

        if ($order_by !== NULL)
        {
            $builder->orderBy($order_by);
        }

        if ($limit !== NULL || $offset !== NULL)
        {
            $builder->limit($limit ?? 0, $offset);
        }

        $batch = $builder->where(['id_roles' => $role_id])->get()->getResultArray();

        // Include every secretary providers.
        $this->attach_secretary_relations($batch);

        foreach ($batch as &$secretary)
        {
            // The page UI never needs the stored credentials, so they stay in the database.
            unset($secretary['settings']['password'], $secretary['settings']['salt']);
        }

        return $batch;
    }

    /**
     * Attach the related providers and settings of the provided secretaries.
     *
     * Runs two aggregate queries instead of two subqueries per secretary (N+1) and maps the
     * results in memory.
     *
     * @param array $batch Secretaries passed by reference; the "providers" and "settings" keys get attached.
     *
     * @return void
     */
    protected function attach_secretary_relations(array &$batch): void
    {
        if (empty($batch))
        {
            return;
        }

        $secretary_ids = array_map(static function (array $secretary): int
        {
            return (int)$secretary['id'];
        }, $batch);

        $provider_rows = $this->db->table('secretaries_providers')
            ->whereIn('id_users_secretary', $secretary_ids)
            ->get()
            ->getResultArray();

        $providers_map = [];
        foreach ($provider_rows as $provider_row)
        {
            $providers_map[$provider_row['id_users_secretary']][] = $provider_row['id_users_provider'];
        }

        $settings_rows = $this->db->table('user_settings')
            ->whereIn('id_users', $secretary_ids)
            ->get()
            ->getResultArray();

        $settings_map = [];
        foreach ($settings_rows as $settings_row)
        {
            $secretary_id = (int)$settings_row['id_users'];
            unset($settings_row['id_users']);
            $settings_map[$secretary_id] = $settings_row;
        }

        foreach ($batch as &$secretary)
        {
            $secretary_id = (int)$secretary['id'];

            $secretary['providers'] = $providers_map[$secretary_id] ?? [];
            $secretary['settings'] = $settings_map[$secretary_id] ?? [];
        }
    }

    /**
     * Get a secretaries setting from the database.
     *
     * @param string $setting_name The setting name that is going to be returned.
     * @param int $secretary_id The selected provider id.
     *
     * @return string Returns the value of the selected user setting.
     */
    public function get_setting($setting_name, $secretary_id)
    {
        $secretary_settings = $this->db->table('user_settings')
            ->where(['id_users' => $secretary_id])->get()->getRowArray();

        return $secretary_settings[$setting_name];
    }
}
