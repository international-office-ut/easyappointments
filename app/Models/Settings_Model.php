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
 * Settings Model (CI4 port, from the CI3 application/models/Settings_model.php)
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
}
