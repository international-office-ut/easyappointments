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

/**
 * Providers Model (CI4 port, from the CI3 application/models/Providers_model.php)
 *
 * Contains the database operations for the service provider users of Easy!Appointments.
 */
class Providers_Model extends Ea_Model {
    /**
     * Providers_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add (insert - update) a service provider record.
     *
     * If the record already exists (id value provided) then it is going to be updated, otherwise inserted into the
     * database.
     *
     * @param array $provider Contains the service provider data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception When the record data validation fails.
     */
    public function add($provider)
    {
        $this->validate($provider);

        if ($this->exists($provider) && ! isset($provider['id']))
        {
            $provider['id'] = $this->find_record_id($provider);
        }

        if ( ! isset($provider['id']))
        {
            $provider['id'] = $this->insert($provider);
        }
        else
        {
            $provider['id'] = $this->update($provider);
        }

        return (int)$provider['id'];
    }

    /**
     * Validate provider data before the insert or  update operation is executed.
     *
     * @param array $provider Contains the provider data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If provider validation fails.
     */
    public function validate($provider)
    {
        // If a provider id is present, check whether the record exist in the database.
        if (isset($provider['id']))
        {
            $num_rows = $this->db->table('users')
                ->where(['id' => $provider['id']])->countAllResults();

            if ($num_rows == 0)
            {
                throw new Exception('Provided record id does not exist in the database.');
            }
        }

        // Validate required fields.
        if ( ! isset(
            $provider['last_name'],
            $provider['email'],
            $provider['phone_number']
        ))
        {
            throw new Exception('Not all required fields are provided: ' . describe_data_fields($provider));
        }

        // Validate provider email address.
        if ( ! filter_var($provider['email'], FILTER_VALIDATE_EMAIL))
        {
            throw new Exception('Invalid email address provided: ' . $provider['email']);
        }

        // Validate provider services.
        if ( ! isset($provider['services']) || ! is_array($provider['services']))
        {
            throw new Exception('Invalid provider services given: ' . describe_data_fields($provider));
        }
        else
        {
            // Check if services are valid int values.
            foreach ($provider['services'] as $service_id)
            {
                if ( ! is_numeric($service_id))
                {
                    throw new Exception('A provider service with invalid id was found: '
                        . describe_data_fields($provider));
                }
            }
        }

        // Validate provider settings.
        if ( ! isset($provider['settings']) || count($provider['settings']) === 0
            || ! is_array($provider['settings']))
        {
            throw new Exception('Invalid provider settings given: ' . describe_data_fields($provider));
        }

        // Check if username exists.
        if (isset($provider['settings']['username']))
        {
            $user_id = (isset($provider['id'])) ? $provider['id'] : NULL;

            if ( ! (new Identities_Model)->validate_username($provider['settings']['username'], $provider['email'], $user_id))
            {
                throw new Exception ('Username already exists. Please select a different '
                    . 'username for this record.');
            }
        }

        // Validate provider password
        if (isset($provider['settings']['password']))
        {
            if (strlen($provider['settings']['password']) < MIN_PASSWORD_LENGTH)
            {
                throw new Exception('The user password must be at least '
                    . MIN_PASSWORD_LENGTH . ' characters long.');
            }
        }

        if ( ! isset($provider['id']) && ! isset($provider['settings']['password']))
        {
            // A new provider row that belongs to an existing identity (same email on another
            // role) inherits the password from that identity, so an empty password is fine.
            (new Identities_Model)->assert_passwordless_row($provider['email'], DB_SLUG_PROVIDER);
        }

        // Validate calendar view mode.
        if (isset($provider['settings']['calendar_view']) && ($provider['settings']['calendar_view'] !== CALENDAR_VIEW_DEFAULT
                && $provider['settings']['calendar_view'] !== CALENDAR_VIEW_TABLE))
        {
            throw new Exception('The calendar view setting must be either "' . CALENDAR_VIEW_DEFAULT
                . '" or "' . CALENDAR_VIEW_TABLE . '", given: ' . $provider['settings']['calendar_view']);
        }

        // Validate appointment mode (empty values will make the customer choose between both modes).
        if (isset($provider['settings']['appointment_mode'])
            && $provider['settings']['appointment_mode'] !== ''
            && $provider['settings']['appointment_mode'] !== APPOINTMENT_MODE_ONLINE
            && $provider['settings']['appointment_mode'] !== APPOINTMENT_MODE_ON_PREMISE
            && $provider['settings']['appointment_mode'] !== APPOINTMENT_MODE_BOTH)
        {
            throw new Exception('The appointment mode setting must be either "' . APPOINTMENT_MODE_ONLINE
                . '", "' . APPOINTMENT_MODE_ON_PREMISE . '" or "' . APPOINTMENT_MODE_BOTH . '", given: '
                . $provider['settings']['appointment_mode']);
        }

        // Online appointments require a Zoom link (the mobile number was the legacy workaround).
        if (isset($provider['settings']['appointment_mode'])
            && $provider['settings']['appointment_mode'] === APPOINTMENT_MODE_ONLINE
            && empty($provider['settings']['zoom_link'])
            && empty($provider['mobile_number']))
        {
            throw new Exception('The "' . APPOINTMENT_MODE_ONLINE . '" appointment mode requires '
                . 'a Zoom link to be configured for the provider.');
        }

        // When inserting a record the email address must be unique.
        $provider_id = (isset($provider['id'])) ? $provider['id'] : '';

        $num_rows = $this->db->table('users')
            ->select('*')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->where('users.email', $provider['email'])
            ->where('users.id !=', $provider_id)
            ->get()
            ->getNumRows();

        if ($num_rows > 0)
        {
            throw new Exception('Given email address belongs to another provider record. '
                . 'Please use a different email.');
        }

        return TRUE;
    }

    /**
     * Validate Records Username
     *
     * @param string $username The provider records username.
     * @param int $user_id The user record id.
     *
     * @return bool Returns the validation result.
     */
    public function validate_username($username, $email, $user_id = NULL)
    {
        return (new Identities_Model)->validate_username(
            $username,
            $email,
            $user_id
        );
    }

    /**
     * Check whether a particular provider record already exists in the database.
     *
     * @param array $provider Contains the provider data. The 'email' value is required in order to check for a provider.
     *
     * @return bool Returns whether the provider record exists or not.
     *
     * @throws Exception When the 'email' value is not provided.
     */
    public function exists($provider)
    {
        if ( ! isset($provider['email']))
        {
            throw new Exception('Provider email is not provided:' . describe_data_fields($provider));
        }

        // This method shouldn't depend on another method of this class.
        $num_rows = $this->db->table('users')
            ->select('*')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email', $provider['email'])
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->get()->getNumRows();

        return $num_rows > 0;
    }

    /**
     * Find the database record id of a provider.
     *
     * @param array $provider Contains the provider data. The 'email' value is required in order to find the record id.
     *
     * @return int Returns the record id.
     *
     * @throws Exception When the provider's email value is not provided.
     */
    public function find_record_id($provider)
    {
        if ( ! isset($provider['email']))
        {
            throw new Exception('Provider email was not provided:' . describe_data_fields($provider));
        }

        $result = $this->db->table('users')
            ->select('users.id')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email', $provider['email'])
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->get();

        if ($result->getNumRows() == 0)
        {
            throw new Exception('Could not find provider record id.');
        }

        return (int)$result->getRow()->id;
    }

    /**
     * Insert a new provider record into the database.
     *
     * @param array $provider Contains the provider data (must be already validated).
     *
     * @return int Returns the new record id.
     *
     * @throws Exception When the insert operation fails.
     */
    protected function insert($provider)
    {

        // New records must not carry a record id; the auto increment value is assigned by
        // the database and a zero or empty id would create a broken user_settings link.
        unset($provider['id']);

        // Get provider role id.
        $provider['id_roles'] = $this->get_providers_role_id();

        // Store provider settings and services (must not be present on the $provider array).
        $services = $provider['services'];
        unset($provider['services']);
        $new_password = $provider['settings']['password'] ?? NULL;
        $settings = $provider['settings'];
        unset($provider['settings']);

        // Insert provider record and save settings.
        if ( ! $this->db->table('users')->insert($provider))
        {
            throw new Exception('Could not insert provider into the database');
        }

        $provider['id'] = (int)$this->db->insertID();

        if ( ! $provider['id'])
        {
            throw new Exception('Could not retrieve the inserted provider id (insert id returned 0).');
        }

        if (isset($settings['password']))
        {
            $settings['salt'] = generate_salt();
            $settings['password'] = hash_password($settings['salt'], $settings['password']);
        }
        else
        {
            // The new row belongs to an existing identity (same email on another role), so
            // its credentials are copied from the sibling row and a new password is not
            // required at this point. NOTE: the provider id is captured ONCE right after
            // the users insert: MySQLi resets the client-side insert id after any
            // non-insert statement (e.g. the SELECTs of the identity lookup), so another
            // read here would return 0 and break the user_settings link.
            $settings = (new Identities_Model)->attach_credentials($settings, $provider['email']);
        }

        $this->save_settings($settings, $provider['id']);
        $this->save_services($services, $provider['id']);

        // Sync the new password to the other role rows of the same identity.
        if ($new_password !== NULL)
        {
            (new Identities_Model)->sync_password($provider['email'], $new_password, $provider['id']);
        }

        // Return the new record id.
        return (int)$provider['id'];
    }

    /**
     * Get the providers role id from the database.
     *
     * @return int Returns the role id for the provider records.
     */
    public function get_providers_role_id()
    {
        return $this->db->table('roles')->where(['slug' => DB_SLUG_PROVIDER])->get()->getRow()->id;
    }

    /**
     * Save the provider settings (used from insert or update operation).
     *
     * @param array $settings Contains the setting values.
     * @param int $provider_id Record id of the provider.
     *
     * @throws Exception If $provider_id argument is invalid.
     * @throws Exception If $settings argument is invalid.
     */
    protected function save_settings($settings, $provider_id)
    {
        if ( ! is_numeric($provider_id))
        {
            throw new Exception('Invalid $provider_id argument given:' . $provider_id);
        }

        if (count($settings) == 0 || ! is_array($settings))
        {
            throw new Exception('Invalid $settings argument given:' . describe_data_fields($settings));
        }

        // Check if the setting record exists in db.
        if ($this->db->table('user_settings')->where(['id_users' => $provider_id])->countAllResults() === 0)
        {
            $this->db->table('user_settings')->insert(['id_users' => $provider_id]);
        }

        foreach ($settings as $name => $value)
        {
            // Sort in descending order the working plan exceptions in a reverse order (makes it easier to edit them
            // later on).
            if ($name === 'working_plan_exceptions')
            {
                $value = json_decode($value, TRUE);

                if ( ! $value)
                {
                    $value = [];
                }

                krsort($value);

                $value = json_encode($value);
            }

            $this->set_setting($name, $value, $provider_id);
        }
    }

    /**
     * Set a provider's setting value in the database.
     *
     * The provider and settings record must already exist.
     *
     * @param string $setting_name The setting's name.
     * @param string $value The setting's value.
     * @param int $provider_id The selected provider id.
     *
     * @return bool
     */
    public function set_setting($setting_name, $value, $provider_id)
    {
        return $this->db->table('user_settings')->where(['id_users' => $provider_id])
            ->update([$setting_name => $value]);
    }

    /**
     * Save the provider services in the database (use on both insert and update operation).
     *
     * @param array $services Contains the service ids that the selected provider can provide.
     * @param int $provider_id The selected provider record id.
     *
     * @throws Exception When the $services argument type is not array.
     * @throws Exception When the $provider_id argument type is not int.
     */
    protected function save_services($services, $provider_id)
    {
        // Validate method arguments.
        if ( ! is_array($services))
        {
            throw new Exception('Invalid argument type $services: ' . $services);
        }

        if ( ! is_numeric($provider_id))
        {
            throw new Exception('Invalid argument type $provider_id: ' . $provider_id);
        }

        // Save provider services in the database (delete old records and add new).
        $this->db->table('services_providers')->where(['id_users' => $provider_id])->delete();

        foreach ($services as $service_id)
        {
            $service_provider = [
                'id_users' => $provider_id,
                'id_services' => $service_id
            ];

            $this->db->table('services_providers')->insert($service_provider);
        }
    }

    /**
     * Update an existing provider record in the database.
     *
     * @param array $provider Contains the provider data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception When the update operation fails.
     */
    protected function update($provider)
    {
        // Store service and settings (must not be present on the $provider array).
        $services = $provider['services'];
        unset($provider['services']);
        $new_password = $provider['settings']['password'] ?? NULL;
        $settings = $provider['settings'];
        unset($provider['settings']);

        $old_row = $this->db->table('users')->where(['id' => $provider['id']])->get()->getRowArray();
        $old_settings = $this->db->table('user_settings')->where(['id_users' => $provider['id']])->get()->getRowArray();

        if (isset($settings['password']))
        {
            $salt = $this->db->table('user_settings')->where(['id_users' => $provider['id']])->get()->getRow()->salt;
            $settings['password'] = hash_password($salt, $settings['password']);
        }

        // Update provider record.
        if ( ! $this->db->table('users')->where('id', $provider['id'])->update($provider))
        {
            throw new Exception('Could not update provider record.');
        }

        $this->save_services($services, $provider['id']);
        $this->save_settings($settings, $provider['id']);

        // Identity cascade: keep the other role rows of the same person in sync.
        (new Identities_Model)->sync_after_update($provider,
            $old_row ? $old_row['email'] : NULL, $old_settings, $settings, $new_password);

        // Return record id.
        return (int)$provider['id'];
    }

    /**
     * Delete an existing provider record from the database.
     *
     * @param $provider_id
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception When the provider id value is not int.
     */
    public function delete($provider_id)
    {
        if ( ! is_numeric($provider_id))
        {
            throw new Exception('Invalid argument type $provider_id: ' . $provider_id);
        }

        $num_rows = $this->db->table('users')->where(['id' => $provider_id])->countAllResults();
        if ($num_rows === 0)
        {
            return FALSE; // Record does not exist in database.
        }

        return $this->db->table('users')->where(['id' => $provider_id])->delete();
    }

    /**
     * Get a specific field value from the database.
     *
     * @param string $field_name The field name of the value to be returned.
     * @param int $provider_id Record id of the value to be returned.
     *
     * @return string Returns the selected record value from the database.
     *
     * @throws Exception When the $field_name argument is not a valid string.
     * @throws Exception When the $provider_id is not a valid int.
     * @throws Exception When the provider record does not exist in the database.
     * @throws Exception When the selected field value is not present on database.
     */
    public function get_value($field_name, $provider_id)
    {
        if ( ! is_numeric($provider_id))
        {
            throw new Exception('Invalid argument provided as $provider_id: ' . $provider_id);
        }

        if ( ! is_string($field_name))
        {
            throw new Exception('$field_name argument is not a string: ' . $field_name);
        }

        // Check whether the provider record exists in database.
        $result = $this->db->table('users')->where(['id' => $provider_id])->get();

        if ($result->getNumRows() == 0)
        {
            throw new Exception('The record with the $provider_id argument does not exist in the database: '
                . $provider_id);
        }

        $row_data = $result->getRowArray();

        if ( ! isset($row_data[$field_name]))
        {
            throw new Exception('The given $field_name argument does not exist in the database: ' . $field_name);
        }

        return $row_data[$field_name];
    }

    /**
     * Get all, or specific records from provider's table.
     *
     * Example:
     *
     * $this->Model->get_batch('id = ' . $recordId);
     *
     * @param mixed|null $where (OPTIONAL) The WHERE clause of the query to be executed.
     * @param int|null $limit
     * @param int|null $offset
     * @param mixed|null $order_by
     *
     * @return array Returns the rows from the database.
     */
    public function get_batch($where = NULL, $limit = NULL, $offset = NULL, $order_by = NULL)
    {
        // Get the role id first and then apply the get_batch() where clause.
        $role_id = $this->get_providers_role_id();

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

        // Include each provider services and settings.
        $this->attach_provider_relations($batch);

        foreach ($batch as &$provider)
        {
            // The page UI never needs the stored credentials, so they stay in the database.
            unset($provider['settings']['password'], $provider['settings']['salt']);
        }

        // Return provider records in an array.
        return $batch;
    }

    /**
     * Attach the related services and settings of the provided providers.
     *
     * Runs two aggregate queries instead of two subqueries per provider (N+1) and maps the
     * results in memory.
     *
     * @param array $batch Providers passed by reference; the "services" and "settings" keys get attached.
     *
     * @return void
     */
    protected function attach_provider_relations(array &$batch): void
    {
        if (empty($batch))
        {
            return;
        }

        $provider_ids = array_map(static function (array $provider): int
        {
            return (int)$provider['id'];
        }, $batch);

        $services_rows = $this->db->table('services_providers')
            ->whereIn('id_users', $provider_ids)
            ->get()
            ->getResultArray();

        $settings_rows = $this->db->table('user_settings')
            ->whereIn('id_users', $provider_ids)
            ->get()
            ->getResultArray();

        $services_map = [];
        foreach ($services_rows as $service_row)
        {
            $services_map[$service_row['id_users']][] = $service_row['id_services'];
        }

        $settings_map = [];
        foreach ($settings_rows as $settings_row)
        {
            $provider_id = (int)$settings_row['id_users'];
            unset($settings_row['id_users']);
            $settings_map[$provider_id] = $settings_row;
        }

        foreach ($batch as &$provider)
        {
            $provider_id = (int)$provider['id'];

            $provider['services'] = $services_map[$provider_id] ?? [];
            $provider['settings'] = $settings_map[$provider_id] ?? [];
        }
    }

    /**
     * Get the available system providers.
     *
     * This method returns the available providers and the services that can provide.
     *
     * @return array Returns an array with the providers data.
     */
    public function get_available_providers()
    {
        // Get provider records from database.
        $providers = $this->db->table('users')
            ->select('users.*')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->orderBy('first_name ASC, last_name ASC, email ASC')
            ->get()->getResultArray();

        // Include each provider services and settings.
        $this->attach_provider_relations($providers);

        foreach ($providers as &$provider)
        {
            // The booking page must not see the provider credentials.
            unset(
                $provider['settings']['username'],
                $provider['settings']['password'],
                $provider['settings']['salt']
            );
        }

        // Return provider records.
        return $providers;
    }

    /**
     * Generate a fresh calendar feed token for the provided provider.
     *
     * Only one feed token exists per provider: generating a new one revokes the previous
     * automatically. The plaintext token is returned once and never stored — the database
     * only keeps its SHA-256 hash, so the feed URL cannot be recovered from the database.
     *
     * @param int $provider_id Provider record id.
     *
     * @return string Returns the base64url token for the feed URL.
     */
    public function generate_feed_token(int $provider_id): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $token_hash = hash('sha256', $token);

        $this->db->transBegin();

        $this->db->table('provider_calendar_tokens')->where('id_users', $provider_id)->delete();

        $this->db->table('provider_calendar_tokens')->insert([
            'id_users' => $provider_id,
            'token_hash' => $token_hash
        ]);

        $this->db->transComplete();

        return $token;
    }

    /**
     * Revoke every calendar feed token of the provided provider.
     *
     * @param int $provider_id Provider record id.
     *
     * @return void
     */
    public function revoke_feed_token(int $provider_id): void
    {
        $this->db->table('provider_calendar_tokens')->where('id_users', $provider_id)->delete();
    }

    /**
     * Check whether the provider currently owns a calendar feed token.
     *
     * @param int $provider_id Provider record id.
     *
     * @return bool Returns whether an active token exists.
     */
    public function has_feed_token(int $provider_id): bool
    {
        return $this->db->table('provider_calendar_tokens')
            ->where('id_users', $provider_id)->countAllResults() > 0;
    }

    /**
     * Resolve the provider record id of a feed token.
     *
     * The token is looked up by its hash, so a wrong guess costs one indexed query and
     * reveals nothing. The last-used timestamp is refreshed at most every ten minutes so
     * that frequent calendar clients do not extend the write load.
     *
     * @param string $token Feed token from the URL (base64url).
     *
     * @return int|null Returns the provider id of the matched token, or NULL when invalid.
     */
    public function get_provider_id_by_feed_token(string $token): ?int
    {
        $token_hash = hash('sha256', $token);

        $row = $this->db->table('provider_calendar_tokens')
            ->select('id, id_users, token_hash, last_used_at')
            ->where('token_hash', $token_hash)
            ->get()
            ->getRowArray();

        if (empty($row) || ! hash_equals($row['token_hash'], $token_hash))
        {
            return NULL;
        }

        $refresh = $row['last_used_at'] === NULL
            || (time() - strtotime($row['last_used_at'])) > 600;

        if ($refresh)
        {
            $this->db->table('provider_calendar_tokens')
                ->where('id', $row['id'])
                ->update(['last_used_at' => date('Y-m-d H:i:s')]);
        }

        return (int)$row['id_users'];
    }

    /**
     * Save the provider working plan exception.
     *
     * @param string $date The working plan exception date (in YYYY-MM-DD format).
     * @param array $working_plan_exception Contains the working plan exception information ("start", "end" and "breaks"
     * properties).
     * @param int $provider_id The selected provider record id.
     *
     * @return bool Return if the new working plan exceptions is correctly saved to DB.
     *
     * @throws Exception If start time is after the end time.
     * @throws Exception If $provider_id argument is invalid.
     */
    public function save_working_plan_exception($date, $working_plan_exception, $provider_id)
    {
        // Validate the working plan exception data.
        $start = date('H:i', strtotime($working_plan_exception['start']));
        $end = date('H:i', strtotime($working_plan_exception['end']));

        if ($start > $end)
        {
            throw new Exception('Working plan exception "start" must be prior to "end".');
        }

        // Make sure the provider record exists.
        $conditions = [
            'id' => $provider_id,
            'id_roles' => $this->db->table('roles')->where(['slug' => DB_SLUG_PROVIDER])->get()->getRow()->id
        ];

        if ($this->db->table('users')->where($conditions)->countAllResults() === 0)
        {
            throw new Exception('Provider record was not found in database: ' . $provider_id);
        }

        // Add record to database.
        $working_plan_exceptions = json_decode($this->get_setting('working_plan_exceptions', $provider_id), TRUE);

        if ( ! isset($working_plan_exception['breaks']))
        {
            $working_plan_exception['breaks'] = [];
        }

        $working_plan_exceptions[$date] = $working_plan_exception;

        return $this->set_setting(
            'working_plan_exceptions',
            json_encode($working_plan_exceptions),
            $provider_id
        );
    }

    /**
     * Get a providers setting from the database.
     *
     * @param string $setting_name The setting name that is going to be returned.
     * @param int $provider_id The selected provider id.
     *
     * @return string Returns the value of the selected user setting.
     */
    public function get_setting($setting_name, $provider_id)
    {
        $provider_settings = $this->db->table('user_settings')
            ->where(['id_users' => $provider_id])->get()->getRowArray();

        return $provider_settings[$setting_name];
    }

    /**
     * Delete a provider working plan exception.
     *
     * @param string $date The working plan exception date (in YYYY-MM-DD format).
     * @param int $provider_id The selected provider record id.
     *
     * @return bool Return if the new working plan exceptions is correctly deleted from DB.
     *
     * @throws Exception If $provider_id argument is invalid.
     */
    public function delete_working_plan_exception($date, $provider_id)
    {
        $provider = $this->get_row($provider_id);

        $working_plan_exceptions = json_decode($provider['settings']['working_plan_exceptions'], TRUE);

        if ( ! isset($working_plan_exceptions[$date]))
        {
            return TRUE; // The selected date does not exist in provider's settings.
        }

        unset($working_plan_exceptions[$date]);

        return $this->set_setting(
            'working_plan_exceptions',
            json_encode(empty($working_plan_exceptions) ? new stdClass() : $working_plan_exceptions),
            $provider_id
        );
    }

    /**
     * Get a specific provider record from the database.
     *
     * @param int $provider_id The id of the record to be returned.
     *
     * @return array Returns an associative array with the selected record's data. Each key has the same name as the
     * database field names.
     *
     * @throws Exception When the selected record does not exist in database.
     */
    public function get_row($provider_id)
    {
        if ( ! is_numeric($provider_id))
        {
            throw new Exception('$provider_id argument is not a valid numeric value: ' . $provider_id);
        }

        // Check if selected record exists on database.
        if ($this->db->table('users')->where(['id' => $provider_id])->countAllResults() == 0)
        {
            throw new Exception('Selected record does not exist in the database.');
        }

        // Get provider data.
        $provider = $this->db->table('users')->where(['id' => $provider_id])->get()->getRowArray();

        // Include provider services.
        $services = $this->db->table('services_providers')
            ->where(['id_users' => $provider_id])->get()->getResultArray();

        $provider['services'] = [];
        foreach ($services as $service)
        {
            $provider['services'][] = $service['id_services'];
        }

        // Include provider settings.
        $provider['settings'] = $this->db->table('user_settings')->where(['id_users' => $provider_id])
            ->get()->getRowArray();

        unset($provider['settings']['id_users']);

        // Return provider data array.
        return $provider;
    }
}
