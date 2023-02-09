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
 * @package core
 * @category test
 */

use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

abstract class expanded_users_testcase extends advanced_testcase {

    protected function assert_has_user($users, stdClass $expected_user, array $expected_user_group_names) {
        foreach ($users as $user) {
            if ($user['user_id'] == $expected_user->id) {
                $this->assertEquals($expected_user->firstname, $user['firstname']);
                $this->assertEquals($expected_user->lastname, $user['lastname']);
                $this->assertEqualsCanonicalizing(
                    $expected_user_group_names,
                    $user['user_group_names'],
                    "Invalid user groups found for user {$expected_user->id}"
                );
                return;
            }
        }
        $this->fail("Expected user {$expected_user->id} to be in result but not found.");
    }

    protected function generate_data() {
        $generator = $this->getDataGenerator();

        $data = new class {
            public $user1, $user2, $user3, $user4, $user5;
            public $user6, $user7, $user8, $user9, $user10;
            public $user11, $user12, $user13, $user14, $user15;
            public $user16, $user17, $user18, $user19, $user20;
            public $cohort1, $cohort2;
            public $pos1, $pos2;
            public $org1, $org2;
        };

        // Create 2 cohorts
        // with 3 members in first
        // with 2 members in second

        $data->cohort1 = $generator->create_cohort();
        $data->cohort2 = $generator->create_cohort();

        $data->user1 = $generator->create_user();
        $data->user2 = $generator->create_user();
        $data->user3 = $generator->create_user();
        $data->user4 = $generator->create_user();
        $data->user5 = $generator->create_user();

        cohort_add_member($data->cohort1->id, $data->user1->id);
        cohort_add_member($data->cohort1->id, $data->user2->id);
        cohort_add_member($data->cohort1->id, $data->user3->id);
        cohort_add_member($data->cohort2->id, $data->user4->id);
        cohort_add_member($data->cohort2->id, $data->user5->id);

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');

        // Create two positions
        // with 3 members in first
        // with 2 members in second

        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Framework 1']);
        $data->pos1 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);
        $data->pos2 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 2']);

        $data->user6 = $generator->create_user();
        $data->user7 = $generator->create_user();
        $data->user8 = $generator->create_user();
        $data->user9 = $generator->create_user();
        $data->user10 = $generator->create_user();

        job_assignment::create([
            'userid' => $data->user6->id,
            'idnumber' => 'dev1',
            'positionid' => $data->pos1->id
        ]);
        job_assignment::create([
            'userid' => $data->user7->id,
            'idnumber' => 'dev2',
            'positionid' => $data->pos1->id
        ]);
        job_assignment::create([
            'userid' => $data->user8->id,
            'idnumber' => 'dev3',
            'positionid' => $data->pos1->id
        ]);
        job_assignment::create([
            'userid' => $data->user9->id,
            'idnumber' => 'dev4',
            'positionid' => $data->pos2->id
        ]);
        job_assignment::create([
            'userid' => $data->user10->id,
            'idnumber' => 'dev5',
            'positionid' => $data->pos2->id
        ]);

        // Create two organisations
        // with 3 members in first
        // with 2 members in second

        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Framework 1']);
        $data->org1 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);
        $data->org2 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 2']);

        $data->user11 = $generator->create_user();
        $data->user12 = $generator->create_user();
        $data->user13 = $generator->create_user();
        $data->user14 = $generator->create_user();
        $data->user15 = $generator->create_user();

        job_assignment::create([
            'userid' => $data->user11->id,
            'idnumber' => 'dev1',
            'organisationid' => $data->org1->id
        ]);
        job_assignment::create([
            'userid' => $data->user12->id,
            'idnumber' => 'dev2',
            'organisationid' => $data->org1->id
        ]);
        job_assignment::create([
            'userid' => $data->user13->id,
            'idnumber' => 'dev3',
            'organisationid' => $data->org1->id
        ]);
        job_assignment::create([
            'userid' => $data->user14->id,
            'idnumber' => 'dev4',
            'organisationid' => $data->org2->id
        ]);
        job_assignment::create([
            'userid' => $data->user15->id,
            'idnumber' => 'dev5',
            'organisationid' => $data->org2->id
        ]);

        // Create 3 additional users

        $data->user16 = $generator->create_user();
        $data->user17 = $generator->create_user();
        $data->user18 = $generator->create_user();

        $data->user19 = $generator->create_user(['firstname' => 'Omaru', 'lastname' => 'Fog']);
        $data->user20 = $generator->create_user(['firstname' => 'Tomaru', 'lastname' => 'Cloud']);

        return $data;
    }

    protected function get_full_name(stdClass $user): string {
        return $user->firstname.' '.$user->lastname;
    }

}
