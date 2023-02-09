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

use aggregation_highest\highest;
use core_phpunit\testcase;
use criteria_childcompetency\childcompetency;
use criteria_linkedcourses\linkedcourses;
use pathway_criteria_group\criteria_group;
use pathway_learning_plan\learning_plan;
use totara_competency\achievement_configuration;
use totara_competency\linked_courses;
use totara_competency\task\default_criteria_on_install;
use totara_core\advanced_feature;
use totara_criteria\criterion;
use totara_hierarchy\entity\competency;

/**
 * @group totara_competency
 */
class totara_competency_default_criteria_on_install_testcase extends testcase {

    /**
     * Test the task can handle there being no competencies.
     */
    public function test_it_does_nothing_with_perform_activated() {
        global $DB;

        advanced_feature::enable('competency_assignment');

        $this->assertEquals(0, $DB->count_records('comp'));

        $task = new default_criteria_on_install();
        $task->execute();

        $this->assertEquals(0, $DB->count_records('totara_competency_scale_aggregation'));
        $this->assertEquals(0, $DB->count_records('totara_competency_pathway'));

        // The other thing we're testing here is no exceptions when no competencies exist.
    }

    /**
     * Test the task can handle there being no competencies.
     */
    public function test_no_existing_competencies() {
        global $DB;

        advanced_feature::disable('competency_assignment');

        $this->assertEquals(0, $DB->count_records('comp'));

        $task = new default_criteria_on_install();
        $task->execute();

        $this->assertEquals(0, $DB->count_records('totara_competency_scale_aggregation'));
        $this->assertEquals(0, $DB->count_records('totara_competency_pathway'));

        // The other thing we're testing here is no exceptions when no competencies exist.
    }

    public function test_competency_without_aggregation_is_processed() {
        global $DB;

        advanced_feature::disable('competency_assignment');

        [$comp, $scale] = $this->generate_comp_and_scale('ALL');

        // We're adding a learning plan so that we have the same conditions as the inverse of this test (below).
        advanced_feature::enable('learningplans');
        $this->add_learning_plan($comp->id);

        // Ensure there definitely isn't aggregation already.
        $this->assertEquals(0, $DB->count_records('totara_competency_scale_aggregation'));

        $task = new default_criteria_on_install();
        $task->execute();

        // There now is an aggregation record.
        $this->assertEquals(1, $DB->count_records('totara_competency_scale_aggregation'));

        // Ensure this is also valid via the API.
        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assertCount(3, $active_pathways);

        $this->assert_pathways_were_created($active_pathways, $scale->minproficiencyid, criterion::AGGREGATE_ALL);
    }

    public function test_competency_with_aggregation_already_is_not_processed() {
        global $DB;

        advanced_feature::disable('competency_assignment');

        [$comp, $scale] = $this->generate_comp_and_scale('ALL');

        // Add aggregation as if this was done in some normal fashion, so use the API.
        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $achievement_configuration->set_aggregation_type(highest::aggregation_type());
        $achievement_configuration->save_aggregation();

        // We're adding a learning plan and we should see this does not get added afterwards.
        advanced_feature::enable('learningplans');
        $this->add_learning_plan($comp->id);

        // Ensure there definitely is aggregation.
        $this->assertEquals(1, $DB->count_records('totara_competency_scale_aggregation'));

        $task = new default_criteria_on_install();
        $task->execute();

        // Still just the one record.
        $this->assertEquals(1, $DB->count_records('totara_competency_scale_aggregation'));

        // Ensure the API still functions.
        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        // That learning plan should not have been added as this competency was not processed as part of install.
        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assertCount(0, $active_pathways);
    }

    /**
     * The point of this test is to ensure that our protection against adding more and more data
     * if the task is run more than once for any reason is adequate.
     */
    public function test_one_competency_on_upgrade_run_multiple_times() {
        global $DB;

        advanced_feature::disable('competency_assignment');

        [$comp, $scale] = $this->generate_comp_and_scale('ALL');

        $this->assertEquals(0, $DB->count_records('totara_competency_scale_aggregation'));

        $task = new default_criteria_on_install();
        $task->execute();

        $this->assertEquals(1, $DB->count_records('totara_competency_scale_aggregation'));

        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assertCount(3, $active_pathways);

        // Run again.
        $task = new default_criteria_on_install();
        $task->execute();

        $this->assertEquals(1, $DB->count_records('totara_competency_scale_aggregation'));

        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assertCount(3, $active_pathways);

        // One more time.
        $task = new default_criteria_on_install();
        $task->execute();

        $this->assertEquals(1, $DB->count_records('totara_competency_scale_aggregation'));

        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assertCount(3, $active_pathways);
    }

    private function add_learning_plan($competency_id = null) {
        /** @var \totara_plan\testing\generator $plan_generator */
        $plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $plan = $plan_generator->create_learning_plan();

        if ($competency_id) {
            $this->setAdminUser();
            $plan_generator->add_learning_plan_competency($plan->id, $competency_id);
        }
    }

    private function generate_comp_and_scale($aggregation_method) {
        global $DB;

        // No events this time
        $sink = $this->redirectEvents();

        /** @var \totara_hierarchy\testing\generator $totara_hierarchy_generator */
        $totara_hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $totara_hierarchy_generator->create_comp_frame([]);
        $comp = $totara_hierarchy_generator->create_comp(
            ['frameworkid' => $compfw->id, 'aggregationmethod' => \competency::COMP_AGGREGATION[$aggregation_method]]
        );

        $scale_assignment = $DB->get_record('comp_scale_assignments', ['frameworkid' => $compfw->id]);
        $scale = $DB->get_record('comp_scale', ['id' => $scale_assignment->scaleid]);

        $this->assertEquals(1, $DB->count_records('comp'));

        // The point is to test the adhoc task adds this data. If the hierarchy generator starts adding it, we'd want
        // to be able to give it the option to say don't do that.
        $this->assertEquals(0, $DB->count_records('totara_competency_scale_aggregation'));
        $this->assertEquals(0, $DB->count_records('totara_competency_pathway'));

        $sink->close();

        return [$comp, $scale];
    }

    private function assert_pathways_were_created($pathways, $min_proficient_id, $criterion_aggregation, $with_lp = true) {
        if ($with_lp) {
            $this->assertCount(3, $pathways);
            $lp_pathway = array_shift($pathways);
            $this->assertInstanceOf(learning_plan::class, $lp_pathway);
        }

        $this->assertCount(2, $pathways);

        /** @var criteria_group $group1_pathway */
        $group1_pathway = array_shift($pathways);
        $this->assertInstanceOf(criteria_group::class, $group1_pathway);
        $this->assertEquals($min_proficient_id, $group1_pathway->get_scale_value()->id);

        $criteria = $group1_pathway->get_criteria();
        $this->assertCount(1, $criteria);
        $criterion = array_shift($criteria);
        $this->assertInstanceOf(linkedcourses::class, $criterion);
        $this->assertEquals($criterion_aggregation, $criterion->get_aggregation_method());

        $competency = $group1_pathway->get_competency();

        $linked_courses = linked_courses::get_linked_courses($competency->id);
        if (!empty($linked_courses)) {
            $this->assertTrue($group1_pathway->is_valid());
        } else {
            $this->assertFalse($group1_pathway->is_valid());
        }

        /** @var criteria_group $group2_pathway */
        $group2_pathway = array_shift($pathways);
        $this->assertInstanceOf(criteria_group::class, $group2_pathway);
        $this->assertEquals($min_proficient_id, $group2_pathway->get_scale_value()->id);

        $criteria = $group2_pathway->get_criteria();
        $this->assertCount(1, $criteria);
        $criterion = array_shift($criteria);
        $this->assertInstanceOf(childcompetency::class, $criterion);
        $this->assertEquals($criterion_aggregation, $criterion->get_aggregation_method());

        $children = $competency->children;
        if ($children->count() > 0) {
            $this->assertTrue($group2_pathway->is_valid());
        } else {
            $this->assertFalse($group2_pathway->is_valid());
        }
    }

    public function test_one_competency_with_lps_disabled() {
        advanced_feature::disable('competency_assignment');
        advanced_feature::enable('learningplans');

        [$comp, $scale] = $this->generate_comp_and_scale('ALL');

        $task = new default_criteria_on_install();
        $task->execute();

        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assert_pathways_were_created($active_pathways, $scale->minproficiencyid, criterion::AGGREGATE_ALL);
    }

    public function test_one_competency_with_lps_disabled_but_one_exists() {
        advanced_feature::disable('competency_assignment');
        advanced_feature::enable('learningplans');

        [$comp, $scale] = $this->generate_comp_and_scale('ALL');

        // Add a learning and attach a competency to it.
        $this->add_learning_plan($comp->id);

        // But now disable learning plans.
        advanced_feature::disable('learningplans');

        $task = new default_criteria_on_install();
        $task->execute();

        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assert_pathways_were_created($active_pathways, $scale->minproficiencyid, criterion::AGGREGATE_ALL);
    }

    public function test_one_competency_with_lps_enabled_but_none_with_competencies_exist() {
        advanced_feature::disable('competency_assignment');

        [$comp, $scale] = $this->generate_comp_and_scale('ALL');

        // Add a learning but we are not attaching a competency.
        $this->add_learning_plan(null);

        // Learning plans are enabled by default, but just for certainty.
        advanced_feature::enable('learningplans');

        $task = new default_criteria_on_install();
        $task->execute();

        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assert_pathways_were_created($active_pathways, $scale->minproficiencyid, criterion::AGGREGATE_ALL);
    }

    public function test_one_competency_with_lps_enabled_and_one_with_competencies_exist() {
        advanced_feature::disable('competency_assignment');

        [$comp, $scale] = $this->generate_comp_and_scale('ALL');

        // Add a learning but we are not attaching a competency.
        $this->add_learning_plan($comp->id);

        // Learning plans are enabled by default, but just for certainty.
        advanced_feature::enable('learningplans');

        $task = new default_criteria_on_install();
        $task->execute();

        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assertCount(3, $active_pathways);

        $this->assert_pathways_were_created($active_pathways, $scale->minproficiencyid, criterion::AGGREGATE_ALL);
    }

    /**
     * Database level checks
     *
     * The idea with the database checks is to simply ensure that we are looking at persisted default data.
     *
     * So there's no need to test that all the right joins are in place between the records. The fact that
     * the API level checks provide the same information should be enough. Otherwise we could always be modifying
     * this test to confirm DB structure rather than confirming the right data is in place for the desired API
     * responses following install.
     */
    public function test_one_competency_on_upgrade_database_checks() {
        advanced_feature::disable('competency_assignment');

        global $DB;

        [$comp, $scale] = $this->generate_comp_and_scale('ALL');

        $this->add_learning_plan($comp->id);

        $this->assertEquals(1, $DB->count_records('comp'));

        $task = new default_criteria_on_install();
        $task->execute();

        $aggregation = $DB->get_records('totara_competency_scale_aggregation', ['competency_id' => $comp->id]);
        $this->assertCount(1, $aggregation);

        $aggregation = array_shift($aggregation);
        $this->assertEquals(highest::aggregation_type(), $aggregation->type);
        $this->assertEquals($comp->id, $aggregation->competency_id);

        $pathways = $DB->get_records('totara_competency_pathway', ['competency_id' => $comp->id], 'sortorder ASC');
        $this->assertCount(3, $pathways);

        foreach ($pathways as $pathway) {
            $this->assertEquals($comp->id, $pathway->competency_id);
        }

        $lp_pathway = array_shift($pathways);
        $this->assertEquals(learning_plan::pathway_type(), $lp_pathway->path_type);

        $group_pathway1 = array_shift($pathways);
        $this->assertEquals(criteria_group::pathway_type(), $group_pathway1->path_type);

        $group_pathway2 = array_shift($pathways);
        $this->assertEquals(criteria_group::pathway_type(), $group_pathway2->path_type);
    }

    /**
     * Confirm behaviour when a single competency has aggregation method of 'ALL'.
     */
    public function test_one_competency_on_upgrade_all() {
        advanced_feature::disable('competency_assignment');

        [$comp, $scale] = $this->generate_comp_and_scale('ALL');

        $task = new default_criteria_on_install();
        $task->execute();

        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assert_pathways_were_created($active_pathways, $scale->minproficiencyid, criterion::AGGREGATE_ALL);
    }

    /**
     * Confirm behaviour when a single competency has aggregation method of 'ANY'.
     */
    public function test_one_competency_on_upgrade_any() {
        advanced_feature::disable('competency_assignment');

        [$comp, $scale] = $this->generate_comp_and_scale('ANY');

        $task = new default_criteria_on_install();
        $task->execute();

        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assert_pathways_were_created($active_pathways, $scale->minproficiencyid, criterion::AGGREGATE_ANY_N);
    }


    /**
     * Confirm behaviour when a single competency has aggregation method of 'OFF'.
     */
    public function test_one_competency_on_upgrade_off() {
        advanced_feature::disable('competency_assignment');

        [$comp, $scale] = $this->generate_comp_and_scale('OFF');

        $task = new default_criteria_on_install();
        $task->execute();

        $competency = new competency($comp);
        $achievement_configuration = new achievement_configuration($competency);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assertCount(1, $active_pathways);
        $this->assertInstanceOf(learning_plan::class, array_shift($active_pathways));
    }

    /**
     * Test achievement configuration is set up correctly when there are a number of competencies with varying
     * configurations.
     */
    public function test_multiple_competencies_on_upgrade() {
        advanced_feature::disable('competency_assignment');

        global $DB;

        // Skip event execution
        $sink = $this->redirectEvents();

        /** @var \totara_hierarchy\testing\generator $totara_hierarchy_generator */
        $totara_hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $scale1 = $totara_hierarchy_generator->create_scale('comp');
        $scale2 = $totara_hierarchy_generator->create_scale('comp');

        $compfw1 = $totara_hierarchy_generator->create_comp_frame(['scale' => $scale1->id]);
        $comp1 = $totara_hierarchy_generator->create_comp(
            ['frameworkid' => $compfw1->id, 'aggregationmethod' => \competency::AGGREGATION_METHOD_ALL]
        );
        $comp2 = $totara_hierarchy_generator->create_comp(
            ['frameworkid' => $compfw1->id, 'aggregationmethod' => \competency::AGGREGATION_METHOD_ALL]
        );

        $comp21 = $totara_hierarchy_generator->create_comp(
            [
                'frameworkid' => $compfw1->id,
                'parentid' => $comp2->id,
                'aggregationmethod' => \competency::AGGREGATION_METHOD_ANY
            ]
        );

        $comp22 = $totara_hierarchy_generator->create_comp(
            [
                'frameworkid' => $compfw1->id,
                'parentid' => $comp2->id,
                'aggregationmethod' => \competency::AGGREGATION_METHOD_ANY
            ]
        );

        $compfw2 = $totara_hierarchy_generator->create_comp_frame(['scale' => $scale2->id]);

        $comp3 = $totara_hierarchy_generator->create_comp(
            ['frameworkid' => $compfw2->id, 'aggregationmethod' => \competency::AGGREGATION_METHOD_OFF]
        );

        $comp4 = $totara_hierarchy_generator->create_comp(
            ['frameworkid' => $compfw2->id, 'aggregationmethod' => \competency::AGGREGATION_METHOD_ANY]
        );

        $comp5 = $totara_hierarchy_generator->create_comp(
            ['frameworkid' => $compfw2->id, 'aggregationmethod' => \competency::AGGREGATION_METHOD_ANY]
        );

        $sink->close();

        // For comp5, we're adding the aggregation record already, as if this has been added via the UI
        // before the install task could be run.
        $competency5 = new competency($comp5);
        $achievement_configuration = new achievement_configuration($competency5);
        $achievement_configuration->set_aggregation_type(highest::aggregation_type());
        $achievement_configuration->save_aggregation();

        // There just needs to be one learning plan with one of the competencies
        // and we should be seeing learning plan pathways on all competencies.
        $this->add_learning_plan($comp3->id);

        $this->assertEquals(7, $DB->count_records('comp'));

        // The point is to test the adhoc task adds this data. If the hierarchy generator starts adding it, we'd want
        // to be able to give it the option to say don't do that.
        // There is one scale aggregation record because of comp5, but that should be all.
        $this->assertEquals(1, $DB->count_records('totara_competency_scale_aggregation'));
        $this->assertEquals(0, $DB->count_records('totara_competency_pathway'));

        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        linked_courses::add_linked_courses(
            $comp4->id,
            [[
                'id' => $course1->id,
                'linktype' => linked_courses::LINKTYPE_OPTIONAL,
            ]]
        );

        $task = new default_criteria_on_install();
        $task->execute();

        /**
         * Competency 1.
         *
         * Aggregation ALL.
         */

        $competency1 = new competency($comp1);
        $achievement_configuration = new achievement_configuration($competency1);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assert_pathways_were_created($active_pathways, $scale1->minproficiencyid, criterion::AGGREGATE_ALL);

        /**
         * Competency 2.
         *
         * Aggregation ALL.
         */

        $competency2 = new competency($comp2);
        $achievement_configuration = new achievement_configuration($competency2);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assert_pathways_were_created($active_pathways, $scale1->minproficiencyid, criterion::AGGREGATE_ALL);

        /**
         * Competency 3.
         *
         * Aggregation OFF.
         */

        $competency3 = new competency($comp3);
        $achievement_configuration = new achievement_configuration($competency3);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assertCount(1, $active_pathways);
        $lp_pathway = array_shift($active_pathways);
        $this->assertInstanceOf(learning_plan::class, $lp_pathway);

        /**
         * Competency 4.
         *
         * Aggregation ANY.
         */

        $competency4 = new competency($comp4);
        $achievement_configuration = new achievement_configuration($competency4);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());

        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assert_pathways_were_created($active_pathways, $scale2->minproficiencyid, criterion::AGGREGATE_ANY_N);

        /**
         * Competency 5.
         *
         * Aggregation ANY.
         *
         * But this competency had aggregation set before the task ran, so nothing should have been added.
         */

        $competency5 = new competency($comp5);
        $achievement_configuration = new achievement_configuration($competency5);
        $this->assertEquals(highest::aggregation_type(), $achievement_configuration->get_aggregation_type());
        $active_pathways = $achievement_configuration->get_active_pathways();
        $this->assertCount(0, $active_pathways);
    }

}
