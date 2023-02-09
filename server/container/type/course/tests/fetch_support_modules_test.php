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
 * @package container_course
 */
defined('MOODLE_INTERNAL') || die();

use container_course\course;

class container_course_fetch_support_modules_testcase extends advanced_testcase {
    /**
     * By default with the fresh instance several modules are not enabled at all.
     * Which it should not appear in the list of supported modules.
     *
     * Note that the reason why we do test like this was because some of the modules in table
     * "ttr_modules" are not supported by the course container itself.
     *
     *
     * @return void
     */
    public function test_fetching_modules_support_should_not_return_disabled_module(): void {
        global $DB;

        // Get all the disabled modules in the current database.
        $disabled_modules = $DB->get_records('modules', ['visible' => 0]);
        $supported_module_names = course::get_module_types_supported();

        foreach ($disabled_modules as $module) {
            // Disabled module will not appear in the list of supported module names.
            self::assertArrayNotHasKey($module->name, $supported_module_names);
            continue;
        }

        // Now check for supported module should be enabled in the database.
        $module_names = array_keys($supported_module_names);
        foreach ($module_names as $module_name) {
            self::assertTrue(
                $DB->record_exists(
                    'modules',
                    [
                        'name' => $module_name,
                        'visible' => 1
                    ]
                )
            );
        }
    }

    /**
     * @return void
     */
    public function test_fetching_all_modules_should_include_disabled_modules(): void {
        global $DB;

        // Get all the disabled modules in the current database.
        $disabled_modules = $DB->get_records('modules', ['visible' => 0]);
        $all_modules = course::get_module_types_supported(false, true);

        foreach ($disabled_modules as $module) {
            self::assertArrayHasKey($module->name, $all_modules);
        }
    }
}