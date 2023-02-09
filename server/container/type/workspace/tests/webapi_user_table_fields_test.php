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

use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @deprecated since Totara 16. The container_workspace_user_table_fields query is
 * no longer used.
 *
 * @group container_workspace
 */
class container_workspace_webapi_user_table_fields_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_user_table_fields(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $result = $this->resolve_graphql_query('container_workspace_user_table_fields');
        $this->assert_deprecated();

        self::assertNotEmpty($result);
        self::assertIsArray($result);
    }

    private function assert_deprecated(): void {
        $messages = [
            'The container_workspace_user_table_fields query is deprecated with no replacement'
        ];

        $this->assertDebuggingCalled($messages, DEBUG_DEVELOPER);
    }
}