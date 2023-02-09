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

use mod_perform\constants;
use mod_perform\models\activity\activity;
use mod_perform\models\response\participant_section;

/**
 * @group perform
 */
class mod_perform_participant_section_model_testcase extends advanced_testcase {

    public function test_get_participant_section_multiple_answerable_participant_instances(): void {
        self::setAdminUser();

        $data_generator = self::getDataGenerator();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $subject_user = $data_generator->create_user();
        $manager_appraiser_user = $data_generator->create_user();

        [
            $subject_section,
            $manager_section,
            $appraiser_section
        ] = $perform_generator->create_section_with_combined_manager_appraiser($subject_user, $manager_appraiser_user);

        $subject_answerable_participants = (new participant_section($subject_section))->get_answerable_participant_instances();
        $manager_answerable_participants = (new participant_section($manager_section))->get_answerable_participant_instances();
        $appraiser_answerable_participants = (new participant_section($appraiser_section))->get_answerable_participant_instances();

        self::assertCount(1, $subject_answerable_participants);
        self::assertSame($subject_user->id, $subject_answerable_participants->first()->participant_id);

        self::assertCount(2, $manager_answerable_participants);
        self::assertCount(2, $appraiser_answerable_participants);

        self::assertEquals(
            $manager_answerable_participants,
            $appraiser_answerable_participants,
            'Both manager and appraiser should have the same answerable participants'
        );

        self::assertNotEquals(
            $manager_answerable_participants->first()->id,
            $manager_answerable_participants->last()->id,
            'Should be two distinct participant instance records for the manager answerable participants'
        );

        self::assertNotEquals(
            $appraiser_answerable_participants->first()->id,
            $appraiser_answerable_participants->last()->id,
            'Should be two distinct participant instance records for the appraiser answerable participants'
        );

        self::assertNotEquals(
            $manager_answerable_participants->first()->id,
            $manager_answerable_participants->last()->id,
            'Should be two distinct participant instance records for the manager answerable participants'
        );

        $all = $appraiser_answerable_participants->all();
        array_push($all, ...$manager_answerable_participants);

        foreach ($all as $answerable_participant) {
            self::assertEquals($manager_appraiser_user->id, $answerable_participant->participant_id);
        }
    }

    /**
     * @param string[] $expected_responses_are_visible_to
     * @dataProvider get_responses_are_visible_to_provider
     */
    public function test_get_responses_are_visible_to(array $expected_responses_are_visible_to): void {
        [
            $manager_section,
            $appraiser_section,
            $subject_section,
        ] = $this->set_up_responses_are_visible($expected_responses_are_visible_to);

        // All participant section should have their responses visible to the same relationships.
        $subject_responses_are_visible_to = (new participant_section($subject_section))
            ->get_responses_are_visible_to();

        $manager_responses_are_visible_to = (new participant_section($manager_section))
            ->get_responses_are_visible_to();

        $appraiser_responses_are_visible_to = (new participant_section($appraiser_section))
            ->get_responses_are_visible_to();

        // Note we are using assertEquals because the ordering matters here.
        self::assertEquals(
            $expected_responses_are_visible_to,
            $subject_responses_are_visible_to->pluck('idnumber')
        );

        self::assertEquals(
            $expected_responses_are_visible_to,
            $manager_responses_are_visible_to->pluck('idnumber')
        );

        self::assertEquals(
            $expected_responses_are_visible_to,
            $appraiser_responses_are_visible_to->pluck('idnumber')
        );
    }

    public function get_responses_are_visible_to_provider(): array {
        return [
            'Responses are not visible to anyone' => [[]],
            'Responses are visible to everyone' => [
                [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER]
            ],
            'Responses are visible to just managers' => [
                [constants::RELATIONSHIP_MANAGER]
            ],
            'Responses are visible to just appraisers' => [
                [constants::RELATIONSHIP_APPRAISER]
            ],
            'Responses are visible to just subjects' => [
                [constants::RELATIONSHIP_SUBJECT]
            ],
        ];
    }

    /**
     * @param string[] $responses_are_visible_to
     * @param bool $expected_result
     * @throws coding_exception
     * @dataProvider can_view_others_responses_provider
     */
    public function test_can_view_others_responses(array $responses_are_visible_to, bool $expected_result): void {
        [$manager_section, $appraiser_section, $subject_section] = $this->set_up_responses_are_visible($responses_are_visible_to);

        $subject_can_view_others_responses = (new participant_section($subject_section))
            ->can_view_others_responses();

        self::assertEquals(
            $expected_result,
            $subject_can_view_others_responses
        );
    }

    public function can_view_others_responses_provider(): array {
        // All cases are acting as subject.
        return [
            'Responses are not visible to anyone' => [
                [], false
            ],
            'Responses are visible to everyone' => [
                [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER], true
            ],
            'Responses are visible to just managers' => [
                [constants::RELATIONSHIP_MANAGER], false
            ],
            'Responses are visible to just appraisers' => [
                [constants::RELATIONSHIP_APPRAISER], false
            ],
            'Responses are visible to just subjects' => [
                [constants::RELATIONSHIP_SUBJECT], true
            ],
        ];
    }

    private function set_up_responses_are_visible(array $expected_responses_are_visible_to): array {
        self::setAdminUser();
        $data_generator = self::getDataGenerator();

        $subject_user = $data_generator->create_user();
        $manager_appraiser_user = $data_generator->create_user();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $subject_instance = $perform_generator->create_subject_instance([
            'activity_name' => 'activity 1',
            'subject_is_participating' => false, // The subject actually is participating, but we will create the instance below.
            'subject_user_id' => $subject_user->id,
            'other_participant_id' => null,
            'include_questions' => false,
        ]);

        $activity = new activity($subject_instance->activity());

        $section = $perform_generator->create_section($activity, ['title' => 'Part one']);

        $manager_section_relationship = $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER],
            in_array(constants::RELATIONSHIP_MANAGER, $expected_responses_are_visible_to, true)
        );

        $appraiser_section_relationship = $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_APPRAISER],
            in_array(constants::RELATIONSHIP_APPRAISER, $expected_responses_are_visible_to, true)
        );

        $subject_section_relationship = $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT],
            in_array(constants::RELATIONSHIP_SUBJECT, $expected_responses_are_visible_to, true)
        );

        $element = $perform_generator->create_element(['title' => 'Question one']);
        $perform_generator->create_section_element($section, $element);

        $manager_section = $perform_generator->create_participant_instance_and_section(
            $activity,
            $manager_appraiser_user,
            $subject_instance->id,
            $section,
            $manager_section_relationship->core_relationship_id
        );

        $appraiser_section = $perform_generator->create_participant_instance_and_section(
            $activity,
            $manager_appraiser_user,
            $subject_instance->id,
            $section,
            $appraiser_section_relationship->core_relationship_id
        );

        $subject_section = $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user,
            $subject_instance->id,
            $section,
            $subject_section_relationship->core_relationship_id
        );

        return [$manager_section, $appraiser_section, $subject_section];
    }

}
