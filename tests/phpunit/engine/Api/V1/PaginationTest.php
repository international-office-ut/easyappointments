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

class PaginationTest extends TestCase {
    public function tearDown(): void
    {
        unset($_GET['page'], $_GET['length']);
    }

    public function test_default_length_is_twenty()
    {
        $_GET['page'] = '2';

        $response = range(1, 25);
        Paginate::process($response);

        $this->assertSame(range(21, 25), $response);
    }

    public function test_length_parameter_limits_page_size()
    {
        $_GET['page'] = '2';
        $_GET['length'] = '4';

        $response = range(1, 10);
        Paginate::process($response);

        $this->assertSame([5, 6, 7, 8], $response);
    }

    public function test_out_of_range_page_returns_empty_list()
    {
        $_GET['page'] = '9';

        $response = range(1, 5);
        Paginate::process($response);

        $this->assertSame([], $response);
    }

    public function test_no_page_keeps_response_untouched()
    {
        $response = range(1, 5);
        Paginate::process($response);

        $this->assertSame(range(1, 5), $response);
    }

    public function test_empty_response_stays_empty()
    {
        $_GET['page'] = '1';

        $response = [];
        Paginate::process($response);

        $this->assertSame([], $response);
    }
}
