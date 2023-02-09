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

defined('MOODLE_INTERNAL') || die();

use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\userdata\assignment_user;
use totara_competency\user_groups;
use totara_job\job_assignment;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * @group totara_competency
 */
class totara_competency_userdata_assignment_user_testcase extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_context_levels() {
        $context_levels = assignment_user::get_compatible_context_levels();
        $this->assertCount(1, $context_levels);
        $this->assertContains(CONTEXT_SYSTEM, $context_levels);
    }

    /**
     * Confirm that no errors are thrown and that correct data is returned when no assignments for the user exist
     * within the system.
     */
    public function test_with_no_data() {
        $user = $this->getDataGenerator()->create_user();

        $export = assignment_user::execute_export(
            new target_user($user),
            context_system::instance()
        );
        $this->assertEmpty($export->files);
        $this->assertEmpty($export->data['assignments']);
        $this->assertEmpty($export->data['logs']);

        $count = assignment_user::execute_count(
            new target_user($user),
            context_system::instance()
        );
        $this->assertEquals(0, $count);

        $result = assignment_user::execute_purge(
            new target_user($user),
            context_system::instance()
        );
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
    }

    public function test_count_assignments() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        ['pos' => $pos, 'org' => $org] = $this->generate_assignments($user1);

        $job_data = [
            'userid' => $user1->id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'positionid' => $pos[0]->id
        ];
        job_assignment::create($job_data);

        $job_data = [
            'userid' => $user2->id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'organisationid' => $org[0]->id
        ];
        job_assignment::create($job_data);

        $this->expand();

        $count = assignment_user::execute_count(
            new target_user($user1),
            context_system::instance()
        );
        $this->assertEquals(4, $count);
    }

    public function test_purge_assignments() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        ['pos' => $pos, 'org' => $org] = $this->generate_assignments($user1);

        $job_data = [
            'userid' => $user1->id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'positionid' => $pos[0]->id
        ];
        job_assignment::create($job_data);

        $job_data = [
            'userid' => $user1->id,
            'idnumber' => 'dev2',
            'fullname' => 'Developer',
            'organisationid' => $org[0]->id
        ];
        job_assignment::create($job_data);

        $job_data = [
            'userid' => $user2->id,
            'idnumber' => 'dev3',
            'fullname' => 'Developer',
            'organisationid' => $org[0]->id
        ];
        job_assignment::create($job_data);

        $this->expand();

        // We should have some logs for these user
        $this->assertGreaterThan(0, competency_assignment_user_log::repository()
            ->where('user_id', $user1->id)
            ->count()
        );
        $expected_log_control_count = competency_assignment_user_log::repository()
            ->where('user_id', $user2->id)
            ->count();
        $this->assertGreaterThan(0, $expected_log_control_count);

        $result = assignment_user::execute_purge(
            new target_user($user1),
            context_system::instance()
        );
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // All records have been deleted
        $this->assertEquals(0, competency_assignment_user::repository()
            ->where('user_id', $user1->id)
            ->count()
        );
        $this->assertEquals(0, assignment::repository()
            ->where('user_group_type', user_groups::USER)
            ->where('user_group_id', $user1->id)
            ->count()
        );
        // Logs are purged as well
        $this->assertEquals(0, competency_assignment_user_log::repository()
            ->where('user_id', $user1->id)
            ->count()
        );

        // There's still the position and organisation assignment
        $this->assertEquals(2, assignment::repository()
            ->count()
        );
        $this->assertEquals(1, competency_assignment_user::repository()
            ->count()
        );
        // Logs of other use haven't been touchec
        $this->assertEquals($expected_log_control_count, competency_assignment_user_log::repository()
            ->where('user_id', $user2->id)
            ->count()
        );
    }

    public function test_export_assignments() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        ['assignments' => $ass, 'pos' => $pos, 'org' => $org] = $this->generate_assignments($user1);

        $job_data = [
            'userid' => $user1->id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'positionid' => $pos[0]->id
        ];
        job_assignment::create($job_data);

        $job_data = [
            'userid' => $user2->id,
            'idnumber' => 'dev3',
            'fullname' => 'Developer',
            'organisationid' => $org[0]->id
        ];
        job_assignment::create($job_data);

        $this->expand();

        $result = assignment_user::execute_export(
            new target_user($user1),
            context_system::instance()
        );
        $this->assertInstanceOf(\totara_userdata\userdata\export::class, $result);
        $this->assertNotEmpty($result->data['assignments']);
        $this->assertNotEmpty($result->data['logs']);

        // Remove last assignment as it's not for the user
        array_pop($ass);

        $expected_assignment_ids = array_column($ass, 'id');
        sort($expected_assignment_ids);
        $actual_assignment_ids = array_column($result->data['assignments'], 'id');
        sort($actual_assignment_ids);

        $this->assertEquals($expected_assignment_ids, $actual_assignment_ids);

        // We export all user name fields as well
        $user_name_fields = array_values(totara_get_all_user_name_fields(false, null, null, null, true));

        foreach ($result->data['assignments'] as $item) {
            $this->assertEqualsCanonicalizing(
                array_merge([
                    'id',
                    'type',
                    'competency_id',
                    'user_group_type',
                    'user_group_id',
                    'optional',
                    'status',
                    'created_by',
                    'created_at',
                    'updated_at',
                    'archived_at',
                    'user_group_name',
                    'idnumber',
                    'competency_name',
                    'competency_description',
                    'status_name',
                    'expand',
                    'minproficiencyid',
                ], $user_name_fields),
                array_keys($item)
            );
        }

        $expected_logs = competency_assignment_user_log::repository()
            ->where('user_id', $user1->id)
            ->get();
        $this->assertEquals($expected_logs->count(), count($result->data['logs']));
        foreach ($result->data['logs'] as $item) {
            $this->assertEquals(
                [
                    'id',
                    'assignment_id',
                    'user_id',
                    'action',
                    'created_at',
                    'action_name',
                ],
                array_keys($item)
            );
        }
    }

    /**
     * Create a few competencies with knows names to test search
     *
     * @param stdClass $user
     * @return array
     */
    protected function generate_assignments(stdClass $user) {
        $data = [
            'competencies' => [],
            'frameworks' => [],
            'assignments' => [],
            'types' => [],
            'pos' => [],
            'org' => []
        ];

        $gen = $this->generator();
        $hierarchy_generator = $gen->hierarchy_generator();

        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Framework 2']);
        $data['pos'][] = $pos1 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);

        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Framework 3']);
        $data['org'][] = $org1 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);


        $data['frameworks'][] = $fw = $hierarchy_generator->create_comp_frame([]);
        $data['frameworks'][] = $fw2 = $hierarchy_generator->create_comp_frame([]);

        $data['types'][] = $type1 = $hierarchy_generator->create_comp_type(['idnumber' => 'type1']);
        $data['types'][] = $type2 = $hierarchy_generator->create_comp_type(['idnumber' => 'type2']);

        $data['competencies'][] = $one = $gen->create_competency(null, $fw->id, [
            'shortname' => 'acc',
            'fullname' => 'Accounting',
            'description' => 'Counting profits',
            'idnumber' => 'accc',
            'typeid' => $type1,
        ]);

        $data['competencies'][] = $two = $gen->create_competency(null, $fw2->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
            'typeid' => $type1,
        ]);

        $data['competencies'][] = $three = $gen->create_competency(null, $fw->id, [
            'shortname' => 'des',
            'fullname' => 'Designing interiors',
            'description' => 'Decorating things',
            'idnumber' => 'des',
            'parentid' => $one->id,
            'typeid' => $type2,
        ]);

        // Create assignments for competencies
        $data['assignments'][] = $gen->assignment_generator()->create_user_assignment($one->id, $user->id, ['status' => assignment::STATUS_ACTIVE]);
        $data['assignments'][] = $gen->assignment_generator()->create_user_assignment($two->id, $user->id, ['status' => assignment::STATUS_ACTIVE]);
        $data['assignments'][] = $gen->assignment_generator()->create_user_assignment($three->id, $user->id, ['status' => assignment::STATUS_ACTIVE]);
        $data['assignments'][] = $gen->assignment_generator()->create_position_assignment($three->id, $pos1->id, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]);
        $data['assignments'][] = $gen->assignment_generator()->create_organisation_assignment($three->id, $org1->id, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]);

        return $data;
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_competency');
    }

    private function expand() {
        // We need the expanded users for the logging to work
        $expand_task = new \totara_competency\expand_task($GLOBALS['DB']);
        $expand_task->expand_all();
    }
}