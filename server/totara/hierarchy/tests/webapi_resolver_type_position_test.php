<?php
/*
 * This file is part of Totara LMS
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_hierarchy
 */

use totara_webapi\phpunit\webapi_phpunit_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the totara hierarchy position type resolver
 */
class totara_hierarchy_webapi_resolver_type_position_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $pos, array $args = []) {
        return $this->resolve_graphql_type('totara_hierarchy_position', $field, (object) $pos, $args);
    }

    public function test_resolve_id() {
        $field = 'id';
        self::assertSame(7, $this->resolve($field, [$field => 7]));
        self::assertSame('7', $this->resolve($field, [$field => '7']));
        self::assertSame(0, $this->resolve($field, [$field => 0]));
        self::assertSame('0', $this->resolve($field, [$field => '0']));
        self::assertSame(-10, $this->resolve($field, [$field => -10]));
        self::assertSame('-10', $this->resolve($field, [$field => '-10']));
        self::assertSame('', $this->resolve($field, [$field => '']));

        try {
            self::assertSame(null, $this->resolve($field, [$field => null]));
            $this->fail('Exception expected');
        } catch (\coding_exception $ex) {
            self::assertStringContainsString('Expected value, but was not found and was not nullable', $ex->getMessage());
        }
    }

    public function test_resolve_idnumber() {
        $field = 'idnumber';
        self::assertSame(7, $this->resolve($field, ['id' => 6, $field => 7]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7']));
        self::assertSame(0, $this->resolve($field, ['id' => 6, $field => 0]));
        self::assertSame('0', $this->resolve($field, ['id' => 6, $field => '0']));
        self::assertSame(-10, $this->resolve($field, ['id' => 6, $field => -10]));
        self::assertSame('-10', $this->resolve($field, ['id' => 6, $field => '-10']));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => '']));

        try {
            self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null]));
            $this->fail('Exception expected');
        } catch (\coding_exception $ex) {
            self::assertStringContainsString('Expected value, but was not found and was not nullable', $ex->getMessage());
        }
    }

    public function test_resolve_path() {
        $field = 'path';
        self::assertSame(7, $this->resolve($field, ['id' => 6, $field => 7]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7']));
        self::assertSame(0, $this->resolve($field, ['id' => 6, $field => 0]));
        self::assertSame('0', $this->resolve($field, ['id' => 6, $field => '0']));
        self::assertSame(-10, $this->resolve($field, ['id' => 6, $field => -10]));
        self::assertSame('-10', $this->resolve($field, ['id' => 6, $field => '-10']));
        self::assertSame('/10', $this->resolve($field, ['id' => 6, $field => '/10']));
        self::assertSame('/10/100/1', $this->resolve($field, ['id' => 6, $field => '/10/100/1']));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => '']));

        try {
            self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null]));
            $this->fail('Exception expected');
        } catch (\coding_exception $ex) {
            self::assertStringContainsString('Expected value, but was not found and was not nullable', $ex->getMessage());
        }
    }

    public function test_resolve_visible() {
        $field = 'visible';
        self::assertSame(true, $this->resolve($field, ['id' => 6, $field => true]));
        self::assertSame(1, $this->resolve($field, ['id' => 6, $field => 1]));
        self::assertSame('1', $this->resolve($field, ['id' => 6, $field => '1']));
        self::assertSame(false, $this->resolve($field, ['id' => 6, $field => false]));
        self::assertSame(0, $this->resolve($field, ['id' => 6, $field => 0]));
        self::assertSame('0', $this->resolve($field, ['id' => 6, $field => '0']));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => '']));

        try {
            self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null]));
            $this->fail('Exception expected');
        } catch (\coding_exception $ex) {
            self::assertStringContainsString('Expected value, but was not found and was not nullable', $ex->getMessage());
        }
    }

    public function test_resolve_parentid() {
        $field = 'parentid';
        self::assertSame(7, $this->resolve($field, ['id' => 6, $field => 7]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7']));
        self::assertSame(0, $this->resolve($field, ['id' => 6, $field => 0]));
        self::assertSame('0', $this->resolve($field, ['id' => 6, $field => '0']));
        self::assertSame(-10, $this->resolve($field, ['id' => 6, $field => -10]));
        self::assertSame('-10', $this->resolve($field, ['id' => 6, $field => '-10']));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => '']));
    }

    public function test_resolve_typeid() {
        $field = 'typeid';
        self::assertSame(7, $this->resolve($field, ['id' => 6, $field => 7]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7']));
        self::assertSame(0, $this->resolve($field, ['id' => 6, $field => 0]));
        self::assertSame('0', $this->resolve($field, ['id' => 6, $field => '0']));
        self::assertSame(-10, $this->resolve($field, ['id' => 6, $field => -10]));
        self::assertSame('-10', $this->resolve($field, ['id' => 6, $field => '-10']));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => '']));
    }

    public function test_resolve_shortname() {
        $field = 'shortname';

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7']));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test']));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test']));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => '<p>Test</p>']));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>']));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => '']));

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_PLAIN]));

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('Test &#38; Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('Test &#38; Test', $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_HTML]));

        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_RAW]));

        $this->setAdminUser();

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('<p>Test</p>', $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('<p>Test & Test</p>', $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_RAW]));
    }

    public function test_resolve_fullname() {
        $field = 'fullname';

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7']));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test']));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test']));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => '<p>Test</p>']));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>']));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => '']));

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_PLAIN]));

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('Test &#38; Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('Test &#38; Test', $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_HTML]));

        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_RAW]));

        $this->setAdminUser();

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('<p>Test</p>', $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('<p>Test & Test</p>', $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_RAW]));
    }

    public function test_resolve_description() {
        $field = 'description';

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7']));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test']));
        self::assertSame('Test &amp; Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test']));
        self::assertSame('<p>Test</p>', $this->resolve($field, ['id' => 6, $field => '<p>Test</p>']));
        self::assertSame('<p>Test &amp; Test</p>', $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>']));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => '']));

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame("Test\n", $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame("Test & Test\n", $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_PLAIN]));

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('Test &amp; Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('<p>Test</p>', $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('<p>Test &amp; Test</p>', $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_HTML]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_HTML]));

        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_RAW]));

        $this->setAdminUser();

        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => 7], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('Test', $this->resolve($field, ['id' => 6, $field => 'Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('Test & Test', $this->resolve($field, ['id' => 6, $field => 'Test & Test'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('<p>Test</p>', $this->resolve($field, ['id' => 6, $field => '<p>Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('<p>Test & Test</p>', $this->resolve($field, ['id' => 6, $field => '<p>Test & Test</p>'], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null], ['format' => \core\format::FORMAT_RAW]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => ''], ['format' => \core\format::FORMAT_RAW]));
    }

    public function test_resolve_frameworkid() {
        $this->setAdminUser();

        $field = 'frameworkid';
        self::assertSame(7, $this->resolve($field, ['id' => 6, $field => 7]));
        self::assertSame('7', $this->resolve($field, ['id' => 6, $field => '7']));
        self::assertSame(0, $this->resolve($field, ['id' => 6, $field => 0]));
        self::assertSame('0', $this->resolve($field, ['id' => 6, $field => '0']));
        self::assertSame(-10, $this->resolve($field, ['id' => 6, $field => -10]));
        self::assertSame('-10', $this->resolve($field, ['id' => 6, $field => '-10']));
        self::assertSame(null, $this->resolve($field, ['id' => 6, $field => null]));
        self::assertSame('', $this->resolve($field, ['id' => 6, $field => '']));
    }

    public function test_resolve_framework() {
        $this->setAdminUser();

        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);
        $typeid = $generator->create_pos_type([]);
        $position = $generator->create_pos(['frameworkid' => $framework->id, 'typeid' => $typeid]);

        self::assertSame((array)$framework, (array)$this->resolve('framework', $position));
    }

    public function test_resolve_parent() {
        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);
        $typeid = $generator->create_pos_type([]);
        $position = $generator->create_pos(['frameworkid' => $framework->id, 'typeid' => $typeid]);
        $child = $generator->create_pos(['frameworkid' => $framework->id, 'typeid' => $typeid, 'parentid' => $position->id]);

        self::assertSame((array)$position, (array)$this->resolve('parent', $child));
    }

    public function test_resolve_children() {
        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);
        $typeid = $generator->create_pos_type([]);
        $position = $generator->create_pos(['frameworkid' => $framework->id, 'typeid' => $typeid]);
        $child = $generator->create_pos(['frameworkid' => $framework->id, 'typeid' => $typeid, 'parentid' => $position->id]);

        self::assertSame(
            [$child->id => (array)$child],
            array_map(
                function ($obj){
                    return (array)$obj;
                },
                $this->resolve('children', $position)
            )
        );
    }

    public function test_resolve_type() {
        global $DB;
        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);
        $typeid = $generator->create_pos_type([]);
        $position = $generator->create_pos(['frameworkid' => $framework->id, 'typeid' => $typeid]);
        self::assertEquals($DB->get_record('pos_type', ['id' => $typeid]), $this->resolve('type', $position));
    }

}
