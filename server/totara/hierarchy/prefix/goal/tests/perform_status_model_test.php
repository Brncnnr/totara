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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

use hierarchy_goal\entity\company_goal_assignment;
use hierarchy_goal\entity\perform_status;
use hierarchy_goal\entity\personal_goal;
use hierarchy_goal\models\company_goal_perform_status;
use hierarchy_goal\models\perform_status as perform_status_model;
use hierarchy_goal\models\personal_goal_perform_status;

require_once __DIR__ . '/perform_linked_goals_base_testcase.php';

/**
 * @group hierarchy_goal
 */
class hierarchy_goal_perform_status_model_testcase extends perform_linked_goals_base_testcase {

    public function goal_type_data_provider() {
        return [
            [goal::SCOPE_PERSONAL, personal_goal_perform_status::class],
            [goal::SCOPE_COMPANY, company_goal_perform_status::class],
        ];
    }

    /**
     * @dataProvider goal_type_data_provider
     * @param int $goal_type
     * @param string|perform_status_model $perform_status_class
     */
    public function test_create_with_invalid_assignment_id(
        int $goal_type,
        string $perform_status_class
    ): void {
        $data = $this->create_activity_data($goal_type);
        $scale_value = $data->scale->values->first();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches(
            '/The specified goal assignment with ID -1 of type ' . $goal_type . ' has not been linked to the performance activity/'
        );

        self::setUser($data->manager_user);
        $perform_status_class::create(
            - 1,
            $scale_value->id,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );
    }

    /**
     * @dataProvider goal_type_data_provider
     * @param int $goal_type
     * @param string|perform_status_model $perform_status_class
     */
    public function test_create_with_existing_status(
        int $goal_type,
        string $perform_status_class
    ): void {
        $data = $this->create_activity_data($goal_type);
        $scale_value = $data->scale->values->first();
        $subject_instance_id = $data->manager_participant_instance1->subject_instance_id;

        self::setUser($data->manager_user);
        $perform_status_class::create(
            $data->goal1_assignment->id,
            $scale_value->id,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "A status has already been saved for subject instance {$subject_instance_id},"
            . " goal ID {$data->goal1->id} and goal type {$goal_type}"
        );

        $perform_status_class::create(
            $data->goal1_assignment->id,
            $scale_value->id,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );
    }

    /**
     * @dataProvider goal_type_data_provider
     * @param int $goal_type
     * @param string|perform_status_model $perform_status_class
     */
    public function test_create_with_invalid_scale_value(
        int $goal_type,
        string $perform_status_class
    ): void {
        $data = $this->create_activity_data($goal_type);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches(
            "/The specified scale value with ID -1 is not valid for the goal with ID {$data->goal1->id}/"
        );

        self::setUser($data->manager_user);
        $perform_status_class::create(
            $data->goal1_assignment->id,
            - 1,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );
    }

    /**
     * @dataProvider goal_type_data_provider
     * @param int $goal_type
     * @param string|perform_status_model $perform_status_class
     */
    public function test_create_successful(
        int $goal_type,
        string $perform_status_class
    ):void {
        $data = $this->create_activity_data($goal_type);
        $scale_value = $data->scale->values->first();
        $now = time();

        self::assertEquals(0, perform_status::repository()->count());

        self::setUser($data->manager_user);
        $perform_status_class::create(
            $data->goal1_assignment->id,
            $scale_value->id,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );
        self::assertEquals(1, perform_status::repository()->count());
        $actual = perform_status::repository()->one(true)->to_array();
        self::assertGreaterThanOrEqual($now, $actual['created_at']);
        unset($actual['id'], $actual['created_at']);

        $expected_company_goal_id = $goal_type === goal::SCOPE_COMPANY ? $data->goal1->id : null;
        $expected_personal_goal_id = $goal_type === goal::SCOPE_PERSONAL ? $data->goal1->id : null;

        self::assertEqualsCanonicalizing([
            'user_id' => $data->subject_user->id,
            'goal_id' => $expected_company_goal_id,
            'goal_personal_id' => $expected_personal_goal_id,
            'scale_value_id' => $scale_value->id,
            'activity_id' => $data->activity->id,
            'subject_instance_id' => $data->subject_instance1->id,
            'status_changer_user_id' => $data->manager_participant_instance1->participant_id,
            'status_changer_relationship_id' => $data->manager_participant_instance1->core_relationship_id,
        ], $actual);
    }

    /**
     * @dataProvider goal_type_data_provider
     * @param int $goal_type
     * @param string|perform_status_model $perform_status_class
     */
    public function test_create_for_deleted_goal(
        int $goal_type,
        string $perform_status_class
    ): void {
        $data = $this->create_activity_data($goal_type);
        $scale_value = $data->scale->values->first();

        if (goal::SCOPE_PERSONAL === $goal_type) {
            personal_goal::repository()
                ->where('id', $data->goal1->id)
                ->where('userid', $data->subject_user->id)
                ->update(['deleted' => 1]);
        } else {
            company_goal_assignment::repository()
                ->where('goalid', $data->goal1->id)
                ->where('userid', $data->subject_user->id)
                ->update(['deleted' => 1]);
        }

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(
            "Could not update goal status for assignment {$data->goal1_assignment->id}, type {$goal_type}"
        );

        self::setUser($data->manager_user);
        $perform_status_class::create(
            $data->goal1_assignment->id,
            $scale_value->id,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );
    }

    /**
     * @dataProvider goal_type_data_provider
     * @param int $goal_type
     * @param string|perform_status_model $perform_status_class
     */
    public function test_can_update_permissions(
        int $goal_type,
        string $perform_status_class
    ): void {
        $data = $this->create_activity_data($goal_type);
        $scale_value = $data->scale->values->first();

        self::setUser($data->manager_user);
        $perform_status_class::create(
            $data->goal1_assignment->id,
            $scale_value->id,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );

        self::expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Goal status update)');
        self::setUser($data->subject_user);
        $perform_status_class::create(
            $data->goal1_assignment->id,
            $scale_value->id,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );
    }
}