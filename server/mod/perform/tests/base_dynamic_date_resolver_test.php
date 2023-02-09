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

use core\collection;
use mod_perform\dates\constants;
use mod_perform\dates\date_offset;
use mod_perform\dates\resolvers\dynamic\base_dynamic_date_resolver;
use mod_perform\dates\resolvers\dynamic\dynamic_date_resolver;

/**
 * Class mod_perform_base_dynamic_date_resolver_testcase
 *
 * @group perform
 */
class mod_perform_base_dynamic_date_resolver_testcase extends advanced_testcase {

    /**
     * @param int $reference_date
     * @param int $expected_end_date
     * @dataProvider end_dates_provider
     */
    public function test_end_dates_are_adjusted_forward_a_day(int $reference_date, int $expected_end_date): void {
        $resolver = $this->create_zero_offset_dynamic_date_resolver([
            1 => $reference_date,
        ]);

        $start = $resolver->get_start(1);
        $end = $resolver->get_end(1);

        self::assertEquals($reference_date, $start);
        self::assertEquals($expected_end_date, $end);
    }

    public function end_dates_provider(): array {
        return [
            'Start of the day reference date' => [strtotime('2020-12-04T00:00:00'), strtotime('2020-12-05T00:00:00')],
            'Middle of the day reference date' => [strtotime('2020-12-04T12:00:00'), strtotime('2020-12-05T12:00:00')],
        ];
    }

    private function create_zero_offset_dynamic_date_resolver(array $date_map): dynamic_date_resolver {
        return new class($date_map) extends base_dynamic_date_resolver {

            public function __construct(array $date_map) {
                $this->date_map = $date_map;

                $zero_off_set = new date_offset(0, date_offset::UNIT_DAY);

                $this->set_parameters($zero_off_set, $zero_off_set, '', array_keys($date_map));
            }

            protected function resolve(): void {
            }

            public function get_options(): collection {
                return collection::new([]);
            }

            public function option_is_available(string $option_key): bool {
                return true;
            }

            public function get_resolver_base(): string {
                return constants::DATE_RESOLVER_USER_BASED;
            }
        };
    }

}
