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

namespace EA\Engine\Booking;

use Exception;

/**
 * Booking Registration Guard
 *
 * Sanitizes the payloads of the public booking flow before they reach the models, resolves the
 * trust rules around appointment IDs and validates the manage-mode hash. Pure logic only: no
 * models, no CodeIgniter state, so the rules are unit-testable on their own.
 */
class Guard {
    public const ANY_PROVIDER = 'any-provider';

    public const MODE_ONLINE = 'ONLINE';

    public const MODE_ON_PREMISE = 'ON_PREMISE';

    public const MODE_BOTH = 'BOTH';

    /**
     * Whitelisted fields the booking flow may set on an appointment record.
     *
     * "id_users_provider" is availability-check input, not a mass-assignment risk: the controller
     * resolves it (including the "any-provider" sentinel) and overwrites the value with the
     * resolved database ID before saving, so custodians of this list must never drop it. The
     * dropping of this field once broke every booking with a "Select Provider" error.
     */
    protected const APPOINTMENT_FIELDS = [
        'id',
        'id_users_provider',
        'id_services',
        'start_datetime',
        'end_datetime',
        'notes',
        'location'
    ];

    /**
     * Whitelisted fields the booking flow may set on a customer record.
     */
    protected const CUSTOMER_FIELDS = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'address',
        'city',
        'zip_code',
        'notes',
        'timezone',
        'language'
    ];

    /**
     * Reduce an appointment payload to the fields the booking flow actually works with and
     * normalize the location value.
     *
     * @param array $raw Appointment data as posted by the booking form.
     * @return array Returns the whitelisted appointment fields with a normalized location.
     */
    public static function appointment(array $raw): array
    {
        $appointment = array_intersect_key($raw, array_flip(self::APPOINTMENT_FIELDS));

        if (array_key_exists('location', $raw)
            && ! in_array($raw['location'], [
                self::MODE_ONLINE,
                self::MODE_ON_PREMISE,
                self::MODE_BOTH,
                NULL,
                ''
            ], TRUE))
        {
            // Only the known location values are accepted, anything else (e.g. legacy or
            // hand-crafted payloads) falls back to the provider resolution ladder.
            $appointment['location'] = NULL;
        }

        return $appointment;
    }

    /**
     * Reduce a customer payload to the fields the booking flow actually works with.
     *
     * @param array $raw Customer data as posted by the booking form.
     * @return array Returns the whitelisted customer fields.
     */
    public static function customer(array $raw): array
    {
        return array_intersect_key($raw, array_flip(self::CUSTOMER_FIELDS));
    }

    /**
     * Only trust a posted appointment ID when the matched record carries the hash of it.
     *
     * @param array $row Appointment row fetched by hash (may be empty if the hash matched nothing).
     * @param int $posted_id Appointment ID posted by the booking form.
     * @return void Throws when the posted ID does not belong to the hashed record.
     * @throws Exception
     */
    public static function check_manage_hash(array $row, int $posted_id): void
    {
        if (empty($row) || (int) $row['id'] !== $posted_id)
        {
            throw new Exception('Invalid appointment hash provided.');
        }
    }

    /**
     * A new booking must never carry the ID of an existing appointment or customer, otherwise
     * the availability check would exclude the slot and the save would overwrite the record.
     *
     * @param array $appointment
     * @param array $customer
     * @return array Returns the ID-stripped appointment as first and customer as second value.
     */
    public static function strip_new_booking_ids(array $appointment, array $customer): array
    {
        unset($appointment['id']);
        unset($customer['id']);

        return [$appointment, $customer];
    }
}
