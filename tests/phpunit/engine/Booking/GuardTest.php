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

use PHPUnit\Framework\TestCase;

class GuardTest extends TestCase {
    public function test_appointment_whitelist_keeps_id_users_provider()
    {
        // Regression: dropping this field once broke every booking with a "Select Provider"
        // error, because the availability check needs it as its input.
        $appointment = Guard::appointment([
            'id' => 7,
            'id_users_provider' => 42,
            'id_services' => 3,
            'start_datetime' => '2026-01-03 09:00:00',
            'end_datetime' => '2026-01-03 09:30:00',
            'notes' => 'notes',
            'location' => 'ONLINE'
        ]);

        $this->assertSame(42, $appointment['id_users_provider']);
    }

    public function test_appointment_whitelist_keeps_any_provider_sentinel()
    {
        $appointment = Guard::appointment(['id_users_provider' => Guard::ANY_PROVIDER]);

        $this->assertSame(Guard::ANY_PROVIDER, $appointment['id_users_provider']);
    }

    public function test_appointment_strips_unknown_columns()
    {
        $appointment = Guard::appointment([
            'id_users_provider' => 42,
            'is_unavailable' => 1,
            'privileges' => '*',
            'hash' => 't',
            'unknown_column' => 'x'
        ]);

        $this->assertSame(['id_users_provider' => 42], $appointment);
    }

    public function test_unknown_location_values_fall_back_to_null()
    {
        $appointment = Guard::appointment(['id_users_provider' => 42, 'location' => 'MOBILE']);
        $this->assertSame(['id_users_provider' => 42, 'location' => NULL], $appointment);

        $appointment = Guard::appointment(['location' => 'CUSTOM']);
        $this->assertSame(['location' => NULL], $appointment);

        // Known modes and empty values are kept as-is.
        foreach ([Guard::MODE_ONLINE, Guard::MODE_ON_PREMISE, Guard::MODE_BOTH, '', NULL] as $location)
        {
            $appointment = Guard::appointment(['location' => $location]);
            $this->assertSame($location, $appointment['location']);
        }
    }

    public function test_valid_manage_hash_passes_silently()
    {
        Guard::check_manage_hash(['id' => '7', 'hash' => 'abc'], 7);

        $this->assertTrue(TRUE); // No exception reached this point.
    }

    public function test_empty_manage_row_throws()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid appointment hash provided.');

        Guard::check_manage_hash([], 7);
    }

    public function test_mismatched_manage_id_throws()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid appointment hash provided.');

        Guard::check_manage_hash(['id' => '8', 'hash' => 'abc'], 7);
    }

    public function test_customer_whitelist()
    {
        $customer = Guard::customer([
            'id' => 9,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone_number' => '123',
            'address' => 'Street 1',
            'city' => 'Berlin',
            'zip_code' => '10115',
            'notes' => 'notes',
            'timezone' => 'Europe/Berlin',
            'language' => 'english',
            'role_slug' => 'admin',
            'privileges' => '*'
        ]);

        unset($customer['id']);

        $this->assertSame([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone_number' => '123',
            'address' => 'Street 1',
            'city' => 'Berlin',
            'zip_code' => '10115',
            'notes' => 'notes',
            'timezone' => 'Europe/Berlin',
            'language' => 'english',
        ], $customer);
    }

    public function test_new_booking_ids_get_stripped()
    {
        [$appointment, $customer] = Guard::strip_new_booking_ids(
            ['id' => 7, 'id_users_provider' => 42],
            ['id' => 9, 'email' => 'jane@example.com']
        );

        $this->assertSame(['id_users_provider' => 42], $appointment);
        $this->assertSame(['email' => 'jane@example.com'], $customer);
    }
}
