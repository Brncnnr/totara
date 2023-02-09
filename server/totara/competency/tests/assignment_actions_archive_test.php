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
use totara_competency\expand_task;
use totara_competency\models\assignment_actions;
use totara_job\job_assignment;
use core\orm\query\table;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/assignment_actions_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_actions_archive_testcase extends totara_competency_assignment_actions_testcase {

    public function test_archiving_draft() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_DRAFT;
        $assignment2->save();

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_ACTIVE;
        $assignment3->save();

        $this->expand();

        $model = new assignment_actions();
        $affected_ids = $model->archive([$assignment1->id, $assignment2->id]);
        $this->assertEmpty($affected_ids);

        $assignment1->refresh();
        $assignment2->refresh();
        $assignment3->refresh();

        // None got archived
        $this->assertEquals(assignment::STATUS_DRAFT, $assignment1->status);
        $this->assertEquals(assignment::STATUS_DRAFT, $assignment2->status);
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment3->status);
    }

    public function test_archiving_single() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_ACTIVE;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_ACTIVE;
        $assignment2->save();

        $this->expand();

        $model = new assignment_actions();
        $affected_ids = $model->archive($assignment1->id);
        $this->assertEquals([$assignment1->id], $affected_ids);

        $assignment1->refresh();
        $assignment2->refresh();

        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment1->status);
        $this->assertGreaterThan(0, $assignment1->updated_at);
        $this->assertEquals($assignment1->updated_at, $assignment1->archived_at);
        // this one is untouched
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment2->status);
        $this->assertEquals(0, $assignment2->archived_at);
    }

    public function test_archiving_active() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);
        $assignment2 = new assignment($assignments[1]);
        $assignment3 = new assignment($assignments[2]);

        $this->expand();
        $this->assertEquals(3, competency_assignment_user::repository()->count());

        $model = new assignment_actions();
        $affected_ids = $model->archive([$assignment1->id, $assignment2->id]);
        $this->assertEqualsCanonicalizing([$assignment1->id, $assignment2->id], $affected_ids);

        $assignment1->refresh();
        $assignment2->refresh();
        $assignment3->refresh();

        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment1->status);
        $this->assertGreaterThan(0, $assignment1->updated_at);
        $this->assertEquals($assignment1->updated_at, $assignment1->archived_at);
        $this->assertFalse($assignment1->expand);
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment2->status);
        $this->assertGreaterThan(0, $assignment2->updated_at);
        $this->assertEquals($assignment2->updated_at, $assignment2->archived_at);
        $this->assertFalse($assignment2->expand);
        // this one is untouched
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment3->status);
        $this->assertEquals(0, $assignment3->archived_at);

        // archived user records where cleaned up
        $this->assertEquals(1, competency_assignment_user::repository()->count());
    }

    public function test_archiving_active_with_continued_tracking() {
        ['competencies' => $competencies, 'assignments' => $assignments] = $this->generate_assignments();

        $gen = $this->generator();
        $hierarchy_generator = $gen->hierarchy_generator();

        $user1 = $gen->assignment_generator()->create_user();
        $user2 = $gen->assignment_generator()->create_user();
        $user3 = $gen->assignment_generator()->create_user();

        $status = ['status' => assignment::STATUS_ACTIVE];

        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Pos Framework']);
        $pos = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);
        $pos_assignment = $gen->assignment_generator()->create_position_assignment($competencies[0]->id, $pos->id, $status);

        $job_data = [
            'userid' => $user1->id,
            'idnumber' => 'dev13',
            'fullname' => 'Developer',
            'positionid' => $pos->id
        ];
        job_assignment::create($job_data);

        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Org Framework']);
        $org = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);
        $org_assignment = $gen->assignment_generator()->create_organisation_assignment($competencies[1]->id, $org->id, $status);

        $job_data = [
            'userid' => $user2->id,
            'idnumber' => 'dev13',
            'fullname' => 'Developer',
            'organisationid' => $org->id
        ];
        job_assignment::create($job_data);

        $cohort = $gen->assignment_generator()->create_cohort();
        $coh_assignment = $gen->assignment_generator()->create_cohort_assignment($competencies[2]->id, $cohort->id, $status);

        cohort_add_member($cohort->id, $user3->id);

        $assignment1 = new assignment($assignments[0]);
        $assignment2 = new assignment($assignments[1]);
        $assignment3 = new assignment($assignments[2]);

        $this->expand();
        $this->assertEquals(6, competency_assignment_user::repository()->count());

        $expected_ids = [$assignment1->id, $pos_assignment->id, $org_assignment->id, $coh_assignment->id];

        $model = new assignment_actions();
        $affected_ids = $model->archive($expected_ids, true);
        $this->assertEqualsCanonicalizing($expected_ids, $affected_ids);

        // One user assignment is gone and for the other group ones new system assignments should have been created
        $this->assertEquals(5, competency_assignment_user::repository()->count());
        // Make sure we have the expected assignments
        $this->assertEquals(2, competency_assignment_user::repository()
            ->where(
                'assignment_id',
                [$assignment2->id, $assignment3->id]
            )
            ->count()
        );
        // New system assignments should have been created
        $this->assertEquals(
            3,
            competency_assignment_user::repository()
                ->join((new table(assignment::TABLE))->as('ass'), 'assignment_id', 'id')
                ->where('ass.type', assignment::TYPE_SYSTEM)
                ->where('user_id', [$user1->id, $user2->id, $user3->id])
                ->count()
        );
    }

    public function test_archiving_active_without_continued_tracking() {
        ['competencies' => $competencies, 'assignments' => $assignments] = $this->generate_assignments();

        $gen = $this->generator();
        $hierarchy_generator = $gen->hierarchy_generator();

        $user1 = $gen->assignment_generator()->create_user();
        $user2 = $gen->assignment_generator()->create_user();
        $user3 = $gen->assignment_generator()->create_user();

        $status = ['status' => assignment::STATUS_ACTIVE];

        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Pos Framework']);
        $pos = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);
        $pos_assignment = $gen->assignment_generator()->create_position_assignment($competencies[0]->id, $pos->id, $status);

        $job_data = [
            'userid' => $user1->id,
            'idnumber' => 'dev13',
            'fullname' => 'Developer',
            'positionid' => $pos->id
        ];
        job_assignment::create($job_data);

        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Org Framework']);
        $org = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);
        $org_assignment = $gen->assignment_generator()->create_organisation_assignment($competencies[1]->id, $org->id, $status);

        $job_data = [
            'userid' => $user2->id,
            'idnumber' => 'dev13',
            'fullname' => 'Developer',
            'organisationid' => $org->id
        ];
        job_assignment::create($job_data);

        $cohort = $gen->assignment_generator()->create_cohort();
        $coh_assignment = $gen->assignment_generator()->create_cohort_assignment($competencies[2]->id, $cohort->id, $status);

        cohort_add_member($cohort->id, $user3->id);

        $assignment1 = new assignment($assignments[0]);
        $assignment2 = new assignment($assignments[1]);
        $assignment3 = new assignment($assignments[2]);

        $this->expand();
        $this->assertEquals(6, competency_assignment_user::repository()->count());

        $expected_ids = [$assignment1->id, $pos_assignment->id, $org_assignment->id, $coh_assignment->id];

        $model = new assignment_actions();
        $affected_ids = $model->archive($expected_ids, false);
        sort($expected_ids);
        sort($affected_ids);
        $this->assertEquals($expected_ids, $affected_ids);

        // User should be gone
        $this->assertEquals(2, competency_assignment_user::repository()->count());
        // No new system assignments should have been created
        $this->assertEquals(0,  assignment::repository()
                ->where('type', assignment::TYPE_SYSTEM)
                ->count()
        );
    }

    public function test_archiving_mix() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $archived_at = time();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_ARCHIVED;
        $assignment3->archived_at = $archived_at;
        $assignment3->updated_at = $archived_at;
        $assignment3->do_not_update_timestamps()->save();

        $model = new assignment_actions();
        $affected_ids = $model->archive([$assignment1->id, $assignment2->id, $assignment3->id]);
        $this->assertEquals([$assignment2->id], $affected_ids);

        $assignment1->refresh();
        $assignment2->refresh();
        $assignment3->refresh();

        $this->assertEquals(assignment::STATUS_DRAFT, $assignment1->status);
        $this->assertEquals(0, (int)$assignment1->archived_at);
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment2->status);
        $this->assertGreaterThan(0, (int)$assignment2->updated_at);
        $this->assertEquals((int)$assignment2->updated_at, (int)$assignment2->archived_at);
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment3->status);
        $this->assertEquals((int)$archived_at, (int)$assignment3->updated_at);
        $this->assertEquals((int)$assignment3->updated_at, (int)$assignment3->archived_at);
    }

    public function test_archiving_archived() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $old_archived_at = time() - 20;

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_ARCHIVED;
        $assignment1->archived_at = $old_archived_at;
        $assignment1->updated_at = $old_archived_at;
        $assignment1->do_not_update_timestamps()->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_ARCHIVED;
        $assignment2->archived_at = $old_archived_at;
        $assignment2->updated_at = $old_archived_at;
        $assignment2->do_not_update_timestamps()->save();

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_ACTIVE;
        $assignment3->save();

        $model = new assignment_actions();
        $affected_ids = $model->archive([$assignment1->id, $assignment2->id]);
        $this->assertEmpty($affected_ids);

        $assignment1->refresh();
        $assignment2->refresh();
        $assignment3->refresh();

        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment1->status);
        $this->assertEquals((int)$old_archived_at, (int)$assignment1->updated_at);
        $this->assertEquals((int)$assignment1->updated_at, (int)$assignment1->archived_at);
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment2->status);
        $this->assertEquals((int)$old_archived_at, (int)$assignment2->updated_at);
        $this->assertEquals((int)$assignment2->updated_at, (int)$assignment2->archived_at);
        // this one is untouched
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment3->status);
        $this->assertEquals(0, (int)$assignment3->archived_at);
    }

    private function expand() {
        // We need the expanded users for the logging to work
        $expand_task = new expand_task($GLOBALS['DB']);
        $expand_task->expand_all();
    }

}