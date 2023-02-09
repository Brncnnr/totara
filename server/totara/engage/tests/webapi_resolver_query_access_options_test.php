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
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;

class totara_engage_webapi_resolver_query_access_options_testcase extends advanced_testcase {
    use \totara_webapi\phpunit\webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_access_options(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $result = $this->resolve_graphql_query('totara_engage_access_options');
        self::assertNotEmpty($result);
        self::assertIsArray($result);
        self::assertEquals(
            [
                [
                    'value' => access::get_code(access::PRIVATE),
                    'label' => get_string('private', 'totara_engage')
                ],
                [
                    'value' => access::get_code(access::RESTRICTED),
                    'label' => get_string('restricted', 'totara_engage')
                ],
                [
                    'value' => access::get_code(access::PUBLIC),
                    'label' => get_string('public', 'totara_engage')
                ]
            ],
            $result
        );
    }
}