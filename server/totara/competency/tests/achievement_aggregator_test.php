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
 * @package totara_competency
 */

use aggregation_test_aggregation\test_aggregation;
use core\collection;
use core_phpunit\testcase;
use hierarchy_competency\event\scale_min_proficient_value_updated;
use pathway_manual\models\roles\manager;
use pathway_test_pathway\test_pathway_evaluator;
use totara_competency\achievement_configuration;
use totara_competency\aggregation_users_table;
use totara_competency\competency_achievement_aggregator;
use totara_competency\competency_aggregator_user_source;
use totara_competency\entity\achievement_via;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\pathway_achievement;
use totara_competency\expand_task;
use totara_competency\hook\competency_achievement_updated_bulk;
use totara_competency\linked_courses;
use totara_competency\min_proficiency_override_for_assignments;
use totara_competency\models\assignment_actions;
use totara_competency\overall_aggregation;
use totara_competency\pathway;
use totara_competency\pathway_evaluator_user_source;
use totara_competency\task\competency_aggregation_queue;
use totara_competency\testing\assignment_generator;
use totara_competency\testing\generator as competency_generator;
use totara_competency\user_groups;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_framework;
use totara_hierarchy\entity\scale_value;
use totara_job\job_assignment;

/**
 * Class totara_competency_achievement_aggregator_testcase
 *
 * Tests behaviour of the competency_achievement_aggregator class.
 * @group totara_competency
 */
class totara_competency_achievement_aggregator_testcase extends testcase {

    protected function setUp(): void {
        parent::setUp();
        // individual tests may disable the feature
        advanced_feature::enable('competency_assignment');
    }

    /**
     * @param pathway_achievement[] $pathway_achievements
     * @return overall_aggregation
     */
    private function create_aggregation_method_achieved_by($pathway_achievements): overall_aggregation {
        $test_aggregation = new test_aggregation();
        $achieved_values = [];
        $achieved_vias = [];
        foreach ($pathway_achievements as $pathway_achievement) {
            $user_id = $pathway_achievement->user_id;
            $achieved_values[$user_id] = $pathway_achievement->scale_value;
            if (!isset($achieved_vias[$user_id])) {
                $achieved_vias[$user_id] = [];
            }
            $achieved_vias[$user_id][] = $pathway_achievement;
        }
        $test_aggregation->set_test_aggregated_data($achieved_values, $achieved_vias);

        return $test_aggregation;
    }

    private function generate_active_expanded_user_assignments($competency, $users, $assignments_per_user = 1) {
        global $DB;

        /** @var assignment_generator $assignment_generator */
        $assignment_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();

        $assignment_ids = [];
        foreach ($users as $user) {
            for ($i = 0; $i < $assignments_per_user; $i++) {
                $assignment = $assignment_generator->create_user_assignment(
                    $competency->id,
                    $user->id,
                    ['status' => assignment::STATUS_ACTIVE]
                );
                $assignment_ids[] = $assignment->id;
            }
        }

        $expand_task = new expand_task($DB);
        $expand_task->expand_all();

        return $assignment_ids;
    }

    public function test_with_no_users() {
        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);

        $source_table = new aggregation_users_table();
        $user_source = new competency_aggregator_user_source($source_table, true);
        $aggregator = new competency_achievement_aggregator($achievement_configuration, $user_source);

        $sink = $this->redirectHooks();
        // We're mainly testing that aggregate completes without an exception.
        $aggregator->aggregate();
        $this->assertEquals(0, $sink->count());
    }

    public function test_with_one_user_requiring_completion() {
        $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency();

        /** @var scale_value $scale_value */
        $scale_value = $competency->scale->sorted_values_high_to_low->first();

        $pathway = $competency_generator->create_test_pathway($competency);
        $pathway->set_test_aggregate_current_value($scale_value);

        $achievement_configuration = new achievement_configuration($competency);
        $achievement_configuration->set_aggregation_type('test_aggregation');

        $user = $this->getDataGenerator()->create_user();
        $this->generate_active_expanded_user_assignments($competency, [$user]);

        $this->aggregate_pathway($pathway, $user);

        $this->assertEquals(0, competency_achievement::repository()->count());

        $sink = $this->redirectHooks();
        $aggregator = $this->get_competency_aggregator_for_pathway_and_user($pathway, $user);
        $pw_achievement = pathway_achievement::get_current($pathway, $user->id);
        $aggregator->aggregate();
        $hooks = $sink->get_hooks();

        $achievements = competency_achievement::repository()->get();
        $this->assertCount(1, $achievements);
        $achievement = $achievements->shift();
        $this->assertEquals($scale_value->id, $achievement->scale_value_id);

        $via_records = achievement_via::repository()->get();
        $this->assertCount(1, $via_records);
        $via_record = $via_records->shift();
        $this->assertEquals($pw_achievement->id, $via_record->pathway_achievement_id);

        $hook = reset($hooks);
        $this->assertInstanceOf(competency_achievement_updated_bulk::class, $hook);
        $sink->close();
    }

    public function test_with_one_user_requiring_completion_via_two_pathways() {
        global $DB;

        $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency();

        /** @var scale_value $scale_value */
        $scale_value = $competency->scale->sorted_values_high_to_low->first();

        // Two pathways that will return the same scale_value.
        $pathway1 = $competency_generator->create_test_pathway($competency);
        $pathway1->set_test_aggregate_current_value($scale_value);
        $pathway2 = $competency_generator->create_test_pathway($competency);
        $pathway2->set_test_aggregate_current_value($scale_value);

        $achievement_configuration = new achievement_configuration($competency);
        $achievement_configuration->set_aggregation_type('test_aggregation');

        $user = $this->getDataGenerator()->create_user();
        $this->generate_active_expanded_user_assignments($competency, [$user]);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        (new test_pathway_evaluator($pathway1, $pw_user_source))->aggregate(time());
        (new test_pathway_evaluator($pathway2, $pw_user_source))->aggregate(time());

        $achievement1 = pathway_achievement::get_current($pathway1, $user->id);
        $achievement2 = pathway_achievement::get_current($pathway2, $user->id);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $comp_user_source = new competency_aggregator_user_source($source_table, true);
        $aggregator = new competency_achievement_aggregator($achievement_configuration, $comp_user_source);

        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement1, $achievement2]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $this->assertEquals(0, $DB->count_records('totara_competency_achievement'));

        $sink = $this->redirectHooks();
        $aggregator->aggregate();
        $hooks = $sink->get_hooks();

        $comp_records = $DB->get_records('totara_competency_achievement');
        $this->assertCount(1, $comp_records);
        $comp_record = reset($comp_records);
        $this->assertEquals($scale_value->id, $comp_record->scale_value_id);

        $via_records = $DB->get_records('totara_competency_achievement_via');
        $this->assertCount(2, $via_records);
        $achievement_ids = [$achievement1->id, $achievement2->id];
        $via_record1 = array_pop($via_records);
        $this->assertContainsEquals($via_record1->pathway_achievement_id, $achievement_ids);
        $via_record2 = array_pop($via_records);
        $this->assertContainsEquals($via_record2->pathway_achievement_id, $achievement_ids);

        // This should ensure that they we did get a via record for both achievements.
        $this->assertNotEquals($via_record1->pathway_achievement_id, $via_record2->pathway_achievement_id);

        $hook = reset($hooks);
        $this->assertInstanceOf(competency_achievement_updated_bulk::class, $hook);
        $sink->close();
    }

    public function test_one_user_from_two_via_records_to_one() {
        global $DB;

        $competency_generator = $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency();

        /** @var scale_value $scale_value */
        $scale_value = $competency->scale->sorted_values_high_to_low->first();

        // Two pathways that will return the same scale_value.
        $pathway1 = $competency_generator->create_test_pathway($competency);
        $pathway1->set_test_aggregate_current_value($scale_value);
        $pathway2 = $competency_generator->create_test_pathway($competency);
        $pathway2->set_test_aggregate_current_value($scale_value);

        $achievement_configuration = new achievement_configuration($competency);
        $achievement_configuration->set_aggregation_type('test_aggregation');

        $user = $this->getDataGenerator()->create_user();
        $this->generate_active_expanded_user_assignments($competency, [$user]);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        (new test_pathway_evaluator($pathway1, $pw_user_source))->aggregate(time());
        (new test_pathway_evaluator($pathway2, $pw_user_source))->aggregate(time());

        $achievement1 = pathway_achievement::get_current($pathway1, $user->id);
        $achievement2 = pathway_achievement::get_current($pathway2, $user->id);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $comp_user_source = new competency_aggregator_user_source($source_table, true);
        $aggregator = new competency_achievement_aggregator($achievement_configuration, $comp_user_source);

        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement1, $achievement2]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $this->assertEquals(0, $DB->count_records('totara_competency_achievement'));

        $aggregator->aggregate();

        // This point is tested more in previous tests.
        $this->assertEquals(1, $DB->count_records('totara_competency_achievement', ['status' => 0]));
        $this->assertEquals(2, $DB->count_records('totara_competency_achievement_via'));

        // We'll replace the aggregation instance with one that will just say the user achieved their score via #2.
        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement2]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $sink = $this->redirectHooks();
        $aggregator->aggregate();
        // Hooks aren't triggered if the value isn't updated.
        $this->assertEquals(0, $sink->count());

        // Check achieved value. Just to make sure it hasn't been set to null or some such thing when the other
        // achievement was taken away.
        $comp_records = $DB->get_records('totara_competency_achievement');
        $this->assertCount(1, $comp_records);
        $comp_record = reset($comp_records);
        $this->assertEquals($scale_value->id, $comp_record->scale_value_id);

        // The value didn't change. So no via records are dropped. The via records give how the value was attained
        // at the time that they achieved the value.
        $via_records = $DB->get_records('totara_competency_achievement_via');
        $this->assertCount(2, $via_records);
        $via_record = array_pop($via_records);
        $this->assertEquals($via_record->pathway_achievement_id, $achievement2->id);
    }

    public function test_one_user_from_having_value_to_null() {
        global $DB;

        $competency_generator = $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency();

        /** @var scale_value $scale_value */
        $scale_value = $competency->scale->sorted_values_high_to_low->first();

        // Two pathways that will return the same scale_value.
        $pathway1 = $competency_generator->create_test_pathway($competency);
        $pathway1->set_test_aggregate_current_value($scale_value);
        $pathway2 = $competency_generator->create_test_pathway($competency);
        $pathway2->set_test_aggregate_current_value($scale_value);

        $achievement_configuration = new achievement_configuration($competency);
        $achievement_configuration->set_aggregation_type('test_aggregation');

        $user = $this->getDataGenerator()->create_user();
        $this->generate_active_expanded_user_assignments($competency, [$user]);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        (new test_pathway_evaluator($pathway1, $pw_user_source))->aggregate(time());
        (new test_pathway_evaluator($pathway2, $pw_user_source))->aggregate(time());

        $achievement1 = pathway_achievement::get_current($pathway1, $user->id);
        $achievement2 = pathway_achievement::get_current($pathway2, $user->id);

        $comp_user_source = new competency_aggregator_user_source($source_table, true);
        $aggregator = new competency_achievement_aggregator($achievement_configuration, $comp_user_source);

        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement1, $achievement2]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $this->assertEquals(0, $DB->count_records('totara_competency_achievement'));

        $aggregator->aggregate();

        // This point is tested more in previous tests.
        $this->assertEquals(1, $DB->count_records('totara_competency_achievement'));
        $this->assertEquals(2, $DB->count_records('totara_competency_achievement_via'));

        // We'll replace the aggregation instance with one that will just say the user achieved their score via #2.
        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement2]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $aggregator->aggregate();

        // Change what value the pathways return. We'll then need to update the achievements used.
        $pathway1->set_test_aggregate_current_value(null);
        $pathway2->set_test_aggregate_current_value(null);

        (new test_pathway_evaluator($pathway1, $pw_user_source))->aggregate(time());
        (new test_pathway_evaluator($pathway2, $pw_user_source))->aggregate(time());

        $achievement1 = pathway_achievement::get_current($pathway1, $user->id);
        $achievement2 = pathway_achievement::get_current($pathway2, $user->id);

        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement1, $achievement2]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $sink = $this->redirectHooks();
        $aggregator->aggregate();
        $hooks = $sink->get_hooks();

        // Order by newest at they back so they can be popped off in that order.
        $comp_records = $DB->get_records('totara_competency_achievement', [], 'time_created ASC, id ASC');
        $this->assertCount(2, $comp_records);
        $comp_record = array_pop($comp_records);
        $this->assertNull($comp_record->scale_value_id);
        $comp_record = array_pop($comp_records);
        $this->assertEquals($scale_value->id, $comp_record->scale_value_id);

        // We have 2 more via records because the aggregation method returned 2 pathway achievements.
        // These are not filtered out by the competency_achievement_aggregator just because they are null.
        // Todo: consider what behaviour meets our needs here:
        // 1. Aggregation methods should return all pathways with a null value
        // 2. Aggregation methods shouldn't return all pathways with a null value.
        //    This is how it currently works. There are logical issues with 1 such as when null achievements are a placeholder
        // And if the aggregation method does return a null value for it's 'via' record,
        // should the competency_achievement_aggregator save it or not.
        $this->assertEquals(4, $DB->count_records('totara_competency_achievement_via'));

        // The value changed, so a hook was executed.
        $hook = reset($hooks);
        $this->assertInstanceOf(competency_achievement_updated_bulk::class, $hook);
        $sink->close();
    }

    public function test_one_user_with_change_in_scale_value() {
        $competency_generator = $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency();

        /** @var scale_value $scale_value1 */
        $scale = $competency->scale;
        $values = $scale->sorted_values_high_to_low;

        $scale_value1 = $values->first();
        $values->next();
        /** @var scale_value $scale_value2 */
        $scale_value2 = $values->current();

        $pathway = $competency_generator->create_test_pathway($competency);
        $pathway->set_test_aggregate_current_value($scale_value1);

        $user = $this->getDataGenerator()->create_user();
        $this->generate_active_expanded_user_assignments($competency, [$user]);

        $this->aggregate_pathway($pathway, $user);

        $pw_achievement1 = pathway_achievement::get_current($pathway, $user->id);

        $this->assertEquals(0, competency_achievement::repository()->count());

        $aggregator = $this->get_competency_aggregator_for_pathway_and_user($pathway, $user);
        $aggregator->aggregate();

        // Should all be about scale value and achievement #1.
        $achievements = competency_achievement::repository()->get();
        $this->assertCount(1, $achievements);
        $achievement = $achievements->shift();
        $this->assertEquals($scale_value1->id, $achievement->scale_value_id);

        $via_records = achievement_via::repository()->get();
        $this->assertCount(1, $via_records);
        $via_record = $via_records->pop();
        $this->assertEquals($via_record->pathway_achievement_id, $pw_achievement1->id);

        $this->assertNotEquals($scale_value1->id, $scale_value2->id);

        $pathway2 = $competency_generator->create_test_pathway($competency);
        $pathway2->set_test_aggregate_current_value($scale_value2);

        $this->aggregate_pathway($pathway2, $user);
        $pw_achievement2 = pathway_achievement::get_current($pathway2, $user->id);

        $sink = $this->redirectHooks();
        $aggregator = $this->get_competency_aggregator_for_pathway_and_user($pathway2, $user);
        $aggregator->aggregate();
        $hooks = $sink->get_hooks();

        // Order by newest at they back so they can be popped off in that order.
        $achievements = competency_achievement::repository()
            ->order_by('time_created', 'asc')
            ->order_by('id', 'asc')
            ->get();
        $this->assertCount(2, $achievements);
        $comp_record2 = $achievements->pop();
        $this->assertEquals($scale_value2->id, $comp_record2->scale_value_id);
        $comp_record1 = $achievements->pop();
        $this->assertEquals($scale_value1->id, $comp_record1->scale_value_id);

        $via_records = achievement_via::repository()
            ->order_by('id', 'asc')
            ->get();
        $this->assertCount(2, $via_records);
        $via_record = $via_records->pop();
        $this->assertEquals($via_record->comp_achievement_id, $comp_record2->id);
        $via_record = $via_records->pop();
        $this->assertEquals($via_record->comp_achievement_id, $comp_record1->id);

        // The value changed, so a hook was triggered.
        $hook = reset($hooks);
        $this->assertInstanceOf(competency_achievement_updated_bulk::class, $hook);
        $sink->close();
    }

    public function test_with_one_user_with_two_assignments_requiring_completion() {
        global $DB;

        $competency_generator = $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency();

        /** @var scale_value $scale_value */
        $scale_value = $competency->scale->sorted_values_high_to_low->first();

        $pathway = $competency_generator->create_test_pathway($competency);
        $pathway->set_test_aggregate_current_value($scale_value);

        $achievement_configuration = new achievement_configuration($competency);
        $achievement_configuration->set_aggregation_type('test_aggregation');

        $user = $this->getDataGenerator()->create_user();
        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        (new test_pathway_evaluator($pathway, $pw_user_source))->aggregate(time());
        $assignmentids = $this->generate_active_expanded_user_assignments($competency, [$user], 2);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $comp_user_source = new competency_aggregator_user_source($source_table, true);
        $aggregator = new competency_achievement_aggregator($achievement_configuration, $comp_user_source);

        $achievement = pathway_achievement::get_current($pathway, $user->id);
        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $this->assertEquals(0, $DB->count_records('totara_competency_achievement'));

        $sink = $this->redirectHooks();
        $aggregator->aggregate();
        $hooks = $sink->get_hooks();

        $comp_records = $DB->get_records('totara_competency_achievement');
        $this->assertCount(2, $comp_records);
        $comp_record1 = array_pop($comp_records);
        $this->assertEquals($scale_value->id, $comp_record1->scale_value_id);
        $this->assertEquals(0, $comp_record1->status);
        $comp_record2 = array_pop($comp_records);
        $this->assertEquals($scale_value->id, $comp_record2->scale_value_id);
        $this->assertEquals(0, $comp_record2->status);
        $this->assertNotEquals($comp_record1->assignment_id, $comp_record2->assignment_id);

        $via_records = $DB->get_records('totara_competency_achievement_via');
        $this->assertCount(2, $via_records);
        $via_record = reset($via_records);
        $this->assertEquals($achievement->id, $via_record->pathway_achievement_id);

        $hook = reset($hooks);
        $this->assertInstanceOf(competency_achievement_updated_bulk::class, $hook);

        // Follow-on scenario. One of the assignments is archived

        $disable_assignment_id = array_pop($assignmentids);

        // Don't trigger events for archiving
        $events_sink = $this->redirectEvents();
        $model = new assignment_actions();
        $model->archive([$disable_assignment_id]);
        $expand_task = new expand_task($DB);
        $expand_task->expand_all();
        $events_sink->close();

        $aggregator = new competency_achievement_aggregator($achievement_configuration, $comp_user_source);
        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement]);
        $aggregator->set_aggregation_instance($aggregation_method);
        $sink->clear();
        $aggregator->aggregate();
        $hooks = $sink->get_hooks();

        $comp_records = $DB->get_records('totara_competency_achievement');
        $this->assertCount(2, $comp_records);
        foreach ($comp_records as $comp_record) {
            if ($comp_record->assignment_id == $disable_assignment_id) {
                $this->assertEquals($scale_value->id, $comp_record->scale_value_id);
                $this->assertEquals(1, $comp_record->status);
            } else {
                $this->assertEquals($scale_value->id, $comp_record->scale_value_id);
                $this->assertEquals(0, $comp_record->status);
            }
        }

        $via_records = $DB->get_records('totara_competency_achievement_via');
        $this->assertCount(2, $via_records);
        $via_record = reset($via_records);
        $this->assertEquals($achievement->id, $via_record->pathway_achievement_id);

        $this->assertCount(0, $hooks);
        $sink->close();
    }

    public function test_change_in_minimum_proficiency() {
        // When the minimum proficient value of a scale changes. We'll need to see if that means
        // any comp records with active assignments should become superseded and replaced with a new one
        // (which is just the case if the scale value they had has gone from proficient to not or vice versa).

        $competency_generator = $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency();

        $keyed_scale_values = $competency
            ->scale
            ->sorted_values_high_to_low
            ->key_by('sortorder')
            ->all(true);

        $min_proficient_value = $competency->scale->min_proficient_value;
        $non_proficient_value = $keyed_scale_values[$min_proficient_value->sortorder + 1];

        // Make sure we got the right values
        $this->assertInstanceOf(scale_value::class, $non_proficient_value);
        $this->assertEquals(0, $non_proficient_value->proficient);
        $this->assertInstanceOf(scale_value::class, $min_proficient_value);
        $this->assertEquals(1, $min_proficient_value->proficient);
        $this->assertEquals($min_proficient_value->id, $competency->scale->minproficiencyid);

        $user = $this->getDataGenerator()->create_user();

        $pathway = $competency_generator->create_test_pathway($competency);
        $pathway->set_test_aggregate_current_value($non_proficient_value);

        $this->aggregate_pathway($pathway, $user);

        $this->generate_active_expanded_user_assignments($competency, [$user], 1);

        $this->assertEquals(0, competency_achievement::repository()->count());

        $aggregator = $this->get_competency_aggregator_for_pathway_and_user($pathway, $user);
        $aggregator->aggregate();

        $achievements = competency_achievement::repository()->get();
        $this->assertEquals(1, $achievements->count());
        /** @var competency_achievement $achievement */
        $achievement = $achievements->first();

        // The user has an achievement but is not considered proficient
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement->status);
        $this->assertEquals(0, $achievement->proficient);

        // alright, now change the minimum proficiency value and trigger the event
        // which is the quickest way of queuing the change
        $scale = $competency->scale;
        $scale->minproficiencyid = $non_proficient_value->id;
        $scale->save();

        $non_proficient_value->proficient = 1;
        $non_proficient_value->save();

        scale_min_proficient_value_updated::create_from_instance((object)$scale->to_array())->trigger();

        $aggregator = $this->get_competency_aggregator_for_pathway_and_user($pathway, $user);
        $aggregator->aggregate();

        $achievements = competency_achievement::repository()
            ->order_by('id', 'asc')
            ->get();
        $this->assertEquals(2, $achievements->count());

        // The first one is the old proficient one, now superseded
        $achievement = $achievements->shift();
        $this->assertEquals(competency_achievement::SUPERSEDED, $achievement->status);
        $this->assertEquals(0, $achievement->proficient);
        $this->assertEquals($non_proficient_value->id, $achievement->scale_value_id);

        // The second one is the new one with the same scale value id but without being proficient
        $achievement = $achievements->shift();
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement->status);
        $this->assertEquals(1, $achievement->proficient);
        $this->assertEquals($non_proficient_value->id, $achievement->scale_value_id);

        // ok now change it back and see if it also switches back to proficient
        $scale = $competency->scale;
        $scale->minproficiencyid = $min_proficient_value->id;
        $scale->save();

        $non_proficient_value->proficient = 0;
        $non_proficient_value->save();

        scale_min_proficient_value_updated::create_from_instance((object)$scale->to_array())->trigger();

        $aggregator = $this->get_competency_aggregator_for_pathway_and_user($pathway, $user);
        $aggregator->aggregate();

        $achievements = competency_achievement::repository()
            ->order_by('id', 'asc')
            ->get();
        $this->assertEquals(3, $achievements->count());

        // The first one is the old proficient one, now superseded
        $achievement = $achievements->shift();
        $this->assertEquals(competency_achievement::SUPERSEDED, $achievement->status);
        $this->assertEquals(0, $achievement->proficient);
        $this->assertEquals($non_proficient_value->id, $achievement->scale_value_id);

        // The second one is also superseded
        $achievement = $achievements->shift();
        $this->assertEquals(competency_achievement::SUPERSEDED, $achievement->status);
        $this->assertEquals(1, $achievement->proficient);
        $this->assertEquals($non_proficient_value->id, $achievement->scale_value_id);

        // The third one is the new one with the same scale value id but without being proficient
        $achievement = $achievements->shift();
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement->status);
        $this->assertEquals(0, $achievement->proficient);
        $this->assertEquals($non_proficient_value->id, $achievement->scale_value_id);
    }

    protected function aggregate_pathway(pathway $pathway, stdClass $user) {
        $competency = $pathway->get_competency();

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);

        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        (new test_pathway_evaluator($pathway, $pw_user_source))->aggregate(time());
    }

    protected function get_competency_aggregator_for_pathway_and_user(pathway $pathway, stdClass $user) {
        $source_table = new aggregation_users_table();

        $achievement_configuration = new achievement_configuration($pathway->get_competency());
        $achievement_configuration->set_aggregation_type('test_aggregation');

        $comp_user_source = new competency_aggregator_user_source($source_table, true);
        $aggregator = new competency_achievement_aggregator($achievement_configuration, $comp_user_source);

        $achievement = pathway_achievement::get_current($pathway, $user->id);
        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement]);
        $aggregator->set_aggregation_instance($aggregation_method);

        return $aggregator;
    }

    public function test_archived_assignment_not_updated() {
        global $DB;

        $competency_generator = $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency();

        $scale = $competency->scale;
        $values = $scale->sorted_values_high_to_low;
        $scale_value1 = $values->first();
        $values->next();
        /** @var scale_value $scale_value2 */
        $scale_value2 = $values->current();

        $pathway = $competency_generator->create_test_pathway($competency);
        $pathway->set_test_aggregate_current_value($scale_value1);

        $achievement_configuration = new achievement_configuration($competency);
        $achievement_configuration->set_aggregation_type('test_aggregation');

        $user = $this->getDataGenerator()->create_user();
        $assignment_ids = $this->generate_active_expanded_user_assignments($competency, [$user]);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        (new test_pathway_evaluator($pathway, $pw_user_source))->aggregate(time());

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $comp_user_source = new competency_aggregator_user_source($source_table, true);
        $aggregator = new competency_achievement_aggregator($achievement_configuration, $comp_user_source);

        $achievement = pathway_achievement::get_current($pathway, $user->id);
        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $this->assertEquals(0, $DB->count_records('totara_competency_achievement'));

        $sink = $this->redirectHooks();
        $aggregator->aggregate();
        $hooks = $sink->get_hooks();

        $comp_records = $DB->get_records('totara_competency_achievement');
        $this->assertCount(1, $comp_records);
        $comp_record_while_assigned = reset($comp_records);
        $this->assertEquals($scale_value1->id, $comp_record_while_assigned->scale_value_id);

        $via_records = $DB->get_records('totara_competency_achievement_via');
        $this->assertCount(1, $via_records);
        $via_record = reset($via_records);
        $this->assertEquals($achievement->id, $via_record->pathway_achievement_id);

        $hook = reset($hooks);
        $this->assertInstanceOf(competency_achievement_updated_bulk::class, $hook);
        $sink->close();

        $disable_assignment_id = array_pop($assignment_ids);

        $model = new assignment_actions();
        $model->archive([$disable_assignment_id]);
        $expand_task = new expand_task($DB);
        $expand_task->expand_all();

        // Add a new pathway achievement, which would prompt a new competency record if it were possible.
        $pathway2 = $competency_generator->create_test_pathway($competency);
        $pathway2->set_test_aggregate_current_value($scale_value2);

        (new test_pathway_evaluator($pathway2, $pw_user_source))->aggregate(time());
        $achievement2 = pathway_achievement::get_current($pathway2, $user->id);

        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement2]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $sink = $this->redirectHooks();
        $aggregator->aggregate();
        $hooks = $sink->get_hooks();

        // There should still be one record.
        $comp_records = $DB->get_records('totara_competency_achievement');
        $this->assertCount(1, $comp_records);

        $comp_record_while_archived = reset($comp_records);

        // It should not equal the new scale value. It should equal the one in the original competency record.
        $this->assertEquals($comp_record_while_assigned->scale_value_id, $comp_record_while_archived->scale_value_id);
        $this->assertEquals($comp_record_while_assigned->proficient, $comp_record_while_archived->proficient);
        $this->assertEquals(competency_achievement::ARCHIVED_ASSIGNMENT, $comp_record_while_archived->status);
    }

    public function test_superseded_record_not_updated() {
        global $DB;

        $competency_generator = $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency();

        $scale = $competency->scale;
        $values = $scale->sorted_values_high_to_low;

        /** @var scale_value $scale_value1 */
        $scale_value1 = $values->first();
        $values->next();
        /** @var scale_value $scale_value2 */
        $scale_value2 = $values->current();
        $values->next();
        /** @var scale_value $scale_value3 */
        $scale_value3 = $values->current();

        $pathway = $competency_generator->create_test_pathway($competency);
        $pathway->set_test_aggregate_current_value($scale_value1);

        $achievement_configuration = new achievement_configuration($competency);
        $achievement_configuration->set_aggregation_type('test_aggregation');

        $user = $this->getDataGenerator()->create_user();
        $this->generate_active_expanded_user_assignments($competency, [$user]);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        (new test_pathway_evaluator($pathway, $pw_user_source))->aggregate(time());

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $comp_user_source = new competency_aggregator_user_source($source_table, true);
        $aggregator = new competency_achievement_aggregator($achievement_configuration, $comp_user_source);

        $achievement = pathway_achievement::get_current($pathway, $user->id);
        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $this->assertEquals(0, $DB->count_records('totara_competency_achievement'));

        $aggregator->aggregate();

        $comp_records = $DB->get_records('totara_competency_achievement');
        $this->assertCount(1, $comp_records);
        $first_comp_record = reset($comp_records);
        $this->assertEquals($scale_value1->id, $first_comp_record->scale_value_id);

        $via_records = $DB->get_records('totara_competency_achievement_via');
        $this->assertCount(1, $via_records);
        $via_record = reset($via_records);
        $this->assertEquals($achievement->id, $via_record->pathway_achievement_id);

        // Add a new pathway achievement, which should prompt a new competency record.
        $pathway2 = $competency_generator->create_test_pathway($competency);
        $pathway2->set_test_aggregate_current_value($scale_value2);

        (new test_pathway_evaluator($pathway2, $pw_user_source))->aggregate(time());
        $achievement2 = pathway_achievement::get_current($pathway2, $user->id);

        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement2]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $aggregator->aggregate();

        $comp_records = $DB->get_records('totara_competency_achievement');
        $this->assertCount(2, $comp_records);
        $reloaded_first_comp_record = $DB->get_record('totara_competency_achievement', ['id' => $first_comp_record->id]);

        $this->assertEquals($first_comp_record->scale_value_id, $reloaded_first_comp_record->scale_value_id);
        $this->assertEquals(competency_achievement::SUPERSEDED, $reloaded_first_comp_record->status);

        $second_comp_record = $DB->get_record('totara_competency_achievement',
            ['status' => competency_achievement::ACTIVE_ASSIGNMENT]
        );
        $this->assertEquals($scale_value2->id, $second_comp_record->scale_value_id);


        // Now we're going to repeat the above one more time. This is to make sure that we're also not updating
        // superseded records that were created prior to aggregation.
        $pathway3 = $competency_generator->create_test_pathway($competency);
        $pathway3->set_test_aggregate_current_value($scale_value3);

        (new test_pathway_evaluator($pathway3, $pw_user_source))->aggregate(time());
        $achievement3 = pathway_achievement::get_current($pathway3, $user->id);

        $aggregation_method = $this->create_aggregation_method_achieved_by([$achievement3]);
        $aggregator->set_aggregation_instance($aggregation_method);

        $aggregator->aggregate();

        $comp_records = $DB->get_records('totara_competency_achievement');
        $this->assertCount(3, $comp_records);
        $reloaded_first_comp_record = $DB->get_record('totara_competency_achievement', ['id' => $first_comp_record->id]);

        // It's the same checks as above. Checking the first comp record now against what it was originally as well as it's
        // current status.
        $this->assertEquals($first_comp_record->scale_value_id, $reloaded_first_comp_record->scale_value_id);
        $this->assertEquals(competency_achievement::SUPERSEDED, $reloaded_first_comp_record->status);

        $third_comp_record = $DB->get_record('totara_competency_achievement',
            ['status' => competency_achievement::ACTIVE_ASSIGNMENT]
        );
        $this->assertEquals($scale_value3->id, $third_comp_record->scale_value_id);
    }

    public function test_aggregation_with_no_preexisting_assignment_on_learn() {
        advanced_feature::disable('competency_assignment');

        global $CFG;

        $competency_generator = $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency();

        /** @var scale_value $scale_value */
        $scale_value = $competency->scale->sorted_values_high_to_low->first();

        $pathway = $competency_generator->create_test_pathway($competency);
        $pathway->set_test_aggregate_current_value($scale_value);

        $achievement_configuration = new achievement_configuration($competency);
        $achievement_configuration->set_aggregation_type('test_aggregation');

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        require_once($CFG->dirroot . '/completion/completion_completion.php');

        $completion = new completion_completion(['course' => $course->id, 'userid' => $user->id]);
        $completion->mark_complete();

        linked_courses::set_linked_courses(
            $competency->id,
            [
                [
                    'id' => $course->id,
                    'linktype' => linked_courses::LINKTYPE_MANDATORY
                ]
            ]
        );

        // No assignment exists
        $this->assertFalse(assignment::repository()->exists());

        $this->aggregate_pathway($pathway, $user);

        $this->assertEquals(0, competency_achievement::repository()->count());

        $sink = $this->redirectHooks();
        $aggregator = $this->get_competency_aggregator_for_pathway_and_user($pathway, $user);
        $pw_achievement = pathway_achievement::get_current($pathway, $user->id);
        $aggregator->aggregate();
        $hooks = $sink->get_hooks();

        $achievements = competency_achievement::repository()->get();
        $this->assertCount(1, $achievements);
        $achievement = $achievements->shift();
        $this->assertEquals($scale_value->id, $achievement->scale_value_id);

        $via_records = achievement_via::repository()->get();
        $this->assertCount(1, $via_records);
        $via_record = $via_records->shift();
        $this->assertEquals($pw_achievement->id, $via_record->pathway_achievement_id);

        $hook = reset($hooks);
        $this->assertInstanceOf(competency_achievement_updated_bulk::class, $hook);
        $sink->close();

        // Now there should be a legacy assignment
        /** @var assignment $assignment */
        $assignment = assignment::repository()->one(true);
        $this->assertEquals(assignment::TYPE_LEGACY, $assignment->type);
        $this->assertEquals(user_groups::USER, $assignment->user_group_type);
        $this->assertEquals($user->id, $assignment->user_group_id);
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment->status);
    }

    public function test_aggregation_with_preexisting_assignment_on_learn() {
        advanced_feature::disable('competency_assignment');

        global $CFG;

        $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency();

        /** @var scale_value $scale_value */
        $scale_value = $competency->scale->sorted_values_high_to_low->first();

        $pathway = $competency_generator->create_test_pathway($competency);
        $pathway->set_test_aggregate_current_value($scale_value);

        $achievement_configuration = new achievement_configuration($competency);
        $achievement_configuration->set_aggregation_type('test_aggregation');

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $assignment_generator = $competency_generator->assignment_generator();
        $assignment1 = $assignment_generator->create_user_assignment(
            $competency->id,
            $user->id,
            [
                'status' => assignment::STATUS_ARCHIVED,
                'type' => assignment::TYPE_LEGACY
            ]
        );

        require_once($CFG->dirroot . '/completion/completion_completion.php');

        $completion = new completion_completion(['course' => $course->id, 'userid' => $user->id]);
        $completion->mark_complete();

        linked_courses::set_linked_courses(
            $competency->id,
            [
                [
                    'id' => $course->id,
                    'linktype' => linked_courses::LINKTYPE_MANDATORY
                ]
            ]
        );

        $this->aggregate_pathway($pathway, $user);

        $this->assertEquals(0, competency_achievement::repository()->count());

        $sink = $this->redirectHooks();
        $aggregator = $this->get_competency_aggregator_for_pathway_and_user($pathway, $user);
        $pw_achievement = pathway_achievement::get_current($pathway, $user->id);
        $aggregator->aggregate();
        $hooks = $sink->get_hooks();

        $achievements = competency_achievement::repository()->get();
        $this->assertCount(1, $achievements);
        $achievement = $achievements->shift();
        $this->assertEquals($scale_value->id, $achievement->scale_value_id);

        $via_records = achievement_via::repository()->get();
        $this->assertCount(1, $via_records);
        $via_record = $via_records->shift();
        $this->assertEquals($pw_achievement->id, $via_record->pathway_achievement_id);

        $hook = reset($hooks);
        $this->assertInstanceOf(competency_achievement_updated_bulk::class, $hook);
        $sink->close();

        // Now there should be a legacy assignment
        /** @var assignment $assignment */
        $assignment2 = assignment::repository()->one(true);
        $this->assertEquals($assignment1->id, $assignment2->id);
    }

    /**
     * Verify that totara_competency_achievement.proficient is set correctly and achievement records are archived correctly
     */
    public function test_aggregation_with_min_proficiency_override_per_assignment(): void {
        global $DB;

        advanced_feature::enable('competency_assignment');

        $competency_generator = competency_generator::instance();
        $assignment_generator = new assignment_generator($competency_generator);

        $scale = $competency_generator->create_scale(
            'comp',
            'Test scale',
            [
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
            ]
        );

        /** @var collection $scale_values */
        $scale_values = $scale->sorted_values_high_to_low->key_by('sortorder');
        $highest_scale_value = $scale_values->first();
        $default_min_proficient_scale_value = $scale->min_proficient_value;
        $highest_non_proficient_scale_value = $scale_values->filter('name', 'Getting there')->first();
        $lowest_scale_value = $scale_values->last();

        /** @var competency_framework $framework */
        $framework = $competency_generator->create_framework($scale, 'Test framework');
        /** @var competency $competency */
        $competency = $competency_generator->create_competency('Test competency', $framework);
        $pathway = $competency_generator->create_test_pathway($competency);

        $position = $assignment_generator->create_position(['frameworkid' => $framework->id]);
        $organisation = $assignment_generator->create_organisation(['frameworkid' => $framework->id]);
        $user = $this->getDataGenerator()->create_user();
        job_assignment::create([
            'userid' => $user->id,
            'idnumber' => 'JobPosition',
            'positionid' => $position->id,
            'organisationid' => $organisation->id,
        ]);

        $user_asg = $assignment_generator->create_user_assignment($competency->id, $user->id);
        $pos_asg = $assignment_generator->create_position_assignment($competency->id, $position->id, ['minproficiencyid' => $highest_scale_value->id]);
        $org_asg = $assignment_generator->create_organisation_assignment($competency->id, $organisation->id, ['minproficiencyid' => $highest_non_proficient_scale_value->id]);

        (new expand_task($DB))->expand_all();

        $achievement_configuration = new achievement_configuration($competency);
        $achievement_configuration->set_aggregation_type('test_aggregation');

        // Tests - Can't use dataprovider as these tests depends on output of previous test to validate archiving as well
        $to_test = [
            1 => [
                'rating' => $lowest_scale_value,
                'expected_achievements' => [
                    [
                        'assignment_id' => $user_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'assignment_id' => $pos_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'assignment_id' => $org_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                ],
                'expected_hook_data' => [
                    'scale_value' => $lowest_scale_value,
                    'is_proficient' => 0,
                    'proficiency_changed' => 0,
                ],
            ],
            2 => [
                'rating' => $highest_non_proficient_scale_value,
                'expected_achievements' => [
                    [
                        'assignment_id' => $user_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $pos_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $org_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $user_asg->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'assignment_id' => $pos_asg->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'assignment_id' => $org_asg->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                ],
                'expected_hook_data' => [
                    'scale_value' => $highest_non_proficient_scale_value,
                    'is_proficient' => 1,
                    'proficiency_changed' => 1,
                ],
            ],
            3 => [
                'rating' => $default_min_proficient_scale_value,
                'expected_achievements' => [
                    [
                        'assignment_id' => $user_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $pos_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $org_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $user_asg->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $pos_asg->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $org_asg->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $user_asg->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'assignment_id' => $pos_asg->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'assignment_id' => $org_asg->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                ],
                'expected_hook_data' => [
                    'scale_value' => $default_min_proficient_scale_value,
                    'is_proficient' => 1,
                    'proficiency_changed' => 1,
                ],
            ],
            4 => [
                'rating' => $highest_scale_value,
                'expected_achievements' => [
                    [
                        'assignment_id' => $user_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $pos_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $org_asg->id,
                        'scale_value_id' => $lowest_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $user_asg->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $pos_asg->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $org_asg->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $user_asg->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $pos_asg->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $org_asg->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'assignment_id' => $user_asg->id,
                        'scale_value_id' => $highest_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'assignment_id' => $pos_asg->id,
                        'scale_value_id' => $highest_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'assignment_id' => $org_asg->id,
                        'scale_value_id' => $highest_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                ],
                'expected_hook_data' => [
                    'scale_value' => $highest_scale_value,
                    'is_proficient' => 1,
                    'proficiency_changed' => 1,
                ],
            ],
        ];

        $sink = $this->redirectHooks();
        foreach ($to_test as $test_nr => $test_data) {
            $pathway->set_test_aggregate_current_value($test_data['rating']);
            $this->aggregate_pathway($pathway, $user);

            $aggregator = $this->get_competency_aggregator_for_pathway_and_user($pathway, $user);
            $aggregator->aggregate();
            $hooks = $sink->get_hooks();

            $expected_achievements = array_map(function ($a) use ($competency, $user) {
                return array_merge($a, ['competency_id' => $competency->id, 'user_id' => $user->id]);
            }, $test_data['expected_achievements']);
            $this->verify_competency_achievements($expected_achievements);

            $this->assertCount(1, $hooks);
            $hook = reset($hooks);
            $this->assertInstanceOf(competency_achievement_updated_bulk::class, $hook);
            $new_scale_value = $test_data['expected_hook_data']['scale_value'];
            $expected = [
                $user->id => [
                    'new_scale_value' => [
                        'id' => $new_scale_value->id,
                        'name' => $new_scale_value->name,
                    ],
                    'is_proficient' => $test_data['expected_hook_data']['is_proficient'],
                    'proficiency_changed' => $test_data['expected_hook_data']['proficiency_changed'],
                ]
            ];
            $this->assertEqualsCanonicalizing($expected, $hook->get_user_ids_proficiency_data());
            $sink->clear();
        }

        $sink->close();
    }

    /**
     * Verify that overall aggregation is done when a user's rating stays the same but min proficiency override changes
     */
    public function test_overall_aggregation_with_changes_in_min_proficiency_override_per_assignment(): void {
        global $DB;

        advanced_feature::enable('competency_assignment');

        $competency_generator  = competency_generator::instance();
        $assignment_generator = new assignment_generator($competency_generator);

        $scale = $competency_generator->create_scale(
            'comp',
            'Test scale',
            [
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
            ]
        );

        /** @var collection $scale_values */
        $scale_values = $scale->sorted_values_high_to_low->key_by('sortorder');
        $highest_scale_value = $scale_values->first();
        $default_min_proficient_scale_value = $scale->min_proficient_value;
        $highest_non_proficient_scale_value = $scale_values->filter('name', 'Getting there')->first();
        $lowest_scale_value = $scale_values->last();

        /** @var competency_framework $framework */
        $framework = $competency_generator->create_framework($scale, 'Test framework');
        $position = $assignment_generator->create_position(['frameworkid' => $framework->id]);
        $organisation = $assignment_generator->create_organisation(['frameworkid' => $framework->id]);
        $user = $this->getDataGenerator()->create_user();
        $manager_user = $this->getDataGenerator()->create_user();
        job_assignment::create([
            'userid' => $user->id,
            'idnumber' => 'JobPosition',
            'positionid' => $position->id,
            'organisationid' => $organisation->id,
        ]);

        /** @var competency $competency1 */
        $competency1 = $competency_generator->create_competency('Test competency 1', $framework);
        $pathway1 = $competency_generator->create_manual($competency1, [manager::class]);

        /** @var competency $competency2 */
        $competency2 = $competency_generator->create_competency('Test competency 2', $framework);
        $pathway2 = $competency_generator->create_manual($competency2, [manager::class]);


        // Multiple assignments. All using the default min proficiency for now
        $user_asg1 = $assignment_generator->create_user_assignment($competency1->id, $user->id);
        $pos_asg1 = $assignment_generator->create_position_assignment($competency1->id, $position->id);

        $user_asg2 = $assignment_generator->create_user_assignment($competency2->id, $user->id);
        $org_asg2 = $assignment_generator->create_organisation_assignment($competency2->id, $organisation->id);

        (new expand_task($DB))->expand_all();

        // Add ratings
        $competency_generator->create_manual_rating(
            $pathway1,
            $user->id,
            $manager_user->id,
            manager::class,
            $default_min_proficient_scale_value->id
        );

        $competency_generator->create_manual_rating(
            $pathway2,
            $user->id,
            $manager_user->id,
            manager::class,
            $highest_non_proficient_scale_value->id
        );

        $to_test = [
            1 => [
                'min_proficiency_overrides' => [],
                'expected_achievements' => [
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $pos_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency2->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg2->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency2->id,
                        'user_id' => $user->id,
                        'assignment_id' => $org_asg2->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                ],
            ],
            2 => [
                'min_proficiency_overrides' => [
                    [
                        'scale_value_id' => $highest_scale_value->id,
                        'assignment_ids' => [$user_asg1->id],
                    ],
                    [
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'assignment_ids' => [$pos_asg1->id],
                    ],
                ],
                'expected_achievements' => [
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $pos_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency2->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg2->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency2->id,
                        'user_id' => $user->id,
                        'assignment_id' => $org_asg2->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                ],
            ],
            3 => [
                'min_proficiency_overrides' => [
                    [
                        'scale_value_id' => $highest_scale_value->id,
                        'assignment_ids' => [$user_asg2->id],
                    ],
                    [
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'assignment_ids' => [$org_asg2->id],
                    ],
                ],
                'expected_achievements' => [
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $pos_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency2->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg2->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency2->id,
                        'user_id' => $user->id,
                        'assignment_id' => $org_asg2->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'competency_id' => $competency2->id,
                        'user_id' => $user->id,
                        'assignment_id' => $org_asg2->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                ],
            ],
            4 => [
                'min_proficiency_overrides' => [
                    [
                        'scale_value_id' => null,
                        'assignment_ids' => [$user_asg1->id, $pos_asg1->id, $user_asg2->id, $org_asg2->id],
                    ],
                ],
                'expected_achievements' => [
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency1->id,
                        'user_id' => $user->id,
                        'assignment_id' => $pos_asg1->id,
                        'scale_value_id' => $default_min_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency2->id,
                        'user_id' => $user->id,
                        'assignment_id' => $user_asg2->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                    [
                        'competency_id' => $competency2->id,
                        'user_id' => $user->id,
                        'assignment_id' => $org_asg2->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'competency_id' => $competency2->id,
                        'user_id' => $user->id,
                        'assignment_id' => $org_asg2->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 1,
                        'status' => competency_achievement::SUPERSEDED,
                    ],
                    [
                        'competency_id' => $competency2->id,
                        'user_id' => $user->id,
                        'assignment_id' => $org_asg2->id,
                        'scale_value_id' => $highest_non_proficient_scale_value->id,
                        'proficient' => 0,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                    ],
                ],
            ],
        ];

        foreach ($to_test as $test_nr => $test_data) {
            foreach ($test_data['min_proficiency_overrides'] as $to_override) {
                (new min_proficiency_override_for_assignments($to_override['scale_value_id'], $to_override['assignment_ids']))->process();
            }

            (new competency_aggregation_queue())->execute();
            $this->verify_competency_achievements($test_data['expected_achievements']);
        }
    }

    /**
     * @param array $expected
     */
    private function verify_competency_achievements(array $expected): void {
        $achievements = competency_achievement::repository()->get();
        $this->assertCount(count($expected), $achievements);
        $achievements = $achievements->to_array();

        foreach ($expected as $e_idx => $expected_achievement) {
            foreach ($achievements as $a_idx => $actual_achievement) {
                $fnd = true;
                foreach ($expected_achievement as $attribute => $value) {
                    if ($actual_achievement[$attribute] != $value) {
                        $fnd = false;
                        break;
                    }
                }

                if ($fnd) {
                    unset($expected[$e_idx]);
                    unset($achievements[$a_idx]);
                    break;
                }
            }
        }

        $this->assertEmpty($expected);
    }
}
