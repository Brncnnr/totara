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
 * @package core_container
 */
defined('MOODLE_INTERNAL') || die();

use core_container\factory;
use core_container\hook_builder;
use totara_core\hook\manager;
use totara_core\hook\base;

class core_container_hook_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function tearDown(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/container/tests/fixtures/container_redirect_watcher.php");

        container_redirect_watcher::reset_counter();
    }

    /**
     * A test to make sure that all the hooks are provided a proper information.
     * @return void
     */
    public function test_redirect_hook(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/container/tests/fixtures/container_redirect_watcher.php");
        require_once("{$CFG->dirroot}/container/tests/fixtures/core_container_mock_container.php");

        // Set up mock container type, and all the hooks mapping.
        factory::phpunit_add_mock_container_class(
            core_container_mock_container::class,
            core_container_mock_container::class
        );

        $hook_classes = array_merge(
            hook_builder::get_redirect_hooks_from_core_subsystems(),
            hook_builder::get_redirect_hooks_from_plugins()
        );

        $watchers = [];
        foreach ($hook_classes as $hook_class) {
            $watchers[] = [
                'hookname' => $hook_class,
                'callback' => [container_redirect_watcher::class, 'redirect_me']
            ];
        }

        manager::phpunit_replace_watchers($watchers);

        // Then start creating an instance so that we can actually test the redirect watcher.
        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        // Change the container type to our mock so that we can start the test.
        $course_record->containertype = core_container_mock_container::class;
        $DB->update_record('course', $course_record);

        foreach ($hook_classes as $hook_class) {
            /** @var base $hook */
            $hook = new $hook_class($course_record->id);
            $hook->execute();
        }

        // The number of redirection should be equal with the number of total redirect hooks.
        $total_redirection = container_redirect_watcher::get_total_redirect();
        $this->assertCount($total_redirection, $hook_classes);
    }
}
