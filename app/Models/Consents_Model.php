<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.3.2
 * ---------------------------------------------------------------------------- */

namespace App\Models;

use App\Helpers\General_helper;

/**
 * Consents Model (CI4 port, from the CI3 application/models/Consents_model.php)
 */
class Consents_Model extends Ea_Model {
    /**
     * Add a consent record to the database.
     *
     * This method adds a consent to the database.
     *
     * @param array $consent Associative array with the consent's data.
     *
     * @return int Returns the consent ID.
     *
     * @throws Exception
     */
    public function add($consent)
    {
        $this->validate($consent);

        if ( ! isset($consent['id']))
        {
            $consent['id'] = $this->insert($consent);
        }
        else
        {
            $this->update($consent);
        }

        return $consent['id'];
    }


    /**
     * Validate consent data before the insert or update operation is executed.
     *
     * @param array $consent Contains the consent data.
     *
     * @throws Exception If customer validation fails.
     */
    public function validate($consent)
    {
        if ( ! isset($consent['first_name'])
            || ! isset($consent['last_name'])
            || ! isset($consent['email'])
            || ! isset($consent['ip'])
            || ! isset($consent['type']))
        {
            throw new Exception('Not all required fields are provided: '
                . General_helper::describe_data_fields($consent));
        }
    }

    /**
     * Insert a new consent record to the database.
     *
     * @param array $consent Associative array with the consent's data.
     *
     * @return int Returns the ID of the new record.
     *
     * @throws Exception If consent record could not be inserted.
     */
    protected function insert($consent)
    {
        $consent['created'] = date('Y-m-d H:i:s');
        $consent['modified'] = date('Y-m-d H:i:s');

        if ( ! $this->db->table('consents')->insert($consent))
        {
            throw new Exception('Could not insert consent to the database.');
        }

        return (int)$this->db->insertID();
    }

    /**
     * Update an existing consent record in the database.
     *
     * The consent data argument should already include the record ID in order to process the update operation.
     *
     * @param array $consent Associative array with the consent's data.
     *
     * @return int Returns the updated record ID.
     *
     * @throws Exception If consent record could not be updated.
     */
    protected function update($consent)
    {
        $consent['modified'] = date('Y-m-d H:i:s');

        if ( ! $this->db->table('consents')->where(['id' => $consent['id']])->update($consent))
        {
            throw new Exception('Could not update consent to the database.');
        }

        return (int)$consent['id'];
    }
}
