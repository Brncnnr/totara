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
use totara_competency\models\assignment as assignment_model;
use totara_competency\user_groups;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_expand_task_testcase extends advanced_testcase {

    /**
     * @var moodle_database
     */
    private $db;

    /**
     * Date generator shortcut
     *
     * @return \core\testing\generator
     */
    protected function generator() {
        return self::getDataGenerator();
    }

    protected function setUp(): void {
        parent::setUp();
        $this->db = $GLOBALS['DB'];
        $this->setAdminUser();
    }

    protected function tearDown(): void {
        $this->db = null;
        parent::tearDown();
    }

    public function test_expand_single_only_marked_assignments() {
        $test_data = $this->prepare_assignments();

        /** @var assignment_model $draft_assignment */
        $draft_assignment = $test_data->draft_ind->first();
        /** @var assignment_model $active_assignment */
        $active_assignment = $test_data->active_ind->first();
        /** @var assignment_model $archived_assignment */
        $archived_assignment = $test_data->archive_ind->first();

        $this->assertEquals(0, $draft_assignment->expand);
        $this->assertEquals(1, $active_assignment->expand);
        $this->assertEquals(0, $archived_assignment->expand);

        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $draft_assignment->id)
                ->exists()
        );
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $active_assignment->id)
                ->exists()
        );
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $archived_assignment->id)
                ->exists()
        );

        $task = new expand_task($this->db);

        // Expanding a draft assignment does not create any users rows
        $task->expand_single($draft_assignment->id);
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $draft_assignment->id)
                ->exists()
        );

        // Expanding a archived assignment does not create any users rows
        $task->expand_single($archived_assignment->id);
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $archived_assignment->id)
                ->exists()
        );

        $entity = $active_assignment->get_entity();
        $entity->expand = false;
        $entity->save();

        // Expanding an active assignment with expand flag set to 0 does not create any users rows
        $task->expand_single($active_assignment->id);
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $active_assignment->id)
                ->exists()
        );

        $entity = $active_assignment->get_entity();
        $entity->expand = true;
        $entity->save();

        // Expanding an active assignment with expand flag set to 0 does not create any users rows
        $task->expand_single($active_assignment->id);
        $this->assertTrue(
            competency_assignment_user::repository()
                ->where('assignment_id', $active_assignment->id)
                ->exists()
        );

        // Flag got reset
        $entity->refresh();
        $this->assertFalse($entity->expand);
    }

    public function test_expand_multiple_only_marked_assignments() {
        $test_data = $this->prepare_assignments();

        /** @var assignment_model $draft_assignment */
        $draft_assignment = $test_data->draft_ind->first();
        /** @var assignment_model $active_assignment */
        $active_assignment = $test_data->active_ind->first();
        /** @var assignment_model $archived_assignment */
        $archived_assignment = $test_data->archive_ind->first();

        $this->assertEquals(0, $draft_assignment->expand);
        $this->assertEquals(1, $active_assignment->expand);
        $this->assertEquals(0, $archived_assignment->expand);

        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $draft_assignment->id)
                ->exists()
        );
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $active_assignment->id)
                ->exists()
        );
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $archived_assignment->id)
                ->exists()
        );

        $task = new expand_task($this->db);

        $entity = $active_assignment->get_entity();
        $entity->expand = false;
        $entity->save();

        // Expanding an active assignment with expand flag set to 0 does not create any users rows
        $task->expand_multiple([$draft_assignment->id, $archived_assignment->id, $active_assignment->id]);
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $draft_assignment->id)
                ->exists()
        );
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $archived_assignment->id)
                ->exists()
        );
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $active_assignment->id)
                ->exists()
        );

        $entity = $active_assignment->get_entity();
        $entity->expand = true;
        $entity->save();

        // Expanding an active assignment with expand flag set to 0 does not create any users rows
        $task->expand_multiple([$active_assignment->id]);
        $this->assertTrue(
            competency_assignment_user::repository()
                ->where('assignment_id', $active_assignment->id)
                ->exists()
        );

        // Flag got reset
        $entity->refresh();
        $this->assertFalse($entity->expand);
    }

    public function test_expand_all_only_marked_assignments() {
        $test_data = $this->prepare_assignments();

        /** @var assignment_model $draft_assignment */
        $draft_assignment = $test_data->draft_ind->first();
        /** @var assignment_model $active_assignment */
        $active_assignment = $test_data->active_ind->first();
        /** @var assignment_model $archived_assignment */
        $archived_assignment = $test_data->archive_ind->first();

        $this->assertEquals(0, $draft_assignment->expand);
        $this->assertEquals(1, $active_assignment->expand);
        $this->assertEquals(0, $archived_assignment->expand);

        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $draft_assignment->id)
                ->exists()
        );
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $active_assignment->id)
                ->exists()
        );
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $archived_assignment->id)
                ->exists()
        );

        $task = new expand_task($this->db);

        $entity = $active_assignment->get_entity();
        $entity->expand = false;
        $entity->save();

        // Expanding an active assignment with expand flag set to 0 does not create any users rows
        $task->expand_all();
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $draft_assignment->id)
                ->exists()
        );
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $archived_assignment->id)
                ->exists()
        );
        $this->assertFalse(
            competency_assignment_user::repository()
                ->where('assignment_id', $active_assignment->id)
                ->exists()
        );

        $entity = $active_assignment->get_entity();
        $entity->expand = true;
        $entity->save();

        // Expanding an active assignment with expand flag set to 0 does not create any users rows
        $task->expand_all();
        $this->assertTrue(
            competency_assignment_user::repository()
                ->where('assignment_id', $active_assignment->id)
                ->exists()
        );

        // Flag got reset
        $entity->refresh();
        $this->assertFalse($entity->expand);
    }

    public function test_expand_one() {
        $test_data = $this->prepare_assignments();

        $this->assertEquals(36, $this->db->count_records('totara_competency_assignments'));
        $this->assertEquals(0, $this->db->count_records('totara_competency_assignment_users'));

        $assignment = $test_data->active_ind->all()[0];

        $task = new expand_task($this->db);
        $task->expand_single($assignment->get_id());

        // there should only be one row now
        $this->assertEquals(1, $this->db->count_records('totara_competency_assignment_users'));

        $this->assert_records_exist(
            $assignment->get_id(),
            [$test_data->user2->id],
            [$assignment->get_field('competency_id')]
        );
    }

    public function test_expand_one_non_existent() {
        $test_data = $this->prepare_assignments();

        $this->assertEquals(36, $this->db->count_records('totara_competency_assignments'));
        $this->assertEquals(0, $this->db->count_records('totara_competency_assignment_users'));

        $task = new expand_task($this->db);
        $task->expand_single(999);

        $this->assertEquals(0, $this->db->count_records('totara_competency_assignment_users'));
    }

    public function test_expand_multiple() {
        $test_data = $this->prepare_assignments();

        $this->assertEquals(36, $this->db->count_records('totara_competency_assignments'));
        $this->assertEquals(0, $this->db->count_records('totara_competency_assignment_users'));

        $assignment1 = $test_data->draft_ind->all()[0];
        $assignment2 = $test_data->active_ind->all()[0];
        $assignment3 = $test_data->archive_ind->all()[0];

        $task = new expand_task($this->db);
        $task->expand_multiple([$assignment1->get_field('id'), $assignment2->get_field('id'), $assignment3->get_field('id')]);

        // there should be one row now
        $this->assertEquals(1, $this->db->count_records('totara_competency_assignment_users'));

        $this->assert_records_exist(
            $assignment2->get_field('id'),
            [$test_data->user2->id],
            [$assignment2->get_field('competency_id')]
        );
    }

    public function test_expand_multiple_unusual_array_content() {
        $test_data = $this->prepare_assignments();

        $this->assertEquals(36, $this->db->count_records('totara_competency_assignments'));
        $this->assertEquals(0, $this->db->count_records('totara_competency_assignment_users'));

        $assignment1 = $test_data->draft_ind->all()[0];
        $assignment2 = $test_data->active_ind->all()[0];

        $task = new expand_task($this->db);
        $task->expand_multiple(["{$assignment1->get_field('id')}", $assignment2->get_field('id'), "dsds", "fssds"]);

        // there should be two rows now
        $this->assertEquals(1, $this->db->count_records('totara_competency_assignment_users'));

        $this->assert_records_exist(
            $assignment2->get_field('id'),
            [$test_data->user2->id],
            [$assignment2->get_field('competency_id')]
        );
    }

    public function test_expand_multiple_with_empty_array() {
        $task = new expand_task($this->db);
        $task->expand_multiple([]);

        $this->assertEquals(0, $this->db->count_records('totara_competency_assignment_users'));
    }

    public function test_expand_all_users() {
        $test_data = $this->prepare_assignments();

        $this->assertEquals(36, $this->db->count_records('totara_competency_assignments'));
        $this->assertEquals(0, $this->db->count_records('totara_competency_assignment_users'));

        $task = new expand_task($this->db);
        $task->expand_all();

        // three active assignments for user user_group where expanded
        $this->assertEquals(3, $this->db->count_records('totara_competency_assignment_users'));

        foreach ($test_data->draft_ind as $assignment) {
            $this->assert_records_dont_exist(
                $assignment->get_field('id'),
                [$test_data->user1->id],
                [$assignment->get_field('competency_id')]
            );
        }
        foreach ($test_data->archive_ind as $assignment) {
            $this->assert_records_dont_exist(
                $assignment->get_field('id'),
                [$test_data->user3->id],
                [$assignment->get_field('competency_id')]
            );
        }
        foreach ($test_data->active_ind as $assignment) {
            $this->assert_records_exist(
                $assignment->get_field('id'),
                [$test_data->user2->id],
                [$assignment->get_field('competency_id')]
            );
        }
        foreach ($test_data->active_coh as $assignment) {
            $this->assert_records_dont_exist(
                $assignment->get_field('id'),
                [$test_data->user1->id, $test_data->user2->id],
                [$assignment->get_field('competency_id')]
            );
        }
    }

    public function test_expand_all_cohorts() {
        $test_data = $this->prepare_assignments();

        $this->assertEquals(36, $this->db->count_records('totara_competency_assignments'));
        $this->assertEquals(0, $this->db->count_records('totara_competency_assignment_users'));

        cohort_add_member($test_data->cohort2->id, $test_data->user2->id);

        $task = new expand_task($this->db);
        $task->expand_all();

        // adding the cohort member added three more records
        $this->assertEquals(6, $this->db->count_records('totara_competency_assignment_users'));

        foreach ($test_data->active_coh as $assignment) {
            $this->assert_records_exist(
                $assignment->get_field('id'),
                [$test_data->user2->id],
                [$assignment->get_field('competency_id')]
            );
            // there's only one user in the cohort so far
            $this->assert_records_dont_exist(
                $assignment->get_field('id'),
                [$test_data->user1->id],
                [$assignment->get_field('competency_id')]
            );
        }

        cohort_add_member($test_data->cohort2->id, $test_data->user1->id);

        $task = new expand_task($this->db);
        $task->expand_all();

        // adding the cohort member added three more records
        $this->assertEquals(9, $this->db->count_records('totara_competency_assignment_users'));

        foreach ($test_data->active_ind as $assignment) {
            $this->assert_records_exist(
                $assignment->get_field('id'),
                [$test_data->user2->id],
                [$assignment->get_field('competency_id')]
            );
        }
        foreach ($test_data->active_coh as $assignment) {
            // both users are now in the cohort and expanded
            $this->assert_records_exist(
                $assignment->get_field('id'),
                [$test_data->user1->id, $test_data->user2->id],
                [$assignment->get_field('competency_id')]
            );
        }
    }

    public function test_expand_all_cohort_member_removed() {
        $test_data = $this->prepare_assignments();

        $this->assertEquals(36, $this->db->count_records('totara_competency_assignments'));
        $this->assertEquals(0, $this->db->count_records('totara_competency_assignment_users'));

        cohort_add_member($test_data->cohort2->id, $test_data->user2->id);

        $task = new expand_task($this->db);
        $task->expand_all();

        // adding the cohort member added three more records
        $this->assertEquals(6, $this->db->count_records('totara_competency_assignment_users'));

        foreach ($test_data->active_coh as $assignment) {
            $this->assert_records_exist(
                $assignment->get_field('id'),
                [$test_data->user2->id],
                [$assignment->get_field('competency_id')]
            );
            // there's only one user in the cohort so far
            $this->assert_records_dont_exist(
                $assignment->get_field('id'),
                [$test_data->user1->id],
                [$assignment->get_field('competency_id')]
            );
        }

        cohort_remove_member($test_data->cohort2->id, $test_data->user2->id);

        $task = new expand_task($this->db);
        $task->expand_all();

        // the records for the user who was removed from the cohort should be gone now
        $this->assertEquals(3, $this->db->count_records('totara_competency_assignment_users'));

        foreach ($test_data->active_coh as $assignment) {
            $this->assert_records_dont_exist(
                $assignment->get_field('id'),
                [$test_data->user2->id],
                [$assignment->get_field('competency_id')]
            );
        }
    }

    public function test_expand_all_position() {
        $test_data = $this->prepare_assignments();

        $this->assertEquals(36, $this->db->count_records('totara_competency_assignments'));
        $this->assertEquals(0, $this->db->count_records('totara_competency_assignment_users'));

        $job_data = [
            'userid' => $test_data->user3->id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'positionid' => $test_data->pos2->id
        ];
        job_assignment::create($job_data);

        $task = new expand_task($this->db);
        $task->expand_all();

        // assigning the position added three more records
        $this->assertEquals(6, $this->db->count_records('totara_competency_assignment_users'));

        foreach ($test_data->active_pos as $assignment) {
            $this->assert_records_exist(
                $assignment->get_field('id'),
                [$test_data->user3->id],
                [$assignment->get_field('competency_id')]
            );
        }

        $job_data = [
            'userid' => $test_data->user4->id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'positionid' => $test_data->pos2->id
        ];
        job_assignment::create($job_data);

        $task = new expand_task($this->db);
        $task->expand_all();

        // assigning the position added three more records
        $this->assertEquals(9, $this->db->count_records('totara_competency_assignment_users'));

        foreach ($test_data->active_pos as $assignment) {
            $this->assert_records_exist(
                $assignment->get_field('id'),
                [$test_data->user4->id],
                [$assignment->get_field('competency_id')]
            );
        }
    }

    public function test_expand_all_organisation() {
        $test_data = $this->prepare_assignments();

        $this->assertEquals(36, $this->db->count_records('totara_competency_assignments'));
        $this->assertEquals(0, $this->db->count_records('totara_competency_assignment_users'));

        $job_data = [
            'userid' => $test_data->user3->id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'organisationid' => $test_data->org2->id
        ];
        job_assignment::create($job_data);

        $task = new expand_task($this->db);
        $task->expand_all();

        // assigning the organisation added three more records
        $this->assertEquals(6, $this->db->count_records('totara_competency_assignment_users'));

        foreach ($test_data->active_org as $assignment) {
            $this->assert_records_exist(
                $assignment->get_field('id'),
                [$test_data->user3->id],
                [$assignment->get_field('competency_id')]
            );
        }

        $job_data = [
            'userid' => $test_data->user4->id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'organisationid' => $test_data->org2->id
        ];
        job_assignment::create($job_data);

        $task = new expand_task($this->db);
        $task->expand_all();

        // assigning the organisation added three more records
        $this->assertEquals(9, $this->db->count_records('totara_competency_assignment_users'));

        foreach ($test_data->active_org as $assignment) {
            $this->assert_records_exist(
                $assignment->get_field('id'),
                [$test_data->user4->id],
                [$assignment->get_field('competency_id')]
            );
        }
    }

    private function prepare_assignments() {
        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->generator()->get_plugin_generator('totara_hierarchy');

        $test_data = new class() {
            public $user1;
            public $user2;
            public $user3;
            public $user4;
            public $cohort1;
            public $cohort2;
            public $cohort3;
            public $pos1;
            public $pos2;
            public $pos3;
            public $org1;
            public $org2;
            public $org3;
            /** @var array|assignment_model[] */
            public $draft_ind = [];
            /** @var array|assignment_model[] */
            public $draft_coh = [];
            /** @var array|assignment_model[] */
            public $draft_pos = [];
            /** @var array|assignment_model[] */
            public $draft_org = [];
            /** @var array|assignment_model[] */
            public $active_ind = [];
            /** @var array|assignment_model[] */
            public $active_coh = [];
            /** @var array|assignment_model[] */
            public $active_pos = [];
            /** @var array|assignment_model[] */
            public $active_org = [];
            /** @var array|assignment_model[] */
            public $archive_ind = [];
            /** @var array|assignment_model[] */
            public $archive_coh = [];
            /** @var array|assignment_model[] */
            public $archive_pos = [];
            /** @var array|assignment_model[] */
            public $archive_org = [];
            public $active = [];
            public $comp1;
            public $comp2;
            public $comp3;
            public $comp4;
            public $comp5;
            public $comp6;
            public $comp7;
            public $comp8;
            public $comp9;
        };

        $test_data->user1 = $this->generator()->create_user();
        $test_data->user2 = $this->generator()->create_user();
        $test_data->user3 = $this->generator()->create_user();
        $test_data->user4 = $this->generator()->create_user();
        $test_data->cohort1 = $this->generator()->create_cohort();
        $test_data->cohort2 = $this->generator()->create_cohort();
        $test_data->cohort3 = $this->generator()->create_cohort();
        $test_data->pos1 = $this->generator()->create_cohort();

        $fw = $hierarchy_generator->create_comp_frame(['fullname' => 'Framework one', 'idnumber' => 'f1']);
        $test_data->comp1 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c1', 'parentid' => 0]);
        $test_data->comp2 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c2', 'parentid' => 0]);
        $test_data->comp3 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c3', 'parentid' => 0]);
        $test_data->comp4 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c4', 'parentid' => 0]);
        $test_data->comp5 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c5', 'parentid' => 0]);
        $test_data->comp6 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c6', 'parentid' => 0]);
        $test_data->comp7 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c7', 'parentid' => 0]);
        $test_data->comp8 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c8', 'parentid' => 0]);
        $test_data->comp9 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c9', 'parentid' => 0]);

        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Framework 2']);
        $test_data->pos1 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);
        $test_data->pos2 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 2']);
        $test_data->pos3 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 3']);

        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Framework 3']);
        $test_data->org1 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);
        $test_data->org2 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 2']);
        $test_data->org3 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 3']);

        $competencies = [$test_data->comp1->id, $test_data->comp2->id, $test_data->comp3->id];

        $actions = new \totara_competency\models\assignment_actions();

        $test_data->draft_ind = $actions->create_from_competencies($competencies, [user_groups::USER => [$test_data->user1->id]], assignment::TYPE_ADMIN, assignment::STATUS_DRAFT);
        $test_data->draft_coh = $actions->create_from_competencies($competencies, [user_groups::COHORT => [$test_data->cohort1->id]], assignment::TYPE_ADMIN, assignment::STATUS_DRAFT);
        $test_data->draft_pos = $actions->create_from_competencies($competencies, [user_groups::POSITION => [$test_data->pos1->id]], assignment::TYPE_ADMIN, assignment::STATUS_DRAFT);
        $test_data->draft_org = $actions->create_from_competencies($competencies, [user_groups::ORGANISATION => [$test_data->org1->id]], assignment::TYPE_ADMIN, assignment::STATUS_DRAFT);

        $competencies = [$test_data->comp4->id, $test_data->comp5->id, $test_data->comp6->id];

        $test_data->active_ind = $actions->create_from_competencies($competencies, [user_groups::USER => [$test_data->user2->id]], assignment::TYPE_ADMIN, assignment::STATUS_ACTIVE);
        $test_data->active_coh = $actions->create_from_competencies($competencies, [user_groups::COHORT => [$test_data->cohort2->id]], assignment::TYPE_ADMIN, assignment::STATUS_ACTIVE);
        $test_data->active_pos = $actions->create_from_competencies($competencies, [user_groups::POSITION => [$test_data->pos2->id]], assignment::TYPE_ADMIN, assignment::STATUS_ACTIVE);
        $test_data->active_org = $actions->create_from_competencies($competencies, [user_groups::ORGANISATION => [$test_data->org2->id]], assignment::TYPE_ADMIN, assignment::STATUS_ACTIVE);

        $competencies = [$test_data->comp7->id, $test_data->comp8->id, $test_data->comp9->id];

        $test_data->archive_ind = $actions->create_from_competencies($competencies, [user_groups::USER => [$test_data->user3->id]], assignment::TYPE_ADMIN, assignment::STATUS_ACTIVE);
        $test_data->archive_coh = $actions->create_from_competencies($competencies, [user_groups::COHORT => [$test_data->cohort3->id]], assignment::TYPE_ADMIN, assignment::STATUS_ACTIVE);
        $test_data->archive_pos = $actions->create_from_competencies($competencies, [user_groups::POSITION => [$test_data->pos3->id]], assignment::TYPE_ADMIN, assignment::STATUS_ACTIVE);
        $test_data->archive_org = $actions->create_from_competencies($competencies, [user_groups::ORGANISATION => [$test_data->org3->id]], assignment::TYPE_ADMIN, assignment::STATUS_ACTIVE);

        /** @var assignment $assignment */
        foreach ($test_data->archive_ind as $assignment) {
            $assignment->archive();
        }
        foreach ($test_data->archive_coh as $assignment) {
            $assignment->archive();
        }
        foreach ($test_data->archive_pos as $assignment) {
            $assignment->archive();
        }
        foreach ($test_data->archive_org as $assignment) {
            $assignment->archive();
        }

        return $test_data;
    }

    /**
     * Assert that records for all given user and competency combinations exist
     *
     * @param int $assignment_id
     * @param array $user_ids
     * @param array $competency_ids
     * @return void
     */
    private function assert_records_exist(int $assignment_id, array $user_ids, array $competency_ids) {
        foreach ($user_ids as $user_id) {
            foreach ($competency_ids as $competency_id) {
                $params = [
                    'assignment_id' => $assignment_id,
                    'user_id' => $user_id,
                    'competency_id' => $competency_id
                ];
                $this->assertTrue($this->db->record_exists('totara_competency_assignment_users', $params));
            }
        }
    }

    /**
     * Assert that records for all given user and competency combinations exist
     *
     * @param int $assignment_id
     * @param array $user_ids
     * @param array $competency_ids
     * @return void
     */
    private function assert_records_dont_exist(int $assignment_id, array $user_ids, array $competency_ids) {
        foreach ($user_ids as $user_id) {
            foreach ($competency_ids as $competency_id) {
                $params = [
                    'assignment_id' => $assignment_id,
                    'user_id' => $user_id,
                    'competency_id' => $competency_id
                ];
                $this->assertFalse($this->db->record_exists('totara_competency_assignment_users', $params));
            }
        }
    }

}