<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\dates\resolvers\anniversary_of;
use mod_perform\dates\resolvers\fixed_range_resolver;

/**
 * Class mod_perform_anniversary_of_date_resolver_testcase
 *
 * @group perform
 */
class mod_perform_anniversary_of_date_resolver_testcase extends advanced_testcase {

    public function test_cant_accept_its_self_as_the_original_resolver(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('anniversary_of can not accept an original date resolver of the type anniversary_of');

        $first_anniversary_of = new anniversary_of(new fixed_range_resolver(time(), time()), time());

        new anniversary_of($first_anniversary_of, time());
    }

    /**
     * @param string $start_date
     * @param string $end_date
     * @param string $now_date
     * @param string $expected_start
     * @param string $expected_end
     * @dataProvider start_and_end_provider
     */
    public function test_get_start_and_end(
        string $start_date,
        string $end_date,
        string $now_date,
        string $expected_start,
        string $expected_end
    ): void {
        $raw_start = strtotime($start_date . 'T00:00:00 UTC');
        $raw_end = strtotime($end_date . 'T00:00:00 UTC');

        $anniversary_cutoff_date = strtotime($now_date . 'T00:00:00 UTC');

        $anniversary_of = new anniversary_of(new fixed_range_resolver($raw_start, $raw_end), $anniversary_cutoff_date);

        $raw_actual_start = $anniversary_of->get_start(1);
        $raw_actual_end = $anniversary_of->get_end(1);

        $actual_start = (new DateTimeImmutable("@{$raw_actual_start}"))->format('Y-m-d');
        $actual_end = (new DateTimeImmutable("@{$raw_actual_end}"))->format('Y-m-d');

        static::assertEquals($actual_start, $expected_start);
        static::assertEquals($actual_end, $expected_end);
    }

    public function start_and_end_provider(): array {
        return [
                                                        // ref start,      ref end,          now,   exp start,        exp end,
            'Only start needs year adjustment' =>       ['2018-01-01', '2020-01-02', '2020-01-01', '2020-01-01', '2020-01-02'],
            'Both boundaries need year adjustment' =>   ['2018-01-01', '2018-01-02', '2020-01-01', '2020-01-01', '2020-01-02'],
            'Same day start/end and need adjustment' => ['2018-01-01', '2018-01-01', '2020-01-01', '2020-01-01', '2020-01-01'],
            'Same day start/end and no adjustment' =>   ['2020-01-01', '2020-01-01', '2020-01-01', '2020-01-01', '2020-01-01'],
        ];
    }
}
