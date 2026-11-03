<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

use App\Libraries\Timezones;

/**
 * Get the one-time CSP nonce of the current request.
 *
 * The nonce value is generated once per request and must be attached to every inline <script>
 * element (the CSP policy allows only nonced scripts). Reports go to the collector via #csp-report.
 *
 * @return string
 */
function csp_nonce(): string
{
    static $nonce;

    if ( ! $nonce)
    {
        $nonce = bin2hex(random_bytes(20));
    }

    return $nonce;
}

/**
 * Render the HTML output of a timezone dropdown element.
 *
 * @param string $attributes HTML element attributes of the dropdown.
 *
 * @return string
 */
function render_timezone_dropdown($attributes = '')
{
    return view('partials/timezone_dropdown', [
        'timezones' => (new Timezones)->to_grouped_array(),
        'attributes' => $attributes
    ]);
}
