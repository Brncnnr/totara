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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package hierarchy_goal
 */

use core\orm\collection;
use hierarchy_goal\userdata\perform_goal_status_other;
use hierarchy_goal\userdata\perform_goal_status_self;
use mod_perform\constants;
use hierarchy_goal\entity\perform_status as perform_status_entity;
use hierarchy_goal\models\company_goal_perform_status as company_goal_perform_status_model;
use hierarchy_goal\models\perform_status as perform_status_model;
use hierarchy_goal\models\personal_goal_perform_status as personal_goal_perform_status_model;
use totara_userdata\userdata\target_user;

require_once __DIR__ . '/perform_linked_goals_base_testcase.php';

/**
 * @group totara_hierarchy
 * @group hierarchy_goal
 */
class hierarchy_goal_userdata_perform_goal_status_testcase extends perform_linked_goals_base_testcase {
    public function goal_type_data_provider() {
        return [
            [goal::SCOPE_PERSONAL, personal_goal_perform_status_model::class],
            [goal::SCOPE_COMPANY, company_goal_perform_status_model::class],
        ];
    }

    /**
     * @dataProvider goal_type_data_provider
     * @param int $goal_type
     * @param string|perform_status_model $perform_status_class
     */
    public function test_purge_self(
        int $goal_type,
        string $perform_status_class
    ): void {
        $data = $this->create_activity_data($goal_type, constants::RELATIONSHIP_SUBJECT);
        $scale_value = $data->scale->values->first();
        $subject_user = new target_user($data->subject_user);
        $manager_user = new target_user($data->manager_user);

        self::assertEquals(0, perform_status_entity::repository()->count());

        self::setUser($data->subject_user);
        $perform_status_class::create(
            $data->goal1_assignment->id,
            $scale_value->id,
            $data->subject_participant_instance1->id,
            $data->section_element->id
        );

        // You can't set a status for personal goals for which no scale was set
        if ($goal_type !== goal::SCOPE_PERSONAL) {
            $perform_status_class::create(
                $data->goal2_assignment->id,
                $scale_value->id,
                $data->subject_participant_instance1->id,
                $data->section_element->id
            );
        }

        $context = context_system::instance();

        /** @var collection $subject_status_rows */
        $subject_status_rows = $this->get_perform_status_rows($subject_user->id, $subject_user->id);
        /** @var collection $manager_status_rows */
        $manager_status_rows = $this->get_perform_status_rows($subject_user->id, $manager_user->id);
        self::assertNotCount(0, $subject_status_rows);
        self::assertCount(0, $manager_status_rows);

        self::assertEquals(
            $subject_status_rows->count(),
            perform_goal_status_self::execute_count($subject_user, $context)
        );
        self::assertEquals(
            0,
            perform_goal_status_self::execute_count($manager_user, $context)
        );

        // Now purge
        // First purge for manager user - should result in no changes
        perform_goal_status_self::execute_purge($manager_user, $context);
        /** @var collection $subject_status_rows2 */
        $subject_status_rows2 = $this->get_perform_status_rows($subject_user->id, $subject_user->id);
        /** @var collection $manager_status_rows2 */
        $manager_status_rows2 = $this->get_perform_status_rows($subject_user->id, $manager_user->id);
        self::assertSameSize($subject_status_rows, $subject_status_rows2);
        self::assertCount(0, $manager_status_rows2);

        // Purge for subject user
        perform_goal_status_self::execute_purge($subject_user, $context);
        $subject_status_rows2 = $this->get_perform_status_rows($subject_user->id, $subject_user->id);
        $manager_status_rows2 = $this->get_perform_status_rows($subject_user->id, $manager_user->id);
        self::assertCount(0, $subject_status_rows2);
        self::assertCount(0, $manager_status_rows2);
    }

    /**
     * @dataProvider goal_type_data_provider
     * @param int $goal_type
     * @param string|perform_status_model $perform_status_class
     */
    public function test_purge_other(
        int $goal_type,
        string $perform_status_class
    ): void {
        $data = $this->create_activity_data($goal_type, constants::RELATIONSHIP_MANAGER);
        $scale_value = $data->scale->values->first();
        $subject_user = new target_user($data->subject_user);
        $manager_user = new target_user($data->manager_user);

        self::assertEquals(0, perform_status_entity::repository()->count());

        self::setUser($data->manager_user);
        $perform_status_class::create(
            $data->goal1_assignment->id,
            $scale_value->id,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );

        // You can't set a status for personal goals for which no scale was set
        if ($goal_type !== goal::SCOPE_PERSONAL) {
            $perform_status_class::create(
                $data->goal2_assignment->id,
                $scale_value->id,
                $data->manager_participant_instance1->id,
                $data->section_element->id
            );
        }

        $context = context_system::instance();

        /** @var collection $subject_status_rows */
        $subject_status_rows = $this->get_perform_status_rows($subject_user->id, $subject_user->id);
        /** @var collection $manager_status_rows */
        $manager_status_rows = $this->get_perform_status_rows($subject_user->id, $manager_user->id);
        self::assertCount(0, $subject_status_rows);
        self::assertNotCount(0, $manager_status_rows);

        self::assertEquals(
            0,
            perform_goal_status_other::execute_count($subject_user, $context)
        );
        self::assertEquals(
            count($manager_status_rows),
            perform_goal_status_other::execute_count($manager_user, $context)
        );

        // Now purge
        // First purge for subject user - should result in no changes
        perform_goal_status_other::execute_purge($subject_user, $context);
        /** @var collection $subject_status_rows2 */
        $subject_status_rows2 = $this->get_perform_status_rows($subject_user->id, $subject_user->id);
        /** @var collection $manager_status_rows2 */
        $manager_status_rows2 = $this->get_perform_status_rows($subject_user->id, $manager_user->id);
        self::assertCount(0, $subject_status_rows2);
        self::assertEqualsCanonicalizing($manager_status_rows, $manager_status_rows2);

        // Purge for manager user
        // Rows are not deleted, just anonymize
        perform_goal_status_other::execute_purge($manager_user, $context);
        $subject_status_rows2 = $this->get_perform_status_rows($subject_user->id, $subject_user->id);
        $manager_status_rows2 = $this->get_perform_status_rows($subject_user->id, $manager_user->id);
        /** @var collection $anon_status_rows */
        $anon_status_rows = $this->get_perform_status_rows($subject_user->id, null);
        self::assertCount(0, $subject_status_rows2);
        self::assertCount(0, $manager_status_rows2);
        self::assertCount(count($manager_status_rows), $anon_status_rows);

        $expected = $manager_status_rows->map(function ($row) {
            $row->status_changer_user_id = null;
            return $row;
        });
        self::assertEqualsCanonicalizing($expected->to_array(), $anon_status_rows->to_array());
    }

    /**
     * @dataProvider goal_type_data_provider
     * @param int $goal_type
     * @param string|perform_status_model $perform_status_class
     */
    public function test_export_self(
        int $goal_type,
        string $perform_status_class
    ): void {
        $data = $this->create_activity_data($goal_type, constants::RELATIONSHIP_SUBJECT);
        $scale_value = $data->scale->values->first();
        $subject_user = new target_user($data->subject_user);
        $manager_user = new target_user($data->manager_user);

        self::assertEquals(0, perform_status_entity::repository()->count());

        self::setUser($data->subject_user);
        $perform_status_class::create(
            $data->goal1_assignment->id,
            $scale_value->id,
            $data->subject_participant_instance1->id,
            $data->section_element->id
        );

        // You can't set a status for personal goals for which no scale was set
        if ($goal_type !== goal::SCOPE_PERSONAL) {
            $perform_status_class::create(
                $data->goal2_assignment->id,
                $scale_value->id,
                $data->subject_participant_instance1->id,
                $data->section_element->id
            );
        }

        $context = context_system::instance();

        $exported = perform_goal_status_self::execute_export($subject_user, $context)
            ->data['perform_goal_status_self'];

        $this->assert_export_equals($exported, $subject_user->id, $subject_user->id);
    }

    /**
     * @dataProvider goal_type_data_provider
     * @param int $goal_type
     * @param string|perform_status_model $perform_status_class
     */
    public function test_export_other(
        int $goal_type,
        string $perform_status_class
    ): void {
        $data = $this->create_activity_data($goal_type, constants::RELATIONSHIP_MANAGER);
        $scale_value = $data->scale->values->first();
        $subject_user = new target_user($data->subject_user);
        $manager_user = new target_user($data->manager_user);

        self::assertEquals(0, perform_status_entity::repository()->count());

        self::setUser($data->manager_user);
        $perform_status_class::create(
            $data->goal1_assignment->id,
            $scale_value->id,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );

        // You can't set a status for personal goals for which no scale was set
        if ($goal_type !== goal::SCOPE_PERSONAL) {
            $perform_status_class::create(
                $data->goal2_assignment->id,
                $scale_value->id,
                $data->manager_participant_instance1->id,
                $data->section_element->id
            );
        }

        $context = context_system::instance();

        $exported = perform_goal_status_other::execute_export($manager_user, $context)
            ->data['perform_goal_status_other'];

        $this->assert_export_equals($exported, $subject_user->id, $manager_user->id);
    }

    private function assert_export_equals(
        array $exported,
        int $user_id,
        int $status_changer_user_id
    ): void {
        $perform_status_rows = $this->get_perform_status_rows($user_id, $status_changer_user_id);

        self::assertSameSize($perform_status_rows, $exported);
        $perform_status_rows = $perform_status_rows->map(function ($row) {
            if ($row->goal_id !== null) {
                /** @var company_goal_perform_status_model $perform_status_model */
                $perform_status_model = company_goal_perform_status_model::load_by_entity($row);
                $company_goal_name = core_text::entities_to_utf8(format_string($perform_status_model->company_goal->shortname));
                $personal_goal_name = null;
            } else {
                /** @var personal_goal_perform_status_model $perform_status_model */
                $perform_status_model = personal_goal_perform_status_model::load_by_entity($row);
                $company_goal_name = null;
                $personal_goal_name = core_text::entities_to_utf8(format_string($perform_status_model->personal_goal->name));
            }

            return (object)[
                'id' => (int)$row->id,
                'user_id' => (int)$row->user_id,
                'activity_name' => core_text::entities_to_utf8(format_string($perform_status_model->activity->name)),
                'company_goal_name' => $company_goal_name,
                'personal_goal_name' => $personal_goal_name,
                'scale_value_name' => $row->scale_value_id
                    ? core_text::entities_to_utf8(format_string($perform_status_model->scale_value->name))
                    : null,
                'status_changer_id' => (int)$row->status_changer_user_id,
                'status_changer_relationship' => $perform_status_model->status_changer_role,
                'created_at' => (int)$row->created_at,
            ];
        });

        self::assertEqualsCanonicalizing($perform_status_rows->to_array(), $exported);
    }

    /**
     * @param int $user_id
     * @param int|null $status_changer_user_id
     * @return collection
     */
    private function get_perform_status_rows(int $user_id, ?int $status_changer_user_id): collection {
        return perform_status_entity::repository()
            ->where('user_id', $user_id)
            ->where('status_changer_user_id', $status_changer_user_id)
            ->get();
    }
}