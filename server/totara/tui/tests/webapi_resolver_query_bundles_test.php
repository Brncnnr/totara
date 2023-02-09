<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

defined('MOODLE_INTERNAL') || die();

class totara_tui_webapi_resolver_query_bundles_testcase extends advanced_testcase {

    use \totara_webapi\phpunit\webapi_phpunit_helper;

    public function test_resolve_missing_components() {
        $this->expectExceptionMessage('Missing required arg: components.');
        $this->resolve_graphql_query('totara_tui_bundles', ['theme' => 'ventura']);
    }

    public function test_resolve_no_theme() {
        $this->expectExceptionMessage('Missing required arg: theme.');
        $this->resolve_graphql_query('totara_tui_bundles', ['components' => []]);
    }

    public function test_resolve_no_components() {
        $result = $this->resolve_graphql_query('totara_tui_bundles', ['components' => [], 'theme' => 'ventura']);
        self::assertSame([], $result);
    }

    public function test_resolve_single_component() {
        $result = $this->resolve_graphql_query('totara_tui_bundles', ['components' => ['tui'], 'theme' => 'ventura']);
        self::assertIsArray($result);
        self::assertCount(3, $result);
        $expected = ['vendors.js', 'tui_bundle.js', 'tui_bundle.scss'];
        $actual = [];
        foreach ($result as $requirement_description) {
            $actual[] = $requirement_description->name;
        }
        self::assertSame($expected, $actual);
    }

    public function test_resolve_multiple_components() {
        $vendors_file = \totara_tui\local\locator\bundle::get_vendors_file();
        if (empty($vendors_file) || !file_exists($vendors_file)) {
            $this->markTestSkipped('Tui build files must exist for this test to complete.');
        }
        $result = $this->resolve_graphql_query('totara_tui_bundles', ['components' => ['tui', 'tui_charts'], 'theme' => 'ventura']);
        self::assertIsArray($result);
        $expected = [
            'tui/vendors.js',
            'tui/tui_bundle.js',
            'tui/tui_bundle.scss',
            'tui_charts/tui_bundle.js',
            'tui_charts/tui_bundle.scss'
        ];
        $actual = [];
        foreach ($result as $requirement_description) {
            $actual[] = $requirement_description->component . '/' . $requirement_description->name;
        }
        self::assertSame($expected, $actual);
        self::assertCount(5, $result);
    }

    /**
     * @coversNothing This is required because the test stack trace is too large for code coverage.
     */
    public function test_graphql_totara_tui_bundles() {
        $vendors_file = \totara_tui\local\locator\bundle::get_vendors_file();
        if (empty($vendors_file) || !file_exists($vendors_file)) {
            $this->markTestSkipped('Tui build files must exist for this test to complete.');
        }
        $result = $this->execute_graphql_operation('totara_tui_bundles_nosession', ['components' => ['tui', 'tui_charts'], 'theme' => 'ventura']);
        self::assertIsArray($result->errors);
        self::assertCount(0, $result->errors);
        $actual = $result->toArray(false);
        $expected = [
            'data' => [
                'bundles' => [
                    [
                        'id' => 'tui:vendors.js',
                        'component' => 'tui',
                        'name' => 'vendors.js',
                        'type' => 'js',
                        'url' => 'https://www.example.com/moodle/totara/tui/javascript.php/1/p/vendors',
                    ],
                    [
                        'id' => 'tui:tui_bundle.js',
                        'component' => 'tui',
                        'name' => 'tui_bundle.js',
                        'type' => 'js',
                        'url' => 'https://www.example.com/moodle/totara/tui/javascript.php/1/p/tui',
                    ],
                    [
                        'id' => 'tui:tui_bundle.scss',
                        'component' => 'tui',
                        'name' => 'tui_bundle.scss',
                        'type' => 'css',
                        'url' => 'https://www.example.com/moodle/totara/tui/styles.php/ventura/1/p/ltr/tui/notenant',
                    ],
                    [
                        'id' => 'tui_charts:tui_bundle.js',
                        'component' => 'tui_charts',
                        'name' => 'tui_bundle.js',
                        'type' => 'js',
                        'url' => 'https://www.example.com/moodle/totara/tui/javascript.php/1/p/tui_charts',
                    ],
                    [
                        'id' => 'tui_charts:tui_bundle.scss',
                        'component' => 'tui_charts',
                        'name' => 'tui_bundle.scss',
                        'type' => 'css',
                        'url' => 'https://www.example.com/moodle/totara/tui/styles.php/ventura/1/p/ltr/tui_charts/notenant',
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $actual);
    }

    /**
     * @coversNothing This is required because the test stack trace is too large for code coverage.
     */
    public function test_graphql_totara_tui_bundles_missing_args() {
        $result = $this->execute_graphql_operation('totara_tui_bundles_nosession', []);
        self::assertIsArray($result->errors);
        self::assertCount(2, $result->errors);
        self::assertInstanceOf(\GraphQL\Error\Error::class, $result->errors[0]);
        self::assertSame('Variable "$components" of required type "[String!]!" was not provided.', $result->errors[0]->getMessage());
        self::assertSame('Variable "$theme" of required type "param_theme!" was not provided.', $result->errors[1]->getMessage());
    }

}