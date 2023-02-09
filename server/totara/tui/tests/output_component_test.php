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

use \totara_tui\output\component;

defined('MOODLE_INTERNAL') || die();

class totara_tui_output_component_testcase extends advanced_testcase {

    public function test_get_name() {
        $component = new component('test');
        self::assertSame('test', $component->get_name());
    }

    public function test_get_props() {
        $component = new component('test', ['foo' => 'bar']);
        self::assertSame(['foo' => 'bar'], $component->get_props());
    }

    public function test_get_props_encoded() {
        $component = new component('test', ['foo' => 'bar']);
        self::assertSame(json_encode(['foo' => 'bar']), $component->get_props_encoded());
    }

    public function test_get_props_encoded_no_props() {
        $this->expectExceptionMessage('Encoded props requested, but there are no props.');
        $component = new component('test');
        $component->get_props_encoded();
    }

    public function test_has_props() {
        $component = new component('test', ['foo' => 'bar']);
        self::assertTrue($component->has_props());

        $component = new component('test', []);
        self::assertTrue($component->has_props());
    }

    public function test_has_props_no_props() {
        $component = new component('test');
        self::assertFalse($component->has_props());
    }

    public function test_register() {
        $component = new component('test', ['foo' => 'bar']);
        $page = new moodle_page();

        self::assertSame($page, $component->register($page));

        $framework = \totara_tui\output\framework::get($page);
        $property = new ReflectionProperty($framework, 'components');
        $property->setAccessible(true);
        $components = $property->getValue($framework);

        self::assertSame($components, ['tui', 'test']);
    }

    public function test_register_component() {
        $page = new moodle_page();
        self::assertSame($page, component::register_component('test', $page));

        $framework = \totara_tui\output\framework::get($page);
        $property = new ReflectionProperty($framework, 'components');
        $property->setAccessible(true);
        $components = $property->getValue($framework);

        self::assertSame($components, ['tui', 'test']);
    }

    public function test_register_component_too_late() {
        $page = new moodle_page();
        $property = new ReflectionProperty($page, '_state');
        $property->setAccessible(true);
        $property->setValue($page, moodle_page::STATE_IN_BODY);

        self::expectExceptionMessage('Unable to register component as the header has already been printed.');
        component::register_component('test', $page);
    }
}
