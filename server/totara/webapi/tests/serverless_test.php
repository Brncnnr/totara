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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core_phpunit\testcase;
use totara_webapi\serverless;

class totara_webapi_serverless_testcase extends testcase {

    public function test_execute_operation() {
        // Any operation will do here.
        $result = serverless::execute_operation(
            'core_lang_strings_nosession',
            [
                'lang' => 'en',
                'ids' => [
                    'edit,core',
                ],
            ]
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayNotHasKey('errors', $result);
        $this->assertSame([
            'lang_strings' => [
                [
                    'lang' => 'en',
                    'identifier' => 'edit',
                    'component' => 'core',
                    'string' => 'Edit',
                ],
            ],
        ], $result['data']);
    }

    public function test_execute_batch_operations() {
        $results = serverless::execute_operation(
            null,
            [
                [
                    'operationName' => 'core_lang_strings_nosession',
                    'variables' => [
                        'lang' => 'en',
                        'ids' => [
                            'edit,core',
                        ],
                    ],
                ],
                [
                    'operationName' => 'totara_webapi_status_nosession',
                    'variables' => [],
                ],
                [
                    'operationName' => 'totara_webapi_status_nosession',
                    'variables' => [],
                ],
            ],
        );

        foreach ($results as $result) {
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayNotHasKey('errors', $result);
        }
    }
}
