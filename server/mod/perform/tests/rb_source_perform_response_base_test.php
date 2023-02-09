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
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\expand_task;
use mod_perform\models\activity\element;
use mod_perform\models\activity\helpers\section_element_manager;
use mod_perform\models\activity\section;
use mod_perform\models\activity\track;
use mod_perform\models\activity\track_assignment_type;
use mod_perform\models\response\participant_section as participant_section_model;
use mod_perform\state\activity\draft;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\user_groups\grouping;
use performelement_aggregation\aggregation;
use performelement_aggregation\calculations\average;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * @group perform
 * @group perform_element
 * @group totara_reportbuilder
 */
class mod_perform_rb_source_perform_response_base_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * Check that derived responses (aggregation element) are included in the report result for all participants with
     * derived responses - even if they are not participating in the section that includes the derived response.
     */
    public function test_multi_section_derived_responses_are_included_for_nonparticipant(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();
        $perform_generator = \mod_perform\testing\generator::instance();

        // Create subject & manager users and add them to an audience.
        $subject_user = $generator->create_user(['firstname' => 'Subject', 'lastname' => 'User']);
        $manager_user = $generator->create_user(['firstname' => 'Manager', 'lastname' => 'User']);
        job_assignment::create([
            'userid' => $subject_user->id,
            'idnumber' => 'subject_job_assignment',
            'managerjaid' => job_assignment::create_default($manager_user->id)->id,
        ]);
        $cohort = $generator->create_cohort();
        cohort_add_member($cohort->id, $subject_user->id);
        cohort_add_member($cohort->id, $manager_user->id);

        // Create an activity with two sections, one for subject only, one for manager only.
        $activity = $perform_generator->create_activity_in_container([
            'create_track' => true,
            'create_section' => false,
            'activity_name' => 'Test activity',
            'activity_status' => draft::get_code()
        ]);
        $subject_section = section::create($activity, 'Section 1 - source questions');
        $manager_section = section::create($activity, 'Section 2 - aggregated question');
        $perform_generator->create_section_relationship(
            $subject_section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT],
            true,
            true
        );
        $perform_generator->create_section_relationship(
            $manager_section,
            ['relationship' => constants::RELATIONSHIP_MANAGER],
            true,
            true
        );
        /** @var track $track */
        $track = track::load_by_activity($activity)->first();
        $track->add_assignment(track_assignment_type::ADMIN, grouping::cohort($cohort->id));

        $this->create_responses_and_assert_report($subject_section, $manager_section, $subject_user, $manager_user);
    }

    /**
     * Check that derived responses (aggregation element) are included in the report result for all participants with
     * derived responses when they ARE participating in the section that includes the derived response. Responses should
     * be in the report even if the section that includes the derived responses has not been completed by anyone.
     */
    public function test_multi_section_derived_responses_are_included_for_participant(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();
        $perform_generator = \mod_perform\testing\generator::instance();

        // Create subject & manager users and add them to an audience.
        $subject_user = $generator->create_user(['firstname' => 'Subject', 'lastname' => 'User']);
        $manager_user = $generator->create_user(['firstname' => 'Manager', 'lastname' => 'User']);
        job_assignment::create([
            'userid' => $subject_user->id,
            'idnumber' => 'subject_job_assignment',
            'managerjaid' => job_assignment::create_default($manager_user->id)->id,
        ]);
        $cohort = $generator->create_cohort();
        cohort_add_member($cohort->id, $subject_user->id);
        cohort_add_member($cohort->id, $manager_user->id);

        // Create an activity with two sections, subject participates in both.
        $activity = $perform_generator->create_activity_in_container([
            'create_track' => true,
            'create_section' => false,
            'activity_name' => 'Test activity',
            'activity_status' => draft::get_code()
        ]);
        $section1 = section::create($activity, 'Section 1 - source questions');
        $section2 = section::create($activity, 'Section 2 - aggregated question');
        $perform_generator->create_section_relationship(
            $section1,
            ['relationship' => constants::RELATIONSHIP_SUBJECT],
            true,
            true
        );
        $perform_generator->create_section_relationship(
            $section2,
            ['relationship' => constants::RELATIONSHIP_MANAGER],
            true,
            true
        );
        $perform_generator->create_section_relationship(
            $section2,
            ['relationship' => constants::RELATIONSHIP_SUBJECT],
            true,
            false
        );
        /** @var track $track */
        $track = track::load_by_activity($activity)->first();
        $track->add_assignment(track_assignment_type::ADMIN, grouping::cohort($cohort->id));

        $this->create_responses_and_assert_report($section1, $section2, $subject_user, $manager_user);
    }

    /**
     * @param section $section1
     * @param section $section2
     * @param stdClass $subject_user
     * @param stdClass $manager_user
     */
    private function create_responses_and_assert_report(
        section $section1,
        section $section2,
        stdClass $subject_user,
        stdClass $manager_user
    ): void {
        // Create two numeric elements in first section.
        $activity = $section1->get_activity();
        $numeric_1 = element::create(
            $activity->get_context(),
            'numeric_rating_scale',
            'Numeric title 1',
            'identifier_numeric',
            '{"defaultValue": "3", "highValue": "5", "lowValue": "1"}',
            true
        );
        $numeric_2 = element::create(
            $activity->get_context(),
            'numeric_rating_scale',
            'Numeric title 2',
            'identifier_numeric',
            '{"defaultValue": "2", "highValue": "5", "lowValue": "1"}',
            true
        );

        /** @var section_entity $section1_entity */
        $section1_entity = section_entity::repository()->find($section1->get_id());
        $section1_element_manager = new section_element_manager($section1_entity);

        $numeric_section_element_1 = $section1_element_manager->add_element_after($numeric_1);
        $numeric_section_element_2 = $section1_element_manager->add_element_after($numeric_2, $numeric_1->get_id());

        // Create an aggregation element in second section (and a short text one, so we have one required respondable question).
        $aggregation_element = element::create(
            $activity->get_context(),
            'aggregation',
            'Aggregation title',
            'identifier_aggregation',
            json_encode([
                aggregation::SOURCE_SECTION_ELEMENT_IDS => [$numeric_section_element_1->id, $numeric_section_element_2->id],
                aggregation::EXCLUDED_VALUES => [],
                aggregation::CALCULATIONS => [average::get_name()],
            ], JSON_THROW_ON_ERROR)
        );

        $short_text_element = element::create(
            $activity->get_context(),
            'short_text',
            'Short text title',
            'identifier_short_text',
            null,
            true
        );

        /** @var section_entity $section2_entity */
        $section2_entity = section_entity::repository()->find($section2->get_id());
        $section2_element_manager = new section_element_manager($section2_entity);
        $short_text_section_element = $section2_element_manager->add_element_after($short_text_element);
        $aggregation_section_element = $section2_element_manager->add_element_after($aggregation_element, $short_text_element->get_id());

        $activity->activate();
        expand_task::create()->expand_all();
        (new subject_instance_creation())->generate_instances();

        // Add response data.
        /** @var participant_instance $subject_participant_instance */
        $subject_participant_instance = participant_instance::repository()
            ->where('participant_id', $subject_user->id)
            ->one(true);
        $q1_response = new element_response();
        $q1_response->participant_instance_id = $subject_participant_instance->id;
        $q1_response->section_element_id = $numeric_section_element_1->id;
        $q1_response->response_data = json_encode(2, JSON_THROW_ON_ERROR);
        $q1_response->save();

        $q2_response = new element_response();
        $q2_response->participant_instance_id = $subject_participant_instance->id;
        $q2_response->section_element_id = $numeric_section_element_2->id;
        $q2_response->response_data = json_encode(3, JSON_THROW_ON_ERROR);
        $q2_response->save();

        // Create report object.
        $rid = $this->create_report('perform_response_base', 'Test response report');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'activity', 'name', null, null, null, 0);
        $this->add_column($report, 'section', 'title', null, null, null, 0);
        $this->add_column($report, 'element', 'title', null, null, null, 0);
        $this->add_column($report, 'participant_instance', 'participant_name', null, null, null, 0);
        $this->add_column($report, 'response', 'response_data', null, null, null, 0);

        // There shouldn't be anything in the report because no section is complete.
        self::assert_report($rid, []);

        // Trigger aggregation calculation by completing the section.
        /** @var participant_section $subject_participant_section_entity */
        $subject_participant_section_entity = participant_section::repository()
            ->where('participant_instance_id', $subject_participant_instance->id)
            ->where('section_id', $section1->id)
            ->one(true);
        $subject_participant_section = participant_section_model::load_by_entity($subject_participant_section_entity);
        $subject_participant_section->complete();

        // Report should have subject's direct responses from section1 and the aggregated response from section2.
        $expected_result = [
            ['Test activity', 'Section 1 - source questions', 'Numeric title 1', 'Subject User', '2'],
            ['Test activity', 'Section 1 - source questions', 'Numeric title 2', 'Subject User', '3'],
            ['Test activity', 'Section 2 - aggregated question', 'Aggregation title', 'Subject User', '{"average":2.5}'],
        ];
        self::assert_report($rid, $expected_result);

        // Manager completes his section for the same subject_instance.
        /** @var participant_instance $manager_participant_instance */
        $manager_participant_instance = participant_instance::repository()
            ->where('participant_id', $manager_user->id)
            ->where('subject_instance_id', $subject_participant_instance->subject_instance_id)
            ->one(true);
        $q1_response = new element_response();
        $q1_response->participant_instance_id = $manager_participant_instance->id;
        $q1_response->section_element_id = $short_text_section_element->id;
        $q1_response->response_data = json_encode('Manager short text answer', JSON_THROW_ON_ERROR);
        $q1_response->save();
        /** @var participant_section $manager_participant_section_entity */
        $manager_participant_section_entity = participant_section::repository()
            ->where('participant_instance_id', $manager_participant_instance->id)
            ->one(true);
        $manager_participant_section = participant_section_model::load_by_entity($manager_participant_section_entity);
        $manager_participant_section->complete();

        // Report should just have one additional row for the manager's short text answer.
        $expected_result[] = [
            'Test activity', 'Section 2 - aggregated question', 'Short text title', 'Manager User', '"Manager short text answer"'
        ];
        self::assert_report($rid, $expected_result);
    }

    /**
     * @param int $report_id
     * @param array $expected_records
     */
    private static function assert_report(int $report_id, array $expected_records): void {
        global $DB;

        $report = reportbuilder::create($report_id);
        [$sql, $params] = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $actual_records = [];
        foreach ($records as $record) {
            $record = (array)$record;
            $actual_records[] = [
                $record['activity_name'],
                $record['section_title'],
                $record['element_title'],
                $record['participant_instance_participant_name'],
                $record['response_response_data'],
            ];
        }
        self::assertEqualsCanonicalizing($expected_records, $actual_records);
    }
}