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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core_course
 * @category totara_notification
 */

use core_course\totara_notification\placeholder\activity as activity_placeholder_group;
use core_phpunit\testcase;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class core_course_totara_notification_course_activity_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        activity_placeholder_group::clear_instance_cache();
    }

    protected function tearDown(): void {
        parent::tearDown();
        activity_placeholder_group::clear_instance_cache();
    }

    public function test_course_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, activity_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            ['name', 'name_link', 'type'],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        $activities = [1 => [], 2 => []];
        $name_prefixes = [1 => 'Test activity ', 2 => 'Another '];

        foreach (['assign', 'data', 'page', 'forum'] as $mod_name) {
            $cm = self::getDataGenerator()->create_module(
                $mod_name,
                ['course' => $course1->id, 'name' => $name_prefixes[1] . $mod_name]
            );
            $activities[1][$mod_name] = get_coursemodule_from_id($mod_name, $cm->cmid);
        }
        foreach (['assign', 'page'] as $mod_name) {
            $cm = self::getDataGenerator()->create_module(
                $mod_name,
                ['course' => $course2->id, 'name' => $name_prefixes[2] . $mod_name]
            );
            $activities[2][$mod_name] = get_coursemodule_from_id($mod_name, $cm->cmid);
        }

        foreach ($activities as $course_idx => $modules) {
            foreach ($modules as $mod_name => $activity) {
                $placeholder_group = activity_placeholder_group::from_id($activity->id);
                $expected_name = $name_prefixes[$course_idx] . $mod_name;
                $expected_type = get_string('modulename', $mod_name);
                $expected_url = '<a href="https://www.example.com/moodle/mod/' . $mod_name .
                    '/view.php?id=' . $activity->id . '">' . $expected_name . '</a>';
                self::assertEquals($expected_name, $placeholder_group->do_get('name'));
                self::assertEquals($expected_url, $placeholder_group->do_get('name_link'));
                self::assertEquals($expected_type, $placeholder_group->do_get('type'));
            }
        }
    }

    public function test_course_placeholders_not_available(): void {
        $course = self::getDataGenerator()->create_course(['fullname' => 'Another test course']);
        $cm = self::getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $activity = get_coursemodule_from_id('assign', $cm->cmid);
        $placeholder_group = activity_placeholder_group::from_id($activity->id);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage("Invalid key 'whatever'");
        $placeholder_group->do_get('whatever');
    }

    public function test_course_placeholders_invalid_id(): void {
        $placeholder_group = activity_placeholder_group::from_id(123);
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('The course activity record is empty');
        $placeholder_group->do_get('name');
    }

    public function test_course_placeholder_instances_are_cached(): void {
        global $DB;

        self::setAdminUser();
        $course = self::getDataGenerator()->create_course();
        $activities = [];
        foreach (['assign', 'page'] as $mod_name) {
            $cm = self::getDataGenerator()->create_module($mod_name, ['course' => $course->id]);
            $activities[$mod_name] = get_coursemodule_from_id($mod_name, $cm->cmid);
        }

        $query_count = $DB->perf_get_reads();
        activity_placeholder_group::from_id($activities['assign']->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        activity_placeholder_group::from_id($activities['assign']->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        activity_placeholder_group::from_id($activities['page']->id);
        self::assertEquals($query_count + 4, $DB->perf_get_reads());

        activity_placeholder_group::from_id($activities['assign']->id);
        activity_placeholder_group::from_id($activities['page']->id);
        self::assertEquals($query_count + 4, $DB->perf_get_reads());
    }

}
