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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use totara_core\hashtag\hashtag;
use totara_webapi\phpunit\webapi_phpunit_helper;

class core_webapi_resolver_query_find_hashtag_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_find_hashtags(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        hashtag::create('test');
        hashtag::create('test1');
        hashtag::create('test2');
        hashtag::create('notfound');

        $result = $this->resolve_graphql_query('core_hashtags_by_pattern', ['pattern' => 'te']);
        self::assertNotEmpty($result);
        self::assertIsArray($result);

        $names = array_map(
            function ($hashtag) {
                return $hashtag->get_display_name();
            }, $result
        );

        self::assertCount(3, $names);
        self::assertContainsEquals('test', $names);
        self::assertContainsEquals('test1', $names);
        self::assertContainsEquals('test2', $names);
    }
}