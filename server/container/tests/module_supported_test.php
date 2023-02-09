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

use totara_core\hook\manager;
use core_container\hook\module_supported_in_container;
use core_container\local\module_supported;

class core_container_module_supported_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function tearDown(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/container/tests/fixtures/container_remove_module_watcher.php");

        container_remove_module_watcher::reset();
    }

    /**
     * @return void
     */
    public function test_remove_module_from_the_list(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/container/tests/fixtures/container_remove_module_watcher.php");
        require_once("{$CFG->dirroot}/container/tests/fixtures/core_container_mock_container.php");

        // Setup hook maps, then start running the fetching of modules
        manager::phpunit_replace_watchers([
            [
                'hookname' => module_supported_in_container::class,
                'callback' => [container_remove_module_watcher::class, 'remove_module']
            ]
        ]);

        $modules = $DB->get_records('modules', [], 'id, name');
        $module_names = array_map(
            function (stdClass $module): string {
                return $module->name;
            },
            $modules
        );

        $this->assertTrue(in_array('facetoface', $module_names));
        $this->assertTrue(in_array('forum', $module_names));

        // We are removing mod facetoface and mod forum out of the lists and we will make sure that the list
        // return from the loader/hook will not contain any of these two mods.
        container_remove_module_watcher::set_modules_to_be_removed(['facetoface', 'forum']);

        $loader = module_supported::instance();

        $loaded_modules = $loader->get_for_container(core_container_mock_container::class);
        $this->assertNotEmpty($loaded_modules);

        // Check that all the loaded modules actually exist in the list we fetched earlier
        foreach ($loaded_modules as $loaded_module_name => $not_used_string) {
            $this->assertTrue(in_array($loaded_module_name, $module_names));
        }

        // Then checking that if the mod facetoface and mod forum is appearing in the list or not.
        $this->assertFalse(in_array('facetoface', $loaded_modules));
        $this->assertFalse(in_array('forum', $loaded_modules));
    }
}