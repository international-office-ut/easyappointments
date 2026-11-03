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

/**
 * Format a stored record datetime according to the system date/time settings.
 *
 * The record datetimes are stored based on the timezone context of the related provider.
 *
 * @param string $datetime Stored datetime string.
 * @param array $settings Settings array with the 'date_format' and 'time_format' values.
 * @param string $source_timezone Timezone the datetime is stored in.
 * @param string|null $target_timezone Timezone in which the datetime will be rendered.
 *
 * @return string
 *
 * @throws Exception If the settings contain invalid format values.
 */
function format_system_datetime(string $datetime, array $settings, string $source_timezone,
    ?string $target_timezone = NULL): string
{
    switch ($settings['date_format'])
    {
        case 'DMY':
            $date_format = 'd/m/Y';
            break;
        case 'DDMY':
            $date_format = 'd.m.Y';
            break;
        case 'MDY':
            $date_format = 'm/d/Y';
            break;
        case 'YMD':
            $date_format = 'Y/m/d';
            break;
        default:
            throw new Exception('Invalid date_format value: ' . $settings['date_format']);
    }

    switch ($settings['time_format'])
    {
        case 'military':
            $time_format = 'H:i';
            break;
        case 'regular':
            $time_format = 'g:i a';
            break;
        default:
            throw new Exception('Invalid time_format value: ' . $settings['time_format']);
    }

    return (new DateTime($datetime, new DateTimeZone($source_timezone)))
        ->setTimezone(new DateTimeZone($target_timezone ?? $source_timezone))
        ->format($date_format . ' ' . $time_format);
}
