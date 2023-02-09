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

use totara_tui\controllers\settings;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/adminlib.php');

class totara_tui_local_controllers_settings_testcase extends advanced_testcase {

    public function test_happy_path_without_tenant() {
        $this->setAdminUser();
        admin_get_root(true); // Fix random errors depending on test order.

        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Unsupported redirect detected, script execution terminated');
        (new settings('ventura'))->process();
    }

    public function test_happy_path_with_tenant() {
        $this->setAdminUser();
        admin_get_root(true); // Fix random errors depending on test order.

        set_config('tenantsenabled', true);

        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Unsupported redirect detected, script execution terminated');
        (new settings('ventura'))->process();
    }

    public function test_login_is_required_arg() {
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Unsupported redirect detected, script execution terminated');
        (new settings('ventura'))->process();
    }

}