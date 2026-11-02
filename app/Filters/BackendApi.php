<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Backend AJAX gate: answers requests without a session with a JSON 403,
 * so that the calendar client never reaches the model layer unauthenticated.
 */
class BackendApi implements FilterInterface {
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('user_id'))
        {
            return;
        }

        if ( ! $request instanceof IncomingRequest)
        {
            return;
        }

        $response = service('response');
        $response->setStatusCode(403);
        $response->setContentType('application/json');
        $response->setBody(json_encode([
            'message' => 'Your session has expired. Please log in again.'
        ]));

        return $response;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
