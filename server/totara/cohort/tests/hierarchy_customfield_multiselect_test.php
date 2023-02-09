<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->dirroot . '/totara/cohort/rules/lib.php'); // For some constants.

/**
 * Test audience rules based on position and organisation multi-select custom fields.
 */
class totara_cohort_hierarchy_customfield_multiselect_testcase extends advanced_testcase {

    /**
     * Set up hierarchies and user data such that:
     *
     * User0
     *  Job1 -> Pos/Org 0 (no customfields)
     * User1
     *  Job1 -> Pos/Org 1 (cf1)
     * User2
     *  Job1 -> Pos/Org 2 (cf2)
     * User3
     *  Job1 -> Pos/Org 3 (cf1 & cf2)
     * User4
     *  Job1 -> Pos/Org 1 (cf1)
     *  Job2 -> Pos/Org 2 (cf2)
     * User5
     *  None -> No Job assignment
     */
    protected function setup_hierarchies() {
        global $DB;

        $this->setAdminUser();

        $basic_generator = $this->getDataGenerator();
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');;

        // Create a multi-select customfield with three options'
        $cf_options = [
            0 => [
                'option' => 'Selection 0',
                'icon' => "",
                'default' => "0",
                'delete' => 0
            ],
            1 => [
                'option' => 'Selection 1',
                'icon' => "",
                'default' => "0",
                'delete' => 0
            ],
            2 => [
                'option' => 'Selection 2',
                'icon' => "",
                'default' => "0",
                'delete' => 0
            ]
        ];

        // Create pos type.
        $ptype1 = $hierarchy_generator->create_hierarchy_type('position', ['fullname' => 'ptype1', 'idnumber' => 'ptype1']);

        $hierarchy_generator->create_hierarchy_type_multiselect(
            [
                'hierarchy' => 'position',
                'typeidnumber' => 'ptype1',
                'value' => ''
            ],
            $cf_options
        );
        $pcf_ms = $DB->get_record('pos_type_info_field', ['datatype' => 'multiselect', 'typeid' => $ptype1]);

        // Make sure we have a framework to put the positions in.
        $pframe = $hierarchy_generator->create_pos_frame(
            [
                'fullname' => 'position framework',
                'idnumber' => 'pframe'
            ]
        );

        // Create positions.
        $positions = [];
        for ($pid = 0; $pid < 4; $pid++) {

            $position = $hierarchy_generator->create_pos(
                [
                    'frameworkid' => $pframe->id,
                    'typeid' => $ptype1,
                    'fullname' => 'position' . $pid,
                    'idnumber' => 'pos' . $pid
                ]
            );
            $positions[] = $position;
        }

        // Position 0 - no selections for custom field
        // Position 1 - Selection 1
        $p1selections = [];
        $hash = md5($cf_options[1]['option']);
        $p1selections[$hash] = $cf_options[1];
        $hierarchy_generator->set_pos_type_multiselect_data($positions[1]->id, $pcf_ms->id, $p1selections);

        // Position 2 - Selection 2
        $p2selections = [];
        $hash = md5($cf_options[2]['option']);
        $p2selections[$hash] = $cf_options[2];
        $hierarchy_generator->set_pos_type_multiselect_data($positions[2]->id, $pcf_ms->id, $p2selections);

        // Position 3 - Selection 1&2
        $p3selections = [];
        $hash = md5($cf_options[1]['option']);
        $p3selections[$hash] = $cf_options[1];
        $hash = md5($cf_options[2]['option']);
        $p3selections[$hash] = $cf_options[2];
        $hierarchy_generator->set_pos_type_multiselect_data($positions[3]->id, $pcf_ms->id, $p3selections);


        // Create org type.
        $otype1 = $hierarchy_generator->create_hierarchy_type('organisation', ['fullname' => 'otype1', 'idnumber' => 'otype1']);

        // Create org type fields (checkbox, menu, text input).
        $ocf_ms = $hierarchy_generator->create_hierarchy_type_multiselect(
            [
                'hierarchy' => 'organisation',
                'typeidnumber' => 'otype1',
                'value' => ''
            ],
            $cf_options
        );
        $ocf_ms = $DB->get_record('org_type_info_field', ['datatype' => 'multiselect', 'typeid' => $otype1]);

        // Make sure we have a framework to put the organisations in.
        $oframe = $hierarchy_generator->create_org_frame(
            [
                'fullname' => 'organisation framework',
                'idnumber' => 'oframe'
            ]
        );

        // Create organisations
        $organisations = [];
        for ($oid = 0; $oid < 4; $oid++) {

            $organisation = $hierarchy_generator->create_org(
                [
                    'frameworkid' => $oframe->id,
                    'typeid' => $otype1,
                    'fullname' => 'organisation' . $oid,
                    'idnumber' => 'org' . $oid
                ]
            );
            $organisations[] = $organisation;
        }

        // organisation 0 - no selections for custom field
        // organisation 1 - Selection 1
        $o1selections = [];
        $hash = md5($cf_options[1]['option']);
        $o1selections[$hash] = $cf_options[1];
        $hierarchy_generator->set_org_type_multiselect_data($organisations[1]->id, $ocf_ms->id, $o1selections);

        // organisation 2 - Selection 2
        $o2selections = [];
        $hash = md5($cf_options[2]['option']);
        $o2selections[$hash] = $cf_options[2];
        $hierarchy_generator->set_org_type_multiselect_data($organisations[2]->id, $ocf_ms->id, $o2selections);

        // organisation 3 - Selection 1&2
        $o3selections = [];
        $hash = md5($cf_options[1]['option']);
        $o3selections[$hash] = $cf_options[1];
        $hash = md5($cf_options[2]['option']);
        $o3selections[$hash] = $cf_options[2];
        $hierarchy_generator->set_org_type_multiselect_data($organisations[3]->id, $ocf_ms->id, $o3selections);

        // Refresh rule list cache.
        cohort_rules_list(true);

        // Create users.
        $users = [];
        $numusers = 6;
        for ($uid = 0; $uid < $numusers; $uid++) {
            $record = [
                "username" => "TestUser{$uid}",
                "firstname" => "TUser",
                "lastname" => "{$uid}",
                "idnumber" => "TU{$uid}",
                "password" => $uid
            ];

            $users[] = $basic_generator->create_user($record);
        }

        // Create a map for users job assignments.
        $jobassignments = [
            0 => [0],
            1 => [1],
            2 => [2],
            3 => [3],
            4 => [1,2],
        ];

        // Create job assignments for the users.
        foreach ($jobassignments as $uid => $ja) {
            foreach ($ja as $jaid) {
                $data = [
                    'userid' => $users[$uid]->id,
                    'fullname' => "job_{$jaid}",
                    'shortname' => "j{$jaid}",
                    'idnumber' => "{$uid}_{$jaid}",
                    'positionid' => $positions[$jaid]->id,
                    'organisationid' => $organisations[$jaid]->id
                ];
                \totara_job\job_assignment::create($data);
            }
        }

        return [$cf_options, $pcf_ms, $ocf_ms, $users, $positions, $organisations];
    }

    /**
     * Test position customfield IS ALL rule.
     *
     * Expected Users:
     * CF0              - No-one
     * CF1              - U1, U3, U4
     * CF2              - U2, U3, U4
     * CF1 & CF2        - U3
     * CF0 & CF1 & CF2  - No-one
     */
    public function test_position_multiselect_rule_is_all() {
        global $DB;


        // Set up the expected data.
        list($cf_options, $pcf_ms, $ocf_ms, $users) = $this->setup_hierarchies();
        $cohort_generator = \totara_cohort\testing\generator::instance();

        // Create a rule to test cf_option 0.
        $c1 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c1->draftcollectionid);

        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[0]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c1);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c1->id]);
        $this->assertCount(0, $members);

        // Create a rule to test cf_option 1.
        $c2 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c2->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[1]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c2);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c2->id]);
        $this->assertCount(3, $members);

        $expected = [$users[1]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 2.
        $c3 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c3->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c3);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c3->id]);
        $this->assertCount(3, $members);

        $expected = [$users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1 & 2.
        $c4 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c4->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c4);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c4->id]);
        $this->assertCount(1, $members);

        $expected = [$users[3]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 0 & 1 & 2.
        $c5 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c5->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[0]['option']),md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c5);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c5->id]);
        $this->assertCount(0, $members);
    }

    /**
     * Test position customfield IS ANY rule.
     *
     * Expected Users:
     *   CF0             - No-one
     *   CF1             - U1, U3, U4
     *   CF2             - U2, U3, U4
     *   CF1 & CF2       - U1, U2, U3, U4
     *   CF0 & CF1 & CF2 - U1, U2, U3, U4
     */
    public function test_position_multiselect_rule_is_any() {
        global $DB;

        // Set up the expected data.
        list($cf_options, $pcf_ms, $ocf_ms, $users) = $this->setup_hierarchies();
        $cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create a rule to test cf_option 0.
        $c1 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c1->draftcollectionid);

        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[0]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c1);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c1->id]);
        $this->assertCount(0, $members);

        // Create a rule to test cf_option 1.
        $c2 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c2->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[1]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c2);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c2->id]);
        $this->assertCount(3, $members);

        $expected = [$users[1]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 2.
        $c3 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c3->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c3);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c3->id]);
        $this->assertCount(3, $members);

        $expected = [$users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1 & 2.
        $c4 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c4->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c4);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c4->id]);
        $this->assertCount(4, $members);

        $expected = [$users[1]->id, $users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 0 & 1 & 2.
        $c5 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c5->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[0]['option']),md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c5);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c5->id]);
        $this->assertCount(4, $members);

        $expected = [$users[1]->id, $users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

    }

    /**
     * Test position customfield NOT ALL rule.
     *
     * Expected Users:
     *   CF0             - U1, U2, U3, U3, U4
     *   CF1             - U0, U2, U4
     *   CF2             - U0, U1, U4
     *   CF1 & CF2       - U0, U1, U2, U4
     *   CF0 & CF1 & CF2 - U0, U1, U2, U4
     */
    public function test_position_multiselect_rule_not_all() {
        global $DB;

        // Set up the expected data.
        list($cf_options, $pcf_ms, $ocf_ms, $users) = $this->setup_hierarchies();
        $cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create a rule to test cf_option 0.
        $c1 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c1->draftcollectionid);

        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[0]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c1);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c1->id]);
        $this->assertCount(5, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1.
        $c2 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c2->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[1]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c2);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c2->id]);
        $this->assertCount(3, $members);

        $expected = [$users[0]->id, $users[2]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 2.
        $c3 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c3->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c3);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c3->id]);
        $this->assertCount(3, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1 & 2.
        $c4 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c4->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c4);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c4->id]);
        $this->assertCount(4, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[2]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 0 & 1 & 2.
        $c5 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c5->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[0]['option']),md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c5);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c5->id]);
        $this->assertCount(5, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }
    }

    /**
     * Test position customfield NOT ANY rule.
     *
     * Expected Users:
     *   CF0             - U1, U2, U3, U3, U4
     *   CF1             - U0, U2, U4
     *   CF2             - U0, U1, U4
     *   CF1 & CF2       - U0
     *   CF0 & CF1 & CF2 - U0
     */
    public function test_position_multiselect_rule_not_any() {
        global $DB;

        // Set up the expected data.
        list($cf_options, $pcf_ms, $ocf_ms, $users) = $this->setup_hierarchies();
        $cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create a rule to test cf_option 0.
        $c1 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c1->draftcollectionid);

        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[0]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c1);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c1->id]);
        $this->assertCount(5, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1.
        $c2 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c2->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[1]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c2);


        $members = $DB->get_records('cohort_members', ['cohortid' => $c2->id]);
        $this->assertCount(3, $members);

        $expected = [$users[0]->id, $users[2]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 2.
        $c3 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c3->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
            'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
            'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c3);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c3->id]);
        $this->assertCount(3, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1 & 2.
        $c4 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c4->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c4);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c4->id]);
        $this->assertCount(1, $members);

        $expected = [$users[0]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 0 & 1 & 2.
        $c5 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c5->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "poscustomfield{$pcf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[0]['option']),md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c5);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c4->id]);
        $this->assertCount(1, $members);

        $expected = [$users[0]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }
    }

    /**
     * Test organisation customfield IS ALL rule.
     *
     * Expected Users:
     * CF0              - No-one
     * CF1              - U1, U3, U4
     * CF2              - U2, U3, U4
     * CF1 & CF2        - U3
     * CF0 & CF1 & CF2  - No-one
     */
    public function test_organisation_multiselect_rule_is_all() {
        global $DB;

        // Set up the expected data.
        list($cf_options, $pcf_ms, $ocf_ms, $users) = $this->setup_hierarchies();
        $cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create a rule to test cf_option 0.
        $c1 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c1->draftcollectionid);

        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[0]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c1);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c1->id]);
        $this->assertCount(0, $members);

        // Create a rule to test cf_option 1.
        $c2 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c2->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[1]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c2);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c2->id]);
        $this->assertCount(3, $members);

        $expected = [$users[1]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 2.
        $c3 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c3->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c3);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c3->id]);
        $this->assertCount(3, $members);

        $expected = [$users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1 & 2.
        $c4 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c4->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c4);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c4->id]);
        $this->assertCount(1, $members);

        $expected = [$users[3]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 0 & 1 & 2.
        $c5 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c5->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[0]['option']),md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c5);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c5->id]);
        $this->assertCount(0, $members);
    }

    /**
     * Test organisation customfield IS ANY rule.
     *
     * Expected Users:
     *   CF0             - No-one
     *   CF1             - U1, U3, U4
     *   CF2             - U2, U3, U4
     *   CF1 & CF2       - U1, U2, U3, U4
     *   CF0 & CF1 & CF2 - U1, U2, U3, U4
     */
    public function test_organisation_multiselect_rule_is_any() {
        global $DB;

        // Set up the expected data.
        list($cf_options, $pcf_ms, $ocf_ms, $users) = $this->setup_hierarchies();
        $cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create a rule to test cf_option 0.
        $c1 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c1->draftcollectionid);

        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[0]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c1);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c1->id]);
        $this->assertCount(0, $members);

        // Create a rule to test cf_option 1.
        $c2 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c2->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[1]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c2);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c2->id]);
        $this->assertCount(3, $members);

        $expected = [$users[1]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 2.
        $c3 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c3->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c3);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c3->id]);
        $this->assertCount(3, $members);

        $expected = [$users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1 & 2.
        $c4 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c4->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c4);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c4->id]);
        $this->assertCount(4, $members);

        $expected = [$users[1]->id, $users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 0 & 1 & 2.
        $c5 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c5->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_ISEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[0]['option']),md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c5);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c5->id]);
        $this->assertCount(4, $members);

        $expected = [$users[1]->id, $users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }
    }

    /**
     * Test organisation customfield NOT ALL rule.
     *
     * Expected Users:
     *   CF0             - U1, U2, U3, U3, U4
     *   CF1             - U0, U2, U4
     *   CF2             - U0, U1, U4
     *   CF1 & CF2       - U0, U1, U2, U4
     *   CF0 & CF1 & CF2 - U0, U1, U2, U4
     */
    public function test_organisation_multiselect_rule_not_all() {
        global $DB;

        // Set up the expected data.
        list($cf_options, $pcf_ms, $ocf_ms, $users) = $this->setup_hierarchies();
        $cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create a rule to test cf_option 0.
        $c1 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c1->draftcollectionid);

        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[0]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c1);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c1->id]);
        $this->assertCount(5, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1.
        $c2 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c2->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[1]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c2);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c2->id]);
        $this->assertCount(3, $members);

        $expected = [$users[0]->id, $users[2]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 2.
        $c3 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c3->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c3);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c3->id]);
        $this->assertCount(3, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1 & 2.
        $c4 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c4->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c4);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c4->id]);
        $this->assertCount(4, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[2]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 0 & 1 & 2.
        $c5 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c5->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ALL
            ],
            [md5($cf_options[0]['option']),md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c5);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c5->id]);
        $this->assertCount(5, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }
    }

    /**
     * Test organisation customfield NOT ANY rule.
     *
     * Expected Users:
     *   CF0             - U1, U2, U3, U3, U4
     *   CF1             - U0, U2, U4
     *   CF2             - U0, U1, U4
     *   CF1 & CF2       - U0
     *   CF0 & CF1 & CF2 - U0
     */
    public function test_organisation_multiselect_rule_not_any() {
        global $DB;

        // Set up the expected data.
        list($cf_options, $pcf_ms, $ocf_ms, $users) = $this->setup_hierarchies();
        $cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create a rule to test cf_option 0.
        $c1 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c1->draftcollectionid);

        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[0]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c1);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c1->id]);
        $this->assertCount(5, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[2]->id, $users[3]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1.
        $c2 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c2->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[1]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c2);


        $members = $DB->get_records('cohort_members', ['cohortid' => $c2->id]);
        $this->assertCount(3, $members);

        $expected = [$users[0]->id, $users[2]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 2.
        $c3 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c3->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c3);

        $members = $DB->get_records('cohort_members', ['cohortid' => $c3->id]);
        $this->assertCount(3, $members);

        $expected = [$users[0]->id, $users[1]->id, $users[4]->id];
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 1 & 2.
        $c4 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c4->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c4);

        $expected = [$users[0]->id];
        $members = $DB->get_records('cohort_members', ['cohortid' => $c4->id]);
        $this->assertCount(1, $members);

        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }

        // Create a rule to test cf_option 0 & 1 & 2.
        $c5 = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $ruleset = cohort_rule_create_ruleset($c5->draftcollectionid);
        $cohort_generator->create_cohort_rule_params(
            $ruleset,
            'alljobassign',
            "orgcustomfield{$ocf_ms->id}",
            [
                'equal' => COHORT_RULES_OP_IN_NOTEQUALTO,
                'exact' => COHORT_RULES_OP_IN_ANY
            ],
            [md5($cf_options[0]['option']),md5($cf_options[1]['option']),md5($cf_options[2]['option'])],
            'listofvalues'
        );
        cohort_rules_approve_changes($c5);

        $expected = [$users[0]->id];
        $members = $DB->get_records('cohort_members', ['cohortid' => $c4->id]);
        $this->assertCount(1, $members);

        foreach ($members as $member) {
            $this->assertTrue(in_array($member->userid, $expected), "{$member->userid} not in expected list");
        }
    }
}
