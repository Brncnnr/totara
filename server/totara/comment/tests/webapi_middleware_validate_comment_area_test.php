<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

use totara_comment\webapi\resolver\middleware\validate_comment_area;
use core\webapi\resolver\result;
use core\webapi\execution_context;
use core\webapi\resolver\payload;
use totara_comment\comment;

class totara_comment_middleware_validate_comment_area_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_validate_comment_area_that_does_not_appear_in_payload(): void {
        $ec = execution_context::create('dev');
        $payload = new payload([], $ec);

        $middleware = new validate_comment_area('comment_area');
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot find area key 'comment_area' in the payload");

        $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );
    }

    /**
     * @return void
     */
    public function test_validate_comment_area_that_has_invalid_area_key(): void {
        $ec = execution_context::create('dev');
        $payload = new payload(['area' => 'DOOM BRINGER!'], $ec);

        $middleware = new validate_comment_area('area');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid comment area: DOOM BRINGER!");

        $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );
    }

    /**
     * @return void
     */
    public function test_validate_comment_area_that_has_valid_comment_area(): void {
        $valid_areas = [
            comment::COMMENT_AREA,
            comment::REPLY_AREA
        ];

        $ec = execution_context::create('dev');
        $middleware = new validate_comment_area('comment_area');

        foreach ($valid_areas as $valid_area) {
            $payload = new payload(['comment_area' => $valid_area], $ec);
            $result = $middleware->handle(
                $payload,
                function (payload $payload): result {
                    $data = $payload->get_variables();
                    return new result($data);
                }
            );

            $result_data = $result->get_data();
            self::assertArrayHasKey('comment_area', $result_data);

            // Check that the value has not changed.
            self::assertEquals($valid_area, $result_data['comment_area']);
        }
    }
}