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
use totara_tui\local\mediation\helper;
use totara_tui\local\mediation\resolver;

defined('MOODLE_INTERNAL') || die();

class totara_tui_local_mediation_resolver_testcase extends advanced_testcase {

    private function get_mock_resolver_instance($rev = 1234) {
        $class = $this->getMockForAbstractClass(resolver::class, [\totara_tui\local\mediation\styles\mediator::class, $rev]);
        $class->expects($this->any())->method('calculate_etag')->willReturn('etag_test');
        $class->expects($this->any())->method('calculate_cachefile')->willReturn(new path('test_path'));
        return $class;
    }

    public function test_get_rev() {
        $instance = $this->get_mock_resolver_instance();
        self::assertSame('1234', $instance->get_rev());
    }

    public function test_should_use_dev_mode() {
        $instance = $this->get_mock_resolver_instance(time());
        self::assertFalse($instance->should_use_dev_mode());
        $instance = $this->get_mock_resolver_instance('-1');
        self::assertTrue($instance->should_use_dev_mode());
    }

}