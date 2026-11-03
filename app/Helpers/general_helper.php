<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Describe the field names of a data argument.
 *
 * Exception messages must not contain the submitted data itself, only the names of its
 * fields, so that payloads never get reflected back to the client.
 *
 * @param mixed $data The data argument of an exception message.
 *
 * @return string Returns the field names or the value type.
 */
function describe_data_fields($data): string
{
    if (is_array($data))
    {
        return '[' . implode(', ', array_map('strval', array_keys($data))) . ']';
    }

    return gettype($data);
}
