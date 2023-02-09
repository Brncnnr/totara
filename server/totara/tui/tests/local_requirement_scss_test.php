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

use \totara_tui\local\requirement\scss;
use \totara_tui\local\locator\bundle;

defined('MOODLE_INTERNAL') || die();

class totara_tui_requirement_local_scss_testcase extends advanced_testcase {

    public function test_basic_operation() {
        $requirement = new scss('totara_tui');
        self::assertSame('totara_tui', $requirement->get_component());
        self::assertSame('tui_bundle.scss', $requirement->get_name());
        self::assertSame(scss::TYPE_CSS, $requirement->get_type());

        $options = ['theme' => 'ventura'];

        $expected = new \stdClass;
        $expected->id = $requirement->get_component() . ':' . $requirement->get_name();
        $expected->type = $requirement->get_type();
        $expected->component = $requirement->get_component();
        $expected->name = $requirement->get_name();
        $expected->url = $requirement->get_url($options)->out(false);

        self::assertEquals($expected, $requirement->get_api_data($options));
    }

    public function test_get_url() {
        global $CFG, $SESSION, $USER;
        $requirement = new scss('totara_tui');
        $theme = 'ventura';
        $direction = get_string('thisdirection', 'langconfig');
        $urlparams = [
            'theme' => $theme,
            'rev' => bundle::get_css_rev(),
            'type' => 'totara_tui',
            'suffix' => bundle::get_css_suffix_for_url(),
            'direction' => $direction,
            'tenant' => 'notenant',
        ];
        $options = ['theme' => $theme];

        $CFG->slasharguments = false;

        $expected = (new \moodle_url('/totara/tui/styles.php', $urlparams))->out(false);
        $actual = $requirement->get_url($options)->out(false);
        self::assertSame($expected, $actual);

        $CFG->slasharguments = true;

        $url = new \moodle_url('/totara/tui/styles.php');
        $url->set_slashargument('/' . $theme . '/' . bundle::get_css_rev() . '/' . bundle::get_css_suffix_for_url() . '/' . $direction . '/totara_tui/notenant');
        $expected = $url->out(false);
        $actual = $requirement->get_url($options)->out(false);
        self::assertSame($expected, $actual);

        // Test multitentanancy CSS URLs

        // Not logged in, session theme
        $id = 5;
        $SESSION->themetenantid = $id;
        $url = new \moodle_url('/totara/tui/styles.php');
        $url->set_slashargument('/' . $theme . '/' . bundle::get_css_rev() . '/' . bundle::get_css_suffix_for_url() . '/' . $direction . '/totara_tui/tenant_' . $id);
        $expected = $url->out(false);
        $actual = $requirement->get_url($options)->out(false);
        self::assertSame($expected, $actual);

        // Guest user, with tenant id, session theme
        $USER->id = 1;
        $USER->tenantid = 6;
        $url = new \moodle_url('/totara/tui/styles.php');
        $url->set_slashargument('/' . $theme . '/' . bundle::get_css_rev() . '/' . bundle::get_css_suffix_for_url() . '/' . $direction . '/totara_tui/tenant_' . $SESSION->themetenantid);
        $expected = $url->out(false);
        $actual = $requirement->get_url($options)->out(false);
        self::assertSame($expected, $actual);

        // Authenticated user, with tenant, session theme
        $USER->id = 2;
        $USER->tenantid = 6;
        $url = new \moodle_url('/totara/tui/styles.php');
        $url->set_slashargument('/' . $theme . '/' . bundle::get_css_rev() . '/' . bundle::get_css_suffix_for_url() . '/' . $direction . '/totara_tui/tenant_' . $USER->tenantid);
        $expected = $url->out(false);
        $actual = $requirement->get_url($options)->out(false);
        self::assertSame($expected, $actual);

        // Authenticated user, with tenant, no session theme
        unset($SESSION->themetenantid);
        $url = new \moodle_url('/totara/tui/styles.php');
        $url->set_slashargument('/' . $theme . '/' . bundle::get_css_rev() . '/' . bundle::get_css_suffix_for_url() . '/' . $direction . '/totara_tui/tenant_' . $USER->tenantid);
        $expected = $url->out(false);
        $actual = $requirement->get_url($options)->out(false);
        self::assertSame($expected, $actual);

        // Authenticated user, no tenant, no session theme
        unset($USER->tenantid);
        $url = new \moodle_url('/totara/tui/styles.php');
        $url->set_slashargument('/' . $theme . '/' . bundle::get_css_rev() . '/' . bundle::get_css_suffix_for_url() . '/' . $direction . '/totara_tui/notenant');
        $expected = $url->out(false);
        $actual = $requirement->get_url($options)->out(false);
        self::assertSame($expected, $actual);

        // Authenticated user, no tenant, session theme
        $SESSION->themetenantid = $id;
        $url = new \moodle_url('/totara/tui/styles.php');
        $url->set_slashargument('/' . $theme . '/' . bundle::get_css_rev() . '/' . bundle::get_css_suffix_for_url() . '/' . $direction . '/totara_tui/notenant');
        $expected = $url->out(false);
        $actual = $requirement->get_url($options)->out(false);
        self::assertSame($expected, $actual);
    }

    public function test_get_url_without_theme() {
        self::expectExceptionMessage('Theme not specified');
        $requirement = new scss('totara_tui');
        $requirement->get_url();
    }

    public function test_required() {
        global $CFG;

        $requirement = new scss('tui');
        if (file_exists($CFG->srcroot . '/client/component/tui/build/tui_bundle.scss')) {
            self::assertTrue($requirement->has_resources_to_load());
        } else {
            self::assertFalse($requirement->has_resources_to_load());
        }

        $requirement = new scss('totara_tui');
        self::assertFalse($requirement->has_resources_to_load());

        $requirement = new scss('space_monkey');
        self::assertFalse($requirement->has_resources_to_load());
    }

}
