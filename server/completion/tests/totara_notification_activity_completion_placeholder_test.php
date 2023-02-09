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
 * @package core_completion
 * @category totara_notification
 */

use core_completion\totara_notification\placeholder\activity_completion as completion_placeholder_group;
use core_phpunit\testcase;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class core_completion_totara_notification_activity_completion_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        completion_placeholder_group::clear_instance_cache();
    }

    protected function tearDown(): void {
        parent::tearDown();
        completion_placeholder_group::clear_instance_cache();
    }

    public function test_activity_completion_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, completion_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            ['completion_date'],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        $course = self::getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($user->id, $course->id);

        $activities = [];

        foreach (['assign', 'data', 'page', 'forum'] as $mod_name) {
            $cm = self::getDataGenerator()->create_module(
                $mod_name,
                ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]
            );
            $activities[$mod_name] = get_coursemodule_from_id($mod_name, $cm->cmid);
        }

        $times = [
            strtotime('-1 day'),
            time(),
        ];

        $completion = new completion_info($course);
        // For testing purposes simulating timecompleted set on one of the activities
        $activities['assign']->timecompleted = $times[0];
        foreach (['assign', 'page'] as $mod_name) {
            $completion->update_state($activities[$mod_name], COMPLETION_COMPLETE, $user->id);
        }

        foreach (['assign', 'page'] as $idx => $mod_name) {
            $placeholder_group = completion_placeholder_group::from_activity_id_and_user_id($activities[$mod_name]->id, $user->id);
            $expected_completion_date = userdate($times[$idx]);
            self::assertEquals($expected_completion_date, $placeholder_group->do_get('completion_date'));
        }

        foreach (['data', 'forum'] as $mod_name) {
            $placeholder_group = completion_placeholder_group::from_activity_id_and_user_id($activities[$mod_name]->id, $user->id);
            self::assertEmpty($placeholder_group->do_get('completion_date'));
        }
    }

    public function test_activity_completion_placeholders_not_available(): void {
        $course = self::getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($user->id, $course->id);
        $cm = self::getDataGenerator()->create_module(
            'assign',
            ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]
        );
        $activity = get_coursemodule_from_id('assign', $cm->cmid);
        $completion = new completion_info($course);
        $completion->update_state($activity, COMPLETION_COMPLETE, $user->id);

        $placeholder_group = completion_placeholder_group::from_activity_id_and_user_id($activity->id, $user->id);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage("Invalid key 'whatever'");
        $placeholder_group->do_get('whatever');
    }

    public function test_activity_completion_placeholders_completion_record_not_found(): void {
        $user = self::getDataGenerator()->create_user();
        $course = self::getDataGenerator()->create_course(['enablecompletion' => 1]);
        $cm = self::getDataGenerator()->create_module(
            'assign',
            ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]
        );
        $activity = get_coursemodule_from_id('assign', $cm->cmid);

        // Invalid activity
        $placeholder_group = completion_placeholder_group::from_activity_id_and_user_id(123, $user->id);
        self::assertEmpty($placeholder_group->do_get('completion_date'));

        // Invalid user
        $placeholder_group = completion_placeholder_group::from_activity_id_and_user_id($activity->id, 123);
        self::assertEmpty($placeholder_group->do_get('completion_date'));

        // No completion record
        $placeholder_group = completion_placeholder_group::from_activity_id_and_user_id($activity->id, $user->id);
        self::assertEmpty($placeholder_group->do_get('completion_date'));
    }

    public function test_activity_completion_placeholder_instances_are_cached(): void {
        global $DB;

        self::setAdminUser();
        $course = self::getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($user->id, $course->id);

        $activities = [];

        /** @var completion_info $completion */
        $completion = new completion_info($course);

        foreach (['assign', 'page'] as $mod_name) {
            $cm = self::getDataGenerator()->create_module(
                $mod_name,
                ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]
            );
            $activities[$mod_name] = get_coursemodule_from_id($mod_name, $cm->cmid);
            $completion->update_state($activities[$mod_name], COMPLETION_COMPLETE, $user->id);
        }

        $query_count = $DB->perf_get_reads();
        completion_placeholder_group::from_activity_id_and_user_id($activities['assign']->id, $user->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        completion_placeholder_group::from_activity_id_and_user_id($activities['assign']->id, $user->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        completion_placeholder_group::from_activity_id_and_user_id($activities['page']->id, $user->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        completion_placeholder_group::from_activity_id_and_user_id($activities['assign']->id, $user->id);
        completion_placeholder_group::from_activity_id_and_user_id($activities['page']->id, $user->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }

}
