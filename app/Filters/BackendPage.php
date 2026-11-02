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
 * Backend pages gate: redirects users without a session to the login page.
 *
 * The controller-level privilege checks stay (defense in depth); this filter
 * removes the unauthenticated round-trip into the controller constructors.
 */
class BackendPage implements FilterInterface {
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('user_id'))
        {
            return;
        }

        return redirect()->to('user/login');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
