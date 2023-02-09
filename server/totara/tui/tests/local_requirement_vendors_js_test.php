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

use \totara_tui\local\requirement\vendors_js;
use \totara_tui\local\locator\bundle;

defined('MOODLE_INTERNAL') || die();

class totara_tui_local_requirement_vendors_js_testcase extends advanced_testcase {

    public function test_basic_operation() {
        $requirement = new vendors_js();
        self::assertSame('tui', $requirement->get_component());
        self::assertSame('vendors.js', $requirement->get_name());
        self::assertSame(vendors_js::TYPE_JS, $requirement->get_type());

        $expected = new \stdClass;
        $expected->id = $requirement->get_component() . ':' . $requirement->get_name();
        $expected->type = $requirement->get_type();
        $expected->component = $requirement->get_component();
        $expected->name = $requirement->get_name();
        $expected->url = $requirement->get_url()->out(false);

        self::assertEquals($expected, $requirement->get_api_data());
    }

    public function test_get_url() {
        global $CFG;
        $requirement = new vendors_js();
        $urlparams = [
            'rev' => bundle::get_js_rev(),
            'component' => 'vendors',
            'suffix' => bundle::get_js_suffix_for_url()
        ];

        $CFG->slasharguments = false;

        $expected = (new \moodle_url('/totara/tui/javascript.php', $urlparams))->out(false);
        $actual = $requirement->get_url()->out(false);
        self::assertSame($expected, $actual);

        $CFG->slasharguments = true;

        $url = new \moodle_url('/totara/tui/javascript.php');
        $url->set_slashargument('/' . bundle::get_js_rev() . '/' . bundle::get_js_suffix_for_url() . '/vendors');
        $expected = $url->out(false);
        $actual = $requirement->get_url()->out(false);
        self::assertSame($expected, $actual);
    }

    public function test_required() {
        $requirement = new vendors_js();
        $vendors_file = bundle::get_vendors_file();
        if ($vendors_file && file_exists($vendors_file)) {
            self::assertTrue($requirement->has_resources_to_load());
        } else {
            self::assertFalse($requirement->has_resources_to_load());
        }
    }

}
