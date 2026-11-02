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

class SortTest extends TestCase {
    protected array $records = [
        ['id' => 1, 'last_name' => 'Smith', 'first_name' => 'John'],
        ['id' => 2, 'last_name' => 'Doe', 'first_name' => 'Adam'],
        ['id' => 3, 'last_name' => 'Doe', 'first_name' => 'Zoe'],
    ];

    protected function tearDown(): void
    {
        unset($_GET['sort']);
    }

    public function test_sorts_ascending_by_prefixless_column()
    {
        $_GET['sort'] = 'last_name';

        $response = $this->records;
        Sort::process($response);

        $this->assertSame([2, 3, 1], array_column($response, 'id'));
    }

    public function test_minus_prefix_sorts_descending()    {
        $_GET['sort'] = '-last_name';

        $response = $this->records;
        Sort::process($response);

        $this->assertSame([1, 2, 3], array_column($response, 'id'));
    }

    public function test_second_column_breaks_ties()
    {
        // "Doe" appears twice: "Zoe" must come after "Adam" on ascending first names.
        $_GET['sort'] = '+last_name,+first_name';

        $response = $this->records;
        Sort::process($response);

        $this->assertSame([2, 3, 1], array_column($response, 'id'));
    }

    public function test_tie_breaker_honors_direction()
    {
        $_GET['sort'] = '+last_name,-first_name';

        $response = $this->records;
        Sort::process($response);

        $this->assertSame([3, 2, 1], array_column($response, 'id'));
    }

    public function test_no_parameter_keeps_original_order()
    {
        $response = $this->records;
        Sort::process($response);

        $this->assertSame($this->records, $response);
    }

    public function test_empty_response_stays_empty()
    {
        $_GET['sort'] = '-id';

        $response = [];
        Sort::process($response);

        $this->assertSame([], $response);
    }
}
