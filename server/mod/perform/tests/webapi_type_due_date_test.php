<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\models\due_date;

use core\date_format;
use core\format;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\type\due_date
 *
 * @group perform
 */
class mod_perform_webapi_type_due_date_testcase extends testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'mod_perform_due_date';

    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        $this->setAdminUser();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/due_date class/");

        $this->resolve_graphql_type(self::TYPE, 'due_date', new \stdClass());
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        $this->setAdminUser();

        $due_date = new due_date(124334343);
        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $due_date);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        $this->setAdminUser();

        $interval_count = 3;
        $interval_type = 'days';
        $raw_due_date = (new DateTimeImmutable("-$interval_count $interval_type"))
            ->getTimestamp();

        $beginning_of_due_day = usergetmidnight($raw_due_date);
        $end_of_due_day = (new DateTimeImmutable("@$beginning_of_due_day"))
            ->setTime(23, 59, 59)
            ->getTimestamp();

        $due_date = new due_date($raw_due_date);

        $testcases = [
            'due date' => ['due_date', date_format::FORMAT_TIMESTAMP, $end_of_due_day],
            'overdue' => ['is_overdue', null, true],
            'interval count' => ['units_to_due_date', null, $interval_count],
            'interval type' => ['units_to_due_date_type', format::FORMAT_PLAIN, $interval_type]
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $format, $expected] = $testcase;
            $args = $format ? ['format' => $format] : [];

            $value = $this->resolve_graphql_type(self::TYPE, $field, $due_date, $args);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }
}
