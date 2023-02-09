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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use container_perform\perform as perform_container;
use core\collection;
use core\entity\adhoc_task;
use core\orm\entity\entity;
use core\orm\query\builder;
use core\task\manager;
use mod_perform\entity\activity\manual_relationship_selection;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\section_element_reference;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track_assignment;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\entity\activity\track_user_assignment_via;
use mod_perform\event\activity_deleted;
use mod_perform\models\activity\activity;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\element_response as element_response_entity;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\entity\activity\section_relationship as section_relationship_entity;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\section_element;
use mod_perform\models\response\section_element_response;
use mod_perform\models\activity\helpers\activity_deletion;
use mod_perform\task\close_activity_subject_instances_task;
use performelement_numeric_rating_scale\numeric_rating_scale;
use performelement_redisplay\redisplay;
use PHPUnit\Framework\Constraint\Constraint;
use totara_core\extended_context;
use totara_notification\model\notification_preference;
use totara_notification\builder\notification_preference_builder;
use totara_notification\entity\notification_preference as notification_preference_entity;

require_once(__DIR__ . '/section_element_reference_test.php');

/**
 * @group perform
 */
class mod_perform_activity_deletion_model_helper_testcase extends section_element_reference_testcase {

    /**
     * @param bool $include_assignment_and_instances Include assignments and subject/participant instances
     * @param bool $include_responses Questions have answers
     * @param bool $use_shared_elements Share elements with other perform activities
     * @dataProvider delete_provider
     */
    public function test_delete(
        bool $include_assignment_and_instances,
        bool $include_responses,
        bool $use_shared_elements
    ): void {
        self::setAdminUser();

        $perform_generator = $this->perform_generator();

        $config = new \mod_perform\testing\activity_generator_configuration();
        $config->set_number_of_users_per_user_group_type($include_assignment_and_instances ? 2 : 0);
        /** @var activity $activity */
        $activity = $perform_generator->create_full_activities($config)->first();
        $activity_id = $activity->id;

        $section1 = $activity->get_sections()->first();
        $element1 = $perform_generator->create_element(['context' => $activity->get_context()]);
        $perform_generator->create_section_element($section1, $element1);

        if ($include_responses) {
            /** @var participant_instance $participant_instance_entity */
            $participant_instance_entity = participant_instance::repository()->order_by('id')->first(true);

            /** @var section_element_entity $section_element_entity */
            $section_element_entity = section_element_entity::repository()->order_by('id')->first(true);

            $section_element_response = new section_element_response(
                participant_instance_model::load_by_entity($participant_instance_entity),
                section_element::load_by_entity($section_element_entity),
                null,
                new collection()
            );

            $section_element_response->set_response_data('question answer')->save();
        }

        if ($use_shared_elements) {
            // Now this element is created in the top level perform context.
            $shared_element = $perform_generator->create_element(['context' => $activity->get_context()->get_parent_context()]);
            $perform_generator->create_section_element($section1, $shared_element);
        }

        $context_id = $activity->get_context()->id;

        $context_row = builder::create()->from('context')->find($context_id);
        self::assertNotNull($context_row, 'perform context should be found');

        $custom_notification = $this->create_custom_totara_notification($activity);
        self::assertNotNull($custom_notification, 'custom totara notification should be created');

        $expected_counts = [
            activity_entity::class => self::equalTo(1),
            manual_relationship_selection::class => self::greaterThan(0),
            track_assignment::class => self::greaterThan(0),
            section_entity::class => self::greaterThan(0),
            section_element_entity::class => self::greaterThan(0),
            section_relationship_entity::class => self::greaterThan(0),

            track_user_assignment_via::class => self::equalTo(0),
            track_user_assignment::class => self::equalTo(0),
            subject_instance::class => self::equalTo(0),
            participant_instance::class => self::equalTo(0),

            element_entity::class => self::equalTo(1),
        ];

        if ($include_assignment_and_instances) {
            $expected_counts[track_user_assignment::class] = self::greaterThan(0);
            $expected_counts[track_user_assignment_via::class] = self::greaterThan(0);
            $expected_counts[subject_instance::class] = self::greaterThan(0);
            $expected_counts[participant_instance::class] = self::greaterThan(0);
        }

        if ($include_responses) {
            $expected_counts[element_response_entity::class] = self::equalTo(1);
        }

        if ($use_shared_elements) {
            $expected_counts[element_entity::class] = self::equalTo(2);
        }

        $this->assert_row_counts($expected_counts);

        $sink = $this->redirectEvents();

        // The actual method call.
        (new activity_deletion($activity))->delete();

        $context_row = builder::create()->from('context')->find($context_id);
        self::assertNotNull(
            $context_row,
            'context should not be deleted by this class, that is the responsibility of the perform container'
        );

        $expected_counts = [
            activity_entity::class => self::equalTo(0),
            manual_relationship_selection::class => self::equalTo(0),
            track_assignment::class => self::equalTo(0),
            section_entity::class => self::equalTo(0),
            section_element_entity::class => self::equalTo(0),
            section_relationship_entity::class => self::equalTo(0),

            track_user_assignment_via::class => self::equalTo(0),
            track_user_assignment::class => self::equalTo(0),
            subject_instance::class => self::equalTo(0),
            participant_instance::class => self::equalTo(0),

            element_entity::class => self::equalTo(0),
        ];

        if ($use_shared_elements) {
            $expected_counts[element_entity::class] = self::equalTo(1);
        }

        $this->assert_row_counts($expected_counts);

        $repository = notification_preference_entity::repository();
        self::assertNull(
            $repository->find_by_context_and_ancestor_id(
                extended_context::make_with_context(
                    $activity->get_context()
                ),
                $custom_notification->get_ancestor_id()
            )
        );

        $events = $sink->get_events();

        $this->assertCount(1, $events);
        $event = array_shift($events);
        $this->assertInstanceOf(activity_deleted::class, $event);
        $this->assertEquals($activity_id, $event->objectid);
        $this->assertEquals($context_id, $event->contextid);
    }

    public function delete_provider(): array {
        return [
            // assignments/instances, response, shared element
            'with subject instances but no responses' => [true, false, false],
            'with subject instances and responses' => [true, true, false],
            'without subject instances or subject instances' => [false, false, false],
            'with shared elements' => [true, true, true],
        ];
    }

    public function test_other_activities_are_not_deleted(): void {
        self::setAdminUser();

        $perform_generator = $this->perform_generator();

        $target_activity = $perform_generator->create_activity_in_container();
        $target_activity_id = $target_activity->id;
        $other_activity1_id = $perform_generator->create_activity_in_container()->id;
        $other_activity2_id = $perform_generator->create_activity_in_container()->id;

        (new activity_deletion($target_activity))->delete();

        self::assertFalse(activity_entity::repository()->where('id', $target_activity_id)->exists());
        self::assertTrue(activity_entity::repository()->where('id', $other_activity1_id)->exists());
        self::assertTrue(activity_entity::repository()->where('id', $other_activity2_id)->exists());
    }

    public function test_files_are_deleted(): void {
        self::setAdminUser();
        $fs = get_file_storage();

        $activity_to_delete = $this->perform_generator()->create_activity_in_container();
        $activity_to_keep = $this->perform_generator()->create_activity_in_container();

        $file_record = [
            'component' => 'test',
            'filearea' => 'mod_perform',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'test.txt'
        ];
        $file_to_delete = $fs->create_file_from_string(
            array_merge($file_record, ['contextid' => $activity_to_delete->context_id]),
            'Test text'
        );
        $file_to_keep = $fs->create_file_from_string(
            array_merge($file_record, ['contextid' => $activity_to_keep->context_id]),
            'Test text'
        );

        $this->assertTrue($fs->file_exists_by_hash($file_to_delete->get_pathnamehash()));
        $this->assertTrue($fs->file_exists_by_hash($file_to_keep->get_pathnamehash()));

        $activity_to_delete->delete();

        $this->assertFalse($fs->file_exists_by_hash($file_to_delete->get_pathnamehash()));
        $this->assertTrue($fs->file_exists_by_hash($file_to_keep->get_pathnamehash()));
    }

    public function test_redisplay_section_element_references_are_deleted(): void {
        $this->create_test_data();
        $perform_generator = $this->perform_generator();

        // As control data that shouldn't be deleted, create another question and redisplay of it in another activity.
        $another_section_in_redisplay_activity = $perform_generator->create_section(
            $this->referencing_redisplay_activity, ['title' => 'referencing_question_section']
        );
        $source_element = $perform_generator->create_element([
            'plugin_name' => numeric_rating_scale::get_plugin_name(),
        ]);
        $source_section_element = $perform_generator->create_section_element($another_section_in_redisplay_activity, $source_element);
        $referencing_redisplay_element = $perform_generator->create_element([
            'plugin_name' => 'redisplay',
            'data' => json_encode([redisplay::SOURCE_SECTION_ELEMENT_ID => $source_section_element->id], JSON_THROW_ON_ERROR),
        ]);
        $another_activity = $perform_generator->create_activity_in_container(['activity_name' => 'another_activity']);
        $another_activity_section = $perform_generator->create_section($another_activity, ['title' => 'another_section']);
        $referencing_redisplay_section_element = $perform_generator->create_section_element(
            $another_activity_section, $referencing_redisplay_element
        );

        self::assertEquals(3, section_element_reference::repository()->count());

        self::assertTrue(
            section_element_reference::repository()
                ->where('id', $this->redisplay_section_element_reference->id)
                ->exists()
        );
        self::assertTrue(
            section_element_reference::repository()
                ->where('id', $this->aggregation_section_element_reference->id)
                ->exists()
        );
        self::assertTrue(
            section_element_reference::repository()
                ->where('source_section_element_id', $source_section_element->id)
                ->where('referencing_element_id', $referencing_redisplay_element->id)
                ->exists()
        );

        $this->source_activity->delete();

        // Only the redisplay section element reference in the source activity should be deleted.
        self::assertEquals(2, section_element_reference::repository()->count());

        self::assertFalse(
            section_element_reference::repository()
                ->where('id', $this->redisplay_section_element_reference->id)
                ->exists()
        );
        self::assertTrue(
            section_element_reference::repository()
                ->where('id', $this->aggregation_section_element_reference->id)
                ->exists()
        );
        self::assertTrue(
            section_element_reference::repository()
                ->where('source_section_element_id', $source_section_element->id)
                ->where('referencing_element_id', $referencing_redisplay_element->id)
                ->exists()
        );
    }

    // Make sure deletion works fine for redisplay elements referencing same activity.
    public function test_redisplay_section_element_references_are_deleted_same_activity(): void {
        $this->create_test_data_referencing_same_section();

        self::assertTrue(
            section_element_reference::repository()
                ->where('source_section_element_id', $this->self_reference_source_section_element->id)
                ->where('referencing_element_id', $this->self_reference_referencing_element->id)
                ->exists()
        );

        $this->self_reference_activity->delete();

        self::assertFalse(
            section_element_reference::repository()
                ->where('source_section_element_id', $this->self_reference_source_section_element->id)
                ->where('referencing_element_id', $this->self_reference_referencing_element->id)
                ->exists()
        );
    }

    public function test_queued_close_activity_subject_instance_adhoc_task_are_deleted(): void {
        $this->setAdminUser();
        $subject_user = \core\testing\generator::instance()->create_user();
        $other_participant = \core\testing\generator::instance()->create_user();

        $subject_instance = \mod_perform\testing\generator::instance()->create_subject_instance([
            'activity_name' => ' activity about target user',
            'activity_type' => 'appraisal',
            'subject_user_id' => $subject_user->id,
            'other_participant_id' => $other_participant->id,
            'subject_is_participating' => true,
        ]);

        $activity = activity::load_by_entity($subject_instance->track->activity);
        $admin_user = get_admin();

        // Queue the task.
        $task = close_activity_subject_instances_task::create($activity->id, $admin_user->id);
        manager::queue_adhoc_task($task, true);

        // Check the task exists:
        $class = close_activity_subject_instances_task::class;
        if (strpos($class, '\\') !== 0) {
            $class = '\\' . $class;
        }

        $adhoc_tasks = adhoc_task::repository()
            ->where('component', 'mod_perform')
            ->where('classname', $class)
            ->where("customdata", json_encode(["activity_id" => $activity->id]))
            ->get();
        $this->assertCount(1, $adhoc_tasks);

        // Delete activity
        perform_container::from_activity($activity)->delete_activity();

        // Confirm the queued adhoc task has been deleted
        $adhoc_tasks = adhoc_task::repository()
            ->where('component', 'mod_perform')
            ->where('classname', $class)
            ->where("customdata", json_encode(["activity_id" => $activity->id]))
            ->get();
        $this->assertEmpty($adhoc_tasks);
    }

    protected function assert_row_counts($expectations): void {
        /** @var Constraint $constraint */
        foreach ($expectations as $entity_class => $constraint) {

            /** @var $entity_class entity */
            $actual_count = $entity_class::repository()->count();

            $message = sprintf('%s count should be %s', $entity_class::TABLE, $constraint->toString());
            self::assertThat($actual_count, $constraint, $message);
        }
    }

    /**
     * @return \mod_perform\testing\generator
     * @throws coding_exception
     */
    private function perform_generator(): \mod_perform\testing\generator {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        return $generator;
    }

    private function create_custom_totara_notification(activity $activity): notification_preference {
        $repository = notification_preference_entity::repository();
        $preference = $repository->find_in_system_context(
            'mod_perform\totara_notification\notification\participant_instance_completion_by_manager_for_subject'
        );

        $builder = new notification_preference_builder(
            $preference->resolver_class_name,
            extended_context::make_with_context(
                $activity->get_context()
            )
        );
        $builder->set_notification_class_name($preference->notification_class_name);
        $builder->set_ancestor_id($preference->id);
        $builder->set_forced_delivery_channels(["totara_task"]);
        $custom_notification = $builder->save();
        return $custom_notification;
    }
}
