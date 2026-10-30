<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.4.2
 * ---------------------------------------------------------------------------- */

/**
 * Encode a value for inline script contexts.
 *
 * The flags prevent values that contain HTML/XML control characters (e.g. a notes value with
 * "</script>") from breaking out of the surrounding script element, so that user-provided
 * data inside the page globals can only ever stay data.
 *
 * @param mixed $value Value to be encoded.
 *
 * @return string Returns the encoded JSON string.
 */
function json_data($value): string
{
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}
