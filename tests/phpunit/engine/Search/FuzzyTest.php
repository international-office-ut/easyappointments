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

namespace EA\Engine\Search;

use PHPUnit\Framework\TestCase;

class FuzzyTest extends TestCase {
    protected array $customers = [
        ['id' => 1, 'first_name' => 'John', 'last_name' => 'Smith', 'email' => 'john.smith@example.com', 'city' => 'Berlin'],
        ['id' => 2, 'first_name' => 'Johanna', 'last_name' => 'Müller', 'email' => 'johanna@example.com', 'city' => 'Paris'],
        ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@doe.co', 'city' => 'London'],
    ];

    public function test_tokens_are_normalized()
    {
        $this->assertSame(['jack', 'smth'], Fuzzy::tokens('  Jack, SmTh!  '));
        $this->assertSame(['muller', 'johanna'], Fuzzy::tokens('Müller  Johanna'));
        $this->assertSame([], Fuzzy::tokens('  --  '));
    }

    public function test_exact_and_partial_matches_are_found()
    {
        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], 'smith');

        $this->assertSame([1], array_column($records, 'id'));

        // Partial tokens match the start of longer words as well ("john" finds "Johanna").
        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], 'john');

        $this->assertSame([1, 2], array_column($records, 'id'));
    }

    public function test_tokens_can_match_different_fields()
    {
        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], 'jane doe');
        $this->assertSame([3], array_column($records, 'id'));

        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], 'doe jane');
        $this->assertSame([3], array_column($records, 'id'));

        // "jane" also matches "Johanna" (fuzzy), but every token must match.
        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], 'joh berlin');
        $this->assertSame([1], array_column($records, 'id'));
    }

    public function test_typos_and_transpositions_still_match()
    {
        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], 'jhon smth');

        $this->assertSame([1], array_column($records, 'id'));
    }

    public function test_accents_in_the_key_are_forgiven()
    {
        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], 'müller');

        $this->assertSame([2], array_column($records, 'id'));

        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], 'andré');

        $this->assertSame([], $records);
    }

    public function test_relevance_puts_exact_substring_matches_first()
    {
        $records = [
            ['id' => 1, 'name' => 'Photoshot Special'], // Fuzzy match ("photoshoot" -> "photoshot").
            ['id' => 2, 'name' => 'Photoshoot Consultation'], // Exact substring.
        ];

        $records = Fuzzy::filter($records, ['name'], 'photoshoot');

        $this->assertSame([2, 1], array_column($records, 'id'));
    }

    public function test_limit_keeps_the_load_more_semantics()
    {
        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], 'joh', 1);

        $this->assertCount(1, $records);
        $this->assertSame(1, $records[0]['id'], 'Best match ("John") must come first.');
    }

    public function test_empty_key_preserves_the_record_order()
    {
        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], '');

        $this->assertSame($this->customers, $records);
    }

    public function test_short_tokens_need_an_exact_substring()
    {
        // "par" has a single-change tolerance, "pa" must match exactly.
        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], 'par');
        $this->assertSame([2], array_column($records, 'id'));

        $records = Fuzzy::filter($this->customers, ['first_name', 'last_name', 'email', 'city'], 'paris');
        $this->assertSame([2], array_column($records, 'id'));
    }

    public function test_numeric_fields_match_as_strings()
    {
        $records = [
            ['id' => 1, 'name' => 'Haircut', 'price' => 25, 'duration' => 90],
        ];

        $this->assertNotEmpty(Fuzzy::filter($records, ['name', 'price', 'duration'], '25'));
        $this->assertNotEmpty(Fuzzy::filter($records, ['name', 'price', 'duration'], '90'));
        $this->assertSame([], Fuzzy::filter($records, ['name', 'price', 'duration'], '26'));
    }
}
