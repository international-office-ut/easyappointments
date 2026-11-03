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
use App\Models\Appointments_Model;
use EA\Engine\Api\V1\Parsers\Unavailabilities as UnavailabilitiesParser;
use EA\Engine\Api\V1\Request;
use EA\Engine\Api\V1\Response;
use EA\Engine\Types\NonEmptyText;

/**
 * Unavailabilities Controller (API v1)
 *

 */
class Unavailabilities extends API_V1_Controller {
    /**
     * Unavailabilities Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\Unavailabilities
     */
    protected $parser;

    protected Appointments_Model $appointments_model;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->appointments_model = new Appointments_Model;
        $this->parser = new UnavailabilitiesParser;
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
            $where = $id !== NULL ? ['id' => $id] : ['is_unavailable' => TRUE];

            $unavailabilities = $this->appointments_model->get_batch($where);

            if ($id !== NULL && count($unavailabilities) === 0)
            {
                $this->throw_record_not_found();
            }

            $response = new Response($unavailabilities);

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
            // Insert the appointment to the database.
            $request = new Request();
            $unavailability = $request->get_body();
            $this->parser->decode($unavailability);

            if (isset($unavailability['id']))
            {
                unset($unavailability['id']);
            }

            $id = $this->appointments_model->add_unavailable($unavailability);

            // Fetch the new object from the database and return it to the client.
            $batch = $this->appointments_model->get_batch(['id' => $id]);
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
            // Update the appointment record.
            $batch = $this->appointments_model->get_batch(['id' => $id]);

            if ($id !== NULL && count($batch) === 0)
            {
                $this->throw_record_not_found();
            }

            $request = new Request();
            $updated_unavailability = $request->get_body();
            $base_unavailability = $batch[0];
            $this->parser->decode($updated_unavailability, $base_unavailability);
            $updated_unavailability['id'] = $id;
            $id = $this->appointments_model->add_unavailable($updated_unavailability);

            // Fetch the updated object from the database and return it to the client.
            $batch = $this->appointments_model->get_batch(['id' => $id]);
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
            $this->appointments_model->delete_unavailable($id);

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
