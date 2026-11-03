<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Rate_limiter;

/**
 * CSP violation report collector.
 *
 * Browsers POST the JSON violation report when the Content-Security-Policy uses the
 * report-uri directive (the value of the policy is generated inside the eaCsp filter).
 * The report content is only logged, never reflected back to the client, and the
 * requests are rate limited per client IP.
 */
class Csp_report extends BaseController {
    /**
     * Store a CSP violation report into the application log.
     */
    public function index(): ResponseInterface
    {
        if ($this->request->getMethod(TRUE) !== 'POST')
        {
            return $this->response->setStatusCode(404);
        }

        $report_key = 'csp|client|' . $this->request->getIPAddress();

        if ( ! Rate_limiter::has_reached_limit($report_key, 10))
        {
            $report = @json_decode((string) $this->request->getBody(), TRUE);

            if (is_array($report['csp-report'] ?? NULL))
            {
                Rate_limiter::register_attempt($report_key, 300);

                file_put_contents(
                    ROOTPATH . 'storage/logs/csp-reports-' . date('Y-m-d') . '.log',
                    '[' . date('Y-m-d H:i:s') . '] '
                        . json_encode($report['csp-report'], JSON_UNESCAPED_SLASHES) . "\n",
                    FILE_APPEND | LOCK_EX
                );
            }
        }

        return $this->response->setStatusCode(204);
    }
}
