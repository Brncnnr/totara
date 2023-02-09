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
use totara_webapi\phpunit\webapi_phpunit_helper;

global $CFG;
require_once($CFG->dirroot.'/totara/appraisal/lib.php');
require_once($CFG->dirroot.'/totara/feedback360/lib.php');

/**
 * @group perform
 */
class webapi_resolver_query_historic_activities_testcase extends advanced_testcase {

    private const QUERY = "mod_perform_historic_activities";

    use webapi_phpunit_helper;

    public function test_query_successful() {
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
    }

    public function test_query_without_feature_should_failed() {
        advanced_feature::disable('performance_activities');
        $this->expectExceptionMessage('Feature performance_activities is not available.');
        $this->resolve_graphql_query(self::QUERY);
    }

    private function create_data() {
        advanced_feature::enable('appraisals');
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // prepare appraisal
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
                                ['name' => 'Text',
                                 'type' => 'text',
                                 'roles' => [appraisal::ROLE_LEARNER => 7]],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $appraisal1 = appraisal::build($def);

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user->id);

        // Add cohort to appraisal.
        $urlparams = ['includechildren' => false, 'listofvalues' => [$cohort->id]];
        $assign = new totara_assign_appraisal('appraisal', $appraisal1);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal1->activate();

        return [$appraisal1, $user];
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
            'role' => appraisal::ROLE_LEARNER,
            'subjectid' => $user_id,
            'appraisalid' => $appraisal->id,
            'action' => 'stages'
        ];
    }
}