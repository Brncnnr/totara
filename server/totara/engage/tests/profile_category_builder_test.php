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
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use core_user\output\myprofile\tree;
use totara_core\advanced_feature;

class totara_engage_profile_category_builder_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_build_admin_tree_when_engage_is_on(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/engage/lib.php");

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $admin_tree = new tree();
        advanced_feature::enable('engage_resources');

        $this->assertEmpty($admin_tree->categories);
        $this->assertEmpty($admin_tree->nodes);

        totara_engage_myprofile_navigation($admin_tree, $user_one);

        $categories = $admin_tree->categories;
        $nodes = $admin_tree->nodes;

        $this->assertCount(1, $nodes);
        $this->assertCount(1, $categories);

        $engage_category = reset($categories);
        $library_node = reset($nodes);

        $this->assertEquals('engage', $engage_category->name);
        $this->assertEquals('user_library', $library_node->name);
    }

    /**
     * @return void
     */
    public function test_build_admin_tree_when_engage_is_off(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/engage/lib.php");

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $admin_tree = new tree();
        advanced_feature::disable('engage_resources');

        $this->assertEmpty($admin_tree->categories);
        $this->assertEmpty($admin_tree->nodes);

        totara_engage_myprofile_navigation($admin_tree, $user_one);

        $this->assertEmpty($admin_tree->categories);
        $this->assertEmpty($admin_tree->nodes);
    }
}