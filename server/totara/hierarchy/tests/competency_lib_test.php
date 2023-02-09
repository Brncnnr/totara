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
 * @package totara_hierarchy
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');

/**
 * @group totara_hierarchy
 */
class totara_hierarchy_competency_lib_testcase extends advanced_testcase {

    /**
     * Integration test of \competency::get_user_completed_competencies static method.
     */
    public function test_get_user_completed_competencies() {
        global $DB;

        // Todo: Make this test pass again (or remove it). Deal with this in cleanup task TL-22134.
        $this->markTestSkipped();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        // By default, the generator will create a scale with one proficient value.
        $scale1 = $hierarchy_generator->create_scale('comp');
        $scale1_proficient_value = $DB->get_record('comp_scale_values', ['id' => $scale1->minproficiencyid]);

        $values = [
            ['name' => 'a', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ['name' => 'b', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
            ['name' => 'c', 'proficient' => 0, 'sortorder' => 3, 'default' => 1],
            ['name' => 'd', 'proficient' => 0, 'sortorder' => 4, 'default' => 0]
        ];
        $scale2 = $hierarchy_generator->create_scale('comp', [], $values);
        $scale2_a = $DB->get_record('comp_scale_values', ['scaleid' => $scale2->id, 'sortorder' => 1]);
        $scale2_b = $DB->get_record('comp_scale_values', ['scaleid' => $scale2->id, 'sortorder' => 2]);
        $scale2_c = $DB->get_record('comp_scale_values', ['scaleid' => $scale2->id, 'sortorder' => 3]);
        $scale2_d = $DB->get_record('comp_scale_values', ['scaleid' => $scale2->id, 'sortorder' => 4]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $compfw1 = $hierarchy_generator->create_comp_frame(['scale' => $scale2->id]);
        $compfw2 = $hierarchy_generator->create_comp_frame(['scale' => $scale1->id]);

        $comp1 = $hierarchy_generator->create_comp(['frameworkid' => $compfw1->id]);
        $comp2 = $hierarchy_generator->create_comp(['frameworkid' => $compfw1->id]);
        $comp3 = $hierarchy_generator->create_comp(['frameworkid' => $compfw2->id]);

        hierarchy_add_competency_evidence($comp1->id, $user1->id, $scale2_a->id, null, null);
        hierarchy_add_competency_evidence($comp2->id, $user1->id, $scale2_c->id, null, null);
        hierarchy_add_competency_evidence($comp3->id, $user1->id, $scale1_proficient_value->id, null, null);

        hierarchy_add_competency_evidence($comp1->id, $user2->id, $scale2_b->id, null, null);
        hierarchy_add_competency_evidence($comp3->id, $user2->id, $scale1_proficient_value->id, null, null);

        $user1_completed = competency::get_user_completed_competencies($user1->id);
        $this->assertCount(2, $user1_completed);
        $this->assertContains($comp1->id, $user1_completed);
        $this->assertContains($comp3->id, $user1_completed);

        $user2_completed = competency::get_user_completed_competencies($user2->id);
        $this->assertCount(2, $user2_completed);
        $this->assertContains($comp1->id, $user2_completed);
        $this->assertContains($comp3->id, $user2_completed);

        $user3_completed = competency::get_user_completed_competencies($user3->id);
        $this->assertCount(0, $user3_completed);
    }

    public function test_get_all_proficient_scale_values() {
        global $DB;

        // There will be a default scale created on install. That has one proficient value called Competent.
        // Get it now before there's anything else in that table that makes it harder to find.
        $default_proficient_value = $DB->get_record('comp_scale_values', ['proficient' => 1]);

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        // By default, the generator will create a scale with one proficient value.
        $scale1 = $hierarchy_generator->create_scale('comp');
        $scale1_proficient_value = $DB->get_record('comp_scale_values', ['id' => $scale1->minproficiencyid]);

        $values = [
            ['name' => 'a', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ['name' => 'b', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
            ['name' => 'c', 'proficient' => 0, 'sortorder' => 3, 'default' => 1],
            ['name' => 'd', 'proficient' => 0, 'sortorder' => 4, 'default' => 0]
        ];
        $scale2 = $hierarchy_generator->create_scale('comp', [], $values);
        $scale2_a = $DB->get_record('comp_scale_values', ['scaleid' => $scale2->id, 'sortorder' => 1]);
        $scale2_b = $DB->get_record('comp_scale_values', ['scaleid' => $scale2->id, 'sortorder' => 2]);

        $all = competency::get_all_proficient_scale_values();
        $this->assertCount(4, $all);
        $all_ids = array_keys($all);

        $this->assertContainsEquals($scale1_proficient_value->id, $all_ids);
        $this->assertContainsEquals($scale2_a->id, $all_ids);
        $this->assertContainsEquals($scale2_b->id, $all_ids);
        $this->assertContainsEquals($default_proficient_value->id, $all_ids);

        // Check it still works when there is nothing in these tables.
        $DB->delete_records('comp_scale');
        $DB->delete_records('comp_scale_values');

        $all = competency::get_all_proficient_scale_values();
        $this->assertCount(0, $all);
    }

    public function test_value_is_proficient() {
        global $DB;

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        // By default, the generator will create a scale with one proficient value.
        $scale1 = $hierarchy_generator->create_scale('comp');
        $scale1_proficient_value = $DB->get_record('comp_scale_values', ['id' => $scale1->minproficiencyid]);

        $values = [
            ['name' => 'a', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ['name' => 'b', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
            ['name' => 'c', 'proficient' => 0, 'sortorder' => 3, 'default' => 1],
            ['name' => 'd', 'proficient' => 0, 'sortorder' => 4, 'default' => 0]
        ];
        $scale2 = $hierarchy_generator->create_scale('comp', [], $values);
        $scale2_a = $DB->get_record('comp_scale_values', ['scaleid' => $scale2->id, 'sortorder' => 1]);
        $scale2_b = $DB->get_record('comp_scale_values', ['scaleid' => $scale2->id, 'sortorder' => 2]);
        $scale2_c = $DB->get_record('comp_scale_values', ['scaleid' => $scale2->id, 'sortorder' => 3]);
        $scale2_d = $DB->get_record('comp_scale_values', ['scaleid' => $scale2->id, 'sortorder' => 4]);

        $this->assertTrue(competency::value_is_proficient($scale1_proficient_value->id));
        $this->assertTrue(competency::value_is_proficient($scale2_a->id));
        $this->assertTrue(competency::value_is_proficient($scale2_b->id));
        $this->assertFalse(competency::value_is_proficient($scale2_c->id));
        $this->assertFalse(competency::value_is_proficient($scale2_d->id));
    }

    public function test_update_assignment_availabilities() {
        global $DB;

        /** @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        // Create 2 frameworks with some competencies
        $frameworks = [];
        $competencies = [];
        $availability_records = [];

        for ($i = 1; $i <= 2; $i++) {
            $frameworks[$i] = $hierarchy_generator->create_comp_frame(['fullname' => "Framework {$i}"]);
            for ($j = 1; $j <= 4; $j++) {
                $id = "{$i}-{$j}";
                $competencies[$id] = $hierarchy_generator->create_comp(['frameworkid' => $frameworks[$i]->id, 'fullname' => "Compentency $id"]);

                // Set the initial comp_assign_availability values for the competencies:
                //     comp1 - none, comp2 - self, comp3 - other, comp4 - both
                if ($j === 2 || $j === 4) {
                    $availability_records[] = [
                        'comp_id' => $competencies[$id]->id,
                        'availability' => competency::ASSIGNMENT_CREATE_SELF,
                    ];
                }
                if ($j === 3 || $j === 4) {
                    $availability_records[] = [
                        'comp_id' => $competencies[$id]->id,
                        'availability' => competency::ASSIGNMENT_CREATE_OTHER,
                    ];
                }
            }
        }

        $DB->insert_records('comp_assign_availability', $availability_records);

        // Check setup is as expected
        $this->validate_assign_availability_records([], [
            ['comp_id' => $competencies['1-2']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['1-3']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['1-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['1-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['2-2']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['2-3']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['2-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['2-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
        ]);

        // Update all in fw1 to 'other'
        competency::update_assignment_availabilities([competency::ASSIGNMENT_CREATE_OTHER], $frameworks[1]->id);
        $this->validate_assign_availability_records([], [
            ['comp_id' => $competencies['1-1']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['1-2']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['1-3']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['1-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['2-2']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['2-3']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['2-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['2-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
        ]);

        // Update all 'self, other'
        competency::update_assignment_availabilities([competency::ASSIGNMENT_CREATE_SELF, competency::ASSIGNMENT_CREATE_OTHER]);
        $this->validate_assign_availability_records([], [
            ['comp_id' => $competencies['1-1']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['1-1']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['1-2']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['1-2']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['1-3']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['1-3']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['1-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['1-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['2-1']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['2-1']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['2-2']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['2-2']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['2-3']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['2-3']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['2-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['2-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
        ]);

        // Update all in fw2 to 'none'
        competency::update_assignment_availabilities([], $frameworks[2]->id);
        $this->validate_assign_availability_records([], [
            ['comp_id' => $competencies['1-1']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['1-1']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['1-2']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['1-2']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['1-3']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['1-3']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
            ['comp_id' => $competencies['1-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF],
            ['comp_id' => $competencies['1-4']->id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER],
        ]);

        // Update all to 'none'
        competency::update_assignment_availabilities([]);
        $this->validate_assign_availability_records([], []);
    }

    /**
     * Verify that {comp_assign_availability} table contains the expected rows
     *
     * @param array $condition Where conditions to apply when reading from {com_assign_availability}
     * @param array $expected Expected rows
     */
    private function validate_assign_availability_records(array $condition = [], array $expected = []) {
        global $DB;

        $rows = $DB->get_records('comp_assign_availability', $condition);

        self::assertSameSize($rows, $expected);
        foreach ($rows as $actual_row) {
            foreach ($expected as $idx => $expected_row) {
                if ($actual_row->comp_id == $expected_row['comp_id'] && $actual_row->availability == $expected_row['availability']) {
                    unset($expected[$idx]);
                    break;
                }
            }
        }

        self::assertEmpty($expected);
    }

}
