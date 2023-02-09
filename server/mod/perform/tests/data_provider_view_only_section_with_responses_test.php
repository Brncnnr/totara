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
 * @category test
 */

use core\collection;
use core\entity\user;
use mod_perform\constants;
use mod_perform\data_providers\response\view_only_section_with_responses;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section;
use mod_perform\models\activity\derived_responses_element_plugin;
use mod_perform\models\activity\section as section_model;
use mod_perform\entity\activity\section_element;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_instance;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\response\responder_group;
use mod_perform\models\response\section_element_response;
use mod_perform\models\response\view_only_element_response;
use mod_perform\state\participant_section\complete;
use mod_perform\testing\activity_generator_configuration;
use mod_perform\testing\generator as perform_generator;
use performelement_aggregation\aggregation;
use performelement_aggregation\calculations\average;

/**
 * @group perform
 */
class data_provider_view_only_section_with_responses_testcase extends advanced_testcase {

    public function test_get_unanswered(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();

        $subject_instance = $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
            'include_questions' => true,
        ]);

        /** @var section $section */
        $section = section::repository()->one(true);

        $data_provider = new view_only_section_with_responses($section, $subject_instance);

        $responses = $data_provider->fetch()->get()->get_section_element_responses();

        self::assertCount(2, $responses);

        foreach ($responses as $response) {
            self::assertInstanceOf(view_only_element_response::class, $response);

            $responder_groups = $response->get_other_responder_groups();

            self::assertCount(2, $responder_groups);

            $subject_responder_group = $this->get_subject_responder_group($responder_groups);
            self::assertCount(1, $subject_responder_group->get_responses());

            /** @var section_element_response $subject_response */
            $subject_response = $subject_responder_group->get_responses()->first();
            self::assertInstanceOf(section_element_response::class, $subject_response);
            self::assertNull($subject_response->get_response_data());

            $manager_responder_group = $this->get_manager_responder_group($responder_groups);
            self::assertCount(1, $manager_responder_group->get_responses());

            /** @var section_element_response $subject_response */
            $manager_response = $manager_responder_group->get_responses()->first();
            self::assertInstanceOf(section_element_response::class, $manager_response);
            self::assertNull($manager_response->get_response_data());
        }
    }

    /**
     * @dataProvider  get_answer_status_provider
     * @param bool $subject_complete
     * @throws coding_exception
     */
    public function test_get_answered(bool $subject_complete): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();
        $manager = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => $manager->id,
            'include_questions' => true,
        ]);

        $generator->create_aggregation_in_activity($subject_instance->activity()->id,
            1,
            [1],
            [
                constants::RELATIONSHIP_SUBJECT => [50],
                constants::RELATIONSHIP_MANAGER => [100],
            ], $subject_complete);

        /** @var section $section */
        $section = section::repository()->one(true);

        $responses = (new view_only_section_with_responses($section, $subject_instance))->fetch()->get()->get_section_element_responses();
        self::assertCount(4, $responses);

        /** @var participant_instance_entity $subject_participant_instance */
        $subject_participant_instance = participant_instance_entity::repository()->where('participant_id', $subject->id)->one();

        // Set answers on each question for the subjects participant instance.
        foreach ($responses->all(false) as $question_number => $response) {
            $section_element_response = $this->section_element_response_from_view_only(
                $response,
                $subject_participant_instance
            );

            $element_plugin = $section_element_response->get_element()->get_element_plugin();

            if (!$element_plugin instanceof derived_responses_element_plugin && !$element_plugin->get_is_aggregatable()) {
                $section_element_response->set_response_data('answer: ' . $question_number);
                $section_element_response->save();
            }
        }

        if ($subject_complete) {
            $this->complete_sections_for_user($subject);
        }

        $responses = (new view_only_section_with_responses($section, $subject_instance))->fetch()->get()->get_section_element_responses();
        self::assertCount(4, $responses);

        // Should be an answer on each question.
        foreach ($responses->all(false) as $question_number => $response) {
            $subject_responder_group = $this->get_subject_responder_group($response->get_other_responder_groups());
            $manager_responder_group = $this->get_manager_responder_group($response->get_other_responder_groups());

            /** @var section_element_response $manager_response */
            $manager_response = $manager_responder_group->get_responses()->first();

            /** @var section_element_response $subject_response */
            $subject_response = $subject_responder_group->get_responses()->first();

            if ($subject_complete) {
                $element_plugin = $subject_response->get_element()->get_element_plugin();

                if ($element_plugin instanceof derived_responses_element_plugin) {
                    self::assertEquals([average::get_name() => 50.00], json_decode($subject_response->get_response_data(), true, 512, JSON_THROW_ON_ERROR));
                    self::assertEquals([average::get_name() => 100.00], json_decode($manager_response->get_response_data(), true, 512, JSON_THROW_ON_ERROR));
                } else if ($element_plugin->get_is_aggregatable()) {
                    self::assertEquals(50, json_decode($subject_response->get_response_data(), true, 512, JSON_THROW_ON_ERROR));
                    self::assertEquals(100, json_decode($manager_response->get_response_data(), true, 512, JSON_THROW_ON_ERROR));
                } else {
                    self::assertEquals('answer: ' . $question_number, $subject_response->get_response_data());
                    self::assertNull($manager_response->get_response_data());
                }
            } else {
                self::assertNull($subject_response->get_response_data());
                self::assertNull($manager_response->get_response_data());
            }
        }
    }

    public function test_non_respondable_elements_do_not_include_other_responder_groups(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();
        $manager = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => $manager->id,
            'include_questions' => true,
        ]);

        /** @var section $section */
        $section = section::repository()->one(true);

        $static_element = $generator->create_element(['title' => 'Static element', 'plugin_name' => 'static_content']);
        $generator->create_section_element(new section_model($section), $static_element, 3);

        $data_provider = new view_only_section_with_responses($section, $subject_instance);

        /** @var view_only_element_response[] $responses */
        $responses = $data_provider->fetch()->get()->get_section_element_responses()->all(false);
        self::assertCount(3, $responses);

        // The question elements keep their subject and manager responder groups.
        self::assertTrue($responses[0]->get_element()->is_respondable);
        self::assertCount(2, $responses[0]->get_other_responder_groups());

        self::assertTrue($responses[1]->get_element()->is_respondable);
        self::assertCount(2, $responses[1]->get_other_responder_groups());

        // The static element should not have any other responder groups
        self::assertFalse($responses[2]->get_element()->is_respondable);
        self::assertCount(
            0, $responses[2]->get_other_responder_groups(),
            'Expected static element to have no responder groups'
        );
    }

    public function test_responses_are_ordered_by_element_sort_order(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();

        $subject_instance = $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
            'include_questions' => true,
        ]);

        /** @var section $section */
        $section = section::repository()->one(true);

        $data_provider = new view_only_section_with_responses($section, $subject_instance);

        /** @var collection|view_only_element_response[] $responses */
        $responses = $data_provider->fetch()->get()->get_section_element_responses()->all(false);

        self::assertCount(2, $responses);

        self::assertEquals(1, $responses[0]->section_element->sort_order);
        self::assertEquals('Question one', $responses[0]->section_element->element->title);

        self::assertEquals(2, $responses[1]->section_element->sort_order);
        self::assertEquals('Question two', $responses[1]->section_element->element->title);

        // Now swap "Question one" to actually be last, sort order should be respected.

        /** @var section_element $question_two */
        $question_two = section_element::repository()->find($responses[0]->section_element->id);
        $question_two->sort_order = 3;
        $question_two->save();

        /** @var collection|view_only_element_response[] $responses */
        $responses = $data_provider->fetch()->get()->get_section_element_responses()->all(false);

        self::assertCount(2, $responses);

        self::assertEquals(2, $responses[0]->section_element->sort_order);
        self::assertEquals('Question two', $responses[0]->section_element->element->title);

        self::assertEquals(3, $responses[1]->section_element->sort_order);
        self::assertEquals('Question one', $responses[1]->section_element->element->title);
    }

    public function get_answer_status_provider(): array {
        return [
            'Subject answered as draft' => [false],
            'Subject answered as complete' => [true],
        ];
    }

    /**
     * @param int $section_sort_order
     * @dataProvider sibling_population_provider
     */
    public function test_sibling_section_population(int $section_sort_order): void {
        self::setAdminUser();

        $generator = perform_generator::instance();

        $config = new activity_generator_configuration();
        $config->set_number_of_sections_per_activity(3);

        $generator->create_full_activities($config);

        /** @var section $section */
        $section = section::repository()->where('sort_order', $section_sort_order)->one();

        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()->order_by('id')->first();

        $data_provider = new view_only_section_with_responses($section, $subject_instance);

        $view_only_section = $data_provider->fetch()->get();

        $siblings = $view_only_section->get_siblings();

        $actual_section_values = $siblings->map(
            function (section_model $section) {
                return [
                    'sort_order' => $section->sort_order,
                    'activity_id' => $section->activity_id,
                    'title' => $section->title,
                ];
            }
        )->to_array();

        $section_title = 'activity ' . $section->activity_id . ' section ';

        $expected_section_values = [
            [
                'sort_order' => 1,
                'activity_id' => $section->activity_id,
                'title' => $section_title . 0
            ],
            [
                'sort_order' => 2,
                'activity_id' => $section->activity_id,
                'title' => $section_title . 1
            ],
            [
                'sort_order' => 3,
                'activity_id' => $section->activity_id,
                'title' => $section_title . 2
            ]
        ];

        // Order matters here, it should be based on sort order.
        self::assertEquals($expected_section_values, $actual_section_values);
    }

    public function sibling_population_provider(): array {
        return [
            'First section' => [1],
            'Second section' => [2],
            'Last section' => [3],
        ];
    }

    /**
     * This covers the case where someone has one, none, or multiple job assignments so they can have any combinations
     * of managers or appraisers.
     *
     * @param int $expected_manager_count
     * @param int $expected_appraiser_count
     * @param string[] $relationships
     * @dataProvider responder_group_population_provider
     */
    public function test_responder_group_population_for_subject(
        int $expected_manager_count,
        int $expected_appraiser_count,
        array $relationships
    ): void {
        self::setAdminUser();

        $generator = perform_generator::instance();

        $subject_user = user::logged_in();
        $subject_user_id = $subject_user->id;

        $subject_instance = $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject_user_id,
            'other_participant_id' => null,
            'include_questions' => false,
        ]);

        $activity = new activity($subject_instance->activity());

        $section = new section_model(section::repository()->one(true));

        // Always create both the manager and appraiser section_relationships
        $manager_section_relationship = $generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );

        $appraiser_section_relationship = $generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_APPRAISER]
        );

        $subject_section_relationship = $generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT]
        );

        $element = $generator->create_element(['title' => 'Question one']);
        $generator->create_section_element($section, $element);

        foreach ($relationships as $relationship_class_name) {
            if ($relationship_class_name === constants::RELATIONSHIP_MANAGER) {
                $core_relationship_id = $manager_section_relationship->core_relationship_id;
            } else {
                $core_relationship_id = $appraiser_section_relationship->core_relationship_id;
            }

            $participant_user = self::getDataGenerator()->create_user();
            $generator->create_participant_instance_and_section(
                $activity,
                $participant_user,
                $subject_instance->id,
                $section,
                $core_relationship_id
            );
        }

        $generator->create_participant_instance_and_section(
            $activity,
            $subject_user->to_record(),
            $subject_instance->id,
            $section,
            $subject_section_relationship->core_relationship_id
        );

        /** @var section $section */
        $section = section::repository()->one(true);

        $data_provider = new view_only_section_with_responses($section, $subject_instance);

        /** @var view_only_element_response $first_element_response */
        $first_element_response = $data_provider->fetch()->get()->get_section_element_responses()->first();
        $responder_groups = $first_element_response->get_other_responder_groups();

        $subject_responder_group = $this->get_subject_responder_group($responder_groups);
        $manager_responder_group = $this->get_manager_responder_group($responder_groups);
        $appraiser_responder_group = $this->get_appraiser_responder_group($responder_groups);

        self::assertEquals('Subject', $subject_responder_group->get_relationship_name());
        self::assertEquals('Manager', $manager_responder_group->get_relationship_name());
        self::assertEquals('Appraiser', $appraiser_responder_group->get_relationship_name());

        // There should always be three groups.
        self::assertCount(3, $first_element_response->get_other_responder_groups());

        // Note these are all empty responses.
        self::assertCount(1, $subject_responder_group->get_responses());
        self::assertCount($expected_manager_count, $manager_responder_group->get_responses());
        self::assertCount($expected_appraiser_count, $appraiser_responder_group->get_responses());
    }

    public function responder_group_population_provider(): array {
        return [
            'Two managers, one appraisers' => [
                2,
                1,
                [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER]
            ],
            'Two appraisers, one managers' => [
                1,
                2,
                [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER, constants::RELATIONSHIP_APPRAISER]
            ],
            'One manager, no appraiser' => [1, 0, [constants::RELATIONSHIP_MANAGER]],
            'No manager, no appraiser' => [0, 0, []],
        ];
    }

    /**
     * This simulates the case where within one job assignment a user has the same manager and appraiser.
     */
    public function test_responder_group_population_same_user_is_manager_and_appraiser(): void {
        self::setAdminUser();

        $generator = perform_generator::instance();

        $subject_user = user::logged_in();
        $subject_user_id = $subject_user->id;
        $manager_appraiser_user = self::getDataGenerator()->create_user();

        $participant_section = $generator->create_section_with_combined_manager_appraiser(
            $subject_user,
            $manager_appraiser_user
        )[0];

        /** @var section $section */
        $section = section::repository()->one(true);

        $data_provider = new view_only_section_with_responses(
            $section,
            $participant_section->participant_instance->subject_instance
        );

        /** @var view_only_element_response $first_element_response */
        $first_element_response = $data_provider->fetch()->get()->get_section_element_responses()->first();

        $manager_responder_group = $this->get_manager_responder_group($first_element_response->get_other_responder_groups());
        $appraiser_responder_group = $this->get_appraiser_responder_group($first_element_response->get_other_responder_groups());

        // There should always be two groups, the manager and appraiser group.
        self::assertCount(3, $first_element_response->get_other_responder_groups());

        // Note these are all empty responses.
        self::assertCount(1, $manager_responder_group->get_responses());
        self::assertCount(1, $appraiser_responder_group->get_responses());

        /** @var participant_instance $manager_participant_instance */
        $manager_participant_instance = $manager_responder_group->get_responses()->first()->get_participant_instance();

        /** @var participant_instance $appraiser_participant_instance */
        $appraiser_participant_instance = $appraiser_responder_group->get_responses()->first()->get_participant_instance();

        self::assertEquals($manager_appraiser_user->id, $manager_participant_instance->participant_id);
        self::assertEquals($manager_appraiser_user->id, $appraiser_participant_instance->participant_id);

        self::assertNotEquals(
            $manager_participant_instance->get_id(),
            $appraiser_participant_instance->get_id(),
            'Manager and appraiser relationship should have separate participant instances'
        );
    }

    public function test_responder_group_population_for_anonymous_activity(): void {
        self::setAdminUser();

        $generator = perform_generator::instance();

        $subject_user = user::logged_in();
        $manager_appraiser_user = self::getDataGenerator()->create_user();

        $subject_instance = $generator->create_subject_instance(
            [
                'activity_name' => 'anonymous activity',
                'subject_is_participating' => false, // The subject actually is participating, but we will create the instance below.
                'subject_user_id' => $subject_user->id,
                'other_participant_id' => null,
                'include_questions' => false,
                'anonymous_responses' => 'true',
            ]
        );

        $activity = new activity($subject_instance->activity());
        $section = $generator->create_section($activity, ['title' => 'Part one']);

        $manager_section_relationship = $generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );
        $appraiser_section_relationship = $generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_APPRAISER]
        );
        $subject_section_relationship = $generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT]
        );

        $element = $generator->create_element(['title' => 'Question one']);
        $generator->create_section_element($section, $element);

        $generator->create_participant_instance_and_section(
            $activity,
            $manager_appraiser_user,
            $subject_instance->id,
            $section,
            $manager_section_relationship->core_relationship_id
        );

        $generator->create_participant_instance_and_section(
            $activity,
            $manager_appraiser_user,
            $subject_instance->id,
            $section,
            $appraiser_section_relationship->core_relationship_id
        );

        $generator->create_participant_instance_and_section(
            $activity,
            $subject_user,
            $subject_instance->id,
            $section,
            $subject_section_relationship->core_relationship_id
        );

        $generator->create_aggregation_in_activity($subject_instance->activity()->id,
            1,
            [1],
            [
                constants::RELATIONSHIP_SUBJECT => [null],
                constants::RELATIONSHIP_MANAGER => [50],
                constants::RELATIONSHIP_APPRAISER => [100],
            ]);

        /** @var section $section */
        $section = section::repository()->one(true);

        $data_provider = new view_only_section_with_responses($section, $subject_instance);

        /** @var view_only_element_response $first_element_response */
        $first_element_response = $data_provider->fetch()->get()->get_section_element_responses()->first();

        $subject_responder_group = $this->get_manager_responder_group($first_element_response->get_other_responder_groups());
        $manager_responder_group = $this->get_manager_responder_group($first_element_response->get_other_responder_groups());
        $appraiser_responder_group = $this->get_appraiser_responder_group($first_element_response->get_other_responder_groups());
        $anonymous_responder_group = $this->get_anonymous_responder_group($first_element_response->get_other_responder_groups());

        // There should always one group.
        self::assertCount(1, $first_element_response->get_other_responder_groups());

        // No specific groups.
        self::assertNull($subject_responder_group);
        self::assertNull($manager_responder_group);
        self::assertNull($appraiser_responder_group);

        // anonymous group contains all data
        self::assertCount(3, $anonymous_responder_group->get_responses());

        /** @var view_only_element_response $aggregate_element_response */
        $aggregate_element_response = $data_provider->fetch()->get()->get_section_element_responses()->last();

        self::assertInstanceOf(aggregation::class, $aggregate_element_response->get_element()->get_element_plugin());

        $subject_responder_group = $this->get_manager_responder_group($aggregate_element_response->get_other_responder_groups());
        $manager_responder_group = $this->get_manager_responder_group($aggregate_element_response->get_other_responder_groups());
        $appraiser_responder_group = $this->get_appraiser_responder_group($aggregate_element_response->get_other_responder_groups());
        $anonymous_responder_group = $this->get_anonymous_responder_group($aggregate_element_response->get_other_responder_groups());

        // No specific groups.
        self::assertNull($subject_responder_group);
        self::assertNull($manager_responder_group);
        self::assertNull($appraiser_responder_group);

        // anonymous group contains all data
        self::assertCount(3, $anonymous_responder_group->get_responses());
    }

    /**
     * @param view_only_element_response $response
     * @param participant_instance_entity $subject_participant_instance
     * @return section_element_response
     * @throws coding_exception
     */
    private function section_element_response_from_view_only(
        view_only_element_response $response,
        participant_instance_entity $subject_participant_instance
    ): section_element_response {
        $section_element_entity = new section_element($response->get_section_element_id());

        return new section_element_response(
            new participant_instance($subject_participant_instance),
            new \mod_perform\models\activity\section_element($section_element_entity),
            null,
            new collection()
        );
    }

    /**
     * @param $responder_groups
     * @return responder_group
     */
    private function get_subject_responder_group($responder_groups): responder_group {
        return $responder_groups->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Subject';
        });
    }

    /**
     * @param $responder_groups
     * @return responder_group
     */
    private function get_manager_responder_group($responder_groups): ?responder_group {
        return $responder_groups->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Manager';
        });
    }

    /**
     * @param $responder_groups
     * @return responder_group
     */
    private function get_appraiser_responder_group($responder_groups): ?responder_group {
        return $responder_groups->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Appraiser';
        });
    }

    /**
     * @param $responder_groups
     * @return responder_group
     */
    private function get_anonymous_responder_group($responder_groups): ?responder_group {
        return $responder_groups->find(
            function (responder_group $group) {
                return $group->get_relationship_name() === get_string('anonymous_group_relationship_name', 'mod_perform');
            }
        );
    }

    /**
     * @param stdClass $user
     * @throws coding_exception
     */
    private function complete_sections_for_user(stdClass $user): void {
        /** @var participant_section $participant_section */
        $participant_sections = participant_section::repository()
            ->as('ps')
            ->join([participant_instance_entity::TABLE, 'pi'], 'pi.id', 'ps.participant_instance_id')
            ->where('pi.participant_id', $user->id)
            ->get();


        foreach ($participant_sections as $participant_section) {
            $participant_section->progress = complete::get_code();
            $participant_section->save();
        }
    }

}