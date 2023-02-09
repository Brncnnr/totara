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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use core_container\container_category_helper;

class container_workspace_category_restore_component_testcase extends advanced_testcase {

    public function test_fetch_categories_without_workspace_category(): void {
        global $CFG;

        // Get the workspace category.
        $workspace_category_id = container_category_helper::get_default_category_id('container_workspace');

        self::setAdminUser();

        require_once("{$CFG->dirroot}/backup/util/ui/restore_ui_components.php");
        $category_search = new restore_category_search();

        $categories = $category_search->get_results();

        self::assertIsArray($categories);
        self::assertNotEmpty($categories);
        self::assertCount(1, $categories);

        $miscellanous_category = reset($categories);
        self::assertNotEquals($workspace_category_id, $miscellanous_category);
    }

}
