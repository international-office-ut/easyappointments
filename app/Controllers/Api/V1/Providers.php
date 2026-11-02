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

use App\Models\Providers_Model;
use EA\Engine\Api\V1\Parsers\Providers as ProvidersParser;
use EA\Engine\Api\V1\Request;
use EA\Engine\Api\V1\Response;
use EA\Engine\Types\NonEmptyText;

/**
 * Providers Controller (API v1)
 *
 * (CI4 port of the CI3 application/controllers/api/v1/Providers.php)
 */
class Providers extends API_V1_Controller {
    /**
     * Providers Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\Providers
     */
    protected $parser;

    protected Providers_Model $providers_model;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->providers_model = new Providers_Model;
        $this->parser = new ProvidersParser;
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

            $providers = $this->providers_model->get_batch($where);

            if ($id !== NULL && count($providers) === 0)
            {
                $this->throw_record_not_found();
            }

            $response = new Response($providers);

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
            // Insert the provider to the database.
            $request = new Request();
            $provider = $request->get_body();
            $this->parser->decode($provider);

            if (array_key_exists('id', $provider))
            {
                unset($provider['id']);
            }

            if ( ! array_key_exists('settings', $provider))
            {
                throw new Exception('No settings property provided.');
            }

            $id = $this->providers_model->add($provider);

            // Fetch the new object from the database and return it to the client.
            $batch = $this->providers_model->get_batch(['id' => $id]);
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
            // Update the provider record.
            $batch = $this->providers_model->get_batch(['id' => $id]);

            if ($id !== NULL && count($batch) === 0)
            {
                $this->throw_record_not_found();
            }

            $request = new Request();
            $updated_provider = $request->get_body();
            $base_provider = $batch[0];
            $this->parser->decode($updated_provider, $base_provider);
            $updated_provider['id'] = $id;
            $id = $this->providers_model->add($updated_provider);

            // Fetch the updated object from the database and return it to the client.
            $batch = $this->providers_model->get_batch(['id' => $id]);
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
            $this->providers_model->delete($id);

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
