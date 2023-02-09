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
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\query\discussion\sort;
use totara_webapi\phpunit\webapi_phpunit_helper;

class container_workspace_webapi_discussion_sorts_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_discussion_sorts(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $result = $this->resolve_graphql_query('container_workspace_discussion_sorts');
        self::assertIsArray($result);
        self::assertEquals(
            [
                sort::RECENT,
                sort::DATE_POSTED
            ],
            $result
        );
    }
}