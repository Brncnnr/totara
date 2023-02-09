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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use mod_perform\constants;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\expand_task;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\track;
use mod_perform\state\participant_instance\not_started;
use mod_perform\task\service\data\subject_instance_activity_collection;
use mod_perform\task\service\participant_instance_creation;
use mod_perform\task\service\participant_instance_dto;
use mod_perform\task\service\participant_section_creation;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\task\service\subject_instance_dto;
use totara_core\relationship\relationship as core_relationship_model;
use totara_job\job_assignment;

/**
 * Class participant_section_creation_service_test
 *
 * @group perform
 */
class mod_perform_participant_section_creation_service_testcase extends advanced_testcase {

    /**
     * Data of Activities used.
     * @var array
     */
    private $activity_trees;

    /**
     * Tests the correct participant is assigned to the right section based on the section relationship
     * when an activity has multiple sections.
     *
     * @return void
     */
    public function test_correct_participant_assigned_to_correct_section(): void {
        // Creates activity with 2 sections: 1 section has Subject relationship.
        // other section has manager & appraiser as relationship.
        $activity_tree = $this->setup_activity_with_multiple_sections();
        $subject_instance_service = new subject_instance_creation();
        $subject_instance_service->generate_instances();
        $subject_instance = subject_instance::repository()->get()->first();
        $participant_sections = participant_section::repository()->get();
        $participant_instances = participant_instance::repository()->get();

        $subject_user_participant_section = $participant_sections->filter('section_id', $activity_tree->subject_user_section->id);
        // Asserts the 1 user(subject) is assigned to the subject_user section.
        $this->assertCount(1, $subject_user_participant_section);
        $subject_user_participant_instance = $participant_instances->find(
            'id',
            $subject_user_participant_section->first()->participant_instance_id
        );
        // Asserts the participant_id of the subject_user section is same as subject user id.
        $this->assertEquals($subject_instance->subject_user_id, $subject_user_participant_instance->participant_id);

        $manager_appraiser_user_participant_section = $participant_sections->filter(
            'section_id',
            $activity_tree->manager_appraiser_user_section->id
        );
        // Asserts the 2 participant sections are created for the manager_appraiser section.
        $this->assertCount(2, $manager_appraiser_user_participant_section);
        // Assert the subject_user participant is not assigned to the manager_appraiser section.
        $this->assertNotContainsEquals(
            $subject_user_participant_instance->id,
            $manager_appraiser_user_participant_section->pluck('participant_instance_id')
        );
    }

    /**
     * Test participant_sections are created when the subject instance service generates subject instances.
     *
     * @return void
     */
    public function test_participant_sections_are_created_from_subject_instance_creation(): void {
        $this->setup_multiple_activities();
        $subject_instance_service = new subject_instance_creation();
        $subject_instance_service->generate_instances();

        $this->assert_participant_sections_created_count();
    }

    /**
     * Test participant_sections are created when the participant instance service generates participant instances.
     *
     * @return void
     */
    public function test_participant_sections_are_created_from_participant_instance_creation(): void {
        $this->setup_multiple_activities();
        $subject_instance_collection = $this->create_subject_instances();
        $participant_instance_service = new participant_instance_creation();
        $participant_instance_service->generate_instances($subject_instance_collection);

        $this->assert_participant_sections_created_count();
    }

    /**
     * Asserts count of participant sections created.
     *
     * @return void
     */
    private function assert_participant_sections_created_count(): void {
        $participant_instance_ids = participant_instance::repository()->get()->pluck('id');
        $created_participant_sections = participant_section::repository()->get();
        $sections = section::repository()->get();
        $activity_sections = [];
        foreach ($sections as $section) {
            if (!isset($activity_sections[$section->activity_id])) {
                $activity_sections[$section->activity_id] = [];
            }
            $activity_sections[$section->activity_id][] = $section->id;
        }

        foreach ($created_participant_sections as $participant_section) {
            $this->assertContainsEquals(
                $participant_section->participant_instance_id,
                $participant_instance_ids
            );
            $activity_id = $sections->find('id', $participant_section->section_id)->activity_id;
            $sections_created_for_participant = $created_participant_sections->filter(
                'participant_instance_id',
                $participant_section->participant_instance_id
            )->pluck('section_id');
            $this->assertEqualsCanonicalizing($sections_created_for_participant, $activity_sections[$activity_id]);
        }
    }

    /**
     * Tests participants are created successfully.
     *
     * @return void
     */
    public function test_create_participant_sections(): void {
        $this->setup_multiple_activities();
        $participant_section_service = new participant_section_creation();
        $participant_instance_dto_collection = $this->setup_participant_instances();
        $subject_instance_activity_collection = (new subject_instance_activity_collection())
            ->load_activity_configs_if_missing($participant_instance_dto_collection->pluck('activity_id'));
        $participant_section_service->generate_sections($participant_instance_dto_collection, $subject_instance_activity_collection);

        $sections = section::repository()->get();
        $created_participant_sections = participant_section::repository()->get();
        $expected_participant_sections_created = 0;

        foreach ($participant_instance_dto_collection as $participant_instance_dto) {
            $this->assertContainsEquals(
                $participant_instance_dto->id,
                $created_participant_sections->pluck('participant_instance_id')
            );
            $activity_sections = $sections->filter('activity_id', $participant_instance_dto->activity_id);
            $this->assertEqualsCanonicalizing(
                $activity_sections->pluck('id'),
                $created_participant_sections->filter('participant_instance_id', $participant_instance_dto->id)->pluck('section_id')
            );
            $expected_participant_sections_created = $expected_participant_sections_created + $activity_sections->count();
        }
        $this->assertEquals($expected_participant_sections_created, $created_participant_sections->count());
    }

    /**
     * Setup participant_data.
     *
     * @return collection
     */
    private function setup_participant_instances(): collection {
        $user_assignments = $this->get_track_user_assignments();
        $subject_instances = new collection();
        $participant_instance_dto_list = new collection();

        foreach ($user_assignments as $user_assignment) {
            $subject_instance = new subject_instance();
            $subject_instance->track_user_assignment_id = $user_assignment->id;
            $subject_instance->subject_user_id = $user_assignment->subject_user_id;
            $subject_instance->save();
            $subject_instances->append(subject_instance_dto::create_from_entity($subject_instance));
        }

        //create participant instances.
        foreach ($subject_instances as $subject_instance) {
            foreach ($this->activity_trees[$subject_instance->activity_id]->section_relationships as $section_relationship) {
                /** @var core_relationship_model $core_relationship */
                $core_relationship = $section_relationship->get_core_relationship();
                $participants = $core_relationship->get_users(
                    [ 'user_id' => $subject_instance->subject_user_id ],
                    context_user::instance($subject_instance->subject_user_id)
                );

                foreach ($participants as $participant) {
                    $participant_instance = new participant_instance();
                    $participant_instance->subject_instance_id = $subject_instance->id;
                    $participant_instance->participant_source = participant_source::INTERNAL;
                    $participant_instance->participant_id = $participant->get_user_id();
                    $participant_instance->core_relationship_id = $section_relationship->core_relationship_id;
                    $participant_instance->progress = not_started::get_code();
                    $participant_instance->save();
                    $participant_instance_dto = participant_instance_dto::create_from_data(
                        [
                            'activity_id' => $subject_instance->activity_id,
                            'core_relationship_id' => $participant_instance->core_relationship_id,
                            'id' => $participant_instance->id,
                        ]
                    );
                    $participant_instance_dto_list->append($participant_instance_dto);
                }
            }
        }

        return $participant_instance_dto_list;
    }

    /**
     * Setup test pre-conditions.
     */
    protected function setUp(): void {
        $this->setAdminUser();
        $this->activity_trees = [];
    }

    /**
     * Setup activity with multiple sections.
     *
     * @return stdClass
     */
    private function setup_activity_with_multiple_sections(): stdClass {
        /** @var \mod_perform\testing\generator $generator */
        $generator = $this->get_perform_generator();

        $activity_tree = new stdClass();
        $activity_tree->identifier = 0;
        $activity_tree->activity = $generator->create_activity_in_container();
        $activity_tree->track = $this->create_activity_track($activity_tree->activity, 1);

        //create sections and add relationships to activity:
        $activity_tree->section_relationships = [];
        $activity_tree->subject_user_section = $generator->create_section(
            $activity_tree->activity,
            ['title' => 'Test activity section 1']
        );
        $activity_tree->section_relationships[] = $generator->create_section_relationship(
            $activity_tree->subject_user_section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT]
        );

        $activity_tree->manager_appraiser_user_section = $generator->create_section(
            $activity_tree->activity,
            ['title' => 'Test activity section 2']
        );
        $activity_tree->section_relationships[] = $generator->create_section_relationship(
            $activity_tree->manager_appraiser_user_section,
            ['relationship' => constants::RELATIONSHIP_APPRAISER]
        );
        $activity_tree->section_relationships[] = $generator->create_section_relationship(
            $activity_tree->manager_appraiser_user_section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );

        $this->setup_job_assignments($activity_tree, 1);
        return $activity_tree;
    }

    /**
     * Setup multiple activities.
     *
     * @return void
     */
    private function setup_multiple_activities(): void {
        $activity_count = 4;
        $users_per_relationship = 2;
        for ($i = 0; $i < $activity_count; $i++) {
            $activity_tree = $this->setup_activity($i);
            $activity_tree->identifier = $i;
            $this->setup_job_assignments($activity_tree, $users_per_relationship);
            $this->activity_trees[$activity_tree->activity->id] = $activity_tree;
        }
    }

    /**
     * Creates an activity track with the specified number of users.
     *
     * @param activity $activity
     * @param int $user_count
     * @return track
     */
    private function create_activity_track(activity $activity, int $user_count = 4): track {
        $generator = $this->get_perform_generator();
        /** @var track $track */
        $tracks = $generator->create_activity_tracks($activity);

        return $generator->create_track_assignments($tracks->first(), 0, 0, 0, $user_count);
    }

    /**
     * Setup activity details.
     *
     * @param int $identifier For number of activities to create.
     * @return stdClass
     */
    private function setup_activity(int $identifier): stdClass {
        $generator = $this->get_perform_generator();

        $activity_tree = new stdClass();
        $activity_tree->activity = $generator->create_activity_in_container(['create_section' => false]);
        $activity_tree->track = $this->create_activity_track($activity_tree->activity, 4);

        //create sections and add relationships to activity:
        $activity_tree->section = $generator->create_section(
            $activity_tree->activity,
            [
                'title' => 'Test activity section '. $identifier,
            ]
        );
        $activity_tree->section_relationships = [];
        $activity_tree->section_relationships[] = $generator->create_section_relationship(
            $activity_tree->section,
            ['relationship' => constants::RELATIONSHIP_APPRAISER]
        );
        $activity_tree->section_relationships[] = $generator->create_section_relationship(
            $activity_tree->section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );

        return $activity_tree;
    }

    /**
     * Sets up job assignments and returns stats of job assignments created.
     *
     * @param stdClass $activity_tree
     * @param int $users_per_relationship Number of users to each relationship should have.
     * @return void
     */
    private function setup_job_assignments($activity_tree, int $users_per_relationship): void {
        $job_assignment_data = [];

        for ($i = 0; $i < $users_per_relationship; $i++) {
            $manager = $this->getDataGenerator()->create_user();
            $appraiser = $this->getDataGenerator()->create_user();

            $job_assignment_data[] = [
                'manager_ja_id' => job_assignment::create_default($manager->id)->id,
                'appraiser_id' => $appraiser->id,
            ];
        }

        foreach ($activity_tree->track->assignments as $assignment) {
            foreach ($job_assignment_data as $key => $job_assignment_datum) {
                job_assignment::create(
                    [
                        'userid' => $assignment->user_group_id,
                        'idnumber' => $assignment->id . $key . $activity_tree->identifier,
                        'managerjaid' => $job_assignment_datum['manager_ja_id'],
                        'appraiserid' => $job_assignment_datum['appraiser_id'],
                    ]
                );
            }
        }
        expand_task::create()->expand_all();
    }

    /**
     * Gets loaded user assignments before subject instances are created.
     *
     * @return collection
     */
    private function get_track_user_assignments(): collection {
        return track_user_assignment::repository()
            ->filter_by_no_subject_instances()
            ->filter_by_active()
            ->filter_by_active_track_and_activity()
            ->with('track')
            ->get();
    }

    /**
     * Create subject instances for the user assignments.
     *
     * @return collection
     */
    private function create_subject_instances(): collection {
        $user_assignments = $this->get_track_user_assignments();
        $subject_instances = new collection();

        foreach ($user_assignments as $user_assignment) {
            $subject_instance = new subject_instance();
            $subject_instance->track_user_assignment_id = $user_assignment->id;
            $subject_instance->subject_user_id = $user_assignment->subject_user_id;
            $subject_instance->save();
            $subject_instances->append(subject_instance_dto::create_from_entity($subject_instance));
        }
        return $subject_instances;
    }

    /**
     * Cleans class properties.
     */
    protected function tearDown(): void {
        $this->activity_trees = null;
    }

    /**
     * Get mod_perform data generator.
     *
     * @return \mod_perform\testing\generator
     */
    private function get_perform_generator(): \mod_perform\testing\generator {
        return \mod_perform\testing\generator::instance();
    }
}
