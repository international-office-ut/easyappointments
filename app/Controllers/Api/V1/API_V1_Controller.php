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
use App\Controllers\BaseController;
use App\Models\Settings_Model;
use EA\Engine\Api\V1\Authorization;
use EA\Engine\Types\NonEmptyText;

/**
 * API V1 Controller
 *
 * Parent controller class for the API v1 resources. Extend this class instead of the
 * CI4 BaseController and call the parent initController from the children.
 *
 * (CI4 port of the CI3 application/controllers/api/v1/API_V1_Controller.php)
 *
 * Authorization ctor note: the engine class receives a CI3-shaped bridge, so
 * both stacks use the same engine code.
 */
abstract class API_V1_Controller extends BaseController {
    /**
     * @var Settings_Model
     */
    protected $settings_model;

    /**
     * This constructor will handle the common operations of each API call.
     *
     * Important: do not forget to call the parent constructor from the child classes.
     *
     * Notice: At the time being only the basic authentication is supported. Make sure
     * that you use the API through SSL/TLS for security.
     */
    public function initController($request, $response, $logger)
    {
        try
        {
            parent::initController($request, $response, $logger);

            $this->settings_model = new Settings_Model;

            $api_token = $this->settings_model->get_setting('api_token');

            $authorization = new Authorization(new Api_Auth_Bridge);

            $provided_token = $this->get_bearer_token();

            if (Authorization::verify_bearer($api_token, $provided_token))
            {
                return;
            }

            if ( ! isset($_SERVER['PHP_AUTH_USER']))
            {
                $this->request_authentication();
                return;
            }

            $username = new NonEmptyText($_SERVER['PHP_AUTH_USER']);
            $password = new NonEmptyText($_SERVER['PHP_AUTH_PW']);
            $authorization->basic($username, $password);
        }
        catch (Exception $exception)
        {
            $this->handle_exception($exception);
            exit;
        }
    }

    /**
     * Returns the bearer token value.
     *
     * @return string
     */
    protected function get_bearer_token()
    {
        $headers = $this->get_authorization_header();

        // HEADER: Get the access token from the header

        if ( ! empty($headers))
        {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches))
            {
                return $matches[1];
            }
        }
        return NULL;
    }

    /**
     * Returns the authorization header.
     *
     * @return string
     */
    protected function get_authorization_header()
    {
        $headers = NULL;

        if (isset($_SERVER['Authorization']))
        {
            $headers = trim($_SERVER['Authorization']);
        }
        else
        {
            if (isset($_SERVER['HTTP_AUTHORIZATION']))
            {
                //Nginx or fast CGI
                $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
            }
            elseif (function_exists('apache_request_headers'))
            {
                $requestHeaders = apache_request_headers();

                // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care
                // about capitalization for Authorization).
                $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));

                if (isset($requestHeaders['Authorization']))
                {
                    $headers = trim($requestHeaders['Authorization']);
                }
            }
        }

        return $headers;
    }

    /**
     * Sets request authentication headers.
     */
    protected function request_authentication()
    {
        $this->response->setHeader('WWW-Authenticate', 'Basic realm="Easy!Appointments"');
        $this->response->setStatusCode(401);
        $this->response->setBody('You are not authorized to use the API.');
        $this->response->send();
        exit;
    }

    /**
     * Outputs the required headers and messages for exception handling.
     *
     * Call this method from catch blocks of child controller callbacks.
     *
     * @param Exception $exception Thrown exception to be outputted.
     */
    protected function handle_exception(Exception $exception)
    {
        $error = [
            'code' => $exception->getCode() ?: 500,
            'message' => $exception->getMessage(),
        ];

        $header = $exception instanceof \EA\Engine\Api\V1\Exception
            ? $exception->getCode() . ' ' . $exception->get_header()
            : '500 Internal Server Error';

        $parts = explode(' ', $header, 2);

        $this->response->setStatusCode((int) $parts[0], $parts[1] ?? '');
        $this->response->setContentType('application/json');
        $this->response->setBody(json_encode($error, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $this->response->send();
        exit;
    }

    /**
     * Throw an API exception stating that the requested record was not found.
     *
     * @throws \EA\Engine\Api\V1\Exception
     */
    protected function throw_record_not_found()
    {
        throw new \EA\Engine\Api\V1\Exception('The requested record was not found!', 404, 'Not Found');
    }
}
