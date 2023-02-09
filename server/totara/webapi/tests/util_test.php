<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

use totara_webapi\local\util;

defined('MOODLE_INTERNAL') || die();

class totara_webapi_util_testcase extends advanced_testcase {

    public function test_get_files_from_dir() {
        // Non-existant folder
        $files = util::get_files_from_dir(__DIR__.'/fixtures/idontexist', 'graphqls');
        $this->assertIsArray($files);
        $this->assertEmpty($files);

        // Folder without any graphqls files
        $files = util::get_files_from_dir(__DIR__.'/', 'graphqls');
        $this->assertIsArray($files);
        $this->assertEmpty($files);

        // Test folder with some test files in it
        $files = util::get_files_from_dir(__DIR__.'/fixtures/webapi', 'graphqls');
        $this->assertIsArray($files);
        $this->assertCount(8, $files);
        $this->assertEqualsCanonicalizing(
            [
                __DIR__.'/fixtures/webapi/test_deprecation.graphqls',
                __DIR__.'/fixtures/webapi/test_schema_1.graphqls',
                __DIR__.'/fixtures/webapi/test_schema_2.graphqls',
                __DIR__.'/fixtures/webapi/test_schema_3.graphqls',
                __DIR__.'/fixtures/webapi/job_assignment.graphqls',
                __DIR__.'/fixtures/webapi/test_union.graphqls',
                __DIR__.'/fixtures/webapi/schema_files_test_1.graphqls',
                __DIR__.'/fixtures/webapi/schema_files_test_2.graphqls',
            ],
            $files
        );
    }

    public function test_is_nosession_request() {
        $request = 'foo';
        $this->assertFalse(util::is_nosession_request($request));

        $request = [];
        $this->assertFalse(util::is_nosession_request($request));

        $request = [
            'operationName' => 'my_test_operation',
            'variables' => []
        ];
        $this->assertFalse(util::is_nosession_request($request));

        $request = [
            'operationName' => 'my_test_operation_nosession',
            'variables' => []
        ];
        $this->assertTrue(util::is_nosession_request($request));

        $request = [
            'variables' => []
        ];
        $this->assertFalse(util::is_nosession_request($request));

        $request = [
            [
                'operationName' => 'my_test_operation1',
                'variables' => []
            ],
            [
                'operationName' => 'my_test_operation2',
                'variables' => []
            ],
        ];
        $this->assertFalse(util::is_nosession_request($request));

        $request = [
            [
                'operationName' => 'my_test_operation1_nosession',
                'variables' => []
            ],
            [
                'operationName' => 'my_test_operation2',
                'variables' => []
            ],
        ];
        $this->assertFalse(util::is_nosession_request($request));

        $request = [
            [
                'operationName' => 'my_test_operation1_nosession',
                'variables' => []
            ],
            [
                'operationName' => 'my_test_operation2_nosession',
                'variables' => []
            ],
        ];
        $this->assertTrue(util::is_nosession_request($request));
    }

}
