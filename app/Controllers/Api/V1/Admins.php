<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

namespace App\Controllers\Api\V1;

use Exception;
use App\Models\Admins_Model;
use EA\Engine\Api\V1\Parsers\Admins as AdminsParser;
use EA\Engine\Api\V1\Request;
use EA\Engine\Api\V1\Response;
use EA\Engine\Types\NonEmptyText;

/**
 * Admins Controller (API v1)
 *

 */
class Admins extends API_V1_Controller {
    /**
     * Admins Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\Admins
     */
    protected $parser;

    protected Admins_Model $admins_model;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->admins_model = new Admins_Model;
        $this->parser = new AdminsParser;
    }

    /**
     * GET API Method
     *
     * @param int $id Optional (null), the record ID to be returned.
     */
    public function get($id = NULL)
    {
        try
        {
            $where = $id !== NULL ? ['id' => $id] : NULL;

            $admins = $this->admins_model->get_batch($where);

            if ($id !== NULL && count($admins) === 0)
            {
                $this->throw_record_not_found();
            }

            $response = new Response($admins);

            $response->encode($this->parser)
                ->search()
                ->sort()
                ->paginate()
                ->minimize()
                ->singleEntry($id)
                ->output();

        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }

    /**
     * POST API Method
     */
    public function post()
    {
        try
        {
            // Insert the admin to the database.
            $request = new Request();
            $admin = $request->get_body();
            $this->parser->decode($admin);

            if (array_key_exists('id', $admin))
            {
                unset($admin['id']);
            }

            if ( ! array_key_exists('settings', $admin))
            {
                throw new Exception('No settings property provided.');
            }

            $id = $this->admins_model->add($admin);

            // Fetch the new object from the database and return it to the client.
            $batch = $this->admins_model->get_batch(['id' => $id]);
            $response = new Response($batch);
            $status = new NonEmptyText('201 Created');
            $response->encode($this->parser)->singleEntry(TRUE)->output($status);
        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }

    /**
     * PUT API Method
     *
     * @param int $id The record ID to be updated.
     */
    public function put($id)
    {
        try
        {
            // Update the admin record.
            $batch = $this->admins_model->get_batch(['id' => $id]);

            if ($id !== NULL && count($batch) === 0)
            {
                $this->throw_record_not_found();
            }

            $request = new Request();
            $updated_admin = $request->get_body();
            $base_admin = $batch[0];
            $this->parser->decode($updated_admin, $base_admin);
            $updated_admin['id'] = $id;
            $id = $this->admins_model->add($updated_admin);

            // Fetch the updated object from the database and return it to the client.
            $batch = $this->admins_model->get_batch(['id' => $id]);
            $response = new Response($batch);
            $response->encode($this->parser)->singleEntry($id)->output();
        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }

    /**
     * DELETE API Method
     *
     * @param int $id The record ID to be deleted.
     */
    public function delete($id)
    {
        try
        {
            $this->admins_model->delete($id);

            $response = new Response([
                'code' => 200,
                'message' => 'Record was deleted successfully!'
            ]);

            $response->output();
        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
        }
    }
}
