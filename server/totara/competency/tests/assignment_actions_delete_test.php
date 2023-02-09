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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 * @category test
 */

use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\models\assignment_actions;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/assignment_actions_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_actions_delete_testcase extends totara_competency_assignment_actions_testcase {

    public function test_delete_multiple() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $model = new assignment_actions();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_ARCHIVED;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_DRAFT;
        $assignment2->save();

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_DRAFT;
        $assignment3->save();

        // Activate to create the logs
        $model->activate($assignment3->id);

        // Expand and check that there are the expected records in the user and log tables
        $this->expand();
        $this->assertEquals(1, competency_assignment_user::repository()->count());

        // DO THE DELETE ACTION
        $affected_ids = $model->delete([$assignment1->id, $assignment2->id, $assignment2->id]);
        $this->assertEqualsCanonicalizing([$assignment1->id, $assignment2->id], $affected_ids);

        $count = assignment::repository()
            ->where('id', [$assignment1->id, $assignment2->id])
            ->count();
        $this->assertEquals(0, $count);

        // Active one does still exist
        $assignment3->refresh();
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment3->status);

        // Assert that only one user entry is left for the one assignment

        $this->assertEquals(1, competency_assignment_user::repository()->count());
        $this->assertEquals(1, competency_assignment_user::repository()
            ->where('assignment_id', $assignment3->id)
            ->count()
        );
    }

    public function test_delete_single() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_ACTIVE;
        $assignment2->save();

        $assignment3 = new assignment($assignments[2]);

        // Expand and check that there are the expected records in the assignment user table
        $this->expand();
        $this->assertEquals(2, competency_assignment_user::repository()->count());
        $this->assertEquals(1, competency_assignment_user::repository()
            ->where('assignment_id', $assignment2->id)
            ->count()
        );

        $model = new assignment_actions();
        $affected_ids = $model->delete($assignment1->id);
        $this->assertEqualsCanonicalizing([$assignment1->id], $affected_ids);

        $assignment2->refresh();

        // this one is gone
        $this->assertEmpty(assignment::repository()->find($assignment1->id));

        // this one is untouched
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment2->status);

        // Assert that the two users for the other two assignments are still there
        $this->assertEquals(2, competency_assignment_user::repository()->count());
        $this->assertEquals(1, competency_assignment_user::repository()
            ->where('assignment_id', $assignment2->id)
            ->count()
        );
        $this->assertEquals(1, competency_assignment_user::repository()
            ->where('assignment_id', $assignment3->id)
            ->count()
        );

        // deleting again should do nothing
        $model = new assignment_actions();
        $affected_ids = $model->delete($assignment1->id);
        $this->assertEmpty($affected_ids);
    }

    private function expand() {
        // We need the expanded users for the logging to work
        $expand_task = new \totara_competency\expand_task($GLOBALS['DB']);
        $expand_task->expand_all();
    }


}