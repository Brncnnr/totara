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

class totara_tui_webapi_resolver_query_themes_with_bundles_testcase extends advanced_testcase {

    use \totara_webapi\phpunit\webapi_phpunit_helper;

    public function test_login_required() {
        self::expectException(require_login_exception::class);
        $this->resolve_graphql_query('totara_tui_themes_with_variables', []);
    }

    public function test_missing_theme_arg() {
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Missing required argument');
        $this->setAdminUser();
        $this->resolve_graphql_query('totara_tui_themes_with_variables', []);
    }

    public function test_invalid_theme_arg() {
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Invalid theme');
        $this->setAdminUser();
        $this->resolve_graphql_query('totara_tui_themes_with_variables', ['theme' => 'bananas']);
    }

    public function test_base() {
        $this->setAdminUser();
        $expected = [];
        $actual = $this->resolve_graphql_query('totara_tui_themes_with_variables', ['theme' => 'base']);
        self::assertSame($expected, $actual);
    }

    public function test_basis() {
        $this->setAdminUser();
        $expected = [];
        $actual = $this->resolve_graphql_query('totara_tui_themes_with_variables', ['theme' => 'basis']);
        self::assertSame($expected, $actual);
    }

    public function test_legacy() {
        $this->setAdminUser();
        $expected = [];
        $actual = $this->resolve_graphql_query('totara_tui_themes_with_variables', ['theme' => 'legacy']);
        self::assertSame($expected, $actual);
    }

    public function test_msteams() {
        $this->setAdminUser();
        $expected = [
            'ventura'
        ];
        $actual = $this->resolve_graphql_query('totara_tui_themes_with_variables', ['theme' => 'msteams']);
        self::assertSame($expected, $actual);
    }

    public function test_roots() {
        $this->setAdminUser();
        $expected = [];
        $actual = $this->resolve_graphql_query('totara_tui_themes_with_variables', ['theme' => 'roots']);
        self::assertSame($expected, $actual);
    }

    public function test_ventura() {
        $this->setAdminUser();
        $expected = [
            'ventura'
        ];
        $actual = $this->resolve_graphql_query('totara_tui_themes_with_variables', ['theme' => 'ventura']);
        self::assertSame($expected, $actual);
    }

    public function test_authenticated_user_can_access() {
        $this->setUser($this->getDataGenerator()->create_user());
        $expected = [
            'ventura'
        ];
        $actual = $this->resolve_graphql_query('totara_tui_themes_with_variables', ['theme' => 'msteams']);
        self::assertSame($expected, $actual);
    }

    public function test_guest_can_access() {
        $this->setGuestUser();
        $expected = [
            'ventura'
        ];
        $actual = $this->resolve_graphql_query('totara_tui_themes_with_variables', ['theme' => 'msteams']);
        self::assertSame($expected, $actual);
    }

}