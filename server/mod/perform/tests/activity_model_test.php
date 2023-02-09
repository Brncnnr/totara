<?php
/*
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_type;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use totara_core\relationship\relationship;

/**
 * @group perform
 */
class mod_perform_activity_model_testcase extends testcase {

    /**
     * @var \mod_perform\testing\generator
     */
    protected $perform_generator;

    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
        $this->perform_generator = \mod_perform\testing\generator::instance();
    }

    protected function tearDown(): void {
        $this->perform_generator = null;
    }

    public function test_can_manage() {
        $data_generator = $this->getDataGenerator();

        $user1 = $data_generator->create_user();
        $user2 = $data_generator->create_user();
        $user3 = $data_generator->create_user();

        $this->setUser($user1);
        $activity_user1 = $this->perform_generator->create_activity_in_container(['activity_name' => 'User1 One']);

        $this->setUser($user2);
        $activity_user2 = $this->perform_generator->create_activity_in_container(['activity_name' => 'User2 One']);

        $this->setAdminUser();

        $this->assertTrue($activity_user1->can_manage($user1->id));
        $this->assertFalse($activity_user1->can_manage($user2->id));
        $this->assertFalse($activity_user1->can_manage($user3->id));

        $this->assertFalse($activity_user2->can_manage($user1->id));
        $this->assertTrue($activity_user2->can_manage($user2->id));
        $this->assertFalse($activity_user2->can_manage($user3->id));

        $this->setUser($user1);
        $this->assertTrue($activity_user1->can_manage());
        $this->assertFalse($activity_user2->can_manage());
    }

    public function test_update_general_info(): void {
        $original_name = 'Existing activity name';
        $original_description = 'Existing activity description';
        $activity_type = 'check-in';

        $activity = $this->perform_generator->create_activity_in_container([
            'activity_name' => $original_name,
            'description' => $original_description,
            'activity_type' => $activity_type,
            'activity_status' => draft::get_code(),
        ]);

        $this->assertEquals($activity->name, $original_name);
        $this->assertEquals($activity->description, $original_description);
        $this->assertEquals($activity->type->name, $activity_type);

        $new_type_id = activity_type::load_by_name('feedback')->id;
        $activity->set_general_info('New name for existing activity', 'New description', $new_type_id)->update();

        // Assert in memory state is correct
        $this->assertEquals('New name for existing activity', $activity->name);
        $this->assertEquals('New description', $activity->description);
        $this->assertEquals($activity->type->id, $new_type_id);

        // Assert persisted state is correct
        $from_database = activity::load_by_id($activity->id);
        $this->assertEquals('New name for existing activity', $from_database->name);
        $this->assertEquals('New description', $from_database->description);
        $this->assertEquals($new_type_id, $from_database->type->id);
    }

    public function test_update_general_info_accepts_null_description(): void {
        $original_name = 'Existing activity name';
        $original_description = 'Existing activity description';
        $activity_type = 'feedback';

        $activity = $this->perform_generator->create_activity_in_container([
            'activity_name' => $original_name,
            'description' => $original_description,
            'activity_type' => $activity_type,
            'activity_status' => draft::get_code(),
        ]);

        $this->assertEquals($activity->name, $original_name);
        $this->assertEquals($activity->description, $original_description);
        $this->assertEquals($activity->type->name, $activity_type);

        $activity->set_general_info('New name for existing activity', null, null)->update();

        // Assert in memory state is correct
        $this->assertEquals('New name for existing activity', $activity->name);
        $this->assertNull($activity->description);

        // Assert persisted state is correct
        $from_database = activity::load_by_id($activity->id);
        $this->assertEquals('New name for existing activity', $from_database->name);
        $this->assertNull($from_database->description);
    }

    public function test_update_attribution_settings(): void {
        $activity = $this->perform_generator->create_activity_in_container([
            'activity_name' => 'Existing activity name',
            'description' => 'Existing activity description',
            'activity_type' => 'check-in',
            'activity_status' => draft::get_code(),
        ]);

        $this->assertFalse($activity->anonymous_responses);

        $activity->set_anonymous_setting(true)->update();

        // Assert in memory state is correct
        $this->assertTrue($activity->anonymous_responses);

        // Assert persisted state is correct
        $from_database = activity::load_by_id($activity->id);
        $this->assertTrue($from_database->anonymous_responses);
    }

    public function test_cant_update_attribution_settings_when_active(): void {
        $activity_model = $this->perform_generator->create_activity_in_container([
            'activity_name' => 'Existing activity name',
            'description' => 'Existing activity description',
            'activity_type' => 'check-in',
            'activity_status' => draft::get_code(),
        ]);
        $activity_entity = new activity_entity($activity_model->id);
        $activity_model = activity::load_by_entity($activity_entity);

        $activity_entity->status = active::get_code();
        $activity_entity->save();

        self::assertTrue($activity_model->is_active(), 'Failed precondition check, activity should be active.');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Attribution settings can not be updated when an activity is active');

        $activity_model->set_anonymous_setting(true);
    }

    /**
     * Test updating an active activity manual relationship fails.
    */
    public function test_cant_update_manual_relationship_when_active(): void {
        $activity = $this->perform_generator->create_activity_in_container([
            'activity_name' => 'Existing activity name',
            'description' => 'Existing activity description',
            'activity_type' => 'check-in',
            'activity_status' => active::get_code(),
        ]);

        $manager_relationship = relationship::load_by_idnumber('manager');

        $manual_relationships_args = [];
        foreach ($activity->manual_relationships as $manual_relationship) {
            $manual_relationships_args[] = [
                'manual_relationship_id' => $manual_relationship->id,
                'selector_relationship_id' => $manager_relationship->id,
            ];
        }

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Can not update selecting relationships, activity is active');

        $activity->update_manual_relationship_selections($manual_relationships_args);
    }

    /**
     * Test updating an active activity manual relationship
     */
    public function test_update_manual_relationship_in_draft_status(): void {
        $activity = $this->perform_generator->create_activity_in_container([
            'activity_name' => 'Existing activity name',
            'description' => 'Existing activity description',
            'activity_type' => 'check-in',
            'activity_status' => draft::get_code(),
        ]);

        $manager_relationship = relationship::load_by_idnumber('manager');

        $manual_relationships_args = [];
        foreach ($activity->manual_relationships as $manual_relationship) {
            $manual_relationships_args[] = [
                'manual_relationship_id' => $manual_relationship->id,
                'selector_relationship_id' => $manager_relationship->id,
            ];
        }

        $activity->update_manual_relationship_selections($manual_relationships_args);
        $relationships = $activity->get_manual_relationships();
        foreach ($relationships as $relationship) {
            $this->assertInstanceOf(activity::class, $relationship->get_activity());
            $this->assertInstanceOf(relationship::class, $relationship->get_selector_relationship());
            $this->assertInstanceOf(relationship::class, $relationship->get_manual_relationship());
        }
    }

    /**
     * @dataProvider update_general_should_validate_new_attributes
     * @param string $new_name
     * @param string $expected_message
     * @throws coding_exception
     */
    public function test_update_general_should_validate_new_attributes(string $new_name, string $expected_message): void {
        $activity = $this->perform_generator->create_activity_in_container([
            'activity_name' => 'Existing activity name',
            'description' => 'Existing activity description',
        ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage($expected_message);

        $activity->set_general_info($new_name, 'New description', null)->update();
    }

    public function test_update_general_should_not_update_type_for_active_activity() {
        $active_activity = $this->perform_generator->create_activity_in_container(
            [
                'activity_name' => 'User1 One',
                'activity_status' => active::get_code(),
            ]
        );

        $this->assertEquals(active::get_code(), $active_activity->status);
        $this->assertEquals(1, $active_activity->type->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot change type of activity " . $active_activity->id . " since it is no longer a draft");
        $active_activity->set_general_info('New name for existing activity', null, 2)->update();
    }

    public function test_update_general_should_fail_for_invalid_activity_type() {
        $activity =  $this->perform_generator->create_activity_in_container([
            'activity_name' => 'New activity name',
            'description' => 'New activity description',
        ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid activity type");
        $activity->set_general_info('New name for existing activity', null, 100)->update();
    }

    public function update_general_should_validate_new_attributes(): array {
        return [
            'Name not present' => [
                '',
                'Name is required',
            ],
            'Name too long' => [
                random_string(activity::NAME_MAX_LENGTH + 1),
                'Name cannot be more than 1024 characters',
            ],
        ];
    }

    public function test_update_general_should_fail_on_new_activity(): void {
        $activity = $this->perform_generator->create_activity_in_container();
        $new_entity = new class extends activity_entity {
            public $exists_now = true;

            public function exists(): bool {
                return $this->exists_now;
            }
        };
        $new_entity->course = $activity->course;

        /** @var activity $activity */
        $activity = activity::load_by_entity($new_entity);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('You can not update a entity that does not exist yet or was deleted.');

        $new_entity->exists_now = false;
        $activity->set_general_info('name', 'description', null)->update();
    }

    public function test_status() {
        $draft_activity = $this->perform_generator->create_activity_in_container(
            [
                'activity_name' => 'User1 One',
                'activity_status' => draft::get_code(),
            ]
        );

        $this->assertEquals(draft::get_code(), $draft_activity->status);
        $state = $draft_activity->get_status_state();
        $this->assertEquals('DRAFT', $state::get_name());
        $this->assertEquals('Draft', $state::get_display_name());

        $active_activity = $this->perform_generator->create_activity_in_container(
            [
                'activity_name' => 'User1 One',
                'activity_status' => active::get_code(),
            ]
        );

        $this->assertEquals(active::get_code(), $active_activity->status);
        $state = $active_activity->get_status_state();
        $this->assertEquals('ACTIVE', $state::get_name());
        $this->assertEquals('Active', $state::get_display_name());
    }

    public function test_update_general_info_not_accepts_title_only_with_spaces(): void {
        $original_name = 'Existing activity name';
        $original_description = 'Existing activity description';
        $activity_type = 'feedback';

        $activity = $this->perform_generator->create_activity_in_container([
            'activity_name' => $original_name,
            'description' => $original_description,
            'activity_type' => $activity_type,
            'activity_status' => draft::get_code(),
        ]);

        $this->assertEquals($activity->name, $original_name);
        $this->assertEquals($activity->description, $original_description);
        $this->assertEquals($activity->type->name, $activity_type);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The following errors need to be fixed: 'Name is required'");
        /** @var activity $activity */
        $activity->set_general_info('   ', null, null)->update();
    }

    public function test_update_general_info_updates_course_container_name(): void {
        $original_name = 'Appraisal';
        $updated_name = 'Feedback';

        $activity = $this->perform_generator->create_activity_in_container(['activity_name' => $original_name]);
        $container = $activity->get_container();

        $this->assertEquals($original_name, $activity->name);
        $this->assertEquals($original_name, $container->fullname);
        $this->assertNotEquals($updated_name, $activity->name);
        $this->assertNotEquals($updated_name, $container->fullname);

        $activity->set_general_info($updated_name, null, null)->update();
        $container = $activity->get_container();

        $this->assertEquals($updated_name, $activity->name);
        $this->assertEquals($updated_name, $container->fullname);
        $this->assertNotEquals($original_name, $activity->name);
        $this->assertNotEquals($original_name, $container->fullname);
    }

    public function test_update_general_info_cannot_change_in_active_state(): void {
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
        // things.
        $activity->set_general_info($activity->name, null, $new_type->id)->update();
        $this->assertEquals($new_type->id, $activity->type->id);

        // But not after it is activated.
        $this->assertTrue($activity->activate()->is_active());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot change type of activity {$activity->id} since it is no longer a draft");
        $activity->set_general_info($activity->name, null, $original_type->id)->update();
    }

    public function test_get_sections_with_respondable_element_count(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $activity = $this->perform_generator->create_activity_in_container();

        // No element in the section will not be returned.
        $sections = $activity->get_sections_ordered_with_respondable_element_count();
        // one default section is for activity.
        self::assertCount(1, $activity->sections);

        $section = $this->perform_generator->create_section($activity);
        $element_one = $this->perform_generator->create_element();
        $element_two = $this->perform_generator->create_element();

        // Create two elements.
        $this->perform_generator->create_section_element($section, $element_one);
        $this->perform_generator->create_section_element($section, $element_two);

        $activity->refresh(true);

        $sections = $activity->get_sections_ordered_with_respondable_element_count();

        // One default section and one is the customized section.
        self::assertCount(2, $sections);

        foreach ($sections as $section) {
            self::assertGreaterThanOrEqual(0, $section->get_respondable_element_count());
        }
    }
}