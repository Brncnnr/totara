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
use totara_competency\models\assignment_actions;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/assignment_actions_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_actions_activate_testcase extends totara_competency_assignment_actions_testcase {

    public function test_activate_multiple() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_DRAFT;
        $assignment2->save();

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_ARCHIVED;
        $assignment3->save();

        $model = new assignment_actions();
        $affected_ids = $model->activate([$assignment1->id, $assignment2->id]);
        $this->assertEqualsCanonicalizing([$assignment1->id, $assignment2->id], $affected_ids);

        $assignment1->refresh();
        $assignment2->refresh();
        $assignment3->refresh();

        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment1->status);
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment2->status);
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment3->status);
    }

    public function test_activating_sets_expand_flag() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->expand = false;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_DRAFT;
        $assignment1->expand = false;
        $assignment2->save();

        $model = new assignment_actions();
        $model->activate([$assignment1->id, $assignment2->id]);

        $assignment1->refresh();
        $assignment2->refresh();

        $this->assertTrue($assignment1->expand);
        $this->assertTrue($assignment2->expand);
    }

    public function test_activate_single() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_ARCHIVED;
        $assignment2->save();

        $model = new assignment_actions();
        $affected_ids = $model->activate($assignment1->id);
        $this->assertEquals([$assignment1->id], $affected_ids);

        $assignment1->refresh();
        $assignment2->refresh();

        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment1->status);
        // this one is untouched
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment2->status);
    }

    public function test_activate_mix() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_ACTIVE;
        $assignment2->save();

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_ARCHIVED;
        $assignment3->save();

        $model = new assignment_actions();
        $affected_ids = $model->activate([$assignment1->id, $assignment2->id, $assignment3->id]);
        $this->assertEquals([$assignment1->id], $affected_ids);

        $assignment1->refresh();
        $assignment2->refresh();
        $assignment3->refresh();

        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment1->status);
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment2->status);
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment3->status);
    }

}