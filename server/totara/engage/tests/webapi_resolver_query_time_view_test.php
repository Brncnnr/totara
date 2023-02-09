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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */

use totara_engage\timeview\time_view;

defined('MOODLE_INTERNAL') || die();


class totara_engage_webapi_resolver_query_time_view_testcase extends advanced_testcase {
    use \totara_webapi\phpunit\webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_time_view(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $result = $this->resolve_graphql_query('totara_engage_time_view_options');

        self::assertIsArray($result);
        self::assertEquals(
            [
                [
                    'value' => time_view::get_code(time_view::LESS_THAN_FIVE),
                    'label' => time_view::get_string(time_view::LESS_THAN_FIVE)
                ],
                [
                    'value' => time_view::get_code(time_view::FIVE_TO_TEN),
                    'label' => time_view::get_string(time_view::FIVE_TO_TEN)
                ],
                [
                    'value' => time_view::get_code(time_view::MORE_THAN_TEN),
                    'label' => time_view::get_string(time_view::MORE_THAN_TEN)
                ]
            ],
            $result
        );
    }
}