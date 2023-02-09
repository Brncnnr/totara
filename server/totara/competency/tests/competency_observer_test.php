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

use core\orm\collection;
use criteria_childcompetency\childcompetency;
use criteria_linkedcourses\linkedcourses;
use hierarchy_competency\event\competency_created;
use hierarchy_competency\event\competency_updated;
use pathway_criteria_group\entity\criteria_group_criterion as criteria_group_criterion_entity;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\pathway;
use totara_core\advanced_feature;
use totara_criteria\criterion;
use totara_criteria\entity\criterion as criterion_entity;

/**
 * Tests covering the competency observer making sure the events do the right thing
 *
 * @group totara_competency
 */
class totara_competency_competency_observer_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
    }

    public function test_updated_event_when_perform_is_enabled() {
        advanced_feature::enable('competency_assignment');

        $comp = $this->create_competency(\competency::AGGREGATION_METHOD_ANY);

        $comp_changed = clone $comp;
        $comp_changed->aggregationmethod = \competency::AGGREGATION_METHOD_ALL;

        $this->assert_does_not_have_criteria($comp_changed->id);

        competency_updated::create_from_old_and_new($comp_changed, $comp)->trigger();

        $this->assert_does_not_have_criteria($comp_changed->id);
    }

    public function test_updated_event_when_aggregation_method_did_not_change() {
        advanced_feature::disable('competency_assignment');

        $comp = $this->create_competency(\competency::AGGREGATION_METHOD_ANY);

        $comp_changed = clone $comp;

        $this->assert_does_not_have_criteria($comp_changed->id);

        competency_updated::create_from_old_and_new($comp_changed, $comp)->trigger();

        $this->assert_does_not_have_criteria($comp_changed->id);
    }

    public function test_updated_event_when_aggregation_method_is_not_set() {
        advanced_feature::disable('competency_assignment');

        $comp = $this->create_competency(\competency::AGGREGATION_METHOD_ANY);

        $comp_changed = clone $comp;
        $comp_changed->aggregation_method = null;

        $this->assert_does_not_have_criteria($comp_changed->id);

        competency_updated::create_from_old_and_new($comp_changed, $comp)->trigger();

        $this->assert_does_not_have_criteria($comp_changed->id);
    }

    public function test_updated_event_gets_processed_if_aggregation_method_changed() {
        advanced_feature::disable('competency_assignment');

        $comp = $this->create_competency(\competency::AGGREGATION_METHOD_ANY);

        $comp_changed = clone $comp;
        $comp_changed->aggregationmethod = \competency::AGGREGATION_METHOD_ALL;

        $this->assert_does_not_have_criteria($comp_changed->id);

        competency_updated::create_from_old_and_new($comp_changed, $comp)->trigger();

        $this->assert_has_criteria($comp_changed->id, criterion::AGGREGATE_ALL);
    }

    public function test_created_event_only_applies_defaults_with_assignment_enabled() {
        advanced_feature::enable('competency_assignment');

        $comp = $this->create_competency(\competency::AGGREGATION_METHOD_ANY);

        $this->assert_does_not_have_criteria($comp->id);

        competency_created::create_from_instance($comp)->trigger();

        $this->assert_does_not_have_criteria($comp->id);
    }

    public function test_created_event_creates_defaults() {
        advanced_feature::disable('competency_assignment');

        $comp = $this->create_competency(\competency::AGGREGATION_METHOD_ANY);

        $this->assert_does_not_have_criteria($comp->id);

        competency_created::create_from_instance($comp)->trigger();

        $this->assert_has_criteria($comp->id, criterion::AGGREGATE_ANY_N);
    }

    protected function create_competency(int $aggregation_method) {
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

        // We don't want the create event fired here
        $sink = $this->redirectEvents();

        $fw = $hierarchy_generator->create_comp_frame(['fullname' => 'Framework one', 'idnumber' => 'f1', 'scale' => $scale->id]);
        $comp = $hierarchy_generator->create_comp([
            'frameworkid' => $fw->id,
            'idnumber' => 'c1',
            'parentid' => 0,
            'aggregationmethod' => $aggregation_method
        ]);

        // Stop redirecting events from now
        $sink->close();

        return $comp;
    }

    protected function assert_does_not_have_criteria(int $competency_id) {
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

    protected function get_criteria(int $competency_id): collection {
        return criterion_entity::repository()
            ->join([criteria_group_criterion_entity::TABLE, 'cgc'], 'id', 'criterion_id')
            ->join([pathway_entity::TABLE, 'pw'], 'cgc.criteria_group_id', 'pw.path_instance_id')
            ->where('plugin_type', [
                (new linkedcourses())->get_plugin_type(),
                (new childcompetency())->get_plugin_type(),
            ])
            ->where('pw.competency_id', $competency_id)
            ->where('pw.status', pathway::PATHWAY_STATUS_ACTIVE)
            ->get();
    }

}
