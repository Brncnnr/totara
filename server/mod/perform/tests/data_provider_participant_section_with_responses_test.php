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
use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\data_providers\response\participant_section_with_responses;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\settings\visibility_conditions\all_responses;
use mod_perform\models\activity\settings\visibility_conditions\none;
use mod_perform\models\activity\settings\visibility_conditions\own_response;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\models\response\participant_section;
use mod_perform\models\response\responder_group;
use mod_perform\models\response\section_element_response;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use mod_perform\state\participant_instance\closed;
use mod_perform\state\participant_instance\open;
use mod_perform\testing\activity_generator_configuration;
use mod_perform\testing\generator as perform_generator;
use performelement_aggregation\aggregation;
use performelement_aggregation\calculations\average;
use totara_core\entity\relationship;
use totara_core\relationship\relationship as core_relationship;

/**
 * @group perform
 */
class mod_perform_data_provider_participant_section_with_responses_testcase extends testcase {

    public function test_get_unanswered(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
            'include_questions' => true,
            'update_participant_sections_status' => true,
        ]);

        $participant_section = new participant_section(
            participant_section_entity::repository()
                ->with(['section_elements', 'participant_instance'])
                ->get()
                ->first()
        );

        $data_provider = new participant_section_with_responses($participant_section);

        /** @var participant_section $fetched_participant_section */
        $fetched_participant_section = $data_provider->build();

        self::assert_same_participant_section($participant_section, $fetched_participant_section);

        $responses = $fetched_participant_section->get_section_element_responses();
        self::assertCount(2, $responses);

        foreach ($responses as $response) {
            self::assertNull($response->response_data);
        }
    }

    public function test_get_answered(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();

        $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
            'include_questions' => true,
            'update_participant_sections_status' => 'complete',
        ]);

        $participant_section = new participant_section(
            participant_section_entity::repository()
                ->with(['section_elements', 'participant_instance'])
                ->get()
                ->first()
        );

        $data_provider = new participant_section_with_responses($participant_section);

        $responses = $data_provider->build()->get_section_element_responses();
        self::assertCount(2, $responses);

        // Set answers on each question.
        foreach ($responses->all(false) as $question_number => $response) {
            $response->set_response_data($question_number);
            $response->save();
        }

        $responses = $data_provider->build()->get_section_element_responses();
        self::assertCount(2, $responses);

        // Should be an answer on each question.
        foreach ($responses->all(false) as $question_number => $response) {
            self::assertEquals($question_number, $response->response_data);
        }
    }

    public function test_not_getting_draft_answers(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();

        $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
            'include_questions' => true,
            'update_participant_sections_status' => 'draft',
        ]);

        $participant_section = new participant_section(
            participant_section_entity::repository()
                ->with(['section_elements', 'participant_instance'])
                ->get()
                ->first()
        );

        $data_provider = new participant_section_with_responses($participant_section);

        $main_responses = $data_provider->build()->get_section_element_responses();
        self::assertCount(2, $main_responses);

        // Set the manager's response on each question.
        foreach ($main_responses->all(false) as $question_number => $main_response) {
            self::assertNull($main_response->response_data);

            $other_responder_groups = $main_response->get_other_responder_groups();
            self::assertCount(1, $other_responder_groups);

            /** @var responder_group $manager_response_group */
            $manager_response_group = $other_responder_groups->first();
            self::assertEquals('Manager', $manager_response_group->get_relationship_name());

            $manager_responses = $manager_response_group->get_responses();
            self::assertCount(1, $manager_responses);

            /** @var section_element_response $manager_response */
            $manager_response = $manager_responses->first();
            self::assertNull($manager_response->response_data);

            $manager_response->set_response_data($question_number);
            $manager_response->save();
        }

        $main_responses = $data_provider->build()->get_section_element_responses();
        self::assertCount(2, $main_responses);

        // Set the manager's response on each question.
        foreach ($main_responses->all(false) as $question_number => $main_response) {
            self::assertNull($main_response->response_data);

            $other_responder_groups = $main_response->get_other_responder_groups();
            self::assertCount(1, $other_responder_groups);

            /** @var responder_group $manager_response_group */
            $manager_response_group = $other_responder_groups->first();
            self::assertEquals('Manager', $manager_response_group->get_relationship_name());

            $manager_responses = $manager_response_group->get_responses();
            self::assertCount(1, $manager_responses);

            /** @var section_element_response $manager_response */
            $manager_response = $manager_responses->first();
            self::assertNull($manager_response->response_data);
        }
    }

    public function test_get_others_answered_responses(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
            'include_questions' => true,
            'update_participant_sections_status' => 'complete',
        ]);

        $participant_section = new participant_section(
            participant_section_entity::repository()
                ->with(['section_elements', 'participant_instance'])
                ->join([participant_instance_entity::TABLE, 'pi'], 'participant_instance_id', 'id')
                ->where('pi.participant_id', $subject->id)
                ->where('pi.subject_instance_id', $subject_instance->id)
                ->one()
        );

        $data_provider = new participant_section_with_responses($participant_section);

        $main_responses = $data_provider->build()->get_section_element_responses();
        self::assertCount(2, $main_responses);

        // Set the manager's response on each question.
        foreach ($main_responses->all(false) as $question_number => $main_response) {
            self::assertNull($main_response->response_data);

            $other_responder_groups = $main_response->get_other_responder_groups();
            self::assertCount(1, $other_responder_groups);

            /** @var responder_group $manager_response_group */
            $manager_response_group = $other_responder_groups->first();
            self::assertEquals('Manager', $manager_response_group->get_relationship_name());

            $manager_responses = $manager_response_group->get_responses();
            self::assertCount(1, $manager_responses);

            /** @var section_element_response $manager_response */
            $manager_response = $manager_responses->first();
            self::assertNull($manager_response->response_data);

            $manager_response->set_response_data($question_number);
            $manager_response->save();
        }

        $main_responses = $data_provider->build()->get_section_element_responses();
        self::assertCount(2, $main_responses);

        // Set the manager's response on each question.
        foreach ($main_responses->all(false) as $question_number => $main_response) {
            self::assertNull($main_response->response_data);

            $other_responder_groups = $main_response->get_other_responder_groups();
            self::assertCount(1, $other_responder_groups);

            /** @var responder_group $manager_response_group */
            $manager_response_group = $other_responder_groups->first();
            self::assertEquals('Manager', $manager_response_group->get_relationship_name());

            $manager_responses = $manager_response_group->get_responses();
            self::assertCount(1, $manager_responses);

            /** @var section_element_response $manager_response */
            $manager_response = $manager_responses->first();
            self::assertEquals($question_number, $manager_response->response_data);
        }
    }

    /**
     * This covers the case where someone has one, none, or multiple job assignments so they can have any combinations
     * of managers or appraisers.
     *
     * @param int $expected_manager_count
     * @param int $expected_appraiser_count
     * @param string[] $relationships
     * @param bool $subject_can_view_others_responses
     *
     * @throws coding_exception
     * @dataProvider responder_group_population_provider
     */
    public function test_responder_group_population_for_subject(
        int $expected_manager_count,
        int $expected_appraiser_count,
        array $relationships,
        bool $subject_can_view_others_responses = true
    ): void {
        self::setAdminUser();

        $generator = perform_generator::instance();

        $subject_user = user::logged_in();
        $subject_user_id = $subject_user->id;

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject_user_id,
            'other_participant_id' => null,
            'include_questions' => false,
            'update_participant_sections_status' => 'complete',
        ]);

        $activity = new activity($subject_instance->activity());

        $section = $generator->create_section($activity, ['title' => 'Part one']);

        // Always create both the manager and appraiser section_relationships
        $manager_section_relationship = $generator->create_section_relationship(
            $section,
            [
                'relationship' => constants::RELATIONSHIP_MANAGER
            ]
        );
        $appraiser_section_relationship = $generator->create_section_relationship(
            $section,
            [
                'relationship' => constants::RELATIONSHIP_APPRAISER
            ]
        );
        $subject_section_relationship = $generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT],
            $subject_can_view_others_responses
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

        $subject_section = $generator->create_participant_instance_and_section(
            $activity,
            $subject_user->to_record(),
            $subject_instance->id,
            $section,
            $subject_section_relationship->core_relationship_id
        );

        $data_provider = new participant_section_with_responses(participant_section::load_by_id($subject_section->id));

        /** @var section_element_response $element_response */
        $element_response = $data_provider->build()->get_section_element_responses()->first();

        static::assertEquals('Subject', $element_response->get_relationship_name());

        /** @var responder_group $manager_responder_group */
        $manager_responder_group = $element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Manager';
        });

        /** @var responder_group $appraiser_responder_group */
        $appraiser_responder_group = $element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Appraiser';
        });

        if (!$subject_can_view_others_responses) {
            self::assertCount(0, $element_response->get_other_responder_groups());
        } else {
            // There should always be two groups if the subject has visibility, the manager and appraiser group.
            self::assertCount(2, $element_response->get_other_responder_groups());

            // Note these are all empty responses.
            self::assertCount($expected_manager_count, $manager_responder_group->get_responses());
            self::assertCount($expected_appraiser_count, $appraiser_responder_group->get_responses());
        }
    }

    public function responder_group_population_provider(): array {
        return [
            'Two managers, one appraisers' => [
                2,
                1,
                [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER]
            ],
            'Two managers, one appraisers - no visibility of other responses' => [
                0,
                0,
                [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER],
                false
            ],
            'Two appraisers, one managers' => [
                1,
                2,
                [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER, constants::RELATIONSHIP_APPRAISER]
            ],
            'Two appraisers, one managers - no visibility of other responses' => [
                0,
                0,
                [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER, constants::RELATIONSHIP_APPRAISER],
                false
            ],
            'One manager, no appraiser' => [1, 0, [constants::RELATIONSHIP_MANAGER]],
            'One manager, no appraiser - no visibility of other responses' => [0, 0, [constants::RELATIONSHIP_MANAGER], false],
            'No manager, no appraiser' => [0, 0, []],
            'No manager, no appraiser  - no visibility of other responses' => [0, 0, [], false],
        ];
    }

    public function test_non_respondable_section_element_is_included(): void {
        $participant_section = $this->create_participant_section_with_respondable_and_non_respondable_elements();
        $elements_in_section = $participant_section->section->get_section_elements();

        $data_provider = new participant_section_with_responses($participant_section);
        $participant_section_with_response = $data_provider->build();

        $this->assertEquals(
            $elements_in_section->count(),
            $participant_section_with_response->get_section_element_responses()->count()
        );
    }

    public function test_non_respondable_element_is_hidden_when_built_for_submitting_response(): void {
        $participant_section = $this->create_participant_section_with_respondable_and_non_respondable_elements();

        $elements_in_section = $participant_section->section->get_section_elements();
        $respondable_elements = $elements_in_section->filter(function ($section_element) {
            /**@var section_element $section_element */
            return $section_element->element->is_respondable;
        });

        $data_provider = new participant_section_with_responses($participant_section);
        $participant_section_with_response = $data_provider->process_for_response_submission()->build();

        $this->assertEquals(
            $respondable_elements->count(),
            $participant_section_with_response->get_section_element_responses()->count()
        );
    }

    private function create_participant_section_with_respondable_and_non_respondable_elements(): participant_section {

        self::setAdminUser();

        $data_generator = self::getDataGenerator();
        /** @var perform_generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        /** @var activity $activity */
        $activity = $perform_generator->create_full_activities()->first();
        /** @var section $section */
        $section = $activity->sections->first();

        $element = $perform_generator->create_element();
        $perform_generator->create_section_element($section, $element);

        $static_element = $perform_generator->create_element(['plugin_name' => 'static_content']);
        $perform_generator->create_section_element($section, $static_element);

        return participant_section::load_by_entity(
            participant_section_entity::repository()
                ->order_by('id', 'desc')
                ->get()->first()
        );
    }

    /**
     * @dataProvider responder_group_population_for_non_subject_provider
     * @param string $fetching_as
     */
    public function test_responder_group_population_for_non_subject(string $fetching_as): void {
        self::setAdminUser();

        $generator = perform_generator::instance();

        $subject_user = user::logged_in();
        $subject_user_id = $subject_user->id;

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject_user_id,
            'other_participant_id' => null,
            'include_questions' => false,
            'update_participant_sections_status' => 'complete',
        ]);

        $activity = new activity($subject_instance->activity());

        $section = $generator->create_section($activity, ['title' => 'Part one']);

        $manager_section_relationship = $generator->create_section_relationship(
            $section,
            [
                'relationship' => constants::RELATIONSHIP_MANAGER
            ]
        );
        $appraiser_section_relationship = $generator->create_section_relationship(
            $section,
            [
                'relationship' => constants::RELATIONSHIP_APPRAISER
            ]
        );
        $subject_section_relationship = $generator->create_section_relationship(
            $section,
            [
                'relationship' => constants::RELATIONSHIP_SUBJECT
            ]
        );
        $view_only_peer_section_relationship = $generator->create_section_relationship(
            $section,
            [
                'relationship' => constants::RELATIONSHIP_PEER
            ],
            true,
            false
        );

        $element = $generator->create_element(['title' => 'Question one']);
        $generator->create_section_element($section, $element);

        $manager_user = self::getDataGenerator()->create_user();
        $appraiser_user = self::getDataGenerator()->create_user();
        $view_only_peer_user = self::getDataGenerator()->create_user();

        $manager_section = $generator->create_participant_instance_and_section(
            $activity,
            $manager_user,
            $subject_instance->id,
            $section,
            $manager_section_relationship->core_relationship_id
        );

        $appraiser_section = $generator->create_participant_instance_and_section(
            $activity,
            $appraiser_user,
            $subject_instance->id,
            $section,
            $appraiser_section_relationship->core_relationship_id
        );

        $view_only_peer_section = $generator->create_participant_instance_and_section(
            $activity,
            $view_only_peer_user,
            $subject_instance->id,
            $section,
            $view_only_peer_section_relationship->core_relationship_id
        );

        $generator->create_participant_instance_and_section(
            $activity,
            $subject_user->to_record(),
            $subject_instance->id,
            $section,
            $subject_section_relationship->core_relationship_id
        );

        switch ($fetching_as) {
            case 'Manager':
                $participant_section_id = $manager_section->id;
                break;
            case 'Appraiser':
                $participant_section_id = $appraiser_section->id;
                break;
            case 'Peer':
                $participant_section_id = $view_only_peer_section->id;
                break;
            default:
                throw new coding_exception('Invalid $fetching_as argument:' . $fetching_as);
        }

        $data_provider = new participant_section_with_responses(participant_section::load_by_id($participant_section_id));

        /** @var section_element_response $element_response */
        $element_response = $data_provider->build()->get_section_element_responses()->first();

        static::assertEquals($fetching_as, $element_response->get_relationship_name());

        /** @var responder_group $manager_responder_group */
        $manager_responder_group = $element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Manager';
        });

        /** @var responder_group $appraiser_responder_group */
        $appraiser_responder_group = $element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Appraiser';
        });

        /** @var responder_group $appraiser_responder_group */
        $subject_responder_group = $element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Subject';
        });

        /** @var responder_group|null $view_only_peer_responder_group */
        $view_only_peer_responder_group = $element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Peer';
        });

        if ($fetching_as === 'Peer') {
            // There should always be three groups, the subject, manager and appraiser group (no peer group).
            self::assertCount(3, $element_response->get_other_responder_groups());
        } else {
            // There should always be two groups, the subject and another for either appraiser/manager group.
            // There should be bo peer group though.
            self::assertCount(2, $element_response->get_other_responder_groups());
        }

        // Note these are all empty responses.
        self::assertCount(1, $subject_responder_group->get_responses());

        self::assertNull($view_only_peer_responder_group, 'Peer (view-only) responder group should never be present');

        if ($fetching_as === 'Manager') {
            self::assertNull(
                $manager_responder_group,
                'When fetching as manager there should not be a manager other responder group'
            );

            self::assertCount(1,
                $appraiser_responder_group->get_responses(),
                'When fetching as manager there should be an empty appraiser response'
            );
        } else if ($fetching_as === 'Appraiser') {
            self::assertNull(
                $appraiser_responder_group,
                'When fetching as appraiser there should not be a appraiser other responder group'
            );

            self::assertCount(1,
                $manager_responder_group->get_responses(),
                'When fetching as appraiser there should be an empty manager response'
            );
        } else {
            self::assertCount(1,
                $appraiser_responder_group->get_responses(),
                'When fetching as peer there should be an empty appraiser response'
            );

            self::assertCount(1,
                $manager_responder_group->get_responses(),
                'When fetching as peer there should be an empty manager response'
            );
        }
    }

    public function responder_group_population_for_non_subject_provider(): array {
        return [
            'Fetching for Manager' => ['Manager'],
            'Fetching for Appraiser' => ['Appraiser'],
            'Fetching for view-only peer' => ['Peer'],
        ];
    }

    /**
     * This covers the case where we are fetching for a non subject participant, where there are more
     * participants in the same relationship.
     *
     * For example a manager fetching the participant section for a subject that has two job assignments
     * and therefor two managers. The other manager should not be excluded from the other responder groups.
     *
     * @throws coding_exception
     */
    public function test_responder_group_population_for_manager_where_there_is_another_manager(): void {
        self::setAdminUser();

        $generator = perform_generator::instance();

        $subject_user = user::logged_in();
        $subject_user_id = $subject_user->id;

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject_user_id,
            'other_participant_id' => null,
            'include_questions' => false,
            'update_participant_sections_status' => 'complete',
        ]);

        $activity = new activity($subject_instance->activity());

        $section = $generator->create_section($activity, ['title' => 'Part one']);

        $manager_section_relationship = $generator->create_section_relationship(
            $section,
            [
                'relationship' => constants::RELATIONSHIP_MANAGER
            ]
        );
        $subject_section_relationship = $generator->create_section_relationship(
            $section,
            [
                'relationship' => constants::RELATIONSHIP_SUBJECT
            ]
        );

        $element = $generator->create_element(['title' => 'Question one']);
        $generator->create_section_element($section, $element);

        $manager_user = self::getDataGenerator()->create_user();
        $other_manager_user = self::getDataGenerator()->create_user();

        // The manager we are fetching for's section.
        $manager_section = $generator->create_participant_instance_and_section(
            $activity,
            $manager_user,
            $subject_instance->id,
            $section,
            $manager_section_relationship->core_relationship_id
        );

        // The other managers section.
        $generator->create_participant_instance_and_section(
            $activity,
            $other_manager_user,
            $subject_instance->id,
            $section,
            $manager_section_relationship->core_relationship_id
        );

        $generator->create_participant_instance_and_section(
            $activity,
            $subject_user->to_record(),
            $subject_instance->id,
            $section,
            $subject_section_relationship->core_relationship_id
        );

        $data_provider = new participant_section_with_responses(participant_section::load_by_id($manager_section->id));

        /** @var section_element_response $element_response */
        $element_response = $data_provider->build()->get_section_element_responses()->first();

        static::assertEquals('Manager', $element_response->get_relationship_name());

        /** @var responder_group $other_manager_responder_group */
        $other_manager_responder_group = $element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Manager';
        });

        /** @var responder_group $appraiser_responder_group */
        $subject_responder_group = $element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Subject';
        });

        // There should always be two groups, the subject and another for either appraiser/manager group.
        self::assertCount(2, $element_response->get_other_responder_groups());

        // Note these are all empty responses.
        self::assertCount(1, $subject_responder_group->get_responses());

        self::assertCount(1, $other_manager_responder_group->get_responses());

        /** @var section_element_response $other_managers_response */
        $other_managers_response = $other_manager_responder_group->get_responses()->first();

        self::assertEquals(
            $other_managers_response->get_participant_instance()->participant_id,
            $other_manager_user->id,
            'Only the other manager should be included in the "manager" other responder group'
        );
    }

    /**
     * This simulates the case where within one job assignment a user has the same manager and appraiser.
     */
    public function test_responder_group_population_same_user_is_manager_and_appraiser(): void {
        self::setAdminUser();

        $generator = perform_generator::instance();

        $subject_user = user::logged_in();
        $manager_appraiser_user = self::getDataGenerator()->create_user();

        [$subject_section] = $generator->create_section_with_combined_manager_appraiser($subject_user, $manager_appraiser_user);

        $data_provider = new participant_section_with_responses(participant_section::load_by_id($subject_section->id));

        /** @var section_element_response $element_response */
        $element_response = $data_provider->build()->get_section_element_responses()->first();

        static::assertEquals('Subject', $element_response->get_relationship_name());

        /** @var responder_group $manager_responder_group */
        $manager_responder_group = $element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Manager';
        });

        /** @var responder_group $appraiser_responder_group */
        $appraiser_responder_group = $element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Appraiser';
        });

        // There should always be two groups, the manager and appraiser group.
        self::assertCount(2, $element_response->get_other_responder_groups());

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

    /**
     * This simulates the case where within one job assignment a user has the same manager and appraiser.
     */
    public function test_responder_group_population_same_user_is_manager_and_appraiser_derived_responder_groups(): void {
        self::setAdminUser();

        $generator = perform_generator::instance();

        $subject_user = user::logged_in();
        $manager_appraiser_user = self::getDataGenerator()->create_user();

        [$subject_section] = $generator->create_section_with_combined_manager_appraiser($subject_user, $manager_appraiser_user);

        $activity_entity = $subject_section->participant_instance->subject_instance->activity();

        $aggregation_section = $generator->create_section(new activity($activity_entity), ['title' => 'Aggregation section']);

        $subject_viewing_aggregation_section = $generator->create_participant_section(
            new activity($activity_entity),
            $subject_section->participant_instance,
            false,
            $aggregation_section
        );

        $generator->create_section_relationship_from_name(['section_name' => 'Aggregation section', 'relationship' => constants::RELATIONSHIP_SUBJECT]);

        $generator->create_aggregation_in_activity(
            $activity_entity->id,
            2,
            [1],
            [
                constants::RELATIONSHIP_MANAGER => [50],
                constants::RELATIONSHIP_APPRAISER => [100],
            ]
        );

        $this->set_participant_instance_availability($subject_section->participant_instance_id, closed::get_code());

        $data_provider = new participant_section_with_responses(new participant_section($subject_viewing_aggregation_section->refresh()));

        /** @var section_element_response $aggregation_element_response */
        $aggregation_element_response = $data_provider->build()->get_section_element_responses()->first();

        static::assertEquals('Subject', $aggregation_element_response->get_relationship_name());
        static::assertNull($aggregation_element_response->get_response_data());

        /** @var responder_group $manager_responder_group */
        $manager_responder_group = $aggregation_element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Manager';
        });

        /** @var responder_group $appraiser_responder_group */
        $appraiser_responder_group = $aggregation_element_response->get_other_responder_groups()->find(function (responder_group $group) {
            return $group->get_relationship_name() === 'Appraiser';
        });

        // There should always be two groups, viewing subject, manager and appraiser group.
        self::assertCount(2, $aggregation_element_response->get_other_responder_groups());


        // Confirming manager responses are correct.
        /** @var participant_instance $manager_participant_instance */
        $manager_participant_instance = $manager_responder_group->get_responses()->first()->get_participant_instance();

        self::assertEquals($manager_appraiser_user->id, $manager_participant_instance->participant_id);
        self::assertCount(1, $manager_responder_group->get_responses());

        /** @var section_element_response $manager_aggregate_response */
        $manager_aggregate_response = $manager_responder_group->get_responses()->first();
        $manager_average = json_decode($manager_aggregate_response->response_data, true, 512, JSON_THROW_ON_ERROR)[average::get_name()];
        self::assertEquals(50.00, $manager_average);
        self::assertTrue($manager_aggregate_response->get_can_respond());

        // Confirming appraiser responses are correct.
        /** @var participant_instance $manager_participant_instance */
        $appraiser_participant_instance = $appraiser_responder_group->get_responses()->first()->get_participant_instance();

        self::assertEquals($manager_appraiser_user->id, $appraiser_participant_instance->participant_id);
        self::assertCount(1, $appraiser_responder_group->get_responses());

        /** @var section_element_response $appraiser_aggregate_response */
        $appraiser_aggregate_response = $appraiser_responder_group->get_responses()->first();
        $appraiser_average = json_decode($appraiser_aggregate_response->response_data, true, 512, JSON_THROW_ON_ERROR)[average::get_name()];
        self::assertEquals(100.00, $appraiser_average);
        self::assertTrue($appraiser_aggregate_response->get_can_respond());

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
        $view_only_peer_user = self::getDataGenerator()->create_user();

        $subject_instance = $generator->create_subject_instance(
            [
                'activity_name' => 'anonymous activity',
                // The subject actually is participating, but we will create the instance below.
                'subject_is_participating' => false,
                'subject_user_id' => $subject_user->id,
                'other_participant_id' => null,
                'include_questions' => false,
                'anonymous_responses' => 'true',
                'update_participant_sections_status' => 'complete',
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
        $view_only_peer_section_relationship = $generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_PEER],
            true,
            false
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
            $view_only_peer_user,
            $subject_instance->id,
            $section,
            $view_only_peer_section_relationship->core_relationship_id
        );

        $subject_section = $generator->create_participant_instance_and_section(
            $activity,
            $subject_user,
            $subject_instance->id,
            $section,
            $subject_section_relationship->core_relationship_id
        );

        $data_provider = new participant_section_with_responses(participant_section::load_by_entity($subject_section));

        /** @var section_element_response $element_response */
        $element_response = $data_provider->build()->get_section_element_responses()->first();

        static::assertEquals('Subject', $element_response->get_relationship_name());

        /** @var responder_group $anonymous_responder_group */
        $anonymous_responder_group = $element_response->get_other_responder_groups()->first();

        // This is swapped out in the front end, but regardless it should not accidentally identify anyone.
        self::assertEquals('Anonymous', $anonymous_responder_group->get_relationship_name());

        // There should always one group
        self::assertCount(1, $element_response->get_other_responder_groups());

        // anonymous group contains all data
        self::assertCount(2, $anonymous_responder_group->get_responses());
    }

    /**
     * Test none visibility condition always allows other responses to be viewed.
     *
     * @return void
     */
    public function test_none_visibility_conditions_applies(): void {
        self::setAdminUser();

        $activity = $this->create_activity();
        $this->update_activity_visibility_condition($activity, none::VALUE);

        $subject_participant_section = $this->get_last_subject_participant_section();
        self::setUser($subject_participant_section->participant_instance->participant_id);
        $subject_participant_section_availabilities = [open::get_code(), closed::get_code()];

        foreach ($subject_participant_section_availabilities as $availability) {
            $this->set_participant_instance_availability($subject_participant_section->participant_instance_id, $availability);
            $this->set_all_other_participant_instances_availability(
                $subject_participant_section->participant_instance_id,
                open::get_code()
            );
            $subject_participant_section->refresh();

            // all other participant_sections open.
            $this->assert_correct_responder_groups($subject_participant_section);

            // Set 1 of the other participant_sections as closed.
            $this->set_manager_participant_instances_availability(
                $subject_participant_section->participant_instance_id,
                closed::get_code()
            );
            $this->assert_correct_responder_groups($subject_participant_section);

            // Set all the other participant_sections as closed.
            $this->set_all_other_participant_instances_availability(
                $subject_participant_section->participant_instance_id,
                closed::get_code()
            );
            $this->assert_correct_responder_groups($subject_participant_section);
        }
    }

    /**
     * Test own response visibility condition always allows other responses to be viewed only when
     * participant's instance has been closed.
     *
     * @return void
     */
    public function test_own_response_visibility_condition_applies(): void {
        self::setAdminUser();

        $activity = $this->create_activity();
        $this->update_activity_visibility_condition($activity, own_response::VALUE);

        $subject_participant_section = $this->get_last_subject_participant_section();
        $subject_participant_section_availabilities = [open::get_code(), closed::get_code()];
        self::setUser($subject_participant_section->participant_instance->participant_id);

        foreach ($subject_participant_section_availabilities as $availability) {
            $this->set_participant_instance_availability($subject_participant_section->participant_instance_id, $availability);
            $this->set_all_other_participant_instances_availability(
                $subject_participant_section->participant_instance_id,
                open::get_code()
            );
            $subject_participant_instance = participant_instance::load_by_id($subject_participant_section->participant_instance_id);

            // all other participant_sections open.
            $subject_participant_instance->get_availability_state()::get_code() === open::get_code()
                ? $this->assert_responder_groups_are_empty($subject_participant_section)
                : $this->assert_correct_responder_groups($subject_participant_section);

            // Set 1 of the other participant_sections as closed.
            $this->set_manager_participant_instances_availability(
                $subject_participant_section->participant_instance_id,
                closed::get_code()
            );
            $subject_participant_instance->get_availability_state()::get_code() === open::get_code()
                ? $this->assert_responder_groups_are_empty($subject_participant_section)
                : $this->assert_correct_responder_groups($subject_participant_section);

            // Set all the other participant_sections as closed.
            $this->set_all_other_participant_instances_availability(
                $subject_participant_section->participant_instance_id,
                closed::get_code()
            );
            $subject_participant_instance->get_availability_state()::get_code() === open::get_code()
                ? $this->assert_responder_groups_are_empty($subject_participant_section)
                : $this->assert_correct_responder_groups($subject_participant_section);
        }
    }

    /**
     * Test own response visibility condition always allows other responses to be viewed only when
     * all participant instances for the subject has been closed.
     *
     * @return void
     */
    public function test_all_responses_visibility_condition_applies(): void {
        self::setAdminUser();

        $activity = $this->create_activity();
        $this->update_activity_visibility_condition($activity, all_responses::VALUE);

        $subject_participant_section = $this->get_last_subject_participant_section();
        self::setUser($subject_participant_section->participant_instance->participant_id);

        $availability = open::get_code();
        $this->set_participant_instance_availability($subject_participant_section->participant_instance_id, $availability);
        $this->set_all_other_participant_instances_availability(
            $subject_participant_section->participant_instance_id,
            open::get_code()
        );

        // all other participant_sections open.
        $this->assert_responder_groups_are_empty($subject_participant_section);

        // Set 1 of the other participant_sections as closed.
        $this->set_manager_participant_instances_availability(
            $subject_participant_section->participant_instance_id,
            closed::get_code()
        );
        $this->assert_responder_groups_are_empty($subject_participant_section);

        // Set all the other participant_sections as closed.
        $this->set_all_other_participant_instances_availability(
            $subject_participant_section->participant_instance_id,
            closed::get_code()
        );
        $this->assert_responder_groups_are_empty($subject_participant_section);

        //test when subject participant section is closed.
        $availability = closed::get_code();
        $this->set_participant_instance_availability($subject_participant_section->participant_instance_id, $availability);
        $this->set_all_other_participant_instances_availability(
            $subject_participant_section->participant_instance_id,
            open::get_code()
        );

        // all other participant_sections open.
        $this->assert_responder_groups_are_empty($subject_participant_section);

        // Set 1 of the other participant_sections as closed.
        $this->set_manager_participant_instances_availability(
            $subject_participant_section->participant_instance_id,
            closed::get_code()
        );
        $this->assert_responder_groups_are_empty($subject_participant_section);

        // Set all the other participant_sections as closed.
        $this->set_all_other_participant_instances_availability(
            $subject_participant_section->participant_instance_id,
            closed::get_code()
        );
        $this->assert_correct_responder_groups($subject_participant_section);

        // test when activity is anonymous
        $this->update_activity_anonymous_setting($activity, true);
        $this->assert_correct_responder_groups($subject_participant_section, true);
    }

    /**
     * Confirms the responder groups are empty.
     *
     * @param participant_section $participant_section
     * @return void
     */
    private function assert_responder_groups_are_empty(participant_section $participant_section): void {
        $selected_participant_section = participant_section::load_by_id($participant_section->id);
        $data_provider = new participant_section_with_responses($selected_participant_section);
        foreach ($data_provider->build()->get_section_element_responses() as $section_element_response) {
            $this->assertCount(0, $section_element_response->get_other_responder_groups());
        }
    }

    /**
     * Confirms the responder groups are not empty.
     *
     * @param participant_section $participant_section
     * @param bool $is_anonymous
     * @return void
     */
    private function assert_correct_responder_groups(participant_section $participant_section, $is_anonymous = false): void {
        $selected_participant_section = participant_section::load_by_id($participant_section->id);
        $data_provider = new participant_section_with_responses($selected_participant_section);
        foreach ($data_provider->build()->get_section_element_responses() as $section_element_response) {
            $group_names = $section_element_response->get_other_responder_groups()->map(function (responder_group $responder_group) {
                return $responder_group->get_relationship_name();
            })->all();


            if ($section_element_response->get_section_element()->element->plugin_name === aggregation::get_plugin_name()) {
                if ($is_anonymous) {
                    self::assertEquals([
                        'Anonymous',
                    ], $group_names);
                } else {
                    // The special aggregate respondents", subject is replaced with "Your" because we are viewing as the subject.
                    self::assertEquals([
                        'Manager',
                        'Appraiser',
                    ], $group_names);
                }
            } else {
                if ($is_anonymous) {
                    self::assertEquals([
                        'Anonymous',
                    ], $group_names);
                } else {
                    // Just the standard "real" respondents, subject is excluded because we are viewing as the subject.
                    self::assertEquals([
                        'Appraiser',
                    ], $group_names);
                }
            }
        }
    }

    /**
     * Gets the subject user's participant section.
     *
     * @return participant_section
     */
    private function get_last_subject_participant_section(): participant_section {
        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()->one(true);

        /**@var participant_instance_entity $subject_participant_instance */
        $subject_participant_instance = participant_instance_entity::repository()
            ->where('participant_source', participant_source::INTERNAL)
            ->where('participant_id', $subject_instance->subject_user_id)
            ->one(true);

        return participant_section::load_by_entity($subject_participant_instance->participant_sections->last());
    }

    /**
     * Creates activity used for visibility conditions tests.
     *
     * @return activity
     * @throws coding_exception
     */
    private function create_activity(): activity {
        $generator = perform_generator::instance();
        $activity_config = new activity_generator_configuration();
        $activity_config->set_number_of_elements_per_section(1)
            ->set_number_of_sections_per_activity(3)
            ->set_relationships_for_section(1,
                [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER]
            )
            ->set_relationships_for_section(2,
                [constants::RELATIONSHIP_MANAGER]
            )
            ->set_relationships_for_section(3,
                [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_APPRAISER]
            )
            ->set_activity_status(active::get_code())
            ->set_number_of_users_per_user_group_type(1)
            ->enable_appraiser_for_each_subject_user()
            ->enable_manager_for_each_subject_user()
            ->add_aggregation(3, [1, 2, 3],
                [
                    constants::RELATIONSHIP_SUBJECT => [180, null, 20],
                    constants::RELATIONSHIP_MANAGER => [50, 150, null],
                    constants::RELATIONSHIP_APPRAISER => [null, null, null],
                ]);
        $activities = $generator->create_full_activities($activity_config);

        return $activities->first();
    }

    /**
     * Set availability of one of the other participant instances.
     *
     * @param int $participant_instance_id
     * @param int $availability
     * @return void
     */
    private function set_manager_participant_instances_availability(int $participant_instance_id, int $availability): void {
        participant_instance_entity::repository()
            ->join([relationship::TABLE, 'core_relationship'], 'core_relationship_id','core_relationship.id')
            ->where('id', '!=', $participant_instance_id)
            ->where('core_relationship.idnumber', constants::RELATIONSHIP_MANAGER)
            ->one(true)
            ->set_attribute('availability', $availability)
            ->update();
    }

    /**
     * Set availability of all the other participant instances.
     *
     * @param int $participant_instance_id
     * @param int $availability
     * @return void
     */
    private function set_all_other_participant_instances_availability(int $participant_instance_id, int $availability): void {
        participant_instance_entity::repository()
            ->where('id', '!=', $participant_instance_id)
            ->update([
                'availability' => $availability
            ]);
    }

    /**
     * Set availability of the other participant instance.
     *
     * @param int $participant_instance_id
     * @param int $availability
     * @return void
     */
    private function set_participant_instance_availability(int $participant_instance_id, int $availability): void {
        participant_instance_entity::repository()
            ->where('id', $participant_instance_id)
            ->update([
                'availability' => $availability
            ]);
    }

    /**
     * Updates activity visibility condition.
     *
     * @param $activity
     * @param $visibility_condition
     * @return void
     */
    private function update_activity_visibility_condition($activity, $visibility_condition): void {
        // set to draft state to update visibility conditions.
        activity_entity::repository()
            ->where('id', $activity->id)
            ->update([
                'status' => draft::get_code(),
            ]);
        self::setAdminUser();
        $activity->update_visibility_condition($visibility_condition);
        $activity->activate();
    }

    /**
     * Update the anonymous setting of an activity.
     *
     * @param $activity
     * @param bool $value
     * @return void
     */
    private function update_activity_anonymous_setting($activity, bool $value): void {
        activity_entity::repository()
            ->where('id', $activity->id)
            ->update([
                'anonymous_responses' => $value,
            ]);
    }

    protected static function assert_same_participant_section(participant_section $expected, participant_section $other): void {
        self::assertEquals(
            $expected->id,
            $other->id
        );

        self::assertEquals(
            $expected->get_section()->id,
            $other->get_section()->id
        );

        self::assertEquals(
            $expected->get_participant_instance()->get_id(),
            $other->get_participant_instance()->get_id()
        );
    }

    public function test_results_are_ordered(): void {
        self::setAdminUser();

        $generator = perform_generator::instance();

        $subject_user = user::logged_in();
        $subject_user_id = $subject_user->id;

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject_user_id,
            'other_participant_id' => null,
            'include_questions' => false,
            'update_participant_sections_status' => 'complete',
        ]);

        $activity = new activity($subject_instance->activity());

        $section = $generator->create_section($activity, ['title' => 'Part one']);

        // Always create both the manager and appraiser section_relationships
        $manager_section_relationship = $generator->create_section_relationship(
            $section,
            [
                'relationship' => constants::RELATIONSHIP_MANAGER
            ]
        );
        $appraiser_section_relationship = $generator->create_section_relationship(
            $section,
            [
                'relationship' => constants::RELATIONSHIP_APPRAISER
            ]
        );
        $subject_section_relationship = $generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT],
            true
        );

        $element = $generator->create_element(['title' => 'Question one']);
        $generator->create_section_element($section, $element);

        $relationships = [
            constants::RELATIONSHIP_APPRAISER,
            constants::RELATIONSHIP_APPRAISER,
            constants::RELATIONSHIP_MANAGER,
            constants::RELATIONSHIP_MANAGER,
            constants::RELATIONSHIP_MANAGER,
        ];
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

        $subject_section = $generator->create_participant_instance_and_section(
            $activity,
            $subject_user->to_record(),
            $subject_instance->id,
            $section,
            $subject_section_relationship->core_relationship_id
        );

        $data_provider = new participant_section_with_responses(participant_section::load_by_id($subject_section->id));

        /** @var section_element_response $section_element_response */
        $section_element_response = $data_provider->build()->section_element_responses->first();

        // The correct number of groups.
        self::assertCount(2, $section_element_response->other_responder_groups);

        // The first group is manager, comes before appraiser.
        /** @var responder_group $first_responder_group */
        $first_responder_group = $section_element_response->other_responder_groups->first();
        self::assertEquals('Manager', $first_responder_group->get_relationship_name());

        // The participant ids within the group are sorted.
        $previous_id = 0;
        foreach ($first_responder_group->get_responses() as $response) {
            self::assertGreaterThan($previous_id, $response->participant_instance->id);
            $previous_id = $response->participant_instance->id;
        }

        // The second group is appraiser, comes after manager.
        /** @var responder_group $last_responder_group */
        $last_responder_group = $section_element_response->other_responder_groups->last();
        self::assertEquals('Appraiser', $last_responder_group->get_relationship_name());

        // The participant ids within the group are sorted.
        $previous_id = 0;
        foreach ($last_responder_group->get_responses() as $response) {
            self::assertGreaterThan($previous_id, $response->participant_instance->id);
            $previous_id = $response->participant_instance->id;
        }
    }

    public function hide_incomplete_setting_provider(): array {
        return [
            ['activate setting' => 1, 'expected displayed responses' => 0],
            ['activate setting' => 0, 'expected displayed responses' => 1],
        ];
    }

    /**
     * @dataProvider hide_incomplete_setting_provider
     * @param int $activate_setting
     * @param int $expected_displayed_responses
     * @return void
     */
    public function test_get_hide_incomplete_setting(int $activate_setting, int $expected_displayed_responses): void {
        set_config('perform_hide_incomplete_responses_closed_instances', $activate_setting);

        $subject = self::getDataGenerator()->create_user();
        $other_participant = self::getDataGenerator()->create_user();

        self::setUser($subject);

        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => $other_participant->id,
            'include_questions' => true,
            'update_participant_sections_status' => true,
        ]);
        $this->assertCount(2, participant_instance_entity::repository()->get());

        subject_instance_model::load_by_entity($subject_instance)->manually_close();

        /** @var participant_instance $subject_participant_instance */
        $subject_participant_instance = participant_instance_entity::repository()
            ->where('core_relationship_id', core_relationship::load_by_idnumber('subject')->id)
            ->one(true);

        $subject_participant_section = participant_section_entity::repository()
                ->where('participant_instance_id', $subject_participant_instance->id)
                ->one(true);
        $participant_section = new participant_section($subject_participant_section);

        $data_provider = new participant_section_with_responses($participant_section);

        /** @var participant_section $fetched_participant_section */
        $fetched_participant_section = $data_provider->build();

        self::assert_same_participant_section($participant_section, $fetched_participant_section);

        $responses = $fetched_participant_section->get_section_element_responses();
        foreach ($responses as $response) {
            /** @var collection $groups */
            $groups = $response->get_other_responder_groups();

            // It's ok to have the responder group here, but the responder should not be displayed when hiding is activated,
            // because their section is not submitted.
            $this->assertCount(1, $groups);
            /** @var responder_group $group */
            $group = $groups->first();
            $responses = $group->get_responses();
            $this->assertCount($expected_displayed_responses, $responses);
        }
    }
}