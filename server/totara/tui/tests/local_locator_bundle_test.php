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

use totara_core\path;
use totara_tui\local\locator\bundle;

defined('MOODLE_INTERNAL') || die();

class totara_tui_local_locator_bundle_test extends advanced_testcase {

    private function build_files_exist() {
        global $CFG;
        return file_exists($CFG->srcroot . '/client/component/tui/build');
    }

    public function test_get_bundle_js_file() {
        global $CFG;
        if ($this->build_files_exist()) {
            self::assertSamePath($CFG->srcroot . '/client/component/tui/build/tui_bundle.js', bundle::get_bundle_js_file('tui'));
        }
        self::assertNull(bundle::get_bundle_js_file('space_monkeys'));
    }

    public function test_get_bundle_css_file() {
        global $CFG;
        if ($this->build_files_exist()) {
            self::assertSamePath($CFG->srcroot . '/client/component/tui/build/tui_bundle.scss', bundle::get_bundle_css_file('tui'));
        }
        self::assertNull(bundle::get_bundle_css_file('theme_ventura'));
        self::assertNull(bundle::get_bundle_js_file('space_monkeys'));
    }

    public function test_get_vendors_file() {
        global $CFG;
        if ($this->build_files_exist()) {
            self::assertSamePath($CFG->srcroot . '/client/component/tui/build/vendors.js', bundle::get_vendors_file());
        }
    }

    public function test_get_style_import() {
        global $CFG;
        if ($this->build_files_exist()) {
            $actual = bundle::get_style_import('theme_ventura', '_variables.scss');
            self::assertSamePath($CFG->srcroot . '/client/component/theme_ventura/build/global_styles/_variables.scss', $actual);
        }

        $actual = bundle::get_style_import('theme_ventura', 'space_monkeys.scss');
        self::assertNull($actual);
    }

    public function test_get_bundle_dependencies() {
        global $CFG;
        $actual = bundle::get_bundle_dependencies('tui');
        self::assertIsArray($actual);
        self::assertCount(0, $actual);

        $actual = bundle::get_bundle_dependencies('theme_ventura');
        self::assertIsArray($actual);
        self::assertCount(0, $actual);

        if ($this->build_files_exist()) {
            $actual = bundle::get_bundle_dependencies('criteria_onactivate');
            self::assertIsArray($actual);
            self::assertCount(1, $actual);
            self::assertSame(['totara_criteria'], $actual);
        }
    }

    public function test_instance_and_reset() {
        $method = new ReflectionMethod(bundle::class, 'instance');
        $method->setAccessible(true);
        $instance = $method->invoke(null);
        self::assertInstanceOf(bundle::class, $instance);
        self::assertSame($instance, $method->invoke(null));
        bundle::reset();
        self::assertNotSame($instance, $method->invoke(null));
    }

    public function test_anticipated_bundle_location() {
        global $CFG;
        $method = new ReflectionMethod(bundle::class, 'anticipated_bundle_location');
        $method->setAccessible(true);
        $actual = $method->invoke(null, 'test');
        self::assertSamePath("$CFG->srcroot/client/component/test/build", $actual);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Invalid bundle name provided.');
        $method->invoke(null, 'test@example.com');
    }

    public function test_get_js_suffix_for_url() {
        self::assertSame('p', bundle::get_js_suffix_for_url());
        set_config('development_mode', '1', 'totara_tui');
        self::assertSame('d', bundle::get_js_suffix_for_url());
        \core_useragent::instance(true, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; KTXN)');
        self::assertSame('dl', bundle::get_js_suffix_for_url());
        unset_config('development_mode', 'totara_tui');
        self::assertSame('pl', bundle::get_js_suffix_for_url());
    }

    public function test_get_css_suffix_for_url() {
        self::assertSame('p', bundle::get_css_suffix_for_url());
        set_config('development_mode', '1', 'totara_tui');
        self::assertSame('d', bundle::get_css_suffix_for_url());
        \core_useragent::instance(true, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; KTXN)');
        self::assertSame('dl', bundle::get_css_suffix_for_url());
        unset_config('development_mode', 'totara_tui');
        self::assertSame('pl', bundle::get_css_suffix_for_url());
    }

    public function test_get_js_rev() {
        global $CFG;
        self::assertSame(1, bundle::get_js_rev());
        $CFG->jsrev = 5;
        self::assertSame(5, bundle::get_js_rev());
        set_config('cache_js', '0', 'totara_tui');
        self::assertSame(-1, bundle::get_js_rev());
    }

    public function test_get_css_rev() {
        self::assertSame(theme_get_revision(), bundle::get_css_rev());
        set_config('cache_scss', '0', 'totara_tui');
        self::assertSame(-1, bundle::get_css_rev());
    }

    public function test_get_bundle_css_json_variables_file() {
        self::assertNotEmpty(bundle::get_bundle_css_json_variables_file('tui'));
        self::assertEmpty(bundle::get_bundle_css_json_variables_file('totara_tui'));
        self::assertNotEmpty(bundle::get_bundle_css_json_variables_file('totara_webapi'));
        self::assertNotEmpty(bundle::get_bundle_css_json_variables_file('theme_ventura'));
        self::assertEmpty(bundle::get_bundle_css_json_variables_file('theme_legacy'));
        self::assertEmpty(bundle::get_bundle_css_json_variables_file('theme_msteams'));
        self::assertNotEmpty(bundle::get_bundle_css_json_variables_file('samples'));
    }

    public function test_any_have_resources() {
        self::assertFalse(bundle::any_have_resources([]));
        self::assertTrue(bundle::any_have_resources(['tui']));
        self::assertTrue(bundle::any_have_resources(['tui', 'samples']));
        self::assertTrue(bundle::any_have_resources(['tui', ['samples']]));
    }
}