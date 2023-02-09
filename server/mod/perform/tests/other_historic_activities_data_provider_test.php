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

use mod_perform\data_providers\activity\other_historic_activities;
use totara_core\advanced_feature;
use totara_job\job_assignment;

global $CFG;
require_once($CFG->dirroot.'/totara/appraisal/lib.php');
require_once($CFG->dirroot.'/totara/feedback360/lib.php');
require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

class other_historic_activities_data_provider_testcase extends appraisal_testcase {

    public function test_get_appraisals() {
        // Create users.
        $teamlead = $this->getDataGenerator()->create_user();
        $manager1 = $this->getDataGenerator()->create_user();
        $manager2 = $this->getDataGenerator()->create_user();
        $appraiser = $this->getDataGenerator()->create_user();
        $user = $this->getDataGenerator()->create_user();
        // Set up appraisal.
        $roles = [];
        $roles[appraisal::ROLE_LEARNER] = 6;
        $roles[appraisal::ROLE_MANAGER] = 6;
        $roles[appraisal::ROLE_TEAM_LEAD] = 6;
        $roles[appraisal::ROLE_APPRAISER] = 6;

        $this->setUser($manager1);

        // 1. assert return empty value without appraisals feature enabled
        $appraisals = other_historic_activities::get_appraisals($manager1->id);
        $this->assertCount(0, $appraisals);

        // 2. assert return empty value with correct feature, but not allowed to see other users appraisals
        advanced_feature::enable('appraisals');
        $result = other_historic_activities::get_appraisals($manager1->id);
        $this->assertCount(0, $result);

        // 3. assert return correct data structure and content
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

        // create job assignments
        $teamleadja = job_assignment::create_default($teamlead->id);
        $managerja = job_assignment::create_default(
            $manager2->id,
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
        $urlparams = ['includechildren' => false, 'listofvalues' => [$cohort->id]];
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $this->setUser($manager2);

        $appraisals = other_historic_activities::get_appraisals($manager2->id);

        $this->assertEquals("Appraisal", $appraisals[0]['activity_name']);

        $appraisal_link = new moodle_url('/totara/appraisal/myappraisal.php', $this->create_param($appraisal, $user->id));
        $this->assertEquals($appraisal_link->out(false), $appraisals[0]['activity_link']);

        $this->assertEquals(get_string('appraisal_legacy', 'totara_appraisal'), $appraisals[0]['type']);

        $this->assertEquals(appraisal::display_status($appraisal->status), $appraisals[0]['status']);

        $this->assertEquals($user->firstname . ' ' . $user->lastname, $appraisals[0]['subject_user']);

        $this->assertEquals('Manager', $appraisals[0]['relationship_to']);

        // 4. assert return empty value without logging in
        $this->setUser(null);
        $appraisals = other_historic_activities::get_appraisals($manager2->id);
        $this->assertCount(0, $appraisals);
    }

    public function test_get_feedbacks() {
        global $DB;

        // Create users.
        $teamlead = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $user = $this->getDataGenerator()->create_user();
        $unrelated_user = $this->getDataGenerator()->create_user();

        $this->setUser($manager);

        $sysctx = context_system::instance();
        $user_role_id = $DB->get_record('role', ['shortname' => 'user'])->id;
        assign_capability('totara/feedback360:viewownreceivedfeedback360', CAP_ALLOW, $user_role_id, $sysctx);

        // 1. assert return empty value without feedback360 feature enabled
        $feedbacks = other_historic_activities::get_feedbacks($manager->id);
        $this->assertCount(0, $feedbacks);

        // 2. assert return empty value with correct feature, but not allowed to see other users feedbacks
        advanced_feature::enable('feedback360');
        $feedbacks = other_historic_activities::get_feedbacks($manager->id);
        $this->assertCount(0, $feedbacks);

        // 3. assert return correct data structure and content
        // setup role
        $roles = [];
        $roles[appraisal::ROLE_LEARNER] = 6;
        $roles[appraisal::ROLE_MANAGER] = 6;
        $roles[appraisal::ROLE_TEAM_LEAD] = 6;
        $roles[appraisal::ROLE_APPRAISER] = 6;
        // setup feedback
        $feedback360 = new feedback360();
        $feedback360->name = 'Feedback';
        $feedback360->description = 'Description';
        $feedback360->anonymous = false;
        $feedback360->selfevaluation = feedback360::SELF_EVALUATION_OPTIONAL;
        $feedback360->save();

        // create job assignments
        $teamleadja = job_assignment::create_default($teamlead->id);
        $managerja = job_assignment::create_default(
            $manager->id,
            [
                'managerjaid' => $teamleadja->id,
            ]
        );

        // Create group and assign users.
        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user->id);

        $listofvalues = [$cohort->id];
        $urlparams = [
            'module'          => 'feedback360',
            'grouptype'       => 'cohort',
            'itemid'          => $feedback360->id,
            'add'             => true,
            'includechildren' => false,
            'listofvalues'    => $listofvalues,
        ];
        $assign = new totara_assign_feedback360('feedback360', $feedback360);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->validate_item_selector(implode(',', $listofvalues));
        $grouptypeobj->handle_item_selector($urlparams);

        $feedback360->activate();

        $this->create_assignment_data($manager->id, $feedback360->id, $user->id);

        $feedbacks = other_historic_activities::get_feedbacks($manager->id);

        $this->assertEquals('Feedback', $feedbacks[0]['activity_name']);

        $feedback_link = new moodle_url('/totara/feedback360/index.php');
        $this->assertEquals($feedback_link->out(false), $feedbacks[0]['activity_link']);

        $this->assertEquals(get_string('feedback360:utf8', 'totara_feedback360'), $feedbacks[0]['type']);
        $this->assertEquals("Completed", $feedbacks[0]['status']);

        $this->assertEquals($user->firstname . ' ' . $user->lastname, $feedbacks[0]['subject_user']);

        $this->assertEquals('Manager', $feedbacks[0]['relationship_to']);

        // 4. assert return empty value without logging in
        $this->setUser(null);
        $feedbacks = other_historic_activities::get_feedbacks($manager->id);
        $this->assertCount(0, $feedbacks);

        // 5. assert return empty value without correct capability
        $this->setUser($unrelated_user);

        $feedbacks = other_historic_activities::get_feedbacks($unrelated_user->id);
        $this->assertCount(0, $feedbacks);
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

    /**
     * Create user assignment and response assginment data
     *
     * @param $manager_id
     * @param $feedback_id
     * @param $user_id
     * @throws dml_exception
     */
    private function create_assignment_data($manager_id, $feedback_id, $user_id) {
        global $DB;
        $now = time();

        $user_data = new \stdClass();
        $user_data->feedback360id = $feedback_id;
        $user_data->userid = $user_id;
        $table = "feedback360_user_assignment";
        $id = $DB->insert_record($table, $user_data);

        $resp_data = new \stdClass();
        $resp_data->timeassigned = $now;
        $resp_data->timecompleted = $now;
        $resp_data->feedback360userassignmentid = $id;
        $resp_data->userid = $manager_id;

        $table = "feedback360_resp_assignment";
        $DB->insert_record($table, $resp_data);
    }
}