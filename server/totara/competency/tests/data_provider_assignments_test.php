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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

global $CFG;

use core\orm\query\builder;
use hierarchy_position\entity\position;
use core\entity\user;
use totara_competency\user_groups;
use totara_competency\data_providers\assignments;
use totara_competency\entity\assignment;

require_once($CFG->dirroot . '/totara/competency/tests/totara_competency_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_data_provider_assignments_testcase extends totara_competency_testcase {

    /**
     * This integration test requires more or less large amount of data created, thus it's performing
     * multiple assertions within one test to avoid unnecessary extra resets
     */
    public function test_it_fetches_filters_and_orders_assignments() {
        $data = $this->create_a_lot_of_data();

        /** @var user $target_user */
        $target_user = $data['users']->item(19);

        $pos = new position($data['pos']);

        $competencies = $data['comps'];

        // We have assignments, we need to check whether correct assignments have been loaded
        $provider = assignments::for($target_user);
        $unsorted_provider = assignments::for($target_user);

        // We'd need to assert that assignments have been ordered correctly.
        // Ordering assignments for this particular purpose of displaying in the competency profile is
        // quite a tedious tasks, so to perform order test, we'll order assignments using a similar callback
        // and then assert that IDs are in the same order. The callback might even be a copy paste at this
        // stage as long it's confirmed that it works correctly, however if the original code is changed (broken)
        // it will help us to detect it.

        $assignments = $provider->fetch()->get();
        $unsorted_assignments = $unsorted_provider->fetch()->get();

        // It's highly unlikely that the assignments will be fetched with the order that we expect them to be in.
        $this->assertEquals(
            $assignments->pluck('id'),
            $unsorted_assignments->sort(Closure::fromCallable([$this, 'sort_assignments_callback']))->pluck('id')
        );

        $has_active_assignments = false;
        $has_archived_assignments = false;

        // Let's assert that we have all the required data fetched, it should include exactly 12 assignments
        $assignments->map(function (assignment $assignment) use ($target_user, &$has_active_assignments, &$has_archived_assignments) {
            // Due to the fact that assignments do not have direct reference to the user, we'd need to compare
            // with data fetched from a related table
            if ($assignment->assignment_user) {
                $this->assertEquals($target_user->id, $assignment->assignment_user->user_id);
                $has_active_assignments = true;
            } else {
                // If this relation isn't loaded, this must be an archived assignment and we need to check that it has been loaded
                // correctly by asserting that a record in a log table exists...
                $this->assertEquals($assignment::STATUS_ARCHIVED, $assignment->status);
                $this->assertTrue(
                    builder::table('totara_competency_assignment_user_logs')
                        ->where('user_id', $target_user->id)
                        ->where('assignment_id', $assignment->id)
                        ->exists()
                );
                $has_archived_assignments = true;
            }
        });

        $this->assertTrue($has_active_assignments, 'Check your generated data, we have not found any active assignments');
        $this->assertTrue($has_archived_assignments, 'Check your generated data, we have not found any archived assignments');

        // Then let's try various set of filters

        // Filter by status
        $provider->set_filters([
            'status' => assignment::STATUS_ARCHIVED,
        ]);

        $filtered = $provider->fetch()->get();

        $this->assertNotEmpty($filtered);

        $filtered->map(function (assignment $assignment) {
            $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment->status);
        });

        // Assert ids

        // Filter by type
        $provider->set_filters([
            'type' => assignment::TYPE_SELF,
        ]);

        $filtered = $provider->fetch()->get();

        $this->assertNotEmpty($filtered);

        $filtered->map(function (assignment $assignment) use ($target_user) {
            $this->assertEquals(assignment::TYPE_SELF, $assignment->type);
            $this->assertEquals(user_groups::USER, $assignment->user_group_type);
            $this->assertEquals($target_user->id, $assignment->user_group_id);
        });

        // Filter by user_group_type
        $provider->set_filters([
            'user_group_type' => user_groups::POSITION,
        ]);

        $filtered = $provider->fetch()->get();

        $this->assertNotEmpty($filtered);

        $filtered->map(function (assignment $assignment) use ($target_user) {
            $this->assertEquals(assignment::TYPE_ADMIN, $assignment->type);
            $this->assertEquals(user_groups::POSITION, $assignment->user_group_type);
        });

        // Filter by user_group_type and id
        $provider->set_filters([
            'user_group_type' => user_groups::POSITION,
            'user_group_id' => $pos->id,
        ]);

        $filtered = $provider->fetch()->get();

        $this->assertNotEmpty($filtered);

        $filtered->map(function (assignment $assignment) use ($target_user, $pos) {
            $this->assertEquals(assignment::TYPE_ADMIN, $assignment->type);
            $this->assertEquals(user_groups::POSITION, $assignment->user_group_type);
            $this->assertEquals($pos->id, $assignment->user_group_id);
        });

        // Filter by competency_id
        $provider->set_filters([
            'competency_id' => $competencies->item(1)->id,
            'user_group_type' => null, // We'll also make sure that it ignores filters with null value
            'user_group_id' => null,
        ]);

        $filtered = $provider->fetch()->get();

        $this->assertNotEmpty($filtered);

        $filtered->map(function (assignment $assignment) use ($competencies) {
            $this->assertEquals($competencies->item(1)->id, $assignment->competency_id);
        });

        // Search
        $provider->set_filters([
            'search' => 'This is a predefined key phrase for searching a competency',
            'this-filter-does-not-exist' => null, // We'll also make sure it ignores filters that don't exist with null value.
        ]);

        $filtered = $provider->fetch()->get();

        $this->assertEqualsCanonicalizing(
            [$competencies->item(1)->id, $competencies->item(2)->id], // From generated date we know that these 2 will be the ones that we're looking for
            array_unique($filtered->pluck('competency_id')) // In the test data there will be more than one way of assigning the same competency...
        );

        // Let's make sure it throws an exception if you try to filter by something isn't defined
        $provider->set_filters([
            'search' => 'This is a predefined key phrase for searching a competency',
            'stupid' => 'no',
        ]);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Filtering by "stupid" is currently not supported');

        $provider->fetch()->get();
    }

    /**
     * Order assignments callback to be used as sort function for assignments collection
     *
     * The algorithm sorts assignments in the following order.
     *
     * Status [asc] (Active before archived)
     * Type [asc] (Admin, other, self, system)
     * User group type [asc] (Position, organisation, audience, individual)
     * Assignment creation date [desc] (Latest first)
     * Competency name (Alphabetically)
     *
     * @param assignment $first
     * @param assignment $second
     * @return int
     */
    protected function sort_assignments_callback(assignment $first, assignment $second) {

        //We need to build integer assignment type map, as we do want to sort by type
        $type_map = [
            assignment::TYPE_ADMIN => 0,
            assignment::TYPE_OTHER => 1,
            assignment::TYPE_SELF => 2,
            assignment::TYPE_SYSTEM => 3,
            assignment::TYPE_LEGACY => 4,
        ];

        //We need to build integer assignment user group type map, as we do want to sort by user group type as well
        $ug_type_map = [
            user_groups::POSITION => 0,
            user_groups::ORGANISATION => 1,
            user_groups::COHORT => 2,
            user_groups::USER => 3,
        ];

        // Let's compare status
        if ($first->status != $second->status) {
            return $first->status <=> $second->status;
        }

        // Let's compare types first
        if ($first->type != $second->type) {
            return ($type_map[$first->type] ?? 999) <=> ($type_map[$second->type] ?? 999);
        }

        // Let's compare user group first
        if ($first->user_group_type != $second->user_group_type) {
            return ($ug_type_map[$first->user_group_type] ?? 999) <=> ($ug_type_map[$second->user_group_type] ?? 999);
        }

        // Then assignment type is the same, let's compare assignment creation date then
        if ($first->created_at != $second->created_at) {
            // Most recent first
            return $second->created_at <=> $first->created_at;
        }

        // If assignments were fetched with competency names, we use their name to sort alphabetically if
        // Creation order is the same
        if (isset($first->competency_name) && isset($second->competency_name)) {
            if ($first->competency_name != $second->competency_name) {
                return $second->competency_name <=> $first->competency_name;
            }
        }

        // All is lost, we can't figure out their exact order.
        return 0;
    }

}