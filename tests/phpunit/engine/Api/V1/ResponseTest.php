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

namespace EA\Engine\Api\V1;

use EA\Engine\Api\V1\Parsers\ParsersInterface;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase {
    protected function tearDown(): void
    {
        unset($_GET['sort'], $_GET['page'], $_GET['length'], $_GET['fields']);
    }

    public function test_encode_applies_parser_to_every_entry()
    {
        $response = new Response([
            ['id' => 1, 'balance' => '-10'],
            ['id' => 2, 'balance' => '20'],
        ]);

        $response->encode(new ParserStub);

        $this->assertSame(-10, $this->responseData($response)[0]['balance']);
        $this->assertSame(20, $this->responseData($response)[1]['balance']);
    }

    public function test_single_entry_collapses_the_list()
    {
        $response = new Response([['id' => 1], ['id' => 2]]);
        $response->singleEntry(5);

        $this->assertSame(['id' => 1], $this->responseData($response));
    }

    public function test_single_entry_without_id_keeps_the_list()
    {
        $response = new Response([['id' => 1], ['id' => 2]]);
        $response->singleEntry(NULL);

        $this->assertSame([['id' => 1], ['id' => 2]], $this->responseData($response));
    }

    public function test_processors_chain_on_get_parameters()
    {
        $_GET['sort'] = '-id';
        $_GET['page'] = '2';
        $_GET['length'] = '1';
        $_GET['fields'] = 'id';

        $response = new Response([
            ['id' => 1, 'last_name' => 'Smith'],
            ['id' => 2, 'last_name' => 'Doe'],
            ['id' => 3, 'last_name' => 'Doe'],
        ]);

        $response
            ->search()
            ->sort()
            ->paginate()
            ->minimize();

        $this->assertSame([['id' => 2]], $this->responseData($response));
    }

    public function test_processors_chain_on_empty_get_parameters()
    {
        $response = new Response([
            ['id' => 1, 'last_name' => 'Smith'],
            ['id' => 2, 'last_name' => 'Doe'],
        ]);

        $response
            ->search()
            ->sort()
            ->paginate()
            ->minimize()
            ->singleEntry(NULL);

        $this->assertSame(
            [
                ['id' => 1, 'last_name' => 'Smith'],
                ['id' => 2, 'last_name' => 'Doe'],
            ],
            $this->responseData($response)
        );
    }

    /**
     * Runs in a separate process so that header() does not collide with PHPUnit output.
     *
     * @runInSeparateProcess
     */
    public function test_output_prints_json()
    {
        $response = new Response([['id' => 1]]);

        ob_start();
        $response->output();
        $output = ob_get_clean();

        $this->assertSame([['id' => 1]], json_decode($output, TRUE));
    }

    /**
     * The "response" member is protected, so reflection is the way in.
     */
    protected function responseData(Response $response): array
    {
        $property = new \ReflectionProperty($response, 'response');

        return $property->getValue($response);
    }
}

/**
 * Minimal parser double: casts "balance" to int so encode() effects are observable.
 */
class ParserStub implements ParsersInterface {
    public function encode(array &$response)
    {
        if (isset($response['balance']))
        {
            $response['balance'] = (int) $response['balance'];
        }
    }

    public function decode(array &$request, ?array $base = NULL)
    {
    }
}
