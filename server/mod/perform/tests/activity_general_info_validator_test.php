<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\state\activity\draft;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_type;
use mod_perform\models\activity\helpers\general_info_validator;

/**
 * @group perform
 */
class mod_perform_activity_general_info_validator_testcase extends advanced_testcase {
    protected $perform_generator;

    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
        $this->perform_generator = \mod_perform\testing\generator::instance();
    }

    protected function tearDown(): void {
        $this->perform_generator = null;
    }

    public function test_validate(): void {
        $activity = $this->perform_generator->create_activity_in_container([]);
        $validator = new general_info_validator(
            $activity,
            $activity->name,
            $activity->description,
            $activity->type->id
        );

        $this->assertEmpty($validator->validate(), 'validation failed');
    }

    public function test_invalid_name(): void {
        $activity = $this->perform_generator->create_activity_in_container([]);
        $validator = new general_info_validator(
            $activity,
            "     ",
            $activity->description,
            $activity->type->id
        );

        $errors = $validator->validate();
        $this->assertEquals(1, $errors->count(), 'validation passed');
        $this->assertStringContainsString($errors->first(), 'Name is required');
    }

    public function test_long_name(): void {
        $max_length = activity::NAME_MAX_LENGTH;

        $activity = $this->perform_generator->create_activity_in_container([]);
        $validator = new general_info_validator(
            $activity,
            str_repeat("a", $max_length + 1),
            $activity->description,
            $activity->type->id
        );

        $errors = $validator->validate();
        $this->assertEquals(1, $errors->count(), 'validation passed');
        $this->assertStringContainsString($errors->first(), "Name cannot be more than $max_length characters");
    }

    public function test_invalid_type_id(): void {
        $activity = $this->perform_generator->create_activity_in_container([
            'activity_status' => draft::get_code()
        ]);
        $validator = new general_info_validator(
            $activity,
            $activity->name,
            $activity->description,
            99
        );

        $errors = $validator->validate();
        $this->assertEquals(1, $errors->count(), 'validation passed');
        $this->assertStringContainsString($errors->first(), 'Invalid activity type');
    }

    public function test_update_changes_in_active_state(): void {
        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_activity_status(draft::get_code())
            ->set_number_of_activities(1)
            ->set_number_of_sections_per_activity(1)
            ->set_number_of_elements_per_section(1)
            ->set_number_of_users_per_user_group_type(1)
            ->disable_user_assignments()
            ->disable_subject_instances();

        $activity = $this->perform_generator
            ->create_full_activities($configuration)
            ->first();

        $original_type = activity_type::load_by_id($activity->type->id);

        $new_type = null;
        switch ($original_type->name) {
            case 'appraisal':
                $new_type = activity_type::load_by_name('feedback');
                break;

            case 'feedback':
                $new_type = activity_type::load_by_name('check-in');
                break;

            default:
                $new_type = activity_type::load_by_name('appraisal');
        }

        // As long as the activity is in the draft state, it is possible to change
        // everthing.
        $validator = new general_info_validator(
            $activity,
            "new activity name",
            "new activity description",
            $new_type->id
        );
        $this->assertEmpty($validator->validate(), 'validation failed');

        // But cannot change the type after it is activated.
        $this->assertTrue($activity->activate()->is_active());

        $validator = new general_info_validator(
            $activity,
            $activity->name,
            $activity->description,
            $new_type->id
        );

        $errors = $validator->validate();
        $this->assertEquals(1, $errors->count(), 'validation passed');
        $this->assertStringContainsString(
            $errors->first(),
            "Cannot change type of activity {$activity->id} since it is no longer a draft"
        );

        // But the name and description can be changed.
        $validator = new general_info_validator(
            $activity,
            "new activity name",
            "new activity description",
            null
        );
        $this->assertEmpty($validator->validate(), 'validation failed');
    }
}