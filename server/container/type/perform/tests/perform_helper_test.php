<?php
/**
 * This file is part of Totara Core
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

use container_perform\perform as perform_container;
use container_perform\perform_helper;

/**
 * @group core_container
 */
class container_perform_perform_helper_testcase extends advanced_testcase {

    public function test_delete_all(): void {
        global $DB;

        // Setting up some data
        //   Not related category and course
        //   1 system activity (category and course)
        //   2 activities in tenant1 (category and course)
        //   1 activity in tenant2 (category and course)

        $default_counts = [
            'categories' => $DB->count_records('course_categories'),
            'courses' => $DB->count_records('course'),
        ];

        $to_create = [
            '' => [
                'activities' => 1,
            ],
            'random' => [
                'courses' => 1,
            ],
            'tenant1' => [
                'activities' => 2,
            ],
            'tenant2' => [
                'activities' => 1,
            ],
        ];

        $categories = [];
        $courses = [];
        $activity_counts = [
            'categories' => 0,
            'courses' => 0,
        ];

        foreach ($to_create as $key => $data) {
            $parent_cat = null;
            if (!empty($key)) {
                $categories[$key] = self::getDataGenerator()->create_category(['name' => $key]);
                $parent_cat = $categories[$key]->id;
            }

            $num_courses = $data['courses'] ?? 0;
            for ($i = 1; $i <= $num_courses; $i++) {
                $courses["{$key}_{$i}"] = self::getDataGenerator()->create_course([
                    'category' => $parent_cat,
                    'shortname' => "course_{$key}_{$i}",
                    'fullname' => "course_{$key}_{$i}",
                ]);
            }

            $num_activities = $data['activities'] ?? 0;
            if ($num_activities) {
                $categories["activity_{$key}"] = self::getDataGenerator()->create_category(
                    ['name' => perform_container::DEFAULT_CATEGORY_NAME, 'parent' => $parent_cat]
                );

                for ($i = 1; $i <= $num_activities; $i++) {
                    $courses["{$key}_{$i}"] = self::getDataGenerator()->create_course([
                        'category' => $categories["activity_{$key}"]->id,
                        'shortname' => "course_{$key}_{$i}",
                        'fullname' => "course_{$key}_{$i}",
                    ]);
                }
                $activity_counts['categories'] += 1;
                $activity_counts['courses'] += $num_activities;
            }
        }

        // Verify we have the expected data
        $this->assertEquals($default_counts['categories'] + count($categories), $DB->count_records('course_categories'));
        $this->assertEquals($default_counts['courses'] + count($courses), $DB->count_records('course'));

        // Now for the test
        $this->setAdminUser();
        perform_helper::delete_all();
        // No categories should've been deleted
        $this->assertEquals($default_counts['categories'] + count($categories),
            $DB->count_records('course_categories')
        );
        $this->assertEquals($default_counts['courses'] + count($courses) - $activity_counts['courses'],
            $DB->count_records('course')
        );
    }

    public function test_delete_all_permission(): void {
        // No user set ...
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('/User capability to uninstall perform containers/');

        perform_helper::delete_all();
    }

}
