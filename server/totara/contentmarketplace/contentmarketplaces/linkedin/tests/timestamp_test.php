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

use contentmarketplace_linkedin\dto\timestamp;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_timestamp_testcase extends testcase {
    /**
     * @return void
     */
    public function test_timestamp_with_invalid_unit(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid timestamp unit specified: hello");

        new timestamp(1500, "hello");
    }

    /**
     * @return void
     */
    public function test_get_timestamp_of_seconds(): void {
        $timestamp = new timestamp(DAYSECS, timestamp::SECONDS);
        self::assertEquals(DAYSECS, $timestamp->get_timestamp());
        self::assertEquals(DAYSECS, $timestamp->get_raw());
        self::assertEquals(timestamp::SECONDS, $timestamp->get_unit());
    }

    /**
     * @return void
     */
    public function test_get_timestamp_of_milliseconds(): void {
        $milliseconds = HOURSECS * timestamp::MILLISECONDS_IN_SECOND;
        $timestamp = new timestamp($milliseconds, timestamp::MILLISECONDS);

        self::assertEquals(HOURSECS, $timestamp->get_timestamp());
        self::assertEquals($milliseconds, $timestamp->get_raw());
        self::assertEquals(timestamp::MILLISECONDS, $timestamp->get_unit());
    }
}