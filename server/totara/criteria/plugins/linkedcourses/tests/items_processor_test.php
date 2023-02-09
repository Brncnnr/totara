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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_criteria
 */

use core_phpunit\testcase;
use totara_competency\aggregation_users_table;
use criteria_linkedcourses\items_processor;
use pathway_criteria_group\criteria_group;
use criteria_linkedcourses\linkedcourses;
use totara_competency\linked_courses;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency;

/**
 * @group totara_competency
 */
class criteria_linkedcourses_items_processor_testcase extends testcase {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->dirroot . '/completion/completion_completion.php');

        set_config('enablecompletion', 1);
    }

    private function set_up_pathway_with_linked_courses_criteria($competency) {
        $linked_course_criterion = new linkedcourses();
        $linked_course_criterion->set_competency_id($competency->id);

        $pathway = new criteria_group();
        $pathway->set_competency($competency);
        $pathway->set_scale_value($competency->scale->sorted_values_high_to_low->first());
        $pathway->add_criterion($linked_course_criterion);
        $pathway->save();
    }

    public function test_update_items_no_data() {
        global $DB;

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        $sink = $this->redirectEvents();
        items_processor::update_items($comp->id);

        // Ensure no history or configuration change entries were logged
        $this->assertSame(0, $DB->count_records('totara_competency_configuration_change', ['competency_id' => $comp->id]));
        $this->assertSame(0, $DB->count_records('totara_competency_configuration_history', ['competency_id' => $comp->id]));
        $this->assertSame(0, $sink->count());

        $sink->close();
    }

    public function test_update_items_criteria_with_no_courses() {
        global $DB;

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency = new competency($comp->id);

        $linked_course_criterion = new linkedcourses();
        $linked_course_criterion->set_competency_id($comp->id);

        $pathway = new criteria_group();
        $pathway->set_competency($competency);
        $pathway->set_scale_value(
            $competency->scale->sorted_values_high_to_low->first()
        );
        $pathway->add_criterion($linked_course_criterion);
        $pathway->save();

        $sink = $this->redirectEvents();

        items_processor::update_items($competency->id);

        $this->assertCount(0, $DB->get_records('totara_criteria_item'));
        $this->assertSame(0, $DB->count_records('totara_competency_configuration_change', ['competency_id' => $comp->id]));
        $this->assertSame(0, $DB->count_records('totara_competency_configuration_history', ['competency_id' => $comp->id]));
        $this->assertSame(0, $sink->count());

        $sink->close();
    }

    public function test_update_items_criteria_with_one_course() {
        global $DB;

        // We sink the events to prevent observer interference
        $sink = $this->redirectEvents();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency = new competency($comp->id);

        $this->set_up_pathway_with_linked_courses_criteria($competency);

        $course = $this->getDataGenerator()->create_course();
        linked_courses::set_linked_courses($competency->id,
            [['id' => $course->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]]
        );

        $sink->clear();

        items_processor::update_items($competency->id);

        $criterion_record = $DB->get_record('totara_criteria', ['plugin_type' => 'linkedcourses']);

        $items = $DB->get_records('totara_criteria_item');
        $this->assertCount(1, $items);
        $item = array_pop($items);
        $this->assertEquals('course', $item->item_type);
        $this->assertEquals($course->id, $item->item_id);
        $this->assertEquals($criterion_record->id, $item->criterion_id);

        $this->assertSame(1, $DB->count_records('totara_competency_configuration_change', ['competency_id' => $comp->id]));
        $this->assertSame(1, $DB->count_records('totara_competency_configuration_history', ['competency_id' => $comp->id]));

        $sink->close();
    }

    public function test_update_items_criteria_with_changes() {
        global $DB;

        // We sink the events to prevent observer interference
        $sink = $this->redirectEvents();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency = new competency($comp->id);

        $this->set_up_pathway_with_linked_courses_criteria($competency);

        $add = $this->getDataGenerator()->create_course();
        $keep = $this->getDataGenerator()->create_course();
        $remove = $this->getDataGenerator()->create_course();
        linked_courses::set_linked_courses(
            $competency->id,
            [
                ['id' => $keep->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $remove->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]
            ]
        );

        $sink->clear();

        items_processor::update_items($competency->id);

        $items = $DB->get_records('totara_criteria_item');
        $this->assertCount(2, $items);
        $item_ids = array_column($items, 'item_id');
        $this->assertEqualsCanonicalizing([$keep->id, $remove->id], $item_ids);

        // At this point, we keep the $keep course, add $add. $remove gets removed.
        linked_courses::set_linked_courses(
            $competency->id,
            [
                ['id' => $keep->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $add->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]
            ]
        );
        $sink->clear();

        items_processor::update_items($competency->id);
        $items = $DB->get_records('totara_criteria_item');
        $this->assertCount(2, $items);
        $item_ids = array_column($items, 'item_id');
        $this->assertEqualsCanonicalizing([$keep->id, $add->id], $item_ids);

        // Final step. Remove all courses from linked.
        // The idea of this test is to check it does remove when there are zero courses linked rather
        // than ignore thinking there's nothing to do.

        linked_courses::set_linked_courses(
            $competency->id,
            []
        );
        $sink->clear();

        items_processor::update_items($competency->id);
        $this->assertEquals(0, $DB->count_records('totara_criteria_item'));

        $sink->close();
    }

    public function test_update_items_criteria_check_queue_perform_no_assignments() {
        global $DB;

        \totara_core\advanced_feature::enable('competency_assignment');

        // We sink the events to prevent observer interference
        $sink = $this->redirectEvents();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency = new competency($comp->id);

        $this->set_up_pathway_with_linked_courses_criteria($competency);

        $add = $this->getDataGenerator()->create_course();
        $keep = $this->getDataGenerator()->create_course();
        $remove = $this->getDataGenerator()->create_course();

        linked_courses::set_linked_courses(
            $competency->id,
            [
                ['id' => $keep->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $remove->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $add->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]
            ]
        );

        $sink->clear();

        items_processor::update_items($competency->id);

        $items = $DB->get_records('totara_criteria_item');
        $this->assertCount(3, $items);
        $item_ids = array_column($items, 'item_id');
        $this->assertEqualsCanonicalizing([$keep->id, $remove->id, $add->id], $item_ids);

        // As no user was assigned nothing is in the queue
        $queue_table = new \totara_competency\aggregation_users_table();
        $queue = $DB->get_records($queue_table->get_table_name());
        $this->assertEmpty($queue);
    }

    public function test_update_items_criteria_check_queue_perform_with_assignments() {
        global $DB;

        \totara_core\advanced_feature::enable('competency_assignment');

        // We sink the events to prevent observer interference
        $sink = $this->redirectEvents();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency = new competency($comp->id);

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $assign_generator = $competency_generator->assignment_generator();
        $user1 = $assign_generator->create_user();
        $user2 = $assign_generator->create_user();
        $assign_generator->create_user_assignment($competency->id, $user1->id);
        $assign_generator->create_user_assignment($competency->id, $user2->id);

        (new \totara_competency\expand_task($DB))->expand_all();

        $this->set_up_pathway_with_linked_courses_criteria($competency);

        $add = $this->getDataGenerator()->create_course();
        $keep = $this->getDataGenerator()->create_course();
        $remove = $this->getDataGenerator()->create_course();

        linked_courses::set_linked_courses(
            $competency->id,
            [
                ['id' => $keep->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $remove->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $add->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]
            ]
        );

        $sink->clear();

        items_processor::update_items($competency->id);

        $items = $DB->get_records('totara_criteria_item');
        $this->assertCount(3, $items);
        $item_ids = array_column($items, 'item_id');
        $this->assertEqualsCanonicalizing([$keep->id, $remove->id, $add->id], $item_ids);

        // As no user was assigned nothing is in the queue
        $queue_table = new \totara_competency\aggregation_users_table();
        $queue = $DB->get_records($queue_table->get_table_name());
        $this->assertCount(2, $queue);

        $this->assertEquals([$competency->id, $competency->id], array_column($queue, 'competency_id'));
        $this->assertEqualsCanonicalizing([$user1->id, $user2->id], array_column($queue, 'user_id'));
    }

    public function test_update_items_criteria_check_queue_learn_only_no_completions() {
        global $DB;

        \totara_core\advanced_feature::disable('competency_assignment');

        // We sink the events to prevent observer interference
        $sink = $this->redirectEvents();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp1 = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $comp2 = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency1 = new competency($comp1->id);
        $competency2 = new competency($comp2->id);

        $this->set_up_pathway_with_linked_courses_criteria($competency1);
        $this->set_up_pathway_with_linked_courses_criteria($competency2);

        $add = $this->getDataGenerator()->create_course();
        $keep = $this->getDataGenerator()->create_course();
        $remove = $this->getDataGenerator()->create_course();

        linked_courses::set_linked_courses(
            $competency1->id,
            [
                ['id' => $keep->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $remove->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $add->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]
            ]
        );

        $sink->clear();

        items_processor::update_items($competency1->id);

        $items = $DB->get_records('totara_criteria_item');
        $this->assertCount(3, $items);
        $item_ids = array_column($items, 'item_id');
        $this->assertEqualsCanonicalizing([$keep->id, $remove->id, $add->id], $item_ids);

        // As no user was assigned nothing is in the queue
        $queue_table = new \totara_competency\aggregation_users_table();
        $queue = $DB->get_records($queue_table->get_table_name());
        $this->assertEmpty($queue);
    }

    public function test_update_items_criteria_check_queue_learn_only_with_completions() {
        global $DB;

        advanced_feature::disable('competency_assignment');

        $queue_table = new aggregation_users_table();

        // We sink the events to prevent observer interference
        $sink = $this->redirectEvents();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp1 = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $comp2 = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency1 = new competency($comp1->id);
        $competency2 = new competency($comp2->id);

        /** @var \totara_competency\testing\generator $competency_generator */
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->set_up_pathway_with_linked_courses_criteria($competency1);
        $this->set_up_pathway_with_linked_courses_criteria($competency2);

        $add = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $keep = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $remove = $this->getDataGenerator()->create_course(['enablecompletion' => true]);

        $sink->clear();

        linked_courses::set_linked_courses(
            $competency1->id,
            [
                ['id' => $keep->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $remove->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        items_processor::update_items($competency1->id);
        // As no user was assigned nothing is in the queue
        $this->assertCount(0, $DB->get_records($queue_table->get_table_name()));

        $completion = new completion_completion(['course' => $add->id, 'userid' => $user1->id]);
        $completion->mark_complete();
        $completion = new completion_completion(['course' => $keep->id, 'userid' => $user1->id]);
        $completion->mark_complete();

        $completion = new completion_completion(['course' => $remove->id, 'userid' => $user2->id]);
        $completion->mark_complete();

        linked_courses::set_linked_courses(
            $competency1->id,
            [
                ['id' => $keep->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $add->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]
            ]
        );

        items_processor::update_items($competency1->id);

        $items = $DB->get_records('totara_criteria_item');
        $this->assertCount(2, $items);
        $item_ids = array_column($items, 'item_id');
        $this->assertEqualsCanonicalizing([$keep->id, $add->id], $item_ids);

        // As no user was assigned nothing is in the queue
        $queue_table = new \totara_competency\aggregation_users_table();
        $queue = $DB->get_records($queue_table->get_table_name());
        $this->assertCount(2, $queue);

        $this->assertEquals([$competency1->id, $competency1->id], array_column($queue, 'competency_id'));
        $this->assertEqualsCanonicalizing([$user1->id, $user2->id], array_column($queue, 'user_id'));
    }

}
