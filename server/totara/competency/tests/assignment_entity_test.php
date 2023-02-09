<?php
/*
 * This file is part of Totara Perform
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 * @category test
 */

use totara_competency\entity\assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_assignment_entity_testcase extends advanced_testcase {

    public function test_filters() {

        /** @var \totara_competency\testing\assignment_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();

        $ass1 = $gen->create_user_assignment(null, null, ['status' => assignment::STATUS_DRAFT]);
        $ass2 = $gen->create_user_assignment(null, null, ['status' => assignment::STATUS_ACTIVE]);
        $ass3 = $gen->create_user_assignment(null, null, ['status' => assignment::STATUS_ARCHIVED]);

        $result = assignment::repository()
            ->filter_by_active()
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals($ass2->id, $result->first()->id);

        $result = assignment::repository()
            ->filter_by_archived()
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals($ass3->id, $result->first()->id);

        $result = assignment::repository()
            ->filter_by_draft()
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals($ass1->id, $result->first()->id);

        $result = assignment::repository()
            ->filter_by_not_draft()
            ->get();

        $this->assertCount(2, $result);
        $ids = $result->pluck('id');
        $this->assertNotContainsEquals($ass1->id, $ids);
    }

    public function test_status_name() {
        $assignment = new assignment();

        $assignment->status = assignment::STATUS_DRAFT;
        $this->assertEquals(assignment::STATUS_NAME_DRAFT, $assignment->status_name);
        $assignment->status = "0";
        $this->assertEquals(assignment::STATUS_DRAFT, $assignment->status);
        $this->assertEquals(assignment::STATUS_NAME_DRAFT, $assignment->status_name);

        $assignment->status = assignment::STATUS_ACTIVE;
        $this->assertEquals(assignment::STATUS_NAME_ACTIVE, $assignment->status_name);
        $assignment->status = "1";
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment->status);
        $this->assertEquals(assignment::STATUS_NAME_ACTIVE, $assignment->status_name);

        $assignment->status = assignment::STATUS_ARCHIVED;
        $this->assertEquals(assignment::STATUS_NAME_ARCHIVED, $assignment->status_name);
        $assignment->status = "2";
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment->status);
        $this->assertEquals(assignment::STATUS_NAME_ARCHIVED, $assignment->status_name);

        $result = $assignment->to_array();
        $this->assertArrayHasKey('status_name', $result);
        $this->assertEquals(assignment::STATUS_NAME_ARCHIVED, $result['status_name']);

        $assignment->status = '5';
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Unknown assignment status '5'");
        $test = $assignment->status_name;
    }

    public function test_filter_by_user_group() {
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $fw = $generator->hierarchy_generator()->create_comp_frame([]);
        $type = $generator->hierarchy_generator()->create_comp_type(['idnumber' => 'type1']);

        $comp1 = $generator->create_competency(null, $fw->id, [
            'shortname' => 'acc',
            'fullname' => 'Accounting',
            'description' => 'Counting profits',
            'idnumber' => 'accc',
            'typeid' => $type,
        ]);

        $comp2 = $generator->create_competency(null, $fw->id, [
            'shortname' => 'acc2',
            'fullname' => 'Accounting 2',
            'description' => 'Counting profits 2',
            'idnumber' => 'accc2',
            'typeid' => $type,
        ]);

        $assignment_generator = $generator->assignment_generator();
        $assignment_generator->create_user_assignment($comp1->id, 1);
        $assignment_generator->create_organisation_assignment($comp2->id, 2);

        $assignments = assignment::repository()
            ->filter_by_user_group_type(\totara_competency\user_groups::USER)
            ->get();

        $this->assertCount(1, $assignments);
        $this->assertEquals(\totara_competency\user_groups::USER, $assignments->first()->user_group_type);

        $assignments = assignment::repository()
            ->filter_by_user_group_type(\totara_competency\user_groups::ORGANISATION)
            ->get();

        $this->assertCount(1, $assignments);
        $this->assertEquals(\totara_competency\user_groups::ORGANISATION, $assignments->first()->user_group_type);

        $assignments = assignment::repository()
            ->filter_by_user_group_type(\totara_competency\user_groups::POSITION)
            ->get();

        $this->assertCount(0, $assignments);
    }

    public function test_filter_by_invalid_user_group() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid assignment type has been passed.');
        assignment::repository()
            ->filter_by_user_group_type('foobar')
            ->get();
    }
}