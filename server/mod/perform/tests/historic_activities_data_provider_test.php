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

use mod_perform\data_providers\activity\historic_activities;
use totara_core\advanced_feature;

global $CFG;
require_once($CFG->dirroot.'/totara/appraisal/lib.php');
require_once($CFG->dirroot.'/totara/feedback360/lib.php');

class historic_activities_data_provider_testcase extends advanced_testcase {

    public function test_get_appraisals() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        // 1. assert return empty value without appraisals feature enabled
        $appraisals = historic_activities::get_appraisals($user1->id);
        $this->assertCount(0, $appraisals);

        // 2. assert return empty value with correct feature, but not allowed to see own appraisals
        advanced_feature::enable('appraisals');
        $result = historic_activities::get_appraisals($user1->id);
        $this->assertCount(0, $result);

        // 3. assert return correct content and data structure
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
                                [
                                    'name' => 'Text',
                                    'type' => 'text',
                                    'roles' => [appraisal::ROLE_LEARNER => 7],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $appraisal1 = appraisal::build($def);

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        // Add cohort to appraisal.
        $urlparams = [
            'includechildren' => false,
            'listofvalues' => [$cohort->id]
        ];
        $assign = new totara_assign_appraisal('appraisal', $appraisal1);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal1->activate();

        $this->setUser($user2);
        $appraisals = historic_activities::get_appraisals($user2->id);

        $this->assertCount(1, $appraisals);
        $this->assertEquals("Appraisal", $appraisals[0]['activity_name']);

        $appraisal_link = new moodle_url('/totara/appraisal/myappraisal.php', $this->create_param($appraisal1, $user2->id));
        $this->assertEquals($appraisal_link->out(false), $appraisals[0]['activity_link']);

        $this->assertEquals(get_string('appraisal_legacy', 'totara_appraisal'), $appraisals[0]['type']);
        $this->assertEquals(appraisal::display_status($appraisal1->status), $appraisals[0]['status']);

        // 4. assert return empty value without logging in
        $this->setUser(null);
        $appraisals = historic_activities::get_appraisals($user2->id);
        $this->assertCount(0, $appraisals);
    }

    public function test_get_feedbacks() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $sysctx = context_system::instance();
        $user_role_id = $DB->get_record('role', ['shortname' => 'user'])->id;
        assign_capability('totara/feedback360:viewownreceivedfeedback360', CAP_ALLOW, $user_role_id, $sysctx);

        // 1. assert return empty value without feedback360 feature enabled
        $feedbacks = historic_activities::get_feedbacks($user->id);
        $this->assertCount(0, $feedbacks);

        // 2. assert return empty value with correct feature, but not allowed to see own feedbacks
        advanced_feature::enable('feedback360');
        $feedbacks = historic_activities::get_feedbacks($user->id);
        $this->assertCount(0, $feedbacks);

        // 3. assert return correct content and structure
        $feedback360 = new feedback360();
        $feedback360->name = 'Feedback';
        $feedback360->description = 'Description';
        $feedback360->anonymous = false;
        $feedback360->selfevaluation = feedback360::SELF_EVALUATION_OPTIONAL;
        $feedback360->save();

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user->id);

        $listofvalues = [$cohort->id];
        $urlparams = [
            'module' => 'feedback360',
            'grouptype' => 'cohort',
            'itemid' => $feedback360->id,
            'add' => true,
            'includechildren' => false,
            'listofvalues' => $listofvalues
        ];
        $assign = new totara_assign_feedback360('feedback360', $feedback360);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->validate_item_selector(implode(',', $listofvalues));
        $grouptypeobj->handle_item_selector($urlparams);

        $feedback360->activate();

        $feedbacks = historic_activities::get_feedbacks($user->id);

        $this->assertEquals('Feedback', $feedbacks[0]['activity_name']);

        $feedback_link = new moodle_url('/totara/feedback360/index.php');
        $this->assertEquals($feedback_link->out(false), $feedbacks[0]['activity_link']);

        $this->assertEquals(get_string('feedback360:utf8', 'totara_feedback360'), $feedbacks[0]['type']);
        $this->assertEquals(feedback360::display_status($feedback360->status), $feedbacks[0]['status']);

        // 4. assert return empty value without logging in
        $this->setUser(null);
        $feedbacks = historic_activities::get_feedbacks($user->id);
        $this->assertCount(0, $feedbacks);

        // 5. assert return empty value without correct capability
        $this->setUser($user);
        unassign_capability('totara/feedback360:viewownreceivedfeedback360', $user_role_id);
        unassign_capability('totara/feedback360:viewownrequestedfeedback360', $user_role_id);
        unassign_capability('totara/feedback360:viewstaffreceivedfeedback360', $user_role_id);

        $feedbacks = historic_activities::get_feedbacks($user->id);
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
            'role' => appraisal::ROLE_LEARNER,
            'subjectid' => $user_id,
            'appraisalid' => $appraisal->id,
            'action' => 'stages'
        ];
    }

}