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
 * Appointment Location Helper
 *
 * Resolves the location of an appointment for display purposes (emails, ICS files, summaries).
 * The `appointments.location` value is either one of the location enum values ("ONLINE",
 * "ON_PREMISE") or a free-form legacy value (address or URL) that is passed through as-is.
 */

if ( ! function_exists('is_appointment_location_online'))
{
    /**
     * Determine whether an appointment takes place online.
     *
     * @param string|null $location The raw appointments.location value.
     * @return bool
     */
    function is_appointment_location_online(?string $location): bool
    {
        return in_array($location, [APPOINTMENT_MODE_ONLINE, 'Zoom'], FALSE);
    }
}

if ( ! function_exists('provider_has_zoom_link'))
{
    /**
     * Check whether a provider has a Zoom link configured.
     *
     * Online appointments only make sense when the interested provider has a Zoom link. The
     * previously used workaround stored the link in the mobile number field, so it is treated
     * as a fallback until the value has been migrated to the dedicated setting.
     *
     * @param array $provider Provider record (may include the "settings" array).
     * @return bool
     */
    function provider_has_zoom_link(array $provider): bool
    {
        $zoom_link = $provider['settings']['zoom_link'] ?? $provider['zoom_link'] ?? NULL;

        return ! empty($zoom_link) || ! empty($provider['mobile_number']);
    }
}

if ( ! function_exists('get_appointment_location_value'))
{
    /**
     * Resolve the display value of an appointment location.
     *
     * Online appointments resolve to the Zoom join link (configured on the provider, with the
     * legacy mobile-number workaround as fallback), on-premise appointments resolve to the
     * effective service location — including the location overrides of the service — and legacy
     * free-form values are returned as they are.
     *
     * @param string|null $location The raw appointments.location value.
     * @param array $service Service record (may be empty).
     * @param array $provider Provider record (may include the "settings" array).
     * @param string|null $start_datetime Appointment start datetime ("Y-m-d H:i:s"), enables the
     * override resolution when provided.
     * @param string|null $timezone Timezone of the compared times (usually the provider timezone).
     * @return string Returns the location display value, may be empty when nothing is configured.
     */
    function get_appointment_location_value(?string $location, array $service, array $provider,
        ?string $start_datetime = NULL, ?string $timezone = NULL): string
    {
        $provider_settings = $provider['settings'] ?? [];

        if (is_appointment_location_online($location))
        {
            return ! empty($provider_settings['zoom_link'])
                ? $provider_settings['zoom_link']
                : ($provider['mobile_number'] ?? '');
        }

        if ($location === APPOINTMENT_MODE_ON_PREMISE)
        {
            // The service location overrides win over the plain service location: a date window
            // matches before a weekday window and both before the default location.
            if ($start_datetime !== NULL && $timezone !== NULL)
            {
                $override = resolve_service_location($service, $start_datetime, $timezone);

                if ($override !== NULL)
                {
                    return $override;
                }
            }

            $service_location = $service['location'] ?? '';

            if ($service_location !== NULL && $service_location !== '')
            {
                return $service_location;
            }

            return trim(implode(' ', array_filter([
                $provider['address'] ?? '',
                $provider['zip_code'] ?? '',
                $provider['city'] ?? '',
            ])));
        }

        if ($location !== NULL && $location !== '')
        {
            return $location; // Legacy free-form value.
        }

        // Empty location: legacy appointments inherited the effective service location.
        if ($start_datetime !== NULL && $timezone !== NULL)
        {
            $override = resolve_service_location($service, $start_datetime, $timezone);

            if ($override !== NULL)
            {
                return $override;
            }
        }

        return $service['location'] ?? '';
    }
}

if ( ! function_exists('resolve_service_location'))
{
    /**
     * Resolve the location of a service override for the given appointment start.
     *
     * The service carries two JSON layers (`location_date_overrides` keyed by ISO date and
     * `location_weekday_modes` keyed by weekday index 0=Sunday...6=Saturday). The date layer
     * wins, then the weekday layer, then no override. A layer matches when one of its time
     * windows contains the start time (`from <= start < to`); the first matching window of the
     * layer applies (the editor prevents overlapping rows).
     *
     * @param array $service Service record (raw JSON columns included).
     * @param string $start_datetime Appointment start datetime ("Y-m-d H:i:s").
     * @param string $timezone Timezone the window comparison runs in (usually the provider timezone).
     * @return string|null Returns the resolved location or NULL when no override matches.
     */
    function resolve_service_location(array $service, string $start_datetime, string $timezone): ?string
    {
        try
        {
            $start = new DateTime($start_datetime, new DateTimeZone($timezone));
        }
        catch (Exception $exception)
        {
            log_message('error', 'Service location override received an invalid timezone: ' . $timezone);

            return NULL;
        }

        $time = $start->format('H:i');
        $date = $start->format('Y-m-d');
        $weekday = (int)$start->format('w'); // 0 = Sunday ... 6 = Saturday

        $date_overrides = json_decode($service['location_date_overrides'] ?? 'null', TRUE);

        if (is_array($date_overrides) && isset($date_overrides[$date])
            && is_array($date_overrides[$date]))
        {
            $override = match_location_window($date_overrides[$date], $time);

            if ($override !== NULL)
            {
                return $override;
            }
        }

        $weekday_modes = json_decode($service['location_weekday_modes'] ?? 'null', TRUE);

        if (is_array($weekday_modes) && isset($weekday_modes[$weekday])
            && is_array($weekday_modes[$weekday]))
        {
            $override = match_location_window($weekday_modes[$weekday], $time);

            if ($override !== NULL)
            {
                return $override;
            }
        }

        return NULL;
    }
}

if ( ! function_exists('match_location_window'))
{
    /**
     * Match the location of the first time window that contains the time.
     *
     * @param array $windows Window entries of the layer.
     * @param string $time The start time ("H:i").
     * @return string|null Returns the matched location or NULL when no window fits.
     */
    function match_location_window(array $windows, string $time): ?string
    {
        foreach ($windows as $window)
        {
            if ( ! is_array($window) || empty($window['location'])
                || empty($window['from']) || empty($window['to']))
            {
                continue;
            }

            if ($window['from'] <= $time && $time < $window['to'])
            {
                return (string)$window['location'];
            }
        }

        return NULL;
    }
}

if ( ! function_exists('resolve_appointment_mode'))
{
    /**
     * Resolve the effective appointment mode of a specific weekday.
     *
     * The weekday override of the service wins over the service mode, then the per-day override
     * of the provider wins over the provider default. Values that are not set fall through, so
     * that broader rules can still apply; missing values everywhere fall back to "BOTH" so that
     * the customer can choose.
     *
     * @param array $provider_settings The provider settings record (weekday modes as JSON string).
     * @param int $weekday Weekday index (0 = Sunday ... 6 = Saturday).
     * @return string Returns one of the APPOINTMENT_MODE_* values.
     */
    function resolve_appointment_mode(array $provider_settings, int $weekday): string
    {
        $provider_weekday_modes = [];

        if ( ! empty($provider_settings['weekday_modes']))
        {
            $provider_weekday_modes = json_decode((string)$provider_settings['weekday_modes'], TRUE) ?: [];

            if ( ! is_array($provider_weekday_modes))
            {
                $provider_weekday_modes = [];
            }
        }

        // First non-empty value wins: the per-day provider override has precedence over the
        // provider default and everything else falls back to "BOTH".
        foreach ([
            $provider_weekday_modes[$weekday] ?? NULL,
            $provider_settings['appointment_mode'] ?? NULL
        ] as $candidate)
        {
            if ($candidate !== NULL && $candidate !== '')
            {
                return $candidate;
            }
        }

        return APPOINTMENT_MODE_BOTH;
    }
}
