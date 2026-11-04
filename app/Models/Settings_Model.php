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

use Exception;

/**
 * Settings Model
 */
class Settings_Model extends Ea_Model {
    /**
     * Get setting value from database.
     *
     * This method returns a system setting from the database.
     *
     * @param string $name The database setting name.
     *
     * @return string Returns the database value for the selected setting.
     *
     * @throws Exception If the $name argument is invalid.
     * @throws Exception If the requested $name setting does not exist in the database.
     */
    public function get_setting($name)
    {
        if ( ! is_string($name))
        {
            // Check argument type.
            throw new Exception('$name argument is not a string: ' . $name);
        }

        $setting = $this->db->table('settings')->where(['name' => $name])->get()->getRow();

        if ( ! $setting)
        {
            throw new Exception('$name setting does not exist in database: ' . $name);
        }

        return $setting->value;
    }

    /**
     * This method sets the value for a specific setting on the database.
     *
     * If the setting doesn't exist, it is going to be created, otherwise updated.
     *
     * @param string $name The setting name.
     * @param string $value The setting value.
     *
     * @return int Returns the setting database id.
     *
     * @throws Exception If $name argument is invalid.
     * @throws Exception If the save operation fails.
     */
    public function set_setting($name, $value)
    {
        if ( ! is_string($name))
        {
            throw new Exception('$name argument is not a string: ' . $name);
        }

        $query = $this->db->table('settings')->where(['name' => $name])->get();

        if ($query->getNumRows() > 0)
        {
            // Update setting
            if ( ! $this->db->table('settings')->where(['name' => $name])->update(['value' => $value]))
            {
                throw new Exception('Could not update database setting.');
            }

            $setting_id = (int)$this->db->table('settings')->where(['name' => $name])->get()->getRow()->id;
        }
        else
        {
            // Insert setting
            $insert_data = [
                'name' => $name,
                'value' => $value
            ];

            if ( ! $this->db->table('settings')->insert($insert_data))
            {
                throw new Exception('Could not insert database setting');
            }

            $setting_id = (int)$this->db->insertID();
        }

        return $setting_id;
    }

    /**
     * Remove a setting from the database.
     *
     * @param string $name The setting name to be removed.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If the $name argument is invalid.
     */
    public function remove_setting($name)
    {
        if ( ! is_string($name))
        {
            throw new Exception('$name is not a string: ' . $name);
        }

        if ($this->db->table('settings')->where(['name' => $name])->countAllResults() == 0)
        {
            return FALSE; // There is no such setting.
        }

        return $this->db->table('settings')->where(['name' => $name])->delete();
    }

    /**
     * Saves all the system settings into the database.
     *
     * This method is useful when trying to save all the system settings at once instead of
     * saving them one by one.
     *
     * @param array $settings Contains all the system settings.
     *
     * @return bool Returns the save operation result.
     *
     * @throws Exception When the update operation won't work for a specific setting.
     */
    public function save_settings($settings)
    {
        if ( ! is_array($settings))
        {
            throw new Exception('$settings argument is invalid: ' . describe_data_fields($settings));
        }

        foreach ($settings as $setting)
        {
            if ( ! $this->db->table('settings')->where('name', $setting['name'])
                ->update(['value' => $setting['value']]))
            {
                throw new Exception('Could not save setting (' . $setting['name']
                    . ' - ' . $setting['value'] . ')');
            }
        }

        return TRUE;
    }

    /**
     * Returns all the system settings at once.
     *
     * @return array Array of all the system settings stored in the 'settings' table.
     */
    public function get_settings()
    {
        return $this->db->table('settings')->get()->getResultArray();
    }

    /**
     * Generate a fresh API token.
     *
     * The plaintext token is returned once and never stored — the database only keeps its
     * SHA-256 hash, so the secret cannot be recovered from the database or the settings page.
     *
     * @return string Returns the base64url token for the "Authorization: Bearer" header.
     */
    public function generate_api_token(): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $this->set_setting('api_token', hash('sha256', $token));

        return $token;
    }

    /**
     * Revoke the current API token (existing integrations stop working immediately).
     */
    public function revoke_api_token(): void
    {
        $this->set_setting('api_token', '');
    }

    /**
     * Returns whether an API token is currently configured.
     */
    public function has_api_token(): bool
    {
        $value = $this->db->table('settings')
            ->select('value')
            ->where('name', 'api_token')
            ->get()
            ->getRowArray()['value'] ?? '';

        return $value !== '';
    }

    /**
     * Returns the values of the provided setting names at once.
     *
     * @param array $names The setting names to be fetched.
     *
     * @return array Returns the setting values, indexed by their name.
     */
    public function pluck_settings(array $names): array
    {
        if (empty($names))
        {
            return [];
        }

        $rows = $this->db->table('settings')->whereIn('name', $names)->get()->getResultArray();

        $settings = [];

        foreach ($rows as $row)
        {
            $settings[$row['name']] = $row['value'];
        }

        return $settings;
    }

    /**
     * Returns the settings that the notification emails require.
     *
     * @return array
     */
    public function notification_settings(): array
    {
        return [
            'company_name' => $this->get_setting('company_name'),
            'company_email' => $this->get_setting('company_email'),
            'company_link' => $this->get_setting('company_link'),
            'date_format' => $this->get_setting('date_format'),
            'time_format' => $this->get_setting('time_format')
        ];
    }
}
