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

class SearchTest extends TestCase {
    protected array $records = [
        ['id' => 1, 'first_name' => 'Jane', 'notes' => ['text' => 'loves cycling']],
        ['id' => 2, 'first_name' => 'John', 'notes' => ['text' => 'loves hiking']],
        ['id' => 3, 'first_name' => 'Jack', 'notes' => ['text' => 'loves swimming']],
    ];

    protected function tearDown(): void
    {
        unset($_GET['q']);
    }

    public function test_entries_with_matches_anywhere_are_kept()
    {
        $_GET['q'] = 'hiking';

        $response = $this->records;
        Search::process($response);

        $this->assertSame([2], array_column($response, 'id'));
    }

    public function test_matching_name_filters_entries()
    {
        $_GET['q'] = 'jane';

        $response = $this->records;
        Search::process($response);

        $this->assertSame([1], array_column($response, 'id'));
    }

    public function test_search_is_case_insensitive()
    {
        $_GET['q'] = 'JACK';

        $response = $this->records;
        Search::process($response);

        $this->assertSame([3], array_column($response, 'id'));
    }

    public function test_no_keyword_keeps_every_entry()
    {
        $response = $this->records;
        Search::process($response);

        $this->assertSame($this->records, $response);
    }

    public function test_empty_response_stays_empty()
    {
        $_GET['q'] = 'jane';

        $response = [];
        Search::process($response);

        $this->assertSame([], $response);
    }
}
