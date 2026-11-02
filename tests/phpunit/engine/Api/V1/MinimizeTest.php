<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

namespace EA\Engine\Api\V1\Processors;

use PHPUnit\Framework\TestCase;

class MinimizeTest extends TestCase {
    protected array $records = [
        ['id' => 1, 'first_name' => 'Jane', 'email' => 'jane@example.com'],
        ['id' => 2, 'first_name' => 'John', 'email' => 'john@example.com'],
    ];

    public function tearDown(): void
    {
        unset($_GET['fields']);
    }

    public function test_only_requested_fields_are_kept()
    {
        $_GET['fields'] = 'id,email';

        $response = $this->records;
        Minimize::process($response);

        $this->assertSame([
            ['id' => 1, 'email' => 'jane@example.com'],
            ['id' => 2, 'email' => 'john@example.com'],
        ], $response);
    }

    public function test_fields_around_commas_are_trimmed()
    {
        $_GET['fields'] = 'id, first_name';

        $response = $this->records;
        Minimize::process($response);

        $this->assertSame([
            ['id' => 1, 'first_name' => 'Jane'],
            ['id' => 2, 'first_name' => 'John'],
        ], $response);
    }

    public function test_absent_fields_are_skipped()
    {
        $_GET['fields'] = 'id,phone_number';

        $response = $this->records;
        Minimize::process($response);

        $this->assertSame([
            ['id' => 1],
            ['id' => 2],
        ], $response);
    }

    public function test_no_fields_keeps_everything()
    {
        $response = $this->records;
        Minimize::process($response);

        $this->assertSame($this->records, $response);
    }

    public function test_empty_response_stays_empty()
    {
        $_GET['fields'] = 'id';

        $response = [];
        Minimize::process($response);

        $this->assertSame([], $response);
    }
}
