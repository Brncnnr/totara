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

use core\collection;
use core\date_format;
use core\orm\query\builder;
use core\webapi\formatter\field\date_field_formatter;
use hierarchy_goal\entity\company_goal;
use hierarchy_goal\entity\company_goal_assignment as company_goal_assignment_entity;
use hierarchy_goal\entity\personal_goal as personal_goal_entity;
use hierarchy_goal\entity\scale_value;
use hierarchy_goal\models\company_goal_perform_status;
use hierarchy_goal\models\personal_goal_perform_status;
use hierarchy_goal\performelement_linked_review\company_goal_assignment;
use hierarchy_goal\performelement_linked_review\goal_assignment_content_type;
use hierarchy_goal\performelement_linked_review\personal_goal_assignment;
use mod_perform\constants;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section_relationship;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\subject_instance;
use performelement_linked_review\content_type_factory;
use performelement_linked_review\models\linked_review_content as linked_review_content_model;
use performelement_linked_review\testing\generator as linked_review_generator;
use totara_core\advanced_feature;
use totara_core\relationship\relationship;

require_once __DIR__ . '/perform_linked_goals_base_testcase.php';

/**
 * @group hierarchy_goal
 * @group totara_hierarchy
 * @group totara_goal
 */
class hierarchy_goal_perform_linked_goals_content_testcase extends perform_linked_goals_base_testcase {

    /**
     * @return string[][]
     */
    public function goal_content_type_data_provider(): array {
        return [
            [personal_goal_assignment::class],
            [company_goal_assignment::class],
        ];
    }

    /**
     * @dataProvider goal_content_type_data_provider
     * @param string|goal_assignment_content_type $goal_content_type_class
     */
    public function test_get_display_settings(string $goal_content_type_class): void {
        $display_settings = $goal_content_type_class::get_display_settings([]);
        $subject_relationship = relationship::load_by_idnumber('subject');

        self::assertEquals(
            ['Ability to change goal status during activity' => 'No'],
            $display_settings
        );

        $display_settings = $goal_content_type_class::get_display_settings([
            'enable_status_change' => false
        ]);

        self::assertEquals(
            ['Ability to change goal status during activity' => 'No'],
            $display_settings
        );

        $display_settings = $goal_content_type_class::get_display_settings([
            'enable_status_change' => true
        ]);

        self::assertEquals(
            ['Ability to change goal status during activity' => 'Yes'],
            $display_settings
        );

        $display_settings = $goal_content_type_class::get_display_settings([
            'enable_status_change' => true,
            'status_change_relationship' => $subject_relationship->id,
        ]);

        self::assertEquals(
            [
                'Ability to change goal status during activity' => 'Yes',
                'Change of goal status participant' => $subject_relationship->get_name(),
            ],
            $display_settings
        );
    }

    public function test_is_enabled(): void {
        self::assertTrue(personal_goal_assignment::is_enabled());
        self::assertTrue(company_goal_assignment::is_enabled());

        $enabled_content_types = content_type_factory::get_all_enabled();
        self::assertContainsEquals(personal_goal_assignment::class, $enabled_content_types->to_array());
        self::assertContainsEquals(company_goal_assignment::class, $enabled_content_types->to_array());

        advanced_feature::disable('goals');

        self::assertFalse(personal_goal_assignment::is_enabled());
        self::assertFalse(company_goal_assignment::is_enabled());

        $enabled_content_types = content_type_factory::get_all_enabled();
        self::assertNotContainsEquals(personal_goal_assignment::class, $enabled_content_types->to_array());
        self::assertNotContainsEquals(company_goal_assignment::class, $enabled_content_types->to_array());
    }

    /**
     * @return string[][]
     */
    public function goal_content_type_names_data_provider(): array {
        return [
            'Personal goal' => ['personal_goal', 'Personal goal'],
            'Company goal' => ['company_goal', 'Company goal'],
        ];
    }

    /**
     * @dataProvider goal_content_type_names_data_provider
     * @param string $content_type_identifier
     * @param string $content_type_display_name
     */
    public function test_saving_element(string $content_type_identifier, string $content_type_display_name): void {
        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $manager_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER);

        $element1_input_data = [
            'content_type' => $content_type_identifier,
            'content_type_settings' => [
                'enable_status_change' => false,
                'status_change_relationship' => null,
            ],
            'selection_relationships' => [$subject_relationship->id],
        ];
        $element1 = linked_review_generator::instance()->create_linked_review_element($element1_input_data);
        $element1_output_data = json_decode($element1->data, true);
        unset($element1_output_data['components'], $element1_output_data['compatible_child_element_plugins']);
        self::assertEquals([
            'content_type' => $content_type_identifier,
            'content_type_settings' => [
                'enable_status_change' => false,
                'status_change_relationship' => null,
            ],
            'selection_relationships' => [$subject_relationship->id],
            'selection_relationships_display' => [
                [
                    'id' => $subject_relationship->id,
                    'name' => $subject_relationship->name
                ],
            ],
            'content_type_display' => $content_type_display_name,
            'content_type_settings_display' => [
                [
                    'title' => 'Ability to change goal status during activity',
                    'value' => 'No',
                ],
            ]
        ], $element1_output_data);

        $element2_input_data = [
            'content_type' => $content_type_identifier,
            'content_type_settings' => [
                'enable_status_change' => true,
                'status_change_relationship' => $manager_relationship->id,
            ],
            'selection_relationships' => [$manager_relationship->id],
        ];
        $element2 = linked_review_generator::instance()->create_linked_review_element($element2_input_data);
        $element2_output_data = json_decode($element2->data, true);
        unset($element2_output_data['components'], $element2_output_data['compatible_child_element_plugins']);
        self::assertEquals([
            'content_type' => $content_type_identifier,
            'content_type_settings' => [
                'enable_status_change' => true,
                'status_change_relationship' => $manager_relationship->id,
                'status_change_relationship_name' => $manager_relationship->get_name(),
            ],
            'selection_relationships' => [$manager_relationship->id],
            'selection_relationships_display' => [
                [
                    'id' => $manager_relationship->id,
                    'name' => $manager_relationship->name
                ],
            ],
            'content_type_display' => $content_type_display_name,
            'content_type_settings_display' => [
                [
                    'title' => 'Ability to change goal status during activity',
                    'value' => 'Yes',
                ],
                [
                    'title' => 'Change of goal status participant',
                    'value' => $manager_relationship->name,
                ],
            ]
        ], $element2_output_data);
    }

    /**
     * @dataProvider goal_content_type_data_provider
     * @param string|goal_assignment_content_type $goal_content_type_class
     */
    public function test_load_with_empty_content_items_collection(string $goal_content_type_class): void {
        $user = self::getDataGenerator()->create_user();
        $dummy_subject_instance = subject_instance::load_by_entity(new subject_instance_entity([
            'id' => 123456,
            'subject_user_id' => $user->id,
        ]));

        self::setUser($user);

        $content_type = new $goal_content_type_class(context_system::instance());

        $result = $content_type->load_content_items(
            $dummy_subject_instance,
            collection::new([]),
            null,
            true,
            time()
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function test_load_personal_goal_items(): void {
        $data = $this->create_activity_data(goal::SCOPE_PERSONAL);
        $user = $data->subject_user;
        $manager_user = $data->manager_user;
        $goal1 = $data->goal1;
        $goal2 = $data->goal2;

        self::setUser($user);

        $created_at = time();

        $content_items = collection::new([
            ['content_id' => $goal1->id],
            ['content_id' => - 123],
            ['content_id' => $goal2->id],
        ]);

        $content_type = new personal_goal_assignment(context_system::instance());

        $subject_instance_model = subject_instance::load_by_entity($data->subject_instance1);
        $result = $content_type->load_content_items(
            $subject_instance_model,
            $content_items,
            null,
            true,
            $created_at
        );

        self::assertIsArray($result);
        self::assertCount(2, $result);

        $goal1_result_item = array_filter($result, static function (array $item) use ($goal1) {
            return (int)$item['id'] === (int)$goal1->id;
        });
        $goal1_result_item = array_shift($goal1_result_item);

        /** @var personal_goal_entity $expected_goal1 */
        $expected_goal1 = personal_goal_entity::repository()->find($goal1->id);

        // Goal1 has a scale and a scale value.
        $expected_scale_values1 = $expected_goal1
            ->scale
            ->values
            ->sort('sortorder', 'desc', false);
        $expected_scale1 = [];
        foreach ($expected_scale_values1 as $expected_scale_value) {
            $expected_scale1[] = [
                'id' => $expected_scale_value->id,
                'name' => $expected_scale_value->name,
                'proficient' => (bool) $expected_scale_value->proficient,
                'sort_order' => $expected_scale_value->sortorder,
            ];
        }

        // The personal goal's target date now cannot be null but the target date
        // in $goal1_result_item is a formatted string for dates > 0 or null otherwise.
        $date_formatter = new date_field_formatter(date_format::FORMAT_DATE, context_system::instance());
        $formatted_targetdate = $goal1->targetdate > 0
            ? $date_formatter->format($goal1->targetdate)
            : null;

        $expected_content_goal1 = [
            'id' => $goal1->id,
            'goal' => [
                'id' => $goal1->id,
                'display_name' => $goal1->name,
                'description' => $goal1->description,
                'goal_scope' => 'PERSONAL',
            ],
            'status' => [
                'id' => $goal1->scalevalueid,
                'name' => 'Created',
            ],
            'scale_values' => $expected_scale1,
            'target_date' => $formatted_targetdate,
            'can_view_goal_details' => true,
            'can_change_status' => false,
            'can_view_status' => true,
            'status_change' => null,
        ];
        self::assertEquals($expected_content_goal1, $goal1_result_item);

        $goal2_result_item = array_filter($result, static function (array $item) use ($goal2) {
            return (int)$item['id'] === (int)$goal2->id;
        });
        $goal2_result_item = array_shift($goal2_result_item);

        // Goal2 doesn't have a scale or a target date.
        $expected_content_goal2 = [
            'id' => $goal2->id,
            'goal' => [
                'id' => $goal2->id,
                'display_name' => $goal2->name,
                'description' => $goal2->description,
                'goal_scope' => 'PERSONAL',
            ],
            'status' => null,
            'scale_values' => null,
            'target_date' => null,
            'can_view_goal_details' => true,
            'can_change_status' => false,
            'can_view_status' => true,
            'status_change' => null,
        ];
        self::assertEquals($expected_content_goal2, $goal2_result_item);

        // Change status of goal1 and check it's returned as expected.
        /** @var scale_value $new_scale_value */
        $new_scale_value = scale_value::repository()->where('name', 'Finished')->one(true);
        // Make sure the new status doesn't have the same timestamp as the historic status.
        self::waitForSecond();

        // This needs to be run by manager to prevent permissions issues
        self::setUser($manager_user);
        personal_goal_perform_status::create(
            $goal1->id,
            $new_scale_value->id,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );

        self::setUser($user);

        $result = $content_type->load_content_items(
            $subject_instance_model,
            collection::new([['content_id' => $goal1->id]]),
            null,
            true,
            $created_at
        );
        self::assertCount(1, $result);

        $goal1_result_item = $result[$goal1->id];
        $expected_content_goal1['status_change'] = [
            'created_at' => $date_formatter->format($created_at),
            'status_changer_user' => [
                'fullname' => 'Manager User'
            ],
            'scale_value' => [
                'id' => $new_scale_value->id,
                'name' => 'Finished',
            ]
        ];
        self::assertEquals($expected_content_goal1, $goal1_result_item);

        // Also verify that the goal_personal entry has been updated.
        /** @var personal_goal_entity $goal_personal */
        $goal_personal = personal_goal_entity::repository()
            ->where('id', $goal1->id)
            ->where('userid', $user->id)
            ->one(true);
        self::assertEquals($new_scale_value->id, $goal_personal->scalevalueid);

        // Now without the proper capability you cannot view the details for make sure that property is correctly set
        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        unassign_capability('totara/hierarchy:viewownpersonalgoal', $role_id, context_system::instance()->id);

        $result = $content_type->load_content_items(
            $subject_instance_model,
            collection::new([['content_id' => $goal1->id]]),
            null,
            true,
            $created_at
        );
        self::assertCount(1, $result);

        $goal1_result_item = $result[$goal1->id];

        self::assertFalse($goal1_result_item['can_view_goal_details']);

        self::setUser($manager_user);

        $result = $content_type->load_content_items(
            $subject_instance_model,
            collection::new([['content_id' => $goal1->id]]),
            null,
            true,
            $created_at
        );
        self::assertCount(1, $result);

        $goal1_result_item = $result[$goal1->id];

        self::assertTrue($goal1_result_item['can_view_goal_details']);

        // Now without the proper capability you cannot view the details for make sure that property is correctly set
        $role_id = builder::table('role')->where('shortname', 'staffmanager')->value('id');
        unassign_capability('totara/hierarchy:viewstaffpersonalgoal', $role_id);

        $result = $content_type->load_content_items(
            $subject_instance_model,
            collection::new([['content_id' => $goal1->id]]),
            null,
            true,
            $created_at
        );
        self::assertCount(1, $result);

        $goal1_result_item = $result[$goal1->id];

        self::assertFalse($goal1_result_item['can_view_goal_details']);

        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        assign_capability('totara/hierarchy:viewallgoals', CAP_ALLOW, $role_id, context_system::instance()->id);

        $result = $content_type->load_content_items(
            $subject_instance_model,
            collection::new([['content_id' => $goal1->id]]),
            null,
            true,
            $created_at
        );
        self::assertCount(1, $result);

        $goal1_result_item = $result[$goal1->id];

        self::assertTrue($goal1_result_item['can_view_goal_details']);

        // Delete the goal. Personal goals are only soft-deleted, so make sure it's not in the result any more.
        goal::delete_goal_item(['id' => $goal1->id], goal::SCOPE_PERSONAL);

        $result = $content_type->load_content_items(
            $subject_instance_model,
            collection::new([['content_id' => $goal1->id]]),
            null,
            true,
            $created_at
        );
        self::assertCount(0, $result);
    }

    public function test_load_company_goal_items(): void {
        $data = $this->create_activity_data(goal::SCOPE_COMPANY);
        $user = $data->subject_user;
        $manager_user = $data->manager_user;
        $goal1 = $data->goal1;
        $goal2 = $data->goal2;

        self::setUser($user);

        $created_at = time();
        /** @var company_goal_assignment_entity $goal_assignment_goal1 */
        $goal_assignment_goal1 = company_goal_assignment_entity::repository()
            ->where('userid', $user->id)
            ->where('goalid', $goal1->id)
            ->one(true);
        /** @var company_goal_assignment_entity $goal_assignment_goal2 */
        $goal_assignment_goal2 = company_goal_assignment_entity::repository()
            ->where('userid', $user->id)
            ->where('goalid', $goal2->id)
            ->one(true);

        $content_items = collection::new([
            ['content_id' => $goal_assignment_goal1->id],
            ['content_id' => - 123],
            ['content_id' => $goal_assignment_goal2->id],
        ]);

        $content_type = new company_goal_assignment(context_system::instance());

        $subject_instance_model = subject_instance::load_by_entity($data->subject_instance1);
        $result = $content_type->load_content_items(
            $subject_instance_model,
            $content_items,
            null,
            true,
            $created_at
        );

        self::assertIsArray($result);
        self::assertCount(2, $result);

        $goal1_result_item = array_filter($result, static function (array $item) use ($goal_assignment_goal1) {
            return (int)$item['id'] === (int)$goal_assignment_goal1->id;
        });
        $goal1_result_item = array_shift($goal1_result_item);

        /** @var company_goal $expected_goal1 */
        $expected_scale_values1 = $goal_assignment_goal1
            ->scale_value
            ->scale
            ->values
            ->sort('sortorder', 'desc', false);
        $expected_scale1 = [];
        foreach ($expected_scale_values1 as $expected_scale_value) {
            $expected_scale1[] = [
                'id' => $expected_scale_value->id,
                'name' => $expected_scale_value->name,
                'proficient' => (bool) $expected_scale_value->proficient,
                'sort_order' => $expected_scale_value->sortorder,
            ];
        }

        // The company goal's target date now cannot be null but the target date
        // in $goal1_result_item is a formatted string for dates > 0 or null otherwise.
        $date_formatter = new date_field_formatter(date_format::FORMAT_DATE, context_system::instance());
        $formatted_target_date = $goal1->targetdate > 0
            ? $date_formatter->format($goal1->targetdate)
            : null;

        $expected_content_goal1 = [
            'id' => $goal_assignment_goal1->id,
            'goal' => [
                'id' => $goal_assignment_goal1->goalid,
                'display_name' => $goal1->fullname,
                'description' => $goal1->description,
                'goal_scope' => 'COMPANY'
            ],
            'status' => [
                'id' => $goal_assignment_goal1->scalevalueid,
                'name' => 'Started',
            ],
            'scale_values' => $expected_scale1,
            'target_date' => $formatted_target_date,
            'can_view_goal_details' => true,
            'can_change_status' => false,
            'can_view_status' => true,
            'status_change' => null,
        ];
        self::assertEquals($expected_content_goal1, $goal1_result_item);

        $goal2_result_item = array_filter($result, static function (array $item) use ($goal_assignment_goal2) {
            return (int)$item['id'] === (int)$goal_assignment_goal2->id;
        });
        $goal2_result_item = array_shift($goal2_result_item);

        // Goal2 doesn't have a target date.
        $expected_content_goal2 = [
            'id' => $goal_assignment_goal2->id,
            'goal' => [
                'id' => $goal_assignment_goal2->goalid,
                'display_name' => $goal2->fullname,
                'description' => $goal2->description,
                'goal_scope' => 'COMPANY'
            ],
            'status' => [
                'id' => $goal_assignment_goal2->scalevalueid,
                'name' => 'Created',
            ],
            'scale_values' => $expected_scale1,
            'target_date' => null,
            'can_view_goal_details' => true,
            'can_change_status' => false,
            'can_view_status' => true,
            'status_change' => null,
        ];
        self::assertEquals($expected_content_goal2, $goal2_result_item);

        // Change status of goal1 and check it's returned as expected.
        /** @var scale_value $new_scale_value */
        $new_scale_value = scale_value::repository()->where('name', 'Finished')->one(true);
        // Make sure the new status doesn't have the same timestamp as the historic status.
        self::waitForSecond();

        // Run with manager user
        self::setUser($manager_user);
        company_goal_perform_status::create(
            $goal_assignment_goal1->id,
            $new_scale_value->id,
            $data->manager_participant_instance1->id,
            $data->section_element->id
        );

        self::setUser($user);
        $result = $content_type->load_content_items(
            $subject_instance_model,
            collection::new([['content_id' => $goal_assignment_goal1->id]]),
            null,
            true,
            $created_at
        );
        self::assertCount(1, $result);

        $goal1_result_item = $result[$goal_assignment_goal1->id];
        $expected_content_goal1['status_change'] = [
            'created_at' => $date_formatter->format($created_at),
            'status_changer_user' => [
                'fullname' => 'Manager User'
            ],
            'scale_value' => [
                'id' => $new_scale_value->id,
                'name' => 'Finished',
            ]
        ];
        self::assertEquals($expected_content_goal1, $goal1_result_item);

        // Also verify that the goal_record entry has been updated.
        /** @var company_goal_assignment_entity $goal_record */
        $goal_record = company_goal_assignment_entity::repository()
            ->where('goalid', $goal1->id)
            ->where('userid', $user->id)
            ->one(true);
        self::assertEquals($new_scale_value->id, $goal_record->scalevalueid);

        self::setUser($manager_user);
        $result = $content_type->load_content_items(
            $subject_instance_model,
            $content_items,
            null,
            true,
            $created_at
        );

        $goal1_result_item = array_filter($result, static function (array $item) use ($goal_assignment_goal1) {
            return (int)$item['id'] === (int)$goal_assignment_goal1->id;
        });
        $goal1_result_item = array_shift($goal1_result_item);

        self::assertTrue($goal1_result_item['can_view_goal_details']);

        // Now without the proper capability you cannot view the details for make sure that property is correctly set
        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        unassign_capability('totara/hierarchy:viewgoal', $role_id, context_system::instance()->id);

        $result = $content_type->load_content_items(
            $subject_instance_model,
            $content_items,
            null,
            true,
            $created_at
        );

        $goal1_result_item = array_filter($result, static function (array $item) use ($goal_assignment_goal1) {
            return (int)$item['id'] === (int)$goal_assignment_goal1->id;
        });
        $goal1_result_item = array_shift($goal1_result_item);

        self::assertFalse($goal1_result_item['can_view_goal_details']);

        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        assign_capability('totara/hierarchy:viewallgoals', CAP_ALLOW, $role_id, context_system::instance()->id);

        $result = $content_type->load_content_items(
            $subject_instance_model,
            $content_items,
            null,
            true,
            $created_at
        );

        $goal1_result_item = array_filter($result, static function (array $item) use ($goal_assignment_goal1) {
            return (int)$item['id'] === (int)$goal_assignment_goal1->id;
        });
        $goal1_result_item = array_shift($goal1_result_item);

        self::assertTrue($goal1_result_item['can_view_goal_details']);
    }

    public function test_get_goal_status_permissions(): void {
        $data = $this->create_activity_data(goal::SCOPE_PERSONAL);
        $participant_instance = participant_instance_model::load_by_entity($data->manager_participant_instance1);
        $subject_relationship = relationship::load_by_idnumber('subject')->id;
        $manager_relationship = relationship::load_by_idnumber('manager')->id;
        self::setUser($data->manager_user);

        $element = new element_entity($data->section_element->element_id);
        $element_data = [
            'content_type' => 'personal_goal',
            'content_type_settings' => [
                'enable_status_change' => false,
                'status_change_relationship' => null,
            ],
            'selection_relationships' => [$subject_relationship],
        ];
        $element->data = json_encode($element_data);
        $element->save();

        $content_items = linked_review_content_model::get_existing_selected_content(
            $data->section_element->id,
            $data->subject_instance1->id
        );

        // Can't view status as false is passed in for 'view other responses'.
        // Can't change status as it is disabled on the element.
        [$can_view, $can_change] = goal_assignment_content_type::get_goal_status_permissions(
            $content_items,
            $data->manager_participant_section1,
            false
        );
        self::assertFalse($can_view);
        self::assertFalse($can_change);

        // Can view status as true is passed in for 'view other responses'.
        [$can_view, $can_change] = goal_assignment_content_type::get_goal_status_permissions(
            $content_items,
            $data->manager_participant_section1,
            true
        );
        self::assertTrue($can_view);
        self::assertFalse($can_change);

        $element_data['content_type_settings']['enable_status_change'] = true;
        $element_data['content_type_settings']['status_change_relationship'] = $subject_relationship;
        $element->data = json_encode($element_data);
        $element->save();
        // Refresh content items, so they include the updated element data.
        $content_items = linked_review_content_model::get_existing_selected_content(
            $data->section_element->id,
            $data->subject_instance1->id
        );

        // Can't change status as user is not of the status_change_relationship
        [$can_view, $can_change] = goal_assignment_content_type::get_goal_status_permissions(
            $content_items,
            $data->manager_participant_section1,
            true
        );
        self::assertTrue($can_view);
        self::assertFalse($can_change);

        $element_data['content_type_settings']['enable_status_change'] = true;
        $element_data['content_type_settings']['status_change_relationship'] = $manager_relationship;
        $element->data = json_encode($element_data);
        $element->save();
        $content_items = linked_review_content_model::get_existing_selected_content(
            $data->section_element->id,
            $data->subject_instance1->id
        );

        // Can change status as it is the correct relationship
        [$can_view, $can_change] = goal_assignment_content_type::get_goal_status_permissions(
            $content_items,
            $data->manager_participant_section1,
            true
        );
        self::assertTrue($can_view);
        self::assertTrue($can_change);

        // Can view status even when passing in false for viewing other responses.
        [$can_view, $can_change] = goal_assignment_content_type::get_goal_status_permissions(
            $content_items,
            $data->manager_participant_section1,
            false
        );
        self::assertTrue($can_view);
        self::assertTrue($can_change);

        $section_relationship = section_relationship::repository()
            ->where('core_relationship_id', $participant_instance->core_relationship_id)
            ->where('section_id', $data->section_element->section_id)
            ->get()
            ->first();
        $section_relationship->delete();
        $content_items = linked_review_content_model::get_existing_selected_content(
            $data->section_element->id,
            $data->subject_instance1->id
        );

        // Can't change status as the relationship doesn't exist on the section.
        [$can_view, $can_change] = goal_assignment_content_type::get_goal_status_permissions(
            $content_items,
            $data->manager_participant_section1,
            true
        );
        self::assertTrue($can_view);
        self::assertFalse($can_change);

        // Re-create relationship.
        $section_relationship = new section_relationship($section_relationship->to_array());
        $section_relationship->save();
        $content_items = linked_review_content_model::get_existing_selected_content(
            $data->section_element->id,
            $data->subject_instance1->id
        );
        [$can_view, $can_change] = goal_assignment_content_type::get_goal_status_permissions(
            $content_items,
            $data->manager_participant_section1,
            true
        );
        self::assertTrue($can_view);
        self::assertTrue($can_change);

        // Can't change status as there is no participant section record.
        participant_section::repository()->delete();
        $content_items = linked_review_content_model::get_existing_selected_content(
            $data->section_element->id,
            $data->subject_instance1->id
        );
        [$can_view, $can_change] = goal_assignment_content_type::get_goal_status_permissions(
            $content_items,
            $data->manager_participant_section1,
            true
        );
        self::assertTrue($can_view);
        self::assertFalse($can_change);
    }
}