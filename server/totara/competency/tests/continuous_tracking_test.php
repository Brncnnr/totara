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

use totara_competency\admin_setting_continuous_tracking;
use totara_competency\entity;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\expand_task;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * @group totara_competency
 */
class totara_competency_continuous_tracking_testcase extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_continuous_tracking_disabled() {
        set_config('continuous_tracking', admin_setting_continuous_tracking::DISABLED, 'totara_competency');

        [
            'pos' => $pos,
            'org' => $org,
            'ass' => $ass
        ] = $this->generate_data();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $job_data = [
            'userid' => $user1->id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'positionid' => $pos[0]->id
        ];
        $job1 = job_assignment::create($job_data);

        $job_data = [
            'userid' => $user2->id,
            'idnumber' => 'dev2',
            'fullname' => 'Developer',
            'organisationid' => $org[0]->id
        ];
        $job2 = job_assignment::create($job_data);

        $this->expand();

        $this->assertEquals(5, competency_assignment_user::repository()->count());

        job_assignment::delete($job1);
        job_assignment::delete($job2);

        $this->expand();

        $this->assertEquals(3, competency_assignment_user::repository()->count());
    }

    public function test_continuous_tracking_enabled() {
        set_config('continuous_tracking', admin_setting_continuous_tracking::ENABLED, 'totara_competency');

        [
            'pos' => $pos,
            'org' => $org,
            'ass' => $ass
        ] = $this->generate_data();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $job_data = [
            'userid' => $user1->id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'positionid' => $pos[0]->id
        ];
        $job1 = job_assignment::create($job_data);

        $job_data = [
            'userid' => $user2->id,
            'idnumber' => 'dev2',
            'fullname' => 'Developer',
            'organisationid' => $org[0]->id
        ];
        $job2 = job_assignment::create($job_data);

        $this->expand();

        $this->assertEquals(5, competency_assignment_user::repository()->count());

        job_assignment::delete($job1);
        job_assignment::delete($job2);

        $this->expand();

        $this->assertEquals(5, competency_assignment_user::repository()->count());
        $this->assertEquals(2, competency_assignment_user::repository()
            ->join(assignment::TABLE, 'assignment_id', 'id')
            ->where(assignment::TABLE.'.type', assignment::TYPE_SYSTEM)
            ->where('user_id', [$user1->id, $user2->id])
            ->count()
        );
    }

    /**
     * Create a few competencies with knows names to test search
     */
    protected function generate_data() {
        $data = [
            'comps' => [],
            'fws' => [],
            'ass' => [],
            'types' => [],
            'pos' => [],
            'org' => []
        ];

        $hierarchy_generator = $this->generator()->hierarchy_generator();

        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Framework 2']);
        $data['pos'][] = $pos1 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);

        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Framework 3']);
        $data['org'][] = $org1 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);

        $data['fws'][] = $fw = $hierarchy_generator->create_comp_frame([]);
        $data['fws'][] = $fw2 = $hierarchy_generator->create_comp_frame([]);

        $data['types'][] = $type1 = $hierarchy_generator->create_comp_type(['idnumber' => 'type1']);
        $data['types'][] = $type2 = $hierarchy_generator->create_comp_type(['idnumber' => 'type2']);

        $data['comps'][] = $one = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'acc',
            'fullname' => 'Accounting',
            'description' => 'Counting profits',
            'idnumber' => 'accc',
            'typeid' => $type1,
        ]);

        $data['comps'][] = $two = $this->generator()->create_competency(null, $fw2->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
            'typeid' => $type1,
        ]);

        $data['comps'][] = $three = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'des',
            'fullname' => 'Designing interiors',
            'description' => 'Decorating things',
            'idnumber' => 'des',
            'parentid' => $one->id,
            'typeid' => $type2,
        ]);

        // Create an assignment for a competency
        $gen = $this->generator()->assignment_generator();
        $data['ass'][] = $gen->create_user_assignment($one->id, null, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]);
        $data['ass'][] = $gen->create_user_assignment($two->id, null, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_SELF]);
        $data['ass'][] = $gen->create_user_assignment($three->id, null, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_SYSTEM]);
        $data['ass'][] = $gen->create_position_assignment($three->id, $pos1->id, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]);
        $data['ass'][] = $gen->create_organisation_assignment($three->id, $org1->id, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]);

        return $data;
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return \totara_competency\testing\generator::instance();
    }

    private function expand() {
        // We need the expanded users for the logging to work
        $expand_task = new expand_task($GLOBALS['DB']);
        $expand_task->expand_all();
    }
}