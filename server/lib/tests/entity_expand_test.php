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

use core\entity\cohort;
use hierarchy_organisation\entity\organisation;
use hierarchy_position\entity\position;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

class core_entity_expand_testcase extends advanced_testcase {

    public function test_cohort_expansion() {
        $generator = $this->getDataGenerator();

        $cohort1 = $generator->create_cohort();
        $cohort2 = $generator->create_cohort();
        $cohort3 = $generator->create_cohort();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();

        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort1->id, $user3->id);
        cohort_add_member($cohort2->id, $user4->id);
        cohort_add_member($cohort2->id, $user5->id);

        $cohort = new cohort($cohort1->id);
        $this->assertEqualsCanonicalizing([$user1->id, $user2->id, $user3->id], $cohort->expand());
        $cohort = new cohort($cohort2->id);
        $this->assertEqualsCanonicalizing([$user4->id, $user5->id], $cohort->expand());
        $cohort = new cohort($cohort3->id);
        $this->assertEquals([], $cohort->expand());
    }

    public function test_position_expansion() {
        $generator = $this->getDataGenerator();
        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');

        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Framework 1']);
        $pos1 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);
        $pos2 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 2']);
        $pos3 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 3']);

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();

        job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => 'dev1',
            'positionid' => $pos1->id
        ]);
        job_assignment::create([
            'userid' => $user2->id,
            'idnumber' => 'dev2',
            'positionid' => $pos1->id
        ]);
        job_assignment::create([
            'userid' => $user3->id,
            'idnumber' => 'dev3',
            'positionid' => $pos1->id
        ]);
        job_assignment::create([
            'userid' => $user4->id,
            'idnumber' => 'dev4',
            'positionid' => $pos2->id
        ]);
        job_assignment::create([
            'userid' => $user5->id,
            'idnumber' => 'dev5',
            'positionid' => $pos2->id
        ]);

        $position = new position($pos1->id);
        $this->assertEqualsCanonicalizing([$user1->id, $user2->id, $user3->id], $position->expand());
        $position = new position($pos2->id);
        $this->assertEqualsCanonicalizing([$user4->id, $user5->id], $position->expand());
        $position = new position($pos3->id);
        $this->assertEqualsCanonicalizing([], $position->expand());
    }

    public function test_organisation_expansion() {
        $generator = $this->getDataGenerator();
        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');

        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Framework 1']);
        $org1 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);
        $org2 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 2']);
        $org3 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 3']);

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();

        job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => 'dev1',
            'organisationid' => $org1->id
        ]);
        job_assignment::create([
            'userid' => $user2->id,
            'idnumber' => 'dev2',
            'organisationid' => $org1->id
        ]);
        job_assignment::create([
            'userid' => $user3->id,
            'idnumber' => 'dev3',
            'organisationid' => $org1->id
        ]);
        job_assignment::create([
            'userid' => $user4->id,
            'idnumber' => 'dev4',
            'organisationid' => $org2->id
        ]);
        job_assignment::create([
            'userid' => $user5->id,
            'idnumber' => 'dev5',
            'organisationid' => $org2->id
        ]);

        $organisation = new organisation($org1->id);
        $this->assertEqualsCanonicalizing([$user1->id, $user2->id, $user3->id], $organisation->expand());
        $organisation = new organisation($org2->id);
        $this->assertEqualsCanonicalizing([$user4->id, $user5->id], $organisation->expand());
        $organisation = new organisation($org3->id);
        $this->assertEqualsCanonicalizing([], $organisation->expand());
    }

}
