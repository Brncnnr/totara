<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use core_user\access_controller;
use core_user\profile\display_setting;
use mod_perform\entity\activity\participant_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\state\participant_instance\not_started;
use mod_perform\testing\generator as perform_generator;
use mod_perform\watcher\core_user as mod_perform_core_user_watcher;
use totara_core\hook\manager as hook_manager;
use core_user\hook\allow_view_profile_field;

class core_user_watcher_testcase extends testcase {

    public function test_can_view_profile_field_cache(): void {
        global $DB;

        // Reset the hook watchers so that we can just test this single watcher.
        hook_manager::phpunit_replace_watchers([
            [
                'hookname' => allow_view_profile_field::class,
                'callback' => [mod_perform_core_user_watcher::class, 'allow_view_profile_field']
            ],
        ]);
        access_controller::clear_instance_cache();

        self::setAdminUser();

        // Create an activity, so we can use the access controller in the activity's (course) context.
        // Make two users common participants for one subject instance.
        $generator = perform_generator::instance();
        $subject_user = self::getDataGenerator()->create_user();
        $main_user = self::getDataGenerator()->create_user();
        $other_user = self::getDataGenerator()->create_user();
        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => false,
            'subject_user_id' => $subject_user->id,
            'other_participant_id' => $other_user->id,
            'include_questions' => false,
        ]);
        $other_participant_instance = new participant_instance();
        $other_participant_instance->core_relationship_id = 0; // stubbed
        $other_participant_instance->participant_source = participant_source::INTERNAL;
        $other_participant_instance->participant_id = $main_user->id;
        $other_participant_instance->subject_instance_id = $subject_instance->id;
        $other_participant_instance->progress = not_started::get_code();
        $other_participant_instance->save();

        $course = get_course($subject_instance->activity()->course);
        self::setUser($main_user);

        mod_perform_core_user_watcher::clear_resolution_cache();

        // Make sure we have three fields configured as display fields.
        display_setting::save_display_fields(['email', 'username', 'department']);

        $hook = new allow_view_profile_field('email', $other_user->id, $main_user->id, $course);
        $query_count_before = $DB->perf_get_reads();
        mod_perform_core_user_watcher::allow_view_profile_field($hook);
        self::assertTrue($hook->has_permission());
        $queries_without_cache = $DB->perf_get_reads() - $query_count_before;

        // Calling it again for a different relevant field should use the cached result.
        // Just make sure that using the cache will result in fewer queries. Being precise here could make the test brittle.
        $hook = new allow_view_profile_field('username', $other_user->id, $main_user->id, $course);
        $query_count_before = $DB->perf_get_reads();
        mod_perform_core_user_watcher::allow_view_profile_field($hook);
        self::assertTrue($hook->has_permission());
        $queries_with_cache = $DB->perf_get_reads() - $query_count_before;

        self::assertLessThan($queries_without_cache, $queries_with_cache);

        // Check clearing the cache works.
        mod_perform_core_user_watcher::clear_resolution_cache();
        $hook = new allow_view_profile_field('department', $other_user->id, $main_user->id, $course);
        $query_count_before = $DB->perf_get_reads();
        mod_perform_core_user_watcher::allow_view_profile_field($hook);
        self::assertTrue($hook->has_permission());
        $queries_after_clearing_cache = $DB->perf_get_reads() - $query_count_before;

        self::assertGreaterThan($queries_with_cache, $queries_after_clearing_cache);

        mod_perform_core_user_watcher::clear_resolution_cache();
    }
}