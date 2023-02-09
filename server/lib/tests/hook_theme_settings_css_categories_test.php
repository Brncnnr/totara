<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */

use core\hook\theme_settings_css_categories as theme_settings_css_categories_hook;

defined('MOODLE_INTERNAL') || die();

class core_hook_theme_settings_css_categories_testcase extends advanced_testcase {

    public function test_hook() {

        $this->setAdminUser();

        // Set up some categories.
        $test_categories = [
            'category_1' => '*',
            'category_2' => [
                'property_1' => [],
                'property_2' => ['transform' => false]
            ],
        ];

        // Create hook instance and confirm that all methods work as expected.
        $hook = new theme_settings_css_categories_hook($test_categories);
        $this->assertEqualsCanonicalizing($test_categories, $hook->get_categories());

        // Add new category.
        $hook->add_category('category_3', ['c3p1' => ['transform' => false], 'c3p2' => []]);

        $test_categories = [
            'category_1' => '*',
            'category_2' => [
                'property_1' => [],
                'property_2' => ['transform' => false]
            ],
            'category_3' => [
                'c3p1' => ['transform' => false],
                'c3p2' => []
            ],
        ];

        $this->assertEqualsCanonicalizing($test_categories, $hook->get_categories());
    }
}
