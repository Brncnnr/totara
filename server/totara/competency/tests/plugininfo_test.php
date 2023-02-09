<?php
/*
 * This file is part of Totara Perform
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_competency
 */

use core_phpunit\testcase;
use totara_competency\entity\assignment;
use totara_competency\expand_task;
use totara_competency\plugininfo;
use totara_competency\task\competency_aggregation_all;
use totara_core\advanced_feature;
use totara_hierarchy\entity\scale as scale_entity;

/**
 * @group totara_competency
 */
class totara_competency_plugininfo_testcase extends testcase {

    private $db;

    protected function setUp(): void {
        parent::setUp();
        $this->db = $GLOBALS['DB'];
        $this->setAdminUser();
    }

    protected function tearDown(): void {
        $this->db = null;
        parent::tearDown();
    }

    /**
     */
    public function test_plugininfo_data() {

        $plugininfo = new plugininfo();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['competencyassignmentsenabled']);
        $this->assertEquals(0, $result['numassignments']);
        $this->assertEquals(0, $result['numuserassignments']);
        $this->assertEquals(0, $result['numachievements']);

        // Generate test data
        $this->generate_data();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['competencyassignmentsenabled']);
        // Should include active and archived but exclude draft
        $this->assertEquals(2, $result['numassignments']);
        // Should be active only
        $this->assertEquals(1, $result['numuserassignments']);
        // Should be one achievement for active only.
        $this->assertEquals(1, $result['numachievements']);

        advanced_feature::disable('competency_assignment');
        $result = $plugininfo->get_usage_for_registration_data();

        // Plugin disabled but data still there.
        $this->assertEquals(0, $result['competencyassignmentsenabled']);
        $this->assertEquals(2, $result['numassignments']);
        $this->assertEquals(1, $result['numuserassignments']);
        $this->assertEquals(1, $result['numachievements']);
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return \totara_competency\testing\generator::instance();
    }

    /**
     * Get criteria specific generator
     *
     * @return \totara_criteria\testing\generator
     */
    protected function criteria_generator() {
        return \totara_criteria\testing\generator::instance();
    }

    /**
     * Generate data required to set registration stats
     */
    private function generate_data() {
        $hierarchy_generator = $this->generator()->hierarchy_generator();
        $scale = $hierarchy_generator->create_scale(
            'comp',
            ['name' => 'Test scale', 'description' => 'Test scale'],
            [
                2 => ['name' => 'Not competent', 'proficient' => 0, 'sortorder' => 2, 'default' => 0],
                1 => ['name' => 'Competent', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );
        $scale_entity = new scale_entity($scale->id);
        $scalevalues = $scale_entity
            ->sorted_values_high_to_low
            ->key_by('sortorder')
            ->all(true);
        $fw = $hierarchy_generator->create_comp_frame(['scale' => $scale->id]);
        $type = $hierarchy_generator->create_comp_type(['idnumber' => 'type1']);
        $comp = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'comp',
            'fullname' => 'Competency',
            'idnumber' => 'comp',
            'typeid' => $type,
        ]);
        // Add 'on activation' criterion to the competency so we get an achievement too.
        $criterion = $this->criteria_generator()->create_onactivate(['competency' => $comp->id]);
        $pathway = $this->generator()->create_criteria_group($comp,
            [$criterion],
            $scalevalues[2]->id
        );

        $gen = $this->generator()->assignment_generator();
        $active_user_assignment = $gen->create_user_assignment($comp->id, null, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]);
        $draft_user_assignment = $gen->create_user_assignment($comp->id, null, ['status' => assignment::STATUS_DRAFT, 'type' => assignment::TYPE_ADMIN]);
        $archived_user_assignment = $gen->create_user_assignment($comp->id, null, ['status' => assignment::STATUS_ARCHIVED, 'type' => assignment::TYPE_ADMIN]);

        // Expand assignment to create user assignments.
        $task = new expand_task($this->db);
        $task->expand_single($active_user_assignment->id);

        // Execute aggregation to create achievements.
        (new competency_aggregation_all())->execute();
    }
}
