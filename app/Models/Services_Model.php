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
 * Services Model
 */
class Services_Model extends Ea_Model {
    /**
     * Services_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add (insert or update) a service record on the database
     *
     * @param array $service Contains the service data. If an 'id' value is provided then the record will be updated.
     *
     * @return int Returns the record id.
     * @throws Exception
     */
    public function add($service)
    {
        $this->validate($service);

        if ( ! isset($service['id']))
        {
            $service['id'] = $this->insert($service);
        }
        else
        {
            $this->update($service);
        }

        return (int)$service['id'];
    }

    /**
     * Validate a service record data.
     *
     * @param array $service Contains the service data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If service validation fails.
     */
    public function validate($service)
    {
        // If record id is provided we need to check whether the record exists in the database.
        if (isset($service['id']))
        {
            $num_rows = $this->db->table('services')->where(['id' => $service['id']])->countAllResults();

            if ($num_rows == 0)
            {
                throw new Exception('Provided service id does not exist in the database.');
            }
        }

        // Check if service category id is valid (only when present).
        if ( ! empty($service['id_service_categories']))
        {
            $num_rows = $this->db->table('service_categories')
                ->where(['id' => $service['id_service_categories']])->countAllResults();

            if ($num_rows == 0)
            {
                throw new Exception('Provided service category id does not exist in database.');
            }
        }

        // Check for required fields
        if ($service['name'] == '')
        {
            throw new Exception('Not all required service fields were provided: '
                . describe_data_fields($service));
        }

        // Duration must be int
        if ($service['duration'] !== NULL)
        {
            if ( ! is_numeric($service['duration']))
            {
                throw new Exception('Service duration is not numeric.');
            }

            if ((int)$service['duration'] < EVENT_MINIMUM_DURATION)
            {
                throw new Exception('The service duration cannot be less than ' . EVENT_MINIMUM_DURATION . ' minutes.');
            }
        }

        if ($service['price'] !== NULL)
        {
            if ( ! is_numeric($service['price']))
            {
                throw new Exception('Service price is not numeric.');
            }
        }

        // Availabilities type must have the correct value.
        if ($service['availabilities_type'] !== NULL && $service['availabilities_type'] !== AVAILABILITIES_TYPE_FLEXIBLE
            && $service['availabilities_type'] !== AVAILABILITIES_TYPE_FIXED)
        {
            throw new Exception('Service availabilities type must be either ' . AVAILABILITIES_TYPE_FLEXIBLE
                . ' or ' . AVAILABILITIES_TYPE_FIXED . ' (given ' . $service['availabilities_type'] . ')');
        }

        if ($service['attendants_number'] !== NULL && ( ! is_numeric($service['attendants_number'])
                || $service['attendants_number'] < 1))
        {
            throw new Exception('Service attendants number must be numeric and greater or equal to one: '
                . $service['attendants_number']);
        }

        // Location override layers are optional JSON strings (hidden from the editor payloads).
        foreach (['location_weekday_modes', 'location_date_overrides'] as $location_layer)
        {
            $value = $service[$location_layer] ?? NULL;

            if ($value === NULL || $value === '')
            {
                continue;
            }

            if ( ! is_string($value))
            {
                throw new Exception('The ' . $location_layer . ' value must be a JSON string.');
            }

            $decoded = json_decode($value, TRUE);

            if ($decoded === NULL && $value !== 'null')
            {
                throw new Exception('The ' . $location_layer . ' value is not valid JSON.');
            }

            if ($decoded !== NULL && ! is_array($decoded))
            {
                throw new Exception('The ' . $location_layer . ' value must be a JSON object.');
            }
        }

        return TRUE;
    }

    /**
     * Insert service record into database.
     *
     * @param array $service Contains the service record data.
     *
     * @return int Returns the new service record id.
     *
     * @throws Exception If service record could not be inserted.
     */
    protected function insert($service)
    {
        if ( ! $this->db->table('services')->insert($service))
        {
            throw new Exception('Could not insert service record.');
        }

        return (int)$this->db->insertID();
    }

    /**
     * Update service record.
     *
     * @param array $service Contains the service data. The record id needs to be included in the array.
     *
     * @throws Exception If service record could not be updated.
     */
    protected function update($service)
    {
        if ( ! $this->db->table('services')->where('id', $service['id'])->update($service))
        {
            throw new Exception('Could not update service record');
        }
    }

    /**
     * Checks whether an service record already exists in the database.
     *
     * @param array $service Contains the service data. Name, duration and price values are mandatory in order to
     * perform the checks.
     *
     * @return bool Returns whether the service record exists.
     *
     * @throws Exception If required fields are missing.
     */
    public function exists($service)
    {
        if ( ! isset(
            $service['name'],
            $service['duration'],
            $service['price']
        ))
        {
            throw new Exception('Not all service fields are provided in order to check whether '
                . 'a service record already exists: ' . describe_data_fields($service));
        }

        $num_rows = $this->db->table('services')->where([
            'name' => $service['name'],
            'duration' => $service['duration'],
            'price' => $service['price']
        ])->countAllResults();

        return $num_rows > 0;
    }

    /**
     * Get the record id of an existing record.
     *
     * Notice: The record must exist, otherwise an exception will be raised.
     *
     * @param array $service Contains the service record data. Name, duration and price values are mandatory for this
     * method to complete.
     *
     * @return int
     *
     * @throws Exception If required fields are missing.
     * @throws Exception If requested service was not found.
     */
    public function find_record_id($service)
    {
        if ( ! isset($service['name'])
            || ! isset($service['duration'])
            || ! isset($service['price']))
        {
            throw new Exception('Not all required fields were provided in order to find the '
                . 'service record id.');
        }

        $result = $this->db->table('services')->where([
            'name' => $service['name'],
            'duration' => $service['duration'],
            'price' => $service['price']
        ])->get();

        if ($result->getNumRows() == 0)
        {
            throw new Exception('Could not find service record id');
        }

        return $result->getRow()->id;
    }

    /**
     * Delete a service record from database.
     *
     * @param int $service_id Record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If $service_id argument is invalid.
     */
    public function delete($service_id)
    {
        if ( ! is_numeric($service_id))
        {
            throw new Exception('Invalid argument type $service_id (value:"' . $service_id . '"');
        }

        $num_rows = $this->db->table('services')->where(['id' => $service_id])->countAllResults();
        if ($num_rows == 0)
        {
            return FALSE; // Record does not exist
        }

        return $this->db->table('services')->where(['id' => $service_id])->delete();
    }

    /**
     * Get a specific row from the services db table.
     *
     * @param int $service_id The record's id to be returned.
     *
     * @return array Returns an associative array with the selected record's data. Each key has the same name as the
     * database field names.
     *
     * @throws Exception If $service_id argument is not valid.
     */
    public function get_row($service_id)
    {
        if ( ! is_numeric($service_id))
        {
            throw new Exception('$service_id argument is not an numeric (value: "' . $service_id . '")');
        }

        return $this->db->table('services')->where(['id' => $service_id])->get()->getRowArray();
    }

    /**
     * Get a specific field value from the database.
     *
     * @param string $field_name The field name of the value to be
     * returned.
     * @param int $service_id The selected record's id.
     *
     * @return string Returns the records value from the database.
     *
     * @throws Exception If $service_id argument is invalid.
     * @throws Exception If $field_name argument is invalid.
     * @throws Exception if requested service does not exist in the database.
     * @throws Exception If requested field name does not exist in the database.
     */
    public function get_value($field_name, $service_id)
    {
        if ( ! is_numeric($service_id))
        {
            throw new Exception('Invalid argument provided as $service_id: ' . $service_id);
        }

        if ( ! is_string($field_name))
        {
            throw new Exception('$field_name argument is not a string: ' . $field_name);
        }

        if ($this->db->table('services')->where(['id' => $service_id])->countAllResults() == 0)
        {
            throw new Exception('The record with the $service_id argument does not exist in the database: ' . $service_id);
        }

        $row_data = $this->db->table('services')->where(['id' => $service_id])->get()->getRowArray();

        if ( ! array_key_exists($field_name, $row_data))
        {
            throw new Exception('The given $field_name argument does not exist in the database: '
                . $field_name);
        }

        return $row_data[$field_name];
    }

    /**
     * Get all, or specific records from service's table.
     *
     * Example:
     *
     * $this->services_model->get_batch(['id' => $record_id]);
     *
     * @param mixed $where
     * @param int|null $limit
     * @param int|null $offset
     * @param mixed $order_by
     *
     * @return array Returns the rows from the database.
     */
    public function get_batch($where = NULL, $limit = NULL, $offset = NULL, $order_by = NULL)
    {
        $builder = $this->db->table('services');

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

        return $builder->get()->getResultArray();
    }

    /**
     * This method returns all the services from the database.
     *
     * @return array Returns an object array with all the database services.
     */
    public function get_available_services()
    {
        return $this->db->table('services')
            ->select('services.*, service_categories.name AS category_name, '
                . 'service_categories.id AS category_id')
            ->distinct()
            ->join('services_providers',
                'services_providers.id_services = services.id', 'inner')
            ->join('service_categories',
                'service_categories.id = services.id_service_categories', 'left')
            ->orderBy('name ASC')
            ->get()->getResultArray();
    }

    /**
     * Add (insert or update) a service category record into database.
     *
     * @param array $category Contains the service category data.
     *
     * @return int Returns the record ID.
     *
     * @throws Exception If service category data are invalid.
     */
    public function add_category($category)
    {
        if ( ! $this->validate_category($category))
        {
            throw new Exception('Service category data are invalid.');
        }

        if ( ! isset($category['id']))
        {
            $this->db->table('service_categories')->insert($category);
            $category['id'] = $this->db->insertID();
        }
        else
        {
            $this->db->table('service_categories')->where('id', $category['id'])->update($category);
        }

        return (int)$category['id'];
    }

    /**
     * Validate a service category record data. This method must be used before adding
     * a service category record into database in order to secure the record integrity.
     *
     * @param array $category Contains the service category data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If required fields are missing.
     */
    public function validate_category($category)
    {
        try
        {
            // Required Fields
            if ( ! isset($category['name']))
            {
                throw new Exception('Not all required fields were provided ');
            }

            if ($category['name'] == '' || $category['name'] == NULL)
            {
                throw new Exception('Required fields cannot be empty or null ($category: '
                    . describe_data_fields($category) . ')');
            }

            return TRUE;
        }
        catch (Exception $exception)
        {
            return FALSE;
        }
    }

    /**
     * Delete a service category record from the database.
     *
     * @param int $category_id Record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception if Service category record was not found.
     */
    public function delete_category($category_id)
    {
        if ( ! is_numeric($category_id))
        {
            throw new Exception('Invalid argument given for $category_id: ' . $category_id);
        }

        $num_rows = $this->db->table('service_categories')->where(['id' => $category_id])
            ->countAllResults();
        if ($num_rows == 0)
        {
            throw new Exception('Service category record not found in database.');
        }

        return $this->db->table('service_categories')->where('id', $category_id)->delete();
    }

    /**
     * Get a service category record data.
     *
     * @param int $category_id Record id to be retrieved.
     *
     * @return array Returns the record data from the database.
     *
     * @throws Exception If $category_id argument is invalid.
     * @throws Exception If service category record does not exist.
     */
    public function get_category($category_id)
    {
        if ( ! is_numeric($category_id))
        {
            throw new Exception('Invalid argument type given $category_id: ' . $category_id);
        }

        $result = $this->db->table('service_categories')->where(['id' => $category_id])->get();

        if ($result->getNumRows() == 0)
        {
            throw new Exception('Service category record does not exist.');
        }

        return $result->getRowArray();
    }

    /**
     * Get all service category records from database.
     *
     * @param mixed $where
     * @param int|null $limit
     * @param int|null $offset
     * @param mixed $order_by
     *
     * @return array Returns an array that contains all the service category records.
     */
    public function get_all_categories($where = NULL, $limit = NULL, $offset = NULL, $order_by = NULL)
    {
        $builder = $this->db->table('service_categories');

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

        return $builder->get()->getResultArray();
    }
}
