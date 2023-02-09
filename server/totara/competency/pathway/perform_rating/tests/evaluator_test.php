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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package pathway_perform_rating
 */

use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section_element as section_element_model;
use mod_perform\testing\generator as perform_generator;
use pathway_manual\manual_evaluator_user_source;
use pathway_perform_rating\entity\perform_rating as perform_rating_entity;
use pathway_perform_rating\models\perform_rating as perform_rating_model;
use pathway_perform_rating\perform_rating;
use pathway_perform_rating\perform_rating_evaluator;
use pathway_perform_rating\testing\generator as perform_rating_generator;
use performelement_linked_review\models\linked_review_content;
use totara_competency\aggregation_users_table;
use totara_competency\entity\pathway_achievement;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;
use totara_job\job_assignment;

/**
 * @group pathway_perform_rating
 * @group totara_competency
 */
class pathway_perform_rating_evaluator_testcase extends testcase {

    public function test_aggregate() {
        $data = $this->create_data();
        $now = time();

        $this->create_rating_records($data->competency->id, [
            [
                'participant_instance_id' => $data->participant_instance1->id,
                'scale_value_id' => $data->scale_values[4]->id,
                'section_element_id' => $data->section_element->id,
                'created_at' => $now++,
            ],
        ]);

        $this->create_userid_table_records($data->user_id_table, $data->competency->id, [$data->users['user']->id]);
        $evaluator = new perform_rating_evaluator($data->perform_rating_pathway, $data->user_id_source);

        // Now aggregate first time
        $evaluator->aggregate($now++);

        $expected = [
            [
                'pathway_id' => $data->perform_rating_pathway->get_id(),
                'scale_value_id' => $data->scale_values[4]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'related_info' => [],
            ],
        ];
        $this->verify_userid_table_records($data->user_id_table, [$data->users['user']->id => 1]);
        $this->verify_pathway_achievements($data->users['user']->id, $expected);

        // Add another and reaggregate
        $this->create_rating_records($data->competency->id, [
            [
                'participant_instance_id' => $data->participant_instance2->id,
                'scale_value_id' => $data->scale_values[3]->id,
                'section_element_id' => $data->section_element->id,
                'created_at' => $now++,
            ],
        ]);

        $data->user_id_table->reset_has_changed(0);
        $evaluator->aggregate($now++);

        $expected = [
            [
                'pathway_id' => $data->perform_rating_pathway->get_id(),
                'scale_value_id' => $data->scale_values[4]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'related_info' => [],
            ],
            [
                'pathway_id' => $data->perform_rating_pathway->get_id(),
                'scale_value_id' => $data->scale_values[3]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'related_info' => [],
            ],
        ];

        $this->verify_userid_table_records($data->user_id_table, [$data->users['user']->id => 1]);
        $this->verify_pathway_achievements($data->users['user']->id, $expected);

        // Add a null one and check that it is the right one
        $this->create_rating_records($data->competency->id, [
            [
                'participant_instance_id' => $data->participant_instance3->id,
                'scale_value_id' => null,
                'section_element_id' => $data->section_element->id,
                'created_at' => $now++,
            ],
        ]);

        $data->user_id_table->reset_has_changed(0);
        $evaluator->aggregate($now++);

        $expected = [
            [
                'pathway_id' => $data->perform_rating_pathway->get_id(),
                'scale_value_id' => $data->scale_values[4]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'related_info' => [],
            ],
            [
                'pathway_id' => $data->perform_rating_pathway->get_id(),
                'scale_value_id' => $data->scale_values[3]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'related_info' => [],
            ],
            [
                'pathway_id' => $data->perform_rating_pathway->get_id(),
                'scale_value_id' => null,
                'status' => pathway_achievement::STATUS_CURRENT,
                'related_info' => [],
            ],
        ];

        $this->verify_userid_table_records($data->user_id_table, [$data->users['user']->id => 1]);
        $this->verify_pathway_achievements($data->users['user']->id, $expected);
    }

    private function create_data() {
        global $DB;

        $this->setAdminUser();

        $data = new class() {
            public $users = [];

            /** @var competency $competency*/
            public $competency;
            public $scale;
            public $scale_values = [];

            /** @var perform_rating $perform_rating_pathway */
            public $perform_rating_pathway;

            /** @var aggregation_users_table $user_id_table */
            public $user_id_table;
            /** @var manual_evaluator_user_source $user_id_source*/
            public $user_id_source;

            /** @var participant_instance_entity */
            public $participant_instance1;
            /** @var participant_instance_entity */
            public $participant_instance2;
            /** @var participant_instance_entity */
            public $participant_instance3;
            /** @var section_element_model */
            public $section_element;
        };

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $data->scale = $hierarchygenerator->create_scale(
            'comp',
            ['name' => 'Test scale', 'description' => 'Test scale'],
            [
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );
        $rows = $DB->get_records('comp_scale_values', ['scaleid' => $data->scale->id], 'sortorder');
        foreach ($rows as $row) {
            $data->scale_values[$row->sortorder] = new scale_value($row->id);
        }

        $framework = $hierarchygenerator->create_comp_frame(['scale' => $data->scale->id]);
        $comp = $hierarchygenerator->create_comp(['frameworkid' => $framework->id]);
        $data->competency = new competency($comp->id);

        $data->users['manager'] = $this->getDataGenerator()->create_user();
        $managerja = job_assignment::create_default($data->users['manager']->id);
        $data->users['appraiser'] = $this->getDataGenerator()->create_user();
        $appraiserja = job_assignment::create_default($data->users['appraiser']->id);
        $data->users['user'] = $this->getDataGenerator()->create_user();
        job_assignment::create_default(
            $data->users['user']->id,
            ['managerjaid' => $managerja->id, 'appraiserid' => $data->users['appraiser']->id]
        );

        $assignment = \totara_competency\testing\generator::instance()->assignment_generator()
            ->create_user_assignment($comp->id, $data->users['user']->id);

        $perform_rating_generator = perform_rating_generator::instance();
        $data->perform_rating_pathway = $perform_rating_generator->create_perform_rating_pathway($data->competency);

        $data->user_id_table = new aggregation_users_table();

        $data->user_id_source = new manual_evaluator_user_source($data->user_id_table, true);

        // Create the perform activity data we need

        $perform_generator = perform_generator::instance();
        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);
        $section_relationship = $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );
        $element = element::create($activity->get_context(), 'linked_review', 'title', '', json_encode([
            'content_type' => 'totara_competency',
            'content_type_settings' => [
                'enable_rating' => true,
                'rating_relationship' => $perform_generator->get_core_relationship(constants::RELATIONSHIP_MANAGER)->id
            ],
            'selection_relationships' => [$section_relationship->core_relationship_id],
        ]));

        $section_element = $perform_generator->create_section_element($section, $element);

        $subject_instance1 = $perform_generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $data->users['user']->id
        ]);

        $subject_instance2 = $perform_generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $data->users['user']->id
        ]);

        $subject_instance3 = $perform_generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $data->users['user']->id
        ]);

        $participant_section1 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $data->users['manager'],
            $subject_instance1->id,
            $section,
            $section_relationship->core_relationship->id
        );

        $participant_section2 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $data->users['manager'],
            $subject_instance2->id,
            $section,
            $section_relationship->core_relationship->id
        );

        $participant_section3 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $data->users['manager'],
            $subject_instance3->id,
            $section,
            $section_relationship->core_relationship->id
        );

        $linked_assignment1 = linked_review_content::create(
            $assignment->id, $section_element->id, $participant_section1->participant_instance_id, false
        );
        $linked_assignment2 = linked_review_content::create(
            $assignment->id, $section_element->id, $participant_section2->participant_instance_id, false
        );
        $linked_assignment3 = linked_review_content::create(
            $assignment->id, $section_element->id, $participant_section3->participant_instance_id, false
        );

        $data->participant_instance1 = $participant_section1->participant_instance;
        $data->participant_instance2 = $participant_section2->participant_instance;
        $data->participant_instance3 = $participant_section3->participant_instance;
        $data->section_element = $section_element;

        return $data;
    }

    /**
     * Create rating records
     *
     * @param int $competency_id
     * @param array $ratings ['scale_value_id', 'participant_instance_id', 'section_element_id', 'created_at']
     *
     * @return void
     */
    private function create_rating_records(int $competency_id, array $ratings): void {
        foreach ($ratings as $to_create) {
            $rating = perform_rating_model::create(
                $competency_id,
                $to_create['scale_value_id'],
                $to_create['participant_instance_id'],
                $to_create['section_element_id']
            );
            if (!empty($to_create['created_at'])) {
                perform_rating_entity::repository()->update_record([
                    'id' => $rating->id,
                    'created_at' => $to_create['created_at'],
                ]);
            }
        }
    }

    /**
     * Helper function to create rows in the user_id table
     *
     * @param aggregation_users_table $user_id_table
     * @param int $competency_id
     * @param array $assigned_users
     */
    private function create_userid_table_records(
        aggregation_users_table $user_id_table,
        int $competency_id,
        array $assigned_users
    ) {
        global $DB;

        $user_id_table->truncate();
        if (empty($assigned_users)) {
            return;
        }

        $tablename = $user_id_table->get_table_name();
        $temp_user_records = [];
        foreach ($assigned_users as $user_id) {
            $temp_user_records[] = $user_id_table->get_insert_record($user_id, $competency_id);
        }
        $DB->insert_records($tablename, $temp_user_records);
    }

    private function verify_pathway_achievements($user_id, $expected_rows) {
        global $DB;

        $actual_rows = $DB->get_records('totara_competency_pathway_achievement', ['user_id' => $user_id]);

        $this->assertSame(count($expected_rows), count($actual_rows));
        foreach ($actual_rows as $actual_row) {
            foreach ($expected_rows as $key => $expected_row) {
                if ((int)$actual_row->pathway_id == $expected_row['pathway_id'] &&
                    (int)$actual_row->status == $expected_row['status'] &&
                    (int)$actual_row->scale_value_id == $expected_row['scale_value_id']
                ) {
                    unset($expected_rows[$key]);
                    break;
                }
            }
        }

        $this->assertSame(0, count($expected_rows));
    }

    /**
     * Helper function to verify rows in the user_id table
     *
     * @param aggregation_users_table $user_id_table
     * @param array $expected
     */
    private function verify_userid_table_records(aggregation_users_table $user_id_table, array $expected) {
        global $DB;

        $rows = $DB->get_records($user_id_table->get_table_name(), $user_id_table->get_filter());
        $this->assertSame(count($expected), count($rows));

        foreach ($rows as $row) {
            $this->assertTrue(isset($expected[$row->user_id]));
            $this->assertEquals($expected[$row->user_id], $row->has_changed);
        }
    }

}
