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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\entity\activity\subject_static_instance;
use totara_job\job_assignment;

/**
 * @group perform
 */
class mod_perform_subject_static_instance_repository_testcase extends advanced_testcase {

    public function test_user_can_view_when_target_user_has_manager_job_assignment(): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $main_user = self::getDataGenerator()->create_user();
        $manager_user = self::getDataGenerator()->create_user();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $main_user->id,
            'other_participant_id' => null,
            'include_questions' => false,
        ]);

        $manager_ja = job_assignment::create([
            'userid' => $manager_user->id,
            'fullname' => 'manager_user_ja',
            'shortname' => 'manager_user_ja',
            'idnumber' => 'manager_user_ja',
            'managerjaid' => null,
        ]);

        $main_user_ja = job_assignment::create([
            'userid' => $manager_user->id,
            'fullname' => 'main_user_ja',
            'shortname' => 'main_user_ja',
            'idnumber' => 'main_user_ja',
            'managerjaid' => $manager_ja->id,
        ]);

        $can_view = subject_static_instance::repository()::user_can_view_other_users_profile($main_user->id, $manager_user->id);

        self::assertFalse($can_view);

        $static_instance = new subject_static_instance();
        $static_instance->subject_instance_id = $subject_instance->id;
        $static_instance->job_assignment_id = $main_user_ja->id;
        $static_instance->manager_job_assignment_id = $manager_ja->id;
        $static_instance->save();

        $can_view = subject_static_instance::repository()::user_can_view_other_users_profile($main_user->id, $manager_user->id);

        self::assertTrue($can_view);
    }

    public function test_user_can_view_when_target_user_is_appraiser(): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $main_user = self::getDataGenerator()->create_user();
        $appraiser_user = self::getDataGenerator()->create_user();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $main_user->id,
            'other_participant_id' => null,
            'include_questions' => false,
        ]);

        $main_user_ja = job_assignment::create([
            'userid' => $main_user->id,
            'fullname' => 'main_user_ja',
            'shortname' => 'main_user_ja',
            'idnumber' => 'main_user_ja',
            'appraiserid' => null,
            'managerjaid' => null,
        ]);

        $can_view = subject_static_instance::repository()::user_can_view_other_users_profile($main_user->id, $appraiser_user->id);

        self::assertFalse($can_view);

        $static_instance = new subject_static_instance();
        $static_instance->subject_instance_id = $subject_instance->id;
        $static_instance->job_assignment_id = $main_user_ja->id;
        $static_instance->appraiser_id = $appraiser_user->id;
        $static_instance->save();

        $can_view = subject_static_instance::repository()::user_can_view_other_users_profile($main_user->id, $appraiser_user->id);

        self::assertTrue($can_view);
    }

}
