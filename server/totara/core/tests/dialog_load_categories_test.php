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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_core
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

class totara_core_dialog_load_categories_testcase extends advanced_testcase {
    /**
     * Asserts that the course categories report builder dialog does not expose hidden categories incorrectly
     */
    public function test_courses_dialog_load_categories(): void {
        global $CFG;
        require_once($CFG->dirroot . '/totara/core/dialogs/dialog_content_category.class.php');

        $generator = $this->getDataGenerator();

        // Visible categories
        $generator->create_category(['name' => 'Cat Normal']);
        $parent_category = $generator->create_category(['name' => 'Cat Normal Parent']);
        $generator->create_category(['name' => 'Cat Normal Child', 'parent' => $parent_category->id]);

        // Hidden categories
        $generator->create_category(['name' => 'Cat Hidden', 'visible' => 0]);
        $generator->create_category(['name' => 'Cat Hidden Child', 'visible' => 0, 'parent' => $parent_category->id]);

        $user = $generator->create_user();
        $this->setUser($user);

        // Load the categories from the dialog, confirming the hidden category do not appear
        $dialog = new totara_dialog_content_category();

        $categories = $dialog->get_all_root_items();
        $category_names = $this->pluck_category_names($categories);
        self::assertEqualsCanonicalizing([
            'Cat Normal',
            'Cat Normal Parent',
            'Miscellaneous',
        ], $category_names);

        $categories = $dialog->get_subcategories_item($parent_category->id);
        $category_names = $this->pluck_category_names($categories);
        self::assertEqualsCanonicalizing([
            'Cat Normal Child',
        ], $category_names);

        // Assign the view hidden categories capability
        $role_id = $generator->create_role();
        role_change_permission($role_id, context_system::instance(), 'moodle/category:viewhiddencategories', CAP_ALLOW);
        $generator->role_assign($role_id, $user->id);

        // Load the categories from the dialog, confirming the hidden category do appear
        $categories = $dialog->get_all_root_items();
        $category_names = $this->pluck_category_names($categories);
        self::assertEqualsCanonicalizing([
            'Cat Normal',
            'Cat Normal Parent',
            'Cat Hidden',
            'Miscellaneous',
        ], $category_names);

        $categories = $dialog->get_subcategories_item($parent_category->id);
        $category_names = $this->pluck_category_names($categories);
        self::assertEqualsCanonicalizing([
            'Cat Normal Child',
            'Cat Hidden Child',
        ], $category_names);
    }

    /**
     * Helper method returning a list of category names
     *
     * @param array $categories
     * @return array
     */
    private function pluck_category_names(array $categories): array {
        return array_map(function ($category): string {
            return $category->name;
        }, $categories);
    }
}
