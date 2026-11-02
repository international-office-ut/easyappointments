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
use App\Models\Secretaries_Model;
use EA\Engine\Api\V1\Parsers\Secretaries as SecretariesParser;
use EA\Engine\Api\V1\Request;
use EA\Engine\Api\V1\Response;
use EA\Engine\Types\NonEmptyText;

/**
 * Secretaries Controller (API v1)
 *
 * (CI4 port of the CI3 application/controllers/api/v1/Secretaries.php)
 */
class Secretaries extends API_V1_Controller {
    /**
     * Secretaries Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\Secretaries
     */
    protected $parser;

    protected Secretaries_Model $secretaries_model;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->secretaries_model = new Secretaries_Model;
        $this->parser = new SecretariesParser;
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

            $secretaries = $this->secretaries_model->get_batch($where);

            if ($id !== NULL && count($secretaries) === 0)
            {
                $this->throw_record_not_found();
            }

            $response = new Response($secretaries);

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
            // Insert the secretary to the database.
            $request = new Request();
            $secretary = $request->get_body();
            $this->parser->decode($secretary);

            if (array_key_exists('id', $secretary))
            {
                unset($secretary['id']);
            }

            if ( ! array_key_exists('settings', $secretary))
            {
                throw new Exception('No settings property provided.');
            }

            $id = $this->secretaries_model->add($secretary);

            // Fetch the new object from the database and return it to the client.
            $batch = $this->secretaries_model->get_batch(['id' => $id]);
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
            // Update the secretary record.
            $batch = $this->secretaries_model->get_batch(['id' => $id]);

            if ($id !== NULL && count($batch) === 0)
            {
                $this->throw_record_not_found();
            }

            $request = new Request();
            $updated_secretary = $request->get_body();
            $base_secretary = $batch[0];
            $this->parser->decode($updated_secretary, $base_secretary);
            $updated_secretary['id'] = $id;
            $id = $this->secretaries_model->add($updated_secretary);

            // Fetch the updated object from the database and return it to the client.
            $batch = $this->secretaries_model->get_batch(['id' => $id]);
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
            $this->secretaries_model->delete($id);

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
