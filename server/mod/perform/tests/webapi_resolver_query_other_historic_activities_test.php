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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

use totara_core\advanced_feature;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

global $CFG;
require_once($CFG->dirroot.'/totara/appraisal/lib.php');
require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

/**
 * @group perform
 */
class webapi_resolver_query_other_historic_activities_testcase extends appraisal_testcase {

    private const QUERY = "mod_perform_other_historic_activities";

    use webapi_phpunit_helper;

    public function test_other_historic_activities() {
        appraisal::clear_permissions_cache();

        [$appraisal, $user] = $this->create_data();
        advanced_feature::enable('performance_activities');
        $result = $this->resolve_graphql_query(self::QUERY);

        // assert number of item
        $this->assertCount(1, $result);

        // assert content and data structure
        $this->assertEquals("Appraisal", $result->first()['activity_name']);

        $appraisal_link = new moodle_url('/totara/appraisal/myappraisal.php', $this->create_param($appraisal, $user->id));
        $this->assertEquals($appraisal_link->out(false), $result->first()['activity_link']);

        $this->assertEquals(get_string('appraisal_legacy', 'totara_appraisal'), $result->first()['type']);
        $this->assertEquals(appraisal::display_status($appraisal->status), $result->first()['status']);

        $this->assertEquals($user->firstname.' '.$user->lastname , $result->first()['subject_user']);

        $this->assertEquals('Manager', $result->first()['relationship_to']);
    }

    public function test_query_without_feature_should_failed() {
        advanced_feature::disable('performance_activities');
        $this->expectExceptionMessage('Feature performance_activities is not available.');
        $this->resolve_graphql_query(self::QUERY);
    }

    private function create_data() {
        advanced_feature::enable('appraisals');

        $this->setAdminUser();

        // Set up appraisal.
        $roles = [];
        $roles[appraisal::ROLE_LEARNER] = 6;
        $roles[appraisal::ROLE_MANAGER] = 6;
        $roles[appraisal::ROLE_TEAM_LEAD] = 6;
        $roles[appraisal::ROLE_APPRAISER] = 6;

        $def = [
            'name' => 'Appraisal',
            'stages' => [
                [
                    'name' => 'Stage',
                    'timedue' => time() + 86400,
                    'pages' => [
                        [
                            'name' => 'Page',
                            'questions' => [
                                ['name' => 'Text', 'type' => 'text', 'roles' => $roles],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $appraisal = appraisal::build($def);

        // Create users.
        $teamlead = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $appraiser = $this->getDataGenerator()->create_user();
        $user = $this->getDataGenerator()->create_user();

        // create job assignments
        $teamleadja = job_assignment::create_default($teamlead->id);
        $managerja = job_assignment::create_default(
            $manager->id,
            [
                'managerjaid' => $teamleadja->id
            ]
        );
        $userja = job_assignment::create_default(
            $user->id,
            [
                'managerjaid' => $managerja->id,
                'appraiserid' => $appraiser->id
            ]
        );

        // Create group and assign users.
        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user->id);

        // Assign group and activate.
        $urlparams = [
            'includechildren' => false,
            'listofvalues' => [$cohort->id]
        ];
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $this->setUser($manager);

        return [$appraisal, $user];
    }

    /**
     * Create parameter for generating appraisal link
     *
     * @param $appraisal
     * @param $user_id
     * @return array
     */
    private function create_param($appraisal,$user_id) {
        return [
            'role' => appraisal::ROLE_MANAGER,
            'subjectid' => $user_id,
            'appraisalid' => $appraisal->id,
            'action' => 'stages'
        ];
    }
}