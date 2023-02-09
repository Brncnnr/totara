<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\entity\assignment;
use totara_competency\expand_task;
use totara_competency\models\assignment_actions;
use totara_competency\user_groups;
use totara_core\advanced_feature;
use totara_job\job_assignment;

/**
 * Tests covering the user group observer making sure the events do the right thing
 *
 * @group totara_competency
 */
class totara_competency_assignment_user_group_observer_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");
    }

    public function test_cohort_member_add_sets_expand_flag() {
        advanced_feature::enable('competency_assignment');

        $data = $this->prepare_assignments();

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        cohort_add_member($data->cohort1->id, $data->user1->id);

        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(3, $expanded_assignments);
        $this->assertEquals([user_groups::COHORT], array_unique($expanded_assignments->pluck('user_group_type')));
        $this->assertEquals([$data->cohort1->id], array_unique($expanded_assignments->pluck('user_group_id')));
    }

    public function test_cohort_member_remove_sets_expand_flag() {
        advanced_feature::enable('competency_assignment');

        $data = $this->prepare_assignments();

        cohort_add_member($data->cohort1->id, $data->user1->id);

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        // Now remove the user from the cohort
        cohort_remove_member($data->cohort1->id, $data->user1->id);

        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(3, $expanded_assignments);
        $this->assertEquals([user_groups::COHORT], array_unique($expanded_assignments->pluck('user_group_type')));
        $this->assertEquals([$data->cohort1->id], array_unique($expanded_assignments->pluck('user_group_id')));
    }

    public function test_job_assignment_position_creation_and_deletion_sets_expand_flag() {
        advanced_feature::enable('competency_assignment');

        $data = $this->prepare_assignments();

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        $job_data = [
            'userid' => $data->user1->id,
            'idnumber' => 'dev13',
            'fullname' => 'Developer',
            'positionid' => $data->pos1->id
        ];
        $job_assignment = job_assignment::create($job_data);

        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(3, $expanded_assignments);
        $this->assertEquals([user_groups::POSITION], array_unique($expanded_assignments->pluck('user_group_type')));
        $this->assertEquals([$data->pos1->id], array_unique($expanded_assignments->pluck('user_group_id')));

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        job_assignment::delete($job_assignment);

        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(3, $expanded_assignments);
        $this->assertEquals([user_groups::POSITION], array_unique($expanded_assignments->pluck('user_group_type')));
        $this->assertEquals([$data->pos1->id], array_unique($expanded_assignments->pluck('user_group_id')));
    }

    public function test_job_assignment_position_change_sets_expand_flag() {
        advanced_feature::enable('competency_assignment');

        $data = $this->prepare_assignments();

        $job_data = [
            'userid' => $data->user1->id,
            'idnumber' => 'dev13',
            'fullname' => 'Developer',
            'positionid' => null
        ];
        $job_assignment = job_assignment::create($job_data);

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        $job_assignment->update([
            'positionid' => $data->pos1->id
        ]);

        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(3, $expanded_assignments);
        $this->assertEquals([user_groups::POSITION], array_unique($expanded_assignments->pluck('user_group_type')));
        $this->assertEquals([$data->pos1->id], array_unique($expanded_assignments->pluck('user_group_id')));
    }

    public function test_job_assignment_position_change_sets_expand_flag_in_old_and_new_position() {
        advanced_feature::enable('competency_assignment');

        $data = $this->prepare_assignments();

        $job_data = [
            'userid' => $data->user1->id,
            'idnumber' => 'dev13',
            'fullname' => 'Developer',
            'positionid' => $data->pos1->id
        ];
        $job_assignment = job_assignment::create($job_data);

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        $job_assignment->update([
            'positionid' => $data->pos2->id
        ]);

        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(6, $expanded_assignments);
        $this->assertEquals([user_groups::POSITION], array_unique($expanded_assignments->pluck('user_group_type')));
        $this->assertEqualsCanonicalizing(
            [$data->pos1->id, $data->pos2->id],
            array_unique($expanded_assignments->pluck('user_group_id'))
        );
    }

    public function test_job_assignment_organisation_creation_and_deletion_sets_expand_flag() {
        advanced_feature::enable('competency_assignment');

        $data = $this->prepare_assignments();

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        $job_data = [
            'userid' => $data->user1->id,
            'idnumber' => 'dev13',
            'fullname' => 'Developer',
            'organisationid' => $data->org1->id
        ];
        $job_assignment = job_assignment::create($job_data);

        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(3, $expanded_assignments);
        $this->assertEquals([user_groups::ORGANISATION], array_unique($expanded_assignments->pluck('user_group_type')));
        $this->assertEquals([$data->org1->id], array_unique($expanded_assignments->pluck('user_group_id')));

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        job_assignment::delete($job_assignment);

        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(3, $expanded_assignments);
        $this->assertEquals([user_groups::ORGANISATION], array_unique($expanded_assignments->pluck('user_group_type')));
        $this->assertEquals([$data->org1->id], array_unique($expanded_assignments->pluck('user_group_id')));
    }

    public function test_job_assignment_organisation_change_sets_expand_flag() {
        advanced_feature::enable('competency_assignment');

        $data = $this->prepare_assignments();

        $job_data = [
            'userid' => $data->user1->id,
            'idnumber' => 'dev13',
            'fullname' => 'Developer',
            'positionid' => null
        ];
        $job_assignment = job_assignment::create($job_data);

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        $job_assignment->update([
            'organisationid' => $data->org1->id
        ]);

        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(3, $expanded_assignments);
        $this->assertEquals([user_groups::ORGANISATION], array_unique($expanded_assignments->pluck('user_group_type')));
        $this->assertEquals([$data->org1->id], array_unique($expanded_assignments->pluck('user_group_id')));
    }

    public function test_job_assignment_organisation_change_sets_expand_flag_in_old_and_new_position() {
        advanced_feature::enable('competency_assignment');

        $data = $this->prepare_assignments();

        $job_data = [
            'userid' => $data->user1->id,
            'idnumber' => 'dev13',
            'fullname' => 'Developer',
            'organisationid' => $data->org1->id
        ];
        $job_assignment = job_assignment::create($job_data);

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        $job_assignment->update([
            'organisationid' => $data->org2->id
        ]);

        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(6, $expanded_assignments);
        $this->assertEquals([user_groups::ORGANISATION], array_unique($expanded_assignments->pluck('user_group_type')));
        $this->assertEqualsCanonicalizing(
            [$data->org1->id, $data->org2->id],
            array_unique($expanded_assignments->pluck('user_group_id'))
        );
    }

    public function test_job_assignment_mixed_creation_and_deletion_sets_expand_flag() {
        advanced_feature::enable('competency_assignment');

        $data = $this->prepare_assignments();

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        $job_data = [
            'userid' => $data->user1->id,
            'idnumber' => 'dev13',
            'fullname' => 'Developer',
            'positionid' => $data->pos1->id,
            'organisationid' => $data->org1->id
        ];
        $job_assignment = job_assignment::create($job_data);

        // Creation of the job assignment triggered the expand flag to be set
        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(6, $expanded_assignments);
        $this->assertEqualsCanonicalizing(
            [user_groups::POSITION, user_groups::ORGANISATION],
            array_unique($expanded_assignments->pluck('user_group_type'))
        );
        $this->assertEqualsCanonicalizing(
            [$data->pos1->id, $data->org1->id],
            array_unique($expanded_assignments->pluck('user_group_id'))
        );

        (new expand_task($GLOBALS['DB']))->expand_all();

        // All expand flags are reset
        $this->assertCount(0, assignment::repository()->filter_by_expand()->get());

        job_assignment::delete($job_assignment);

        // Deletion triggered the expand flag to be set
        $expanded_assignments = assignment::repository()->filter_by_expand()->get();
        $this->assertCount(6, $expanded_assignments);
        $this->assertEqualsCanonicalizing(
            [user_groups::POSITION, user_groups::ORGANISATION],
            array_unique($expanded_assignments->pluck('user_group_type'))
        );
        $this->assertEqualsCanonicalizing(
            [$data->pos1->id, $data->org1->id],
            array_unique($expanded_assignments->pluck('user_group_id'))
        );
    }

    private function prepare_assignments() {
        $this->setAdminUser();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->generator()->get_plugin_generator('totara_hierarchy');

        $test_data = new class() {
            public $user1;

            public $cohort1;
            public $cohort2;

            public $pos1;
            public $pos2;

            public $org1;
            public $org2;

            public $active_ind = [];
            public $active_coh = [];
            public $active_pos = [];
            public $active_org = [];
            public $active = [];

            public $comp1;
            public $comp2;
            public $comp3;
        };

        $test_data->user1 = $this->generator()->create_user();
        $test_data->cohort1 = $this->generator()->create_cohort();
        $test_data->cohort2 = $this->generator()->create_cohort();

        $fw = $hierarchy_generator->create_comp_frame(['fullname' => 'Framework one', 'idnumber' => 'f1']);
        $test_data->comp1 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c1', 'parentid' => 0]);
        $test_data->comp2 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c2', 'parentid' => 0]);
        $test_data->comp3 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c3', 'parentid' => 0]);

        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Framework 2']);
        $test_data->pos1 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);
        $test_data->pos2 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 2']);

        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Framework 3']);
        $test_data->org1 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);
        $test_data->org2 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 2']);

        $actions = new assignment_actions();

        $competencies = [$test_data->comp1->id, $test_data->comp2->id, $test_data->comp3->id];

        $test_data->active_ind = $actions->create_from_competencies(
            $competencies,
            [user_groups::USER => [$test_data->user1->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        );
        $test_data->active_coh = $actions->create_from_competencies(
            $competencies,
            [user_groups::COHORT => [$test_data->cohort1->id, $test_data->cohort2->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        );
        $test_data->active_pos = $actions->create_from_competencies(
            $competencies,
            [user_groups::POSITION => [$test_data->pos1->id, $test_data->pos2->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        );
        $test_data->active_org = $actions->create_from_competencies(
            $competencies,
            [user_groups::ORGANISATION => [$test_data->org1->id, $test_data->org2->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        );

        return $test_data;
    }

    /**
     * Date generator shortcut
     *
     * @return \core\testing\generator
     */
    protected function generator() {
        return self::getDataGenerator();
    }

}
