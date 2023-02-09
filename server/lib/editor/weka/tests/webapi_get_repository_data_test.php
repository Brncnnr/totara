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
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;

class editor_weka_webapi_get_repository_data_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * This is to test that the field max_bytes in repository data is a numeric string
     * @return void
     */
    public function test_get_repository_data_that_has_max_bytes(): void {
        global $CFG;
        $CFG->maxbytes = 100;

        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);
        $result = $this->execute_graphql_operation('editor_weka_get_repository_data', []);

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertArrayHasKey('repository_data', $result->data);

        $repository_data = $result->data['repository_data'];
        self::assertArrayHasKey('max_bytes', $repository_data);
        self::assertIsString($repository_data['max_bytes']);
        self::assertEquals(100, (int) $repository_data['max_bytes']);

        self::assertArrayHasKey('url', $repository_data);
        self::assertArrayHasKey('repository_id', $repository_data);
    }
}