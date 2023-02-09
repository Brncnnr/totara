<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package pathway_criteria_group
 */

use core_phpunit\testcase;
use pathway_criteria_group\criteria_group;
use totara_competency\aggregation_task;
use totara_competency\aggregation_users_table;
use criteria_coursecompletion\coursecompletion;
use totara_competency\achievement_configuration;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class pathway_criteria_group_course_completion_integration_testcase extends testcase {

    public function test_course_completion_leads_to_comp_achievement_via_cron() {
        global $DB;

        $competency_generator = \totara_competency\testing\generator::instance();
        $assignment_generator = $competency_generator->assignment_generator();

        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);

        // Create course completion before setting up the competency and assignment as we want its event
        // to be ignored while we test cron operations.
        /** @var completion_completion $completion */
        $completion = new completion_completion(['course' => $course1->id, 'userid' => $user1->id]);
        $completion->mark_complete();

        $competency1 = $competency_generator->create_competency();

        $pathway = new criteria_group();
        $pathway->set_competency($competency1);
        /** @var scale_value $expected_value1 */
        $expected_value1 = $competency1->scale->sorted_values_high_to_low->first();

        $pathway->set_scale_value($expected_value1);

        $course_completion_criteria = new coursecompletion();
        $course_completion_criteria->set_aggregation_method(coursecompletion::AGGREGATE_ALL);
        $course_completion_criteria->add_items([$course1->id]);

        $pathway->add_criterion($course_completion_criteria);

        $pathway->save();

        $configuration = new achievement_configuration($competency1);
        $configuration->set_aggregation_type('highest');
        $configuration->save_aggregation();

        $assignment1 = $assignment_generator->create_user_assignment($competency1->id, $user1->id);
        $model = new \totara_competency\models\assignment_actions();
        $model->activate([$assignment1->id]);
        $expand_task = new \totara_competency\expand_task($DB);
        $expand_task->expand_all();

        $this->assertEquals(1, $DB->count_records('totara_competency_assignment_users'));

        // At this point we have the competency with it's configured criteria and aggregation.
        // We also have a user with an active and expanded assignment to that competency.
        // The course was also complete, but that fact has not yet entered the criteria system.
        $this->assertEquals(0, $DB->count_records('totara_competency_achievement'));
        $this->assertEquals(0, $DB->count_records('totara_competency_achievement_via'));
        $this->assertEquals(0, $DB->count_records('totara_competency_pathway_achievement'));
        $this->assertEquals(0, $DB->count_records('totara_criteria_item_record'));

        (new totara_competency\task\competency_aggregation_all())->execute();

        $this->assertEquals(1, $DB->count_records('totara_competency_achievement'));
        $this->assertEquals(1, $DB->count_records('totara_competency_achievement_via'));
        $this->assertEquals(1, $DB->count_records('totara_competency_pathway_achievement'));
        $this->assertEquals(1, $DB->count_records('totara_criteria_item_record'));

        $this->assertEquals(1, $DB->get_field('totara_criteria_item_record', 'criterion_met', []));
        $this->assertEquals($expected_value1->id, $DB->get_field('totara_competency_pathway_achievement', 'scale_value_id', []));

        $comp_record = $DB->get_record('totara_competency_achievement', []);
        $this->assertEquals($expected_value1->id, $comp_record->scale_value_id);
    }

    public function test_course_completion_leads_to_comp_achievement_via_events() {
        global $DB;

        $competency_generator = \totara_competency\testing\generator::instance();
        $assignment_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();

        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);

        $competency1 = $competency_generator->create_competency();

        $pathway = new criteria_group();
        $pathway->set_competency($competency1);
        /** @var scale_value $expected_value1 */
        $expected_value1 = $competency1->scale->sorted_values_high_to_low->first();

        $pathway->set_scale_value($expected_value1);

        $course_completion_criteria = new coursecompletion();
        $course_completion_criteria->set_aggregation_method(coursecompletion::AGGREGATE_ALL);
        $course_completion_criteria->add_items([$course1->id]);

        $pathway->add_criterion($course_completion_criteria);

        $pathway->save();

        $configuration = new achievement_configuration($competency1);
        $configuration->set_aggregation_type('highest');
        $configuration->save_aggregation();

        $assignment1 = $assignment_generator->create_user_assignment($competency1->id, $user1->id);
        $model = new \totara_competency\models\assignment_actions();
        $model->activate([$assignment1->id]);
        $expand_task = new \totara_competency\expand_task($DB);
        $expand_task->expand_all();

        $this->assertEquals(1, $DB->count_records('totara_competency_assignment_users'));

        // At this point we have the competency with it's configured criteria and aggregation.
        // We also have a user with an active and expanded assignment to that competency.
        $this->assertEquals(0, $DB->count_records('totara_competency_achievement'));
        $this->assertEquals(0, $DB->count_records('totara_competency_achievement_via'));
        $this->assertEquals(0, $DB->count_records('totara_competency_pathway_achievement'));
        $this->assertEquals(0, $DB->count_records('totara_criteria_item_record'));

        // Complete the course and the rest should be done via a series of events following that.
        /** @var completion_completion $completion */
        $completion = new completion_completion(['course' => $course1->id, 'userid' => $user1->id]);

        $completion->mark_complete();
        // Verify that a row was inserted in the aggregation queue
        $this->assertTrue($DB->record_exists('totara_competency_aggregation_queue',
            ['user_id' => $user1->id, 'competency_id' => $competency1->id, 'process_key' => null]
        ));

        // Run the task
        $this->run_aggregation_task();

        // Ordered these according to when they happen.
        $this->assertEquals(1, $DB->count_records('totara_criteria_item_record'));
        $this->assertEquals(1, $DB->count_records('totara_competency_pathway_achievement'));
        $this->assertEquals(1, $DB->count_records('totara_competency_achievement'));
        $this->assertEquals(1, $DB->count_records('totara_competency_achievement_via'));

        $this->assertEquals(1, $DB->get_field('totara_criteria_item_record', 'criterion_met', []));
        $this->assertEquals($expected_value1->id, $DB->get_field('totara_competency_pathway_achievement', 'scale_value_id', []));

        $comp_record = $DB->get_record('totara_competency_achievement', []);
        $this->assertEquals($expected_value1->id, $comp_record->scale_value_id);
    }

    private function run_aggregation_task() {
        (new aggregation_task(new aggregation_users_table(), false))->execute();
    }

}
