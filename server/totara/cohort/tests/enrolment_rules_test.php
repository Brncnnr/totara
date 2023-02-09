<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/cohort/lib.php');


class totara_cohort_enrolment_rules_testcase extends advanced_testcase {

    private $course1 = null;
    private $course2 = null;
    private $users = array();
    private $cohort = null;
    private $program1 = null;
    private $program2 = null;
    private $ruleset = 0;
    private $cohort_generator = null;
    private $program_generator = null;
    const TEST_COURSE_COUNT_USERS = 4;

    protected function tearDown(): void {
        $this->course1 = null;
        $this->course2 = null;
        $this->users = null;
        $this->cohort = null;
        $this->program1 = null;
        $this->program2 = null;
        $this->ruleset = null;
        $this->cohort_generator = null;
        $this->program_generator = null;
        parent::tearDown();
    }

/**

    Test data:
-----------------------------------------------
User #         |    1       2       3       4
-----------------------------------------------
-----------------------------------------------
Course 1  - C1 |    x       x
-----------------------------------------------
Course 2  - C2 |                    x
-----------------------------------------------
Program 1 - P1 |    x
-----------------------------------------------
Program 2 - P2 |    x                       x
-----------------------------------------------


Program 1 - Course 1 only
Program 2 - Course 1 and Course 2


  Condition             Audience Count
-----------------------------------------------
  C1 && C2                      0
-----------------------------------------------
  C1 || C2                      3
-----------------------------------------------
       Test plan pauses here to handle testing of enrolment
       status and enrolment time end values
       before continuing with the below...
-----------------------------------------------
  P1 && P2                      1
-----------------------------------------------
  P1 || P2                      2
-----------------------------------------------
  C2 && P2                      0
-----------------------------------------------
  C2 || P2                      3
-----------------------------------------------
  P1 && C1                      1
-----------------------------------------------
  P1 || C1                      2
-----------------------------------------------

    */

    public function setUp(): void {
        global $DB;

        parent::setup();
        set_config('enablecompletion', 1);
        $this->setAdminUser();

        // Create users.
        for ($i = 0; $i < self::TEST_COURSE_COUNT_USERS; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }
        $this->assertEquals(self::TEST_COURSE_COUNT_USERS, count($this->users));

        // Create courses.
        $setting = array('enablecompletion' => 1, 'completionstartonenrol' => 1);
        $this->assertEquals(1, $DB->count_records('course'));
        $this->course1 = $this->getDataGenerator()->create_course($setting);
        $this->course2 = $this->getDataGenerator()->create_course($setting);
        $this->assertEquals(3, $DB->count_records('course'));

        // Create programs.
        $this->program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $this->assertEquals(0, $DB->count_records('prog'));
        $this->program1 = $this->program_generator->create_program();
        $this->program2 = $this->program_generator->create_program();
        $this->assertEquals(2, $DB->count_records('prog'));

        // Enrol users to courses.
        $this->getDataGenerator()->enrol_user($this->users[0]->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->users[1]->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->users[2]->id, $this->course2->id);

        // Assign courses as content to programs.
        $this->program_generator->add_courses_and_courseset_to_program($this->program1, [[$this->course1]]);
        $this->program_generator->add_courses_and_courseset_to_program($this->program2, [[$this->course1, $this->course2]]);

        // Assign users to programs.
        $this->program_generator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $this->users[0]->id, null, true);
        $this->program_generator->assign_to_program($this->program2->id, ASSIGNTYPE_INDIVIDUAL, $this->users[0]->id, null, true);
        $this->program_generator->assign_to_program($this->program2->id, ASSIGNTYPE_INDIVIDUAL, $this->users[3]->id, null, true);

        // Set totara_cohort generator.
        $this->cohort_generator = \totara_cohort\testing\generator::instance();
    }

    public function test_enrolment_rules() {
        global $DB;
        $this->setAdminUser();

        // C1 AND C2 - should match 0 users.
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
        $this->cohort_generator->create_cohort_rule_params(
            $this->ruleset,
            'learning',
            'courseenrolmentlist',
            array('operator' => COHORT_RULE_ENROLMENT_OP_ALL),
            array($this->course1->id, $this->course2->id),
            'listofids'
        );
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // C1 OR C2 - should match 3 users.
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
        $this->cohort_generator->create_cohort_rule_params(
            $this->ruleset,
            'learning',
            'courseenrolmentlist',
            array('operator' => COHORT_RULE_ENROLMENT_OP_ANY),
            array($this->course1->id, $this->course2->id),
            'listofids'
        );
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(3, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Test the effects of changing a users enrolment status - should now be 2 in cohort, then 3 again when we change status back.
        $this->update_status('user_enrolments', 'status', 'userid = ' . $this->users[1]->id, 1);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(2, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
        $this->update_status('user_enrolments', 'status', 'userid = ' . $this->users[1]->id, 0);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(3, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Test the effects of changing an enrolment method status - should now be 1 in cohort, then 3 again when we change status back.
        $where = 'id = (SELECT enrolid FROM {user_enrolments} WHERE userid = ' . $this->users[1]->id . ')';
        $this->update_status('enrol', 'status', $where, 1);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(1, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
        $where = 'id = (SELECT enrolid FROM {user_enrolments} WHERE userid = ' . $this->users[1]->id . ')';
        $this->update_status('enrol', 'status', $where, 0);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(3, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Test the effects of enrolment time end before today - should now be 2 in cohort, then 3 again when we change to future.
        $this->update_status('user_enrolments', 'timeend', 'userid = ' . $this->users[1]->id, 100);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(2, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
        $this->update_status('user_enrolments', 'timeend', 'userid = ' . $this->users[1]->id, time() + 100);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(3, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // P1 AND P2 - should match 1 user.
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
        $this->cohort_generator->create_cohort_rule_params(
            $this->ruleset,
            'learning',
            'programenrolmentlist',
            array('operator' => COHORT_RULE_ENROLMENT_OP_ALL),
            array($this->program1->id, $this->program2->id),
            'listofids'
        );
        // Refresh rule list cache.
        cohort_rules_list(true);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(1, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // P1 OR P2 - should match 2 users.
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
        $this->cohort_generator->create_cohort_rule_params(
            $this->ruleset,
            'learning',
            'programenrolmentlist',
            array('operator' => COHORT_RULE_ENROLMENT_OP_ANY),
            array($this->program1->id, $this->program2->id),
            'listofids'
        );
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(2, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // C2 AND P2 - should match 0 users.
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
        $this->cohort_generator->create_cohort_rule_params(
            $this->ruleset,
            'learning',
            'courseenrolmentlist',
            array('operator' => COHORT_RULE_ENROLMENT_OP_ALL),
            array($this->course2->id),
            'listofids'
        );
        $this->cohort_generator->create_cohort_rule_params(
            $this->ruleset,
            'learning',
            'programenrolmentlist',
            array('operator' => COHORT_RULE_ENROLMENT_OP_ALL),
            array($this->program2->id),
            'listofids'
        );
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Changes operator AND for OR in the previous rule:
        // C2 || P2 - should match 3 users.
        $result = totara_cohort_update_operator($this->cohort->id, $this->ruleset, COHORT_OPERATOR_TYPE_RULESET, COHORT_RULES_OP_OR);
        $this->assertTrue($result);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(3, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // P1 AND C1 - should match 1 users.
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
        $this->cohort_generator->create_cohort_rule_params(
            $this->ruleset,
            'learning',
            'programenrolmentlist',
            array('operator' => COHORT_RULE_ENROLMENT_OP_ALL),
            array($this->program1->id),
            'listofids'
        );
        $this->cohort_generator->create_cohort_rule_params(
            $this->ruleset,
            'learning',
            'courseenrolmentlist',
            array('operator' => COHORT_RULE_ENROLMENT_OP_ALL),
            array($this->course1->id),
            'listofids'
        );
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(1, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Changes operator AND to OR in the previous rule:
        // P1 || C1 - should match 2 users.
        $result = totara_cohort_update_operator($this->cohort->id, $this->ruleset, COHORT_OPERATOR_TYPE_RULESET, COHORT_RULES_OP_OR);
        $this->assertTrue($result);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(2, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Helper to flip status values.
     *
     * @param $table   Table which will be updated.
     * @param $field   Status field whose value will be set.
     * @param $where   SQL "where" clause identifying the row to be updated.
     * @param $value   The new staus value (0 = enabled, 1 = disabled).
     */
    private function update_status($table, $field, $where, $value) {
        global $DB;

        $sql = "UPDATE {" . $table . "} SET {$field} = {$value} WHERE {$where}" ;
        $DB->execute($sql);
    }
}
