<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 * @category test
 */

use totara_competency\user_groups;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_assignment_generator_testcase extends advanced_testcase {

    /**
     * Moodle database shortcut
     *
     * @return \moodle_database
     */
    protected function db() {
        return $GLOBALS['DB'];
    }

    /**
     * @return \totara_competency\testing\assignment_generator
     */
    protected function generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();
    }

    /**
     * @return \totara_competency\testing\generator
     */
    protected function competency_generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_competency');
    }

    protected function setUp(): void {
        parent::setUp();
    }

    public function test_it_generates_raw_assignment() {
        $this->assertEquals(0, $this->db()->count_records('totara_competency_assignments'));

        $ass = $this->generator()->create_assignment([
            'competency_id' => $this->competency_generator()->create_competency()->id,
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->generator()->create_user()->id,
        ]);

        $this->assertEquals($ass, array_values($this->db()->get_records('totara_competency_assignments'))[0]);
        $this->assertEquals(1, $this->db()->count_records('totara_competency_assignments'));
    }

    public function test_it_overrides_default_attributes_in_a_raw_assignment() {
        $this->assertEquals(0, $this->db()->count_records('totara_competency_assignments'));

        $attributes = [
            'competency_id' => $this->competency_generator()->create_competency()->id,
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->generator()->create_user()->id,
            'status' => 1,
            'optional' => 1,
            'created_by' => $this->generator()->create_user()->id,
            'created_at' => time() - 1000,
            'updated_at' => time() - 500,
            'archived_at' => time() - 500,
            'type' => 'self',
            'expand' => 0,
            'minproficiencyid' => null,
        ];

        $ass = $this->generator()->create_assignment($attributes);

        $record = array_values($this->db()->get_records('totara_competency_assignments'))[0];

        // Check that record contains all out attributes:
        $attributes['id'] = $ass->id;
        $this->assertEquals((object) $attributes, $record);

        $this->assertEquals($ass, $record);
        $this->assertEquals(1, $this->db()->count_records('totara_competency_assignments'));
    }

    public function test_it_generates_assignment_for_a_user() {
        $this->assertEquals(0, $this->db()->count_records('totara_competency_assignments'));

        $ass = $this->generator()->create_user_assignment();

        $this->assertEquals($ass, $record = array_values($this->db()->get_records('totara_competency_assignments'))[0]);
        $this->assertEquals(1, $this->db()->count_records('totara_competency_assignments'));

        $this->assertEquals(user_groups::USER, $record->user_group_type);
    }

    public function test_it_generates_assignment_for_a_position() {
        $this->assertEquals(0, $this->db()->count_records('totara_competency_assignments'));

        $ass = $this->generator()->create_position_assignment();

        $this->assertEquals($ass, $record = array_values($this->db()->get_records('totara_competency_assignments'))[0]);
        $this->assertEquals(1, $this->db()->count_records('totara_competency_assignments'));

        $this->assertEquals(user_groups::POSITION, $record->user_group_type);
    }

    public function test_it_generates_assignment_for_an_organisation() {
        $this->assertEquals(0, $this->db()->count_records('totara_competency_assignments'));

        $ass = $this->generator()->create_organisation_assignment();

        $this->assertEquals($ass, $record = array_values($this->db()->get_records('totara_competency_assignments'))[0]);
        $this->assertEquals(1, $this->db()->count_records('totara_competency_assignments'));


        $this->assertEquals(user_groups::ORGANISATION, $record->user_group_type);
    }

    public function test_it_generates_assignment_for_an_audience() {
        $this->assertEquals(0, $this->db()->count_records('totara_competency_assignments'));

        $ass = $this->generator()->create_audience_assignment();

        $this->assertEquals($ass, $record = array_values($this->db()->get_records('totara_competency_assignments', [], 'id'))[0]);
        $this->assertEquals(1, $this->db()->count_records('totara_competency_assignments'));

        $this->assertEquals(user_groups::COHORT, $record->user_group_type);

        $ass = $this->generator()->create_cohort_assignment();

        $this->assertEquals($ass, $record = array_values($this->db()->get_records('totara_competency_assignments', [], 'id'))[1]);
        $this->assertEquals(2, $this->db()->count_records('totara_competency_assignments'));

        $this->assertEquals(user_groups::COHORT, $record->user_group_type);
    }

}
