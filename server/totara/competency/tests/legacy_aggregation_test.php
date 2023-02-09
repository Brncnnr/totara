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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

use aggregation_highest\highest;
use core\orm\collection;
use core\orm\query\builder;
use core_phpunit\testcase;
use criteria_childcompetency\childcompetency;
use criteria_coursecompletion\coursecompletion;
use criteria_linkedcourses\linkedcourses;
use pathway_criteria_group\criteria_group;
use pathway_criteria_group\entity\criteria_group_criterion as criteria_group_criterion_entity;
use totara_competency\achievement_configuration;
use totara_competency\aggregation_users_table;
use totara_competency\entity\configuration_change;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\legacy_aggregation;
use totara_competency\linked_courses;
use totara_competency\pathway;
use totara_core\advanced_feature;
use totara_criteria\criterion;
use totara_criteria\entity\criterion as criterion_entity;
use totara_hierarchy\entity\competency as competency_entity;
use totara_hierarchy\entity\scale as scale_entity;

/**
 * @group totara_competency
 */
class totara_competency_legacy_aggregation_testcase extends testcase {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
        require_once($CFG->dirroot . '/completion/completion_completion.php');
    }

    public function test_it_does_not_do_anything_with_perform_enabled() {
        advanced_feature::enable('competency_assignment');

        $data = $this->create_data();

        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->apply();

        $this->assert_not_has_criteria($data->competency->id);
        $this->assert_has_criteria($data->control_competency->id, criterion::AGGREGATE_ANY_N);
    }

    public function test_aggregation_set_from_off_to_all() {
        advanced_feature::disable('competency_assignment');

        $data = $this->create_data();

        // No criteria exist at the beginning
        $this->assert_not_has_criteria($data->competency->id);
        $this->assert_has_criteria($data->control_competency->id, criterion::AGGREGATE_ANY_N);

        // Set it to off
        $data->competency->aggregationmethod = competency::AGGREGATION_METHOD_ALL;
        $data->competency->save();

        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->apply();

        $this->assert_has_criteria($data->competency->id, criterion::AGGREGATE_ALL);
        $this->assert_has_criteria($data->control_competency->id, criterion::AGGREGATE_ANY_N);
    }

    public function test_aggregation_set_from_off_to_any_with_one_existing_criteria() {
        advanced_feature::disable('competency_assignment');

        $data = $this->create_data();
        $min_proficient_value = $data->scale->min_proficient_value;

        // No criteria exist at the beginning
        $this->assert_not_has_criteria($data->competency->id);
        $this->assert_has_criteria($data->control_competency->id, criterion::AGGREGATE_ANY_N);

        // Set it to off
        $data->competency->aggregationmethod = competency::AGGREGATION_METHOD_ANY;
        $data->competency->save();

        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->create_default_criteria(new linkedcourses(), $min_proficient_value);

        $this->assertCount(1, $this->get_criteria($data->competency->id, (new linkedcourses())->get_plugin_type()));
        $this->assertCount(0, $this->get_criteria($data->competency->id, (new childcompetency())->get_plugin_type()));

        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->apply();

        // Even with one preexisting we should end up with the two default ones
        $this->assert_has_criteria($data->competency->id, criterion::AGGREGATE_ANY_N);
        $this->assert_has_criteria($data->control_competency->id, criterion::AGGREGATE_ANY_N);
    }

    public function test_setting_aggregation_to_off_removes_default_criteria() {
        advanced_feature::disable('competency_assignment');

        $data = $this->create_data();
        $min_proficient_value = $data->scale->min_proficient_value;

        // Make sure there are criteria first
        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->create_default_criteria(new linkedcourses(), $min_proficient_value)
            ->create_default_criteria(new childcompetency(), $min_proficient_value);

        // Now switch aggregation OFF
        $data->competency->aggregationmethod = competency::AGGREGATION_METHOD_OFF;
        $data->competency->save();

        // Make sure we start with criteria
        $this->assert_has_criteria($data->competency->id, criterion::AGGREGATE_ANY_N);
        $this->assert_has_criteria($data->control_competency->id, criterion::AGGREGATE_ANY_N);

        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->apply();

        // It should have removed the criteria
        $this->assert_not_has_criteria($data->competency->id);
        // The control competency's criteria should still be there
        $this->assert_has_criteria($data->control_competency->id, criterion::AGGREGATE_ANY_N);
    }

    public function test_changing_aggregation_method_queues_for_aggregation() {
        advanced_feature::disable('competency_assignment');

        $data = $this->create_data();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $completion = new completion_completion(['course' => $course1->id, 'userid' => $user1->id]);
        $completion->mark_complete();
        $completion = new completion_completion(['course' => $course2->id, 'userid' => $user2->id]);
        $completion->mark_complete();
        $completion = new completion_completion(['course' => $course3->id, 'userid' => $user3->id]);
        $completion->mark_complete();

        linked_courses::set_linked_courses(
            $data->competency->id,
            [
                [
                    'id' => $course1->id,
                    'linktype' => linked_courses::LINKTYPE_MANDATORY,
                ],
                [
                    'id' => $course2->id,
                    'linktype' => linked_courses::LINKTYPE_MANDATORY,
                ],
            ]
        );

        // No criteria exist at the beginning
        $this->assert_not_has_criteria($data->competency->id);
        $this->assert_has_criteria($data->control_competency->id, criterion::AGGREGATE_ANY_N);

        // Set it to off
        $data->competency->aggregationmethod = competency::AGGREGATION_METHOD_ALL;
        $data->competency->save();

        $this->assertFalse(
            configuration_change::repository()
                ->where('competency_id', $data->competency->id)
                ->where('change_type', configuration_change::CHANGED_COMPETENCY_AGGREGATION)
                ->exists()
        );

        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->apply();

        $this->assert_has_criteria($data->competency->id, criterion::AGGREGATE_ALL);
        $this->assert_has_criteria($data->control_competency->id, criterion::AGGREGATE_ANY_N);

        $source_table = new aggregation_users_table();
        $queue_entries = builder::table($source_table->get_table_name())->get();
        $this->assertCount(2, $queue_entries);
        $this->assertEquals([$data->competency->id], array_unique($queue_entries->pluck('competency_id')));
        $this->assertEqualsCanonicalizing([$user1->id, $user2->id], $queue_entries->pluck('user_id'));

        $this->assertTrue(
            configuration_change::repository()
                ->where('competency_id', $data->competency->id)
                ->where('change_type', configuration_change::CHANGED_COMPETENCY_AGGREGATION)
                ->exists()
        );
    }

    public function test_setting_aggregation_to_off_in_combination_with_multiple_criteria() {
        advanced_feature::disable('competency_assignment');

        $data = $this->create_data();
        $min_proficient_value = $data->scale->min_proficient_value;

        // Make sure there are criteria first
        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->create_default_criteria(new linkedcourses(), $min_proficient_value)
            ->create_default_criteria(new childcompetency(), $min_proficient_value);

        $group = new criteria_group();
        $group->set_competency($data->competency);
        $group->set_scale_value($min_proficient_value);

        // Make sure there's a third criteria which contain linkedcourses and childcompetencies
        // But also a third criteria which should stay untouched
        $criterion = new childcompetency();
        $criterion->set_aggregation_method(criterion::AGGREGATE_ALL);
        $criterion->set_competency_id($data->competency->id);
        $group->add_criterion($criterion);

        $criterion = new linkedcourses();
        $criterion->set_aggregation_method(criterion::AGGREGATE_ALL);
        $criterion->set_competency_id($data->competency->id);
        $group->add_criterion($criterion);

        $criterion = new coursecompletion();
        $criterion->set_aggregation_method(criterion::AGGREGATE_ALL);
        $criterion->set_competency_id($data->competency->id);
        $group->add_criterion($criterion);

        // Save the group with three criterions
        $group->save();

        // Now switch aggregation OFF
        $data->competency->aggregationmethod = competency::AGGREGATION_METHOD_OFF;
        $data->competency->save();

        // Should have linkedcourses and childcompetency
        $this->assertCount(2, $this->get_criteria($data->competency->id, (new linkedcourses())->get_plugin_type()));
        $this->assertCount(2, $this->get_criteria($data->competency->id, (new childcompetency())->get_plugin_type()));
        // As well as coursecompletion
        $this->assertCount(1, $this->get_criteria($data->competency->id, (new coursecompletion())->get_plugin_type()));

        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->apply();

        // linkedcourses and childcompetency should be gone
        $this->assertCount(0, $this->get_criteria($data->competency->id, (new linkedcourses())->get_plugin_type()));
        $this->assertCount(0, $this->get_criteria($data->competency->id, (new childcompetency())->get_plugin_type()));
        // Still has coursecompletion
        $this->assertCount(1, $this->get_criteria($data->competency->id, (new coursecompletion())->get_plugin_type()));
    }

    public function test_setting_aggregation_from_any_to_all() {
        advanced_feature::disable('competency_assignment');

        $data = $this->create_data();
        $min_proficient_value = $data->scale->min_proficient_value;

        // Now switch aggregation ANY
        $data->competency->aggregationmethod = competency::AGGREGATION_METHOD_ANY;
        $data->competency->save();

        // Make sure there are criteria first
        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->create_default_criteria(new linkedcourses(), $min_proficient_value)
            ->create_default_criteria(new childcompetency(), $min_proficient_value);

        // All criteria should now be set to ANY
        $this->assert_has_criteria($data->competency->id, criterion::AGGREGATE_ANY_N);

        // Now switch aggregation ALL
        $data->competency->aggregationmethod = competency::AGGREGATION_METHOD_ALL;
        $data->competency->save();

        // Apply it
        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->apply();

        // Criteria should still be there but aggregation should have changed
        $this->assert_has_criteria($data->competency->id, criterion::AGGREGATE_ALL);
        $this->assert_has_criteria($data->control_competency->id, criterion::AGGREGATE_ANY_N);
    }

    public function test_setting_aggregation_from_all_to_any() {
        advanced_feature::disable('competency_assignment');

        $data = $this->create_data();
        $min_proficient_value = $data->scale->min_proficient_value;

        // Now switch aggregation ALL
        $data->competency->aggregationmethod = competency::AGGREGATION_METHOD_ALL;
        $data->competency->save();

        // Make sure there are criteria first
        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->create_default_criteria(new linkedcourses(), $min_proficient_value)
            ->create_default_criteria(new childcompetency(), $min_proficient_value);

        // All criteria should now be set to ALL
        $this->assert_has_criteria($data->competency->id, criterion::AGGREGATE_ALL);

        // Now switch aggregation ANY
        $data->competency->aggregationmethod = competency::AGGREGATION_METHOD_ANY;
        $data->competency->save();

        // Apply it
        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->apply();

        // Criteria should still be there but aggregation should have changed
        $this->assert_has_criteria($data->competency->id, criterion::AGGREGATE_ANY_N);
    }

    public function test_create_default_pathways_with_existing_criteria() {
        advanced_feature::disable('competency_assignment');

        $data = $this->create_data();

        $previous_criteria1 = $this->get_criteria($data->competency->id);
        $previous_criteria2 = $this->get_criteria($data->control_competency->id);

        $aggregation = new legacy_aggregation($data->control_competency);
        $aggregation->create_default_pathways();

        // This should not have changed anything as we already have the defaults
        $criteria1 = $this->get_criteria($data->competency->id);
        $this->assertEquals($previous_criteria1, $criteria1);
        $criteria2 = $this->get_criteria($data->control_competency->id);
        $this->assertEquals($previous_criteria2, $criteria2);
    }

    public function test_create_default_pathways_with_no_criteria() {
        advanced_feature::disable('competency_assignment');

        $data = $this->create_data();

        $previous_criteria = $this->get_criteria($data->control_competency->id);

        $aggregation = new legacy_aggregation($data->control_competency);
        $aggregation->create_default_pathways();

        // This should not have changed anything as we already have the defaults
        $criteria = $this->get_criteria($data->control_competency->id);
        $this->assertEquals($previous_criteria, $criteria);
        // This one should not be touched
        $this->assert_not_has_criteria($data->competency->id);
    }

    public function test_create_default_pathways() {
        advanced_feature::disable('competency_assignment');

        $data = $this->create_data();

        $previous_criteria = $this->get_criteria($data->control_competency->id);

        // Make sure we start with no criteria
        $this->assert_not_has_criteria($data->competency->id);

        $aggregation = new legacy_aggregation($data->competency);
        $aggregation->create_default_pathways();

        // This should not have changed anything as we already have the defaults
        $criteria = $this->get_criteria($data->control_competency->id);
        $this->assertEquals($previous_criteria, $criteria);

        // Assert that we have the new default criteria created
        $this->asset_has_default_pathways($data->competency);
    }

    protected function create_data() {
        $data = new class {
            /** @var scale_entity */
            public $scale;
            public $fw;
            /** @var competency_entity $competency */
            public $competency;
            /** @var competency_entity $contril_competency */
            public $control_competency;
        };

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator =  $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $scale = $hierarchy_generator->create_scale(
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

        /** @var scale_entity $scale */
        $data->scale = scale_entity::repository()->find($scale->id);
        $min_proficient_value = $data->scale->min_proficient_value;

        // We don't want the create event fired here
        $sink = $this->redirectEvents();

        $fw = $hierarchy_generator->create_comp_frame(['fullname' => 'Framework one', 'idnumber' => 'f1', 'scale' => $scale->id]);
        $comp = $hierarchy_generator->create_comp([
            'frameworkid' => $fw->id,
            'idnumber' => 'c1',
            'parentid' => 0,
            'aggregationmethod' => \competency::AGGREGATION_METHOD_ANY
        ]);

        $data->competency = new competency_entity($comp);

        $comp2 = $hierarchy_generator->create_comp([
            'frameworkid' => $fw->id,
            'idnumber' => 'c1',
            'parentid' => 0,
            'aggregationmethod' => \competency::AGGREGATION_METHOD_ANY
        ]);
        $data->control_competency = new competency_entity($comp2);

        // Make sure there are criteria for the control competency
        $aggregation = new legacy_aggregation($data->control_competency);
        $aggregation->create_default_pathways();

        // Stop redirecting events from now
        $sink->close();

        return $data;
    }

    protected function assert_not_has_criteria(int $competency_id) {
        $criteria = $this->get_criteria($competency_id);
        $this->assertEquals(0, count($criteria), 'Expected no default criteria to be present');
    }

    protected function assert_has_criteria(int $competency_id, int $aggregation_method) {
        $criteria = $this->get_criteria($competency_id);
        $this->assertGreaterThanOrEqual(2, count($criteria), 'Expected default criteria not found');
        // There should only be one aggregation method for all results
        $this->assertEquals(
            [$aggregation_method],
            array_unique($criteria->pluck('aggregation_method')),
            'Criteria does not have expected aggregation method'
        );
    }

    protected function get_criteria(int $competency_id, $plugin_types = null): collection {
        if (empty($plugin_types)) {
            $plugin_types = [
                (new linkedcourses())->get_plugin_type(),
                (new childcompetency())->get_plugin_type(),
            ];
        }

        return criterion_entity::repository()
            ->join([criteria_group_criterion_entity::TABLE, 'cgc'], 'id', 'criterion_id')
            ->join([pathway_entity::TABLE, 'pw'], 'cgc.criteria_group_id', 'pw.path_instance_id')
            ->where('plugin_type', $plugin_types)
            ->where('pw.competency_id', $competency_id)
            ->where('pw.status', pathway::PATHWAY_STATUS_ACTIVE)
            ->get();
    }

    protected function asset_has_default_pathways(competency_entity $competency) {
        // Should have linked courses and child competencies
        $this->assertCount(2, $this->get_criteria($competency->id));

        $has_learning_plan = pathway_entity::repository()
            ->where('competency_id', $competency->id)
            ->where('path_type', 'learning_plan')
            ->where('sortorder', 1)
            ->exists();
        $this->assertTrue($has_learning_plan, 'Learning plan pathway not found');

        $achievement_configuration = new achievement_configuration($competency);
        $this->assertTrue($achievement_configuration->has_aggregation_type(highest::aggregation_type()));
    }

}
