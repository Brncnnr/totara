<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\dto\timespan;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_timespan_testcase extends testcase {
    /**
     * Provide data for testing minute factory function of timespan.
     * @internal
     * @return array
     */
    public function provide_minute_data(): array {
        return [
            [10, (10 * MINSECS)],
            [12, (12 * MINSECS)],
            [25, (25 * MINSECS)]
        ];
    }

    /**
     * @dataProvider provide_minute_data
     *
     * @param int $minute
     * @param int $seconds
     *
     * @return void
     */
    public function test_instantiate_with_minute_factory(int $minute, int $seconds): void {
        $timespan = timespan::minutes($minute);
        self::assertEquals($minute, $timespan->get_raw_duration());
        self::assertEquals(timespan::UNIT_MINUTE, $timespan->get_raw_unit());

        self::assertEquals($seconds, $timespan->get());
    }

    /**
     * Provide data for testing hours factory function of timespan.
     *
     * @internal
     * @return array
     */
    public function provide_hours_data(): array {
        return [
            [1, HOURSECS],
            [55, (55 * HOURSECS)],
            [19, (19 * HOURSECS)],
        ];
    }

    /**
     * @dataProvider provide_hours_data
     *
     * @param int $hours
     * @param int $seconds
     *
     * @return void
     */
    public function test_instantiate_with_hours_factory(int $hours, int $seconds): void {
        $timespan = timespan::hours($hours);
        self::assertEquals($hours, $timespan->get_raw_duration());
        self::assertEquals(timespan::UNIT_HOUR, $timespan->get_raw_unit());

        self::assertEquals($seconds, $timespan->get());
    }

    /**
     * @return void
     */
    public function test_instantiate_with_seconds_factory(): void {
        $timespan = timespan::seconds(DAYSECS);
        self::assertEquals(DAYSECS, $timespan->get_raw_duration());
        self::assertEquals(timespan::UNIT_SECOND, $timespan->get_raw_unit());

        self::assertEquals(DAYSECS, $timespan->get());
    }
}