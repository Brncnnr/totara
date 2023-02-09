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
 * Tests the totara hierarchy organisation_framework type resolver
 */
class totara_hierarchy_webapi_resolver_type_organisation_framework_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $pos, array $args = []) {
        return $this->resolve_graphql_type('totara_hierarchy_organisation_framework', $field, (object) $pos, $args);
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
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

    public function test_resolve_organisations() {
        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_org_frame([]);
        $typeid = $generator->create_org_type([]);
        $organisation1 = $generator->create_org(['frameworkid' => $framework->id, 'typeid' => $typeid]);
        $organisation2 = $generator->create_org(['frameworkid' => $framework->id, 'typeid' => $typeid]);

        self::assertEquals(
            [$organisation1->id => $organisation1, $organisation2->id => $organisation2],
            $this->resolve('organisations', $framework)
        );
    }

}
