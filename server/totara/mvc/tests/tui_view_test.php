<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_mvc
 */

use totara_mvc\tui_view;

defined('MOODLE_INTERNAL') || die();


class totara_mvc_tui_view_testcase extends advanced_testcase {

    public function test_view() {
        $view = new tui_view('totara_mvc/pages/MyComponent', ['name' => 'James']);

        $output = $view->render();

        $expected_output = '<span data-tui-component="totara_mvc/pages/MyComponent" '.
            'data-tui-props="{&quot;name&quot;:&quot;James&quot;}"></span>';

        // The tui component renderer creates a span with data attributes only, the real loading of the component
        // happens on the client side and cannot be tested here.
        $this->assertStringContainsString($expected_output, $output);

        $view = tui_view::create('totara_mvc/pages/MyComponent', ['name' => 'James']);

        $output = $view->render();
        $this->assertStringContainsString($expected_output, $output);
    }

    public function test_component_cannot_be_empty() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('You have to provide a valid vue component name');
        new tui_view('', ['name' => 'James']);
    }

    public function test_component_cannot_be_empty_factory_method() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('You have to provide a valid vue component name');
        tui_view::create('', ['name' => 'James']);
    }

}
