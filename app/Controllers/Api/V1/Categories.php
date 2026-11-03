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
use App\Models\Services_Model;
use EA\Engine\Api\V1\Parsers\Categories as CategoriesParser;
use EA\Engine\Api\V1\Request;
use EA\Engine\Api\V1\Response;
use EA\Engine\Types\NonEmptyText;

/**
 * Categories Controller (API v1)
 *

 */
class Categories extends API_V1_Controller {
    /**
     * Categories Resource Parser
     *
     * @var \EA\Engine\Api\V1\Parsers\Categories
     */
    protected $parser;

    protected Services_Model $services_model;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);

        $this->services_model = new Services_Model;
        $this->parser = new CategoriesParser;
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

            $categories = $this->services_model->get_all_categories($where);

            if ($id !== NULL && count($categories) === 0)
            {
                $this->throw_record_not_found();
            }

            $response = new Response($categories);

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
            // Insert the category to the database.
            $request = new Request();
            $category = $request->get_body();
            $this->parser->decode($category);

            if (isset($category['id']))
            {
                unset($category['id']);
            }

            $id = $this->services_model->add_category($category);

            // Fetch the new object from the database and return it to the client.
            $batch = $this->services_model->get_all_categories(['id' => $id]);
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
            // Update the category record.
            $batch = $this->services_model->get_all_categories(['id' => $id]);

            if ($id !== NULL && count($batch) === 0)
            {
                $this->throw_record_not_found();
            }

            $request = new Request();
            $updated_category = $request->get_body();
            $base_category = $batch[0];
            $this->parser->decode($updated_category, $base_category);
            $updated_category['id'] = $id;
            $id = $this->services_model->add_category($updated_category);

            // Fetch the updated object from the database and return it to the client.
            $batch = $this->services_model->get_all_categories(['id' => $id]);
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
            $this->services_model->delete_category($id);

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
