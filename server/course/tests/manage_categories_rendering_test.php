<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_course
 */
use core_phpunit\testcase;

class core_course_manage_categories_rendering_testcase extends testcase {
    /**
     * @return void
     */
    public function test_rendering_non_selected_category_should_render_caret_right(): void {
        global $PAGE, $OUTPUT;

        $generator = self::getDataGenerator();
        $category_one = $generator->create_category(["name" => "Category one"]);
        $category_two = $generator->create_category(["name" => "Category two"]);

        $category_one_child = $generator->create_category(["parent" => $category_one->id]);

        /** @var core_course_management_renderer $renderer */
        $renderer = $PAGE->get_renderer("core_course", "management");

        // The html content that is rendered when the category two (without sub category) was selected.
        $collapsed_html = $renderer->category_listing($category_two);
        self::assertNotEmpty($collapsed_html);

        // Expect that the icon collapsed is being used
        self::assertStringNotContainsString("tfont-var-caret-down-fill", $collapsed_html);
        self::assertStringContainsString("tfont-var-caret-right-fill", $collapsed_html);

        // Now check the expand html, which we are rendering the selected sub category.
        // Check that the icon should not be existing from the content.
        $expanded_html = $renderer->category_listing($category_one_child);
        self::assertStringNotContainsString("tfont-var-caret-right-fill", $expanded_html);
        self::assertStringContainsString("tfont-var-caret-down-fill", $expanded_html);
    }
}