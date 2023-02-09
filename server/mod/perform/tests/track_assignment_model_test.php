<?php
/**
 * This file is part of Totara Learn
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\models\activity\track_assignment;
use mod_perform\models\activity\track_assignment_type;

use mod_perform\user_groups\grouping;

/**
 * @coversDefaultClass \mod_perform\models\activity\track_assignment
 *
 * @group perform
 */
class mod_perform_track_assignment_model_testcase extends advanced_testcase {
    /**
     * @covers ::create
     * @covers ::load_by_track
     */
    public function test_create_assignments(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container();

        $track = $generator->create_activity_tracks($activity)->first();
        $this->assertEmpty($track->assignments->all(), 'parent has assignments');

        $grouping = grouping::org(12334);
        $assignment_type = track_assignment_type::ADMIN;
        $assignment = track_assignment::create($track, $assignment_type, $grouping);

        $grouping->set_assignment($assignment);

        $this->assertEquals($track->id, $assignment->track_id, 'wrong parent');
        $this->assertEquals($assignment_type, $assignment->type, 'wrong assign type');
        $this->assertEquals($grouping, $assignment->group, 'wrong group');

        $this->assertEquals(1, $track->assignments->count(), 'parent not updated');

        // Confirm the repository really has the new track assignment.
        $retrieved_count = track_assignment::load_by_track($track)->count();
        $this->assertEquals(1, $retrieved_count, 'wrong assignment retrieval count');

        // Retrieve by assignment type.
        $grouping = grouping::pos(43434);
        $assignment = track_assignment::create($track, $assignment_type, $grouping);
        $retrieved_count = track_assignment::load_by_track(
            $track,
            $assignment_type
        )->count();
        $this->assertEquals(2, $retrieved_count, 'wrong assignment retrieval count');

        // Retrieve by assignment type and group.
        $retrieved_count = track_assignment::load_by_track(
            $track,
            $assignment_type,
            $grouping
        )->count();
        $this->assertEquals(1, $retrieved_count, 'wrong assignment retrieval count');
    }

    /**
     * @covers ::create
     * @covers ::remove
     */
    public function test_remove(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container();
        $track = $generator->create_single_activity_track_and_assignment($activity);

        $assignments = $track->assignments;
        $this->assertCount(1, $assignments->all(), 'track has no assignments');

        $assignments->first()->remove();

        // Confirm the repository really has deleted the track assignment.
        $retrieved_count = track_assignment::load_by_track($track)->count();
        $this->assertEquals(0, $retrieved_count, 'wrong assignment retrieval count');
    }
}
