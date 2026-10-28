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
     * service location (with the provider address as fallback) and legacy free-form values are
     * returned as they are.
     *
     * @param string|null $location The raw appointments.location value.
     * @param array $service Service record (may be empty).
     * @param array $provider Provider record (may include the "settings" array).
     * @return string Returns the location display value, may be empty when nothing is configured.
     */
    function get_appointment_location_value(?string $location, array $service, array $provider): string
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

        // Empty location: legacy appointments inherited the service location.
        return $service['location'] ?? '';
    }
}

if ( ! function_exists('resolve_appointment_mode'))
{
    /**
     * Resolve the effective appointment mode of a specific weekday.
     *
     * The weekday override of the service wins over the service mode, which wins over the
     * provider default. Empty values fall back to "BOTH" so that the customer can choose.
     *
     * @param array $service Service record, including a "weekday_modes" map.
     * @param array $provider_settings The provider settings record (may be empty).
     * @param int $weekday Weekday index (0 = Sunday ... 6 = Saturday).
     * @return string Returns one of the APPOINTMENT_MODE_* values.
     */
    function resolve_appointment_mode(array $service, array $provider_settings, int $weekday): string
    {
        $weekday_mode = $service['weekday_modes'][$weekday] ?? NULL;
        $service_mode = $service['appointment_mode'] ?? NULL;
        $provider_mode = $provider_settings['appointment_mode'] ?? NULL;

        foreach ([$weekday_mode, $service_mode, $provider_mode] as $mode)
        {
            if ($mode !== NULL && $mode !== '')
            {
                return $mode;
            }
        }

        return APPOINTMENT_MODE_BOTH;
    }
}
