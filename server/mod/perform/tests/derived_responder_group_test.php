<?php

use core\collection;
use core\entity\user;
use mod_perform\constants;
use mod_perform\data_providers\response\derived_responder_group;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\section;
use mod_perform\models\response\participant_section;
use mod_perform\models\response\responder_group;
use mod_perform\state\activity\active;
use mod_perform\testing\activity_generator_configuration;
use mod_perform\testing\generator as perform_generator;
use performelement_aggregation\aggregation;
use performelement_aggregation\calculations\average;

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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 * @category test
 * @group perform
 */
class mod_perform_data_provider_derived_responder_group_testcase extends advanced_testcase {

    private const SUBJECT_AVG = 100.00;
    private const MANAGER_AVG = 300.00;

    public function test_relationships_are_in_every_section(): void {
        // Subject respond to every section, manager responds to section 1 and 2, appraiser does not respond.
        [
            $aggregation_section,
            $subject_participant_section,
            $manager_participant_section,
            $appraiser_participant_section,
            $aggregation_section_element
        ] = $this->create_test_data($this->get_same_relationships_config());

        // When viewing as the subject.
        $data_provider = derived_responder_group::for_participant_section($subject_participant_section, false);
        self::assertTrue($data_provider->viewing_participant_has_source_relationship($aggregation_section_element));

        $responder_groups = $data_provider->build_for($aggregation_section_element);
        self::assertCount(2, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Manager', 0, [$manager_participant_section->participant_instance_id], self::MANAGER_AVG);
        self::assert_responder_group($responder_groups, 'Appraiser', 1, [$appraiser_participant_section->participant_instance_id], null);


        // When viewing as the manager.
        $data_provider = derived_responder_group::for_participant_section($manager_participant_section, false);
        self::assertTrue($data_provider->viewing_participant_has_source_relationship($aggregation_section_element));

        $responder_groups = $data_provider->build_for($aggregation_section_element);
        self::assertCount(2, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Subject', 0, [$subject_participant_section->participant_instance_id], self::SUBJECT_AVG);
        self::assert_responder_group($responder_groups, 'Appraiser', 1, [$appraiser_participant_section->participant_instance_id], null);


        // When viewing as the appraiser.
        $data_provider = derived_responder_group::for_participant_section($appraiser_participant_section, false);
        self::assertTrue($data_provider->viewing_participant_has_source_relationship($aggregation_section_element));

        $responder_groups = $data_provider->build_for($aggregation_section_element);
        self::assertCount(2, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Subject', 0, [$subject_participant_section->participant_instance_id], self::SUBJECT_AVG);
        self::assert_responder_group($responder_groups, 'Manager', 1, [$manager_participant_section->participant_instance_id], self::MANAGER_AVG);


        // When viewing none of the participants.
        $data_provider = derived_responder_group::for_view_only_section(
            $aggregation_section,
            $subject_participant_section->participant_instance->subject_instance,
            false
        );
        self::assertFalse($data_provider->viewing_participant_has_source_relationship($aggregation_section_element));

        $responder_groups = $data_provider->build_for($aggregation_section_element);
        self::assertCount(3, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Subject', 0, [$subject_participant_section->participant_instance_id], self::SUBJECT_AVG);
        self::assert_responder_group($responder_groups, 'Manager', 1, [$manager_participant_section->participant_instance_id], self::MANAGER_AVG);
        self::assert_responder_group($responder_groups, 'Appraiser', 2, [$appraiser_participant_section->participant_instance_id], null);
    }

    public function test_relationships_are_in_every_section_anonymous(): void {
        // Subject respond to every section, manager responds to section 0 and 1, appraiser does not respond.
        [
            $aggregation_section,
            $subject_participant_section,
            $manager_participant_section,
            $appraiser_participant_section,
            $aggregation_section_element
        ] = $this->create_test_data($this->get_same_relationships_config());

        // When viewing as the subject.
        $responder_groups = derived_responder_group::for_participant_section($subject_participant_section, true)
            ->build_for($aggregation_section_element);

        self::assertCount(1, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Anonymous', 0, [
            $manager_participant_section->participant_instance_id,
            $appraiser_participant_section->participant_instance_id
        ], self::MANAGER_AVG, null);

        // When viewing as the manager.
        $responder_groups = derived_responder_group::for_participant_section($manager_participant_section, true)
            ->build_for($aggregation_section_element);

        self::assertCount(1, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Anonymous', 0, [
            $subject_participant_section->participant_instance_id,
            $appraiser_participant_section->participant_instance_id
        ], self::SUBJECT_AVG, null);

        // When viewing as the appraiser.
        $responder_groups = derived_responder_group::for_participant_section($appraiser_participant_section, true)
            ->build_for($aggregation_section_element);

        self::assertCount(1, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Anonymous', 0, [
            $subject_participant_section->participant_instance_id,
            $manager_participant_section->participant_instance_id
        ], self::SUBJECT_AVG, self::MANAGER_AVG);

        // When viewing none of the participants.
        $responder_groups = derived_responder_group::for_view_only_section(
            $aggregation_section,
            $subject_participant_section->participant_instance->subject_instance,
            true
        )->build_for($aggregation_section_element);

        self::assertCount(1, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Anonymous', 0, [
            $subject_participant_section->participant_instance_id,
            $manager_participant_section->participant_instance_id,
            $appraiser_participant_section->participant_instance_id
        ], self::SUBJECT_AVG, self::MANAGER_AVG, null);
    }

    public function test_disparate_relationships(): void {
        // The subject responds to section 0, the manager to section 1, and the appraiser is a participant in section 2 but does not respond.
        [
            $aggregation_section,
            $subject_participant_section, // Null, they do not have participant section for section 2.
            $manager_participant_section, // Null, they do not have participant section for section 2.
            $appraiser_participant_section,
            $aggregation_section_element
        ] = $this->create_test_data($this->get_disparate_relationships_config());

        // When viewing as the appraiser.
        $data_provider = derived_responder_group::for_participant_section($appraiser_participant_section, false);
        self::assertTrue($data_provider->viewing_participant_has_source_relationship($aggregation_section_element));

        $responder_groups = $data_provider->build_for($aggregation_section_element);
        self::assertCount(2, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Subject', 0, null, self::SUBJECT_AVG);
        self::assert_responder_group($responder_groups, 'Manager', 1, null, self::MANAGER_AVG);

        // When viewing none of the participants.
        $responder_groups = derived_responder_group::for_view_only_section(
            $aggregation_section,
            $appraiser_participant_section->participant_instance->subject_instance,
            false
        )->build_for($aggregation_section_element);

        self::assertCount(3, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Subject', 0, null, self::SUBJECT_AVG);
        self::assert_responder_group($responder_groups, 'Manager', 1, null, self::MANAGER_AVG);
        self::assert_responder_group($responder_groups, 'Appraiser', 2, [$appraiser_participant_section->participant_instance_id], null);
    }

    public function test_disparate_relationships_anonymous(): void {
        // The subject responds to section 0, the manager to section 1, and the appraiser is a participant in section 2 but does not respond.
        [
            $aggregation_section,
            $subject_participant_section, // Null, they do not have participant section for section 2.
            $manager_participant_section, // Null, they do not have participant section for section 2.
            $appraiser_participant_section,
            $aggregation_section_element
        ] = $this->create_test_data($this->get_disparate_relationships_config());

        // When viewing as the appraiser.
        $data_provider = derived_responder_group::for_participant_section($appraiser_participant_section, true);
        self::assertTrue($data_provider->viewing_participant_has_source_relationship($aggregation_section_element));

        $responder_groups = $data_provider->build_for($aggregation_section_element);
        self::assertCount(1, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Anonymous', 0, null, self::SUBJECT_AVG, self::MANAGER_AVG);

        // When viewing none of the participants.
        $responder_groups = derived_responder_group::for_view_only_section(
            $aggregation_section,
            $appraiser_participant_section->participant_instance->subject_instance,
            true
        )->build_for($aggregation_section_element);

        self::assertCount(1, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Anonymous', 0, null, self::SUBJECT_AVG, self::MANAGER_AVG, null);
    }

    public function test_viewing_participant_is_not_in_any_source_sections(): void {
        // The subject responds to section 0, the manager to section 1, and the appraiser is a participant in section 2 but does not respond.
        [
            $aggregation_section,
            $subject_participant_section, // Null, they do not have participant section for section 2.
            $manager_participant_section, // Null, they do not have participant section for section 2.
            $appraiser_participant_section,
            $aggregation_section_element
        ] = $this->create_test_data($this->get_subject_not_in_aggregation_display_section());

        // When viewing as the appraiser.
        $data_provider = derived_responder_group::for_participant_section($subject_participant_section, false);
        self::assertFalse($data_provider->viewing_participant_has_source_relationship($aggregation_section_element));

        $responder_groups = $data_provider->build_for($aggregation_section_element);
        self::assertCount(1, $responder_groups);
        self::assert_responder_group($responder_groups, 'Manager', 0, null, self::MANAGER_AVG);
    }

    public function test_participants_not_identified(): void {
        // The subject responds to section 2, the manager and appraiser to 0 and 1..
        [
            $aggregation_section,
            $subject_participant_section,
            $manager_participant_section,
            $appraiser_participant_section,
            $aggregation_section_element
        ] = $this->create_test_data($this->get_disparate_relationships_config_no_participants_identified());

        // Simulate no subject or appraiser identified.
        (new participant_instance($manager_participant_section->participant_instance->get_id()))->delete();
        (new participant_instance($appraiser_participant_section->participant_instance->get_id()))->delete();

        // When viewing as the subject.
        $data_provider = derived_responder_group::for_participant_section($subject_participant_section, false);
        self::assertFalse($data_provider->viewing_participant_has_source_relationship($aggregation_section_element));

        $responder_groups = $data_provider->build_for($aggregation_section_element);
        self::assertCount(2, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Manager', 0);
        self::assert_responder_group($responder_groups, 'Appraiser', 1);

        // When viewing none of the participants.
        $responder_groups = derived_responder_group::for_view_only_section(
            $aggregation_section,
            $appraiser_participant_section->participant_instance->subject_instance,
            false
        )->build_for($aggregation_section_element);

        self::assertCount(2, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Manager', 0);
        self::assert_responder_group($responder_groups, 'Appraiser', 1);
    }

    public function test_multiple_participants_per_relationship(): void {
        // Subject respond to every section, manager responds to section 0 and 1, appraiser does not respond.
        [
            $aggregation_section,
            $subject_participant_section,
            $manager_participant_section,
            $appraiser_participant_section,
            $aggregation_section_element
        ] = $this->create_test_data($this->get_same_relationships_config());

        $activity = $manager_participant_section->participant_instance->subject_instance->activity;
        $generator = perform_generator::instance();

        $subject_instance_id = $appraiser_participant_section->participant_instance->subject_instance_id;
        $aggregate_display_section = $appraiser_participant_section->section;
        $appraiser_user_id = $appraiser_participant_section->participant_instance->participant_id;
        $manager_user_id = $appraiser_participant_section->participant_instance->participant_id;

        // Create a second manager from the appraiser, they are now both the appraiser and second manager.
        $second_manager_participant_section = $generator->create_participant_instance_and_section(
            $activity,
            new user($appraiser_user_id),
            $subject_instance_id,
            $aggregate_display_section,
            constants::RELATIONSHIP_MANAGER
        );

        // Create a peer with the original manager.
        $generator->create_section_relationship($aggregate_display_section, ['relationship' => constants::RELATIONSHIP_PEER]);
        $peer_participant_section = $generator->create_participant_instance_and_section(
            $activity,
            new user($manager_user_id),
            $subject_instance_id,
            $aggregate_display_section,
            constants::RELATIONSHIP_PEER
        );

        // When viewing as the subject.
        $responder_groups = derived_responder_group::for_participant_section($subject_participant_section, false)
            ->build_for($aggregation_section_element);

        self::assertCount(3, $responder_groups);
        // The groups should be in relationship source order.
        self::assert_responder_group($responder_groups, 'Manager', 0, [
            $manager_participant_section->participant_instance_id,
            $second_manager_participant_section->participant_instance_id,
        ], self::MANAGER_AVG, null);

        self::assert_responder_group($responder_groups, 'Appraiser', 1, [
            $appraiser_participant_section->participant_instance_id
        ], null);

        self::assert_responder_group($responder_groups, 'Peer', 2, [
            $peer_participant_section->participant_instance_id
        ], null);

        // When viewing as the original manager.
        $responder_groups = derived_responder_group::for_participant_section($manager_participant_section, false)
            ->build_for($aggregation_section_element);

        self::assertCount(4, $responder_groups);

        self::assert_responder_group($responder_groups, 'Subject', 0, [
            $subject_participant_section->participant_instance_id,
        ], self::SUBJECT_AVG);

        self::assert_responder_group($responder_groups, 'Manager', 1, [
            $second_manager_participant_section->participant_instance_id,
        ], null);

        self::assert_responder_group($responder_groups, 'Appraiser', 2, [
            $appraiser_participant_section->participant_instance_id
        ], null);

        self::assert_responder_group($responder_groups, 'Peer', 3, [
            $peer_participant_section->participant_instance_id
        ], null);


        // When viewing none of the participants.
        $responder_groups = derived_responder_group::for_view_only_section(
            $aggregation_section,
            $subject_participant_section->participant_instance->subject_instance,
            false
        )->build_for($aggregation_section_element);

        self::assertCount(4, $responder_groups);
        // This should be in the order of relationship id, with "5" coming first.
        self::assert_responder_group($responder_groups, 'Subject', 0, [
            $subject_participant_section->participant_instance_id
        ], self::SUBJECT_AVG);

        self::assert_responder_group($responder_groups, 'Manager', 1, [
            $manager_participant_section->participant_instance_id,
            $second_manager_participant_section->participant_instance_id,
        ], self::MANAGER_AVG, null);

        self::assert_responder_group($responder_groups, 'Appraiser', 2, [
            $appraiser_participant_section->participant_instance_id
        ], null);

        self::assert_responder_group($responder_groups, 'Peer', 3, [
            $peer_participant_section->participant_instance_id
        ], null);

        // When viewing as the subject and is anonymous true.
        $responder_groups = derived_responder_group::for_participant_section($subject_participant_section, true)
            ->build_for($aggregation_section_element);

        self::assertCount(1, $responder_groups);

        self::assert_responder_group($responder_groups, 'Anonymous', 0, [
            $manager_participant_section->participant_instance_id,
            $second_manager_participant_section->participant_instance_id,
            $appraiser_participant_section->participant_instance_id,
            $peer_participant_section->participant_instance_id,
        ], self::MANAGER_AVG, null, null, null);
    }

    /**
     * @param collection|responder_group[] $responder_groups
     * @param string $expected_name
     * @param int $expected_index
     * @param int[] $expected_participant_instinct_ids
     * @param float|null ...$expected_averages
     */
    public static function assert_responder_group(
        collection $responder_groups,
        string $expected_name,
        int $expected_index,
        ?array $expected_participant_instinct_ids = null,
        ?float ...$expected_averages): void {
        $responder_group = $responder_groups[$expected_index] ?? null;

        if ($responder_group === null) {
            self::fail("No responder group at index {$expected_index}");
        }

        self::assertEquals($expected_name, $responder_group->get_relationship_name());
        self::assertCount(count($expected_averages), $responder_group->get_responses());

        if (count($expected_averages) === 0) {
            self::assertCount(0, $responder_group->get_responses());
            return;
        }

        foreach ($expected_averages as $i => $expected_average) {
            if ($expected_participant_instinct_ids !== null) {
                self::assertEquals($expected_participant_instinct_ids[$i], $responder_group->get_responses()[$i]->get_participant_instance()->id);
            }

            if ($expected_average === null) {
                self::assertNull($responder_group->get_responses()[$i]->response_data);
            } else {
                self::assertEquals(
                    [average::get_name() => $expected_average],
                    json_decode($responder_group->get_responses()[$i]->response_data, true, 512, JSON_THROW_ON_ERROR)
                );
            }
        }
    }

    /**
     * @param activity_generator_configuration $configuration
     * @return section[]|participant_section[]|section_element[]
     * @throws coding_exception
     */
    private function create_test_data(activity_generator_configuration $configuration): array {
        $generator = perform_generator::instance();

        self::setAdminUser();
        $activities = $generator->create_full_activities($configuration);

        /** @var activity $activity */
        $activity = $activities->first();

        /** @var section $aggregation_section */
        $aggregation_section = $activity->get_sections()->all()[2];

        /** @var participant_section $subject_participant_section */
        $subject_participant_section = $aggregation_section->get_participant_sections()->find(function (participant_section $participant_section) {
            return $participant_section->participant_instance->core_relationship->idnumber === constants::RELATIONSHIP_SUBJECT;
        });

        /** @var participant_section $subject_participant_section */
        $manager_participant_section = $aggregation_section->get_participant_sections()->find(function (participant_section $participant_section) {
            return $participant_section->participant_instance->core_relationship->idnumber === constants::RELATIONSHIP_MANAGER;
        });

        /** @var participant_section $subject_participant_section */
        $appraiser_participant_section = $aggregation_section->get_participant_sections()->find(function (participant_section $participant_section) {
            return $participant_section->participant_instance->core_relationship->idnumber === constants::RELATIONSHIP_APPRAISER;
        });

        /** @var section_element_entity $aggregation_section_element */
        $aggregation_section_element = section_element_entity::repository()
            ->join([element::TABLE, 'e'], 'element_id', 'e.id')
            ->where('e.plugin_name', aggregation::get_plugin_name())
            ->one(true);

        return [
            $aggregation_section,
            $subject_participant_section,
            $manager_participant_section,
            $appraiser_participant_section,
            new section_element($aggregation_section_element),
        ];
    }

    /**
     * Subject respond to every section, manager responds to section 1 and 2, appraiser does not respond.
     * Not responding means empty answers are submitted, rather than the section not being completed.
     *
     * @return activity_generator_configuration
     */
    private function get_same_relationships_config(): activity_generator_configuration {
        $all_relationships = [
            constants::RELATIONSHIP_SUBJECT,
            constants::RELATIONSHIP_MANAGER,
            constants::RELATIONSHIP_APPRAISER
        ];

        return activity_generator_configuration::new()->set_number_of_elements_per_section(1)
            ->set_number_of_sections_per_activity(3)
            ->set_relationships_for_section(1, $all_relationships)
            ->set_relationships_for_section(2, $all_relationships)
            ->set_relationships_for_section(3, $all_relationships)
            ->set_activity_status(active::get_code())
            ->set_number_of_users_per_user_group_type(1)
            ->enable_appraiser_for_each_subject_user()
            ->enable_manager_for_each_subject_user()
            ->add_aggregation(3, [1, 2, 3],
                [
                    constants::RELATIONSHIP_SUBJECT => [100, 110, 90], // All filled in questions.
                    constants::RELATIONSHIP_MANAGER => [200, 400, null], // A mix of filled in and skipped questions.
                    constants::RELATIONSHIP_APPRAISER => [null, null, null], // No response for any source questions.
                ]);
    }

    /**
     * The subject responds to section 1, the manager to section 2, and the appraiser is a participant in section 3 but does not respond.
     * Not responding means empty answers are submitted, rather than the section not being completed.
     *
     * @return activity_generator_configuration
     */
    private function get_disparate_relationships_config(): activity_generator_configuration {
        return activity_generator_configuration::new()->set_number_of_elements_per_section(1)
            ->set_number_of_sections_per_activity(3)
            ->set_relationships_for_section(1, [constants::RELATIONSHIP_SUBJECT])
            ->set_relationships_for_section(2, [constants::RELATIONSHIP_MANAGER])
            ->set_relationships_for_section(3, [constants::RELATIONSHIP_APPRAISER])
            ->set_activity_status(active::get_code())
            ->set_number_of_users_per_user_group_type(1)
            ->enable_appraiser_for_each_subject_user()
            ->enable_manager_for_each_subject_user()
            ->add_aggregation(3, [1, 2, 3],
                [
                    constants::RELATIONSHIP_SUBJECT => [self::SUBJECT_AVG, null, null], // Only responding to section 1.
                    constants::RELATIONSHIP_MANAGER => [null, self::MANAGER_AVG, null], // Only responding to section 2.
                    constants::RELATIONSHIP_APPRAISER => [null, null, null], // Only a part of section 3, but not responding to it.
                ]);
    }

    /**
     * The subject responds to section 1, the manager to section 2, and the appraiser is a participant in section 3 but does not respond.
     * Not responding means empty answers are submitted, rather than the section not being completed.
     *
     * @return activity_generator_configuration
     */
    private function get_subject_not_in_aggregation_display_section(): activity_generator_configuration {
        return activity_generator_configuration::new()->set_number_of_elements_per_section(1)
            ->set_number_of_sections_per_activity(3)
            ->set_relationships_for_section(1, [constants::RELATIONSHIP_MANAGER])
            ->set_relationships_for_section(2, [constants::RELATIONSHIP_MANAGER])
            ->set_relationships_for_section(3, [constants::RELATIONSHIP_SUBJECT])
            ->set_activity_status(active::get_code())
            ->set_number_of_users_per_user_group_type(1)
            ->enable_appraiser_for_each_subject_user()
            ->enable_manager_for_each_subject_user()
            ->add_aggregation(3, [1, 2],
                [
                    constants::RELATIONSHIP_MANAGER => [self::MANAGER_AVG, self::MANAGER_AVG],
                ]);
    }

    /**
     * The appraiser responds to section 3, and manager ans subject to section 2, but the aggregation element only aggregates on section 1.
     *
     * @return activity_generator_configuration
     */
    private function get_disparate_relationships_config_no_participants_identified(): activity_generator_configuration {
        return activity_generator_configuration::new()->set_number_of_elements_per_section(1)
            ->set_number_of_sections_per_activity(3)
            ->set_relationships_for_section(1, [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER])
            ->set_relationships_for_section(2, [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER, constants::RELATIONSHIP_SUBJECT])
            ->set_activity_status(active::get_code())
            ->set_number_of_users_per_user_group_type(1)
            ->enable_appraiser_for_each_subject_user()
            ->enable_manager_for_each_subject_user()
            ->add_aggregation(3, [1],
                [
                    constants::RELATIONSHIP_SUBJECT => [null],
                    constants::RELATIONSHIP_MANAGER => [null],
                    constants::RELATIONSHIP_APPRAISER => [null],
                ]);
    }

}