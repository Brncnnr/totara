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

use totara_tui\local\mediation\helper;

defined('MOODLE_INTERNAL') || die();

class totara_tui_local_mediation_helper_testcase extends advanced_testcase {

    public function test_validate_theme_name() {
        self::assertFalse(helper::validate_theme_name('theme_ventura'));
        self::assertTrue(helper::validate_theme_name('ventura'));
        self::assertFalse(helper::validate_theme_name('foobar'));
        self::assertFalse(helper::validate_theme_name('foo.bar'));
        self::assertFalse(helper::validate_theme_name('foo-bar'));
    }

    public function test_validate_theme_name_custom_theme_path() {
        global $CFG;
        $CFG->themedir = $CFG->dirroot . '/theme';
        self::assertFalse(helper::validate_theme_name('theme_ventura'));
        self::assertTrue(helper::validate_theme_name('ventura'));
        self::assertFalse(helper::validate_theme_name('foobar'));
        self::assertFalse(helper::validate_theme_name('foo.bar'));
        self::assertFalse(helper::validate_theme_name('foo-bar'));
    }

    public function test_validate_theme_component_name() {
        $ref_class = new ReflectionClass(helper::class);
        $method = $ref_class->getMethod('validate_theme_component_name');
        $method->setAccessible(true);

        self::assertTrue($method->invoke(null, 'ventura'));
        self::assertTrue($method->invoke(null, 'example2'));
        self::assertTrue($method->invoke(null, 'example_2'));
        self::assertFalse($method->invoke(null, 'foo.bar'));
        self::assertFalse($method->invoke(null, 'foo-bar'));
    }

    public function test_get_args() {
        global $CFG;
        require_once($CFG->libdir . '/configonlylib.php');

        $_GET['file'] = '/1234/totara_tui';
        $actual = helper::get_args([
            'rev' => 'INT',
            'component' => 'SAFEDIR'
        ]);
        self::assertSame([1234, 'totara_tui'], $actual);

        unset($_GET['file']);
        $_GET['rev'] = '1234';
        $_GET['component'] = 'totara_tui';
        $actual = helper::get_args([
            'rev' => 'INT',
            'component' => 'SAFEDIR'
        ]);
        self::assertSame([1234, 'totara_tui'], $actual);

        unset($_GET['rev']);
        unset($_GET['component']);
    }
}