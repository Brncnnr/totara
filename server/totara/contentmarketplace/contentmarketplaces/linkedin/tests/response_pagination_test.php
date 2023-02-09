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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */

use contentmarketplace_linkedin\api\response\pagination;
use contentmarketplace_linkedin\exception\json_validation_exception;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_response_pagination_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_pagination_from_valid_json(): void {
        $json_data = (object) [
            'total' => 100,
            'count' => 50,
            'start' => 1,
            'links' => [
                (object) [
                    'rel' => 'next',
                    'href' => '/v2/api?q=learningAssets&start=50',
                    'type' => 'application/json'
                ]
            ]
        ];

        $pagination = pagination::create($json_data);

        self::assertEquals(100, $pagination->get_total());
        self::assertEquals(50, $pagination->get_count());
        self::assertEquals(1, $pagination->get_start());
        self::assertNull($pagination->get_previous_link());
        self::assertEquals('/v2/api?q=learningAssets&start=50', $pagination->get_next_link());
        self::assertTrue($pagination->has_next());
    }

    /**
     * @return void
     */
    public function test_create_pagination_from_invalid_json(): void {
        $json_data = (object) [
            'total' => 150,
            'start' => 1,
            'links' => [
                (object) [
                    'rel' => 'next',
                    'href' => '/v2/api?q=learningAssets&start=100',
                    'type' => 'application/json'
                ]
            ]
        ];

        $this->expectException(json_validation_exception::class);
        $this->expectExceptionMessage("Failed to validate the json data: Missing field 'count'.");

        pagination::create($json_data);
    }

    /**
     * @return void
     */
    public function test_pagination_does_not_have_next(): void {
        $json_data = (object) [
            'total' => 100,
            'count' => 50,
            'start' => 1,
            'links' => []
        ];

        $pagination = pagination::create($json_data);

        self::assertFalse($pagination->has_next());
        self::assertEquals(100, $pagination->get_total());
        self::assertEquals(50, $pagination->get_count());
        self::assertEquals(1, $pagination->get_start());
        self::assertNull($pagination->get_previous_link());
        self::assertNull($pagination->get_next_link());
    }
}