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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');

class totara_cohort_course_completion_history_rules_testcase extends reportcache_advanced_testcase {

    private $course1 = null;
    private $course2 = null;
    private $course3 = null;
    private $course4 = null;
    private $users = array();
    private $cohort = null;
    private $ruleset = 0;
    private $coursestocomplete = array();
    private $cohort_generator = null;
    const TEST_COURSE_COUNT_USERS = 53;

    protected function tearDown(): void {
        $this->course1 = null;
        $this->course2 = null;
        $this->course3 = null;
        $this->course4 = null;
        $this->users = null;
        $this->cohort = null;
        $this->ruleset = null;
        $this->coursestocomplete = null;
        $this->cohort_generator = null;
        parent::tearDown();
    }

    /*
     * Course completion data:
     *-----------------*----------*--------------*-----------------*-----------------*
     | Group of users  |  course  | time started | time completed  | time completion |
     |-----------------*----------*--------------*-----------------*-----------------*
     | user1  - user15 |  course1 |  -10 days    |    -1 day       |     9 days      |
     |-----------------*----------*--------------*-----------------*-----------------*
     | user1  - user15 |  course2 |  -10 days    |    -5 days      |     5 days      |
     |-----------------*----------*--------------*-----------------*-----------------*
     | user16 - user36 |  course1 |  -5 days     |    -3 days      |     2 days      |
     |-----------------*----------*--------------*-----------------*-----------------*
     | user16 - user36 |  course2 |  -5 days     |    -5 days      |     0 days      |
     |-----------------*----------*--------------*-----------------*-----------------*
     | user37 - user50 |  course2 |  -7 days     |    -1 day       |     6 days      |
     *-----------------*----------*--------------*-----------------*-----------------*
     *-----------------*----------*--------------*------------*
     | Group of users  |  course  | time started | completed  |
     *-----------------*----------*--------------*------------*
     | user1  - user4  |  course3 |  now         |    yes     |
     *-----------------*----------*--------------*------------*
     | user5  - user7  |  course3 |  now         |    no      |
     *-----------------*----------*--------------*------------*
     | user1  - user2  |  course4 |  now         |    yes     |
     *-----------------*----------*--------------*------------*
     | user3  - user7  |  course4 |  now         |    no      |
     *-----------------*----------*--------------*------------*
    */
    public function setUp(): void {
        global $DB;

        parent::setup();
        set_config('enablecompletion', 1);
        $this->setAdminUser();

        // Set totara_cohort generator.
        $this->cohort_generator = \totara_cohort\testing\generator::instance();

        // Create users.
        for ($i = 1; $i <= self::TEST_COURSE_COUNT_USERS; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $this->users[$i] = $user;
        }
        $this->assertEquals(self::TEST_COURSE_COUNT_USERS, count($this->users));

        // Create a couple of courses.
        $now = time();
        $setting = array('enablecompletion' => 1, 'completionstartonenrol' => 1);
        $this->course1 = $this->getDataGenerator()->create_course($setting);
        $this->course2 = $this->getDataGenerator()->create_course($setting);
        $this->course3 = $this->getDataGenerator()->create_course($setting);
        $this->course4 = $this->getDataGenerator()->create_course($setting);
        $this->coursestocomplete[$this->course1->id] = 1;
        $this->coursestocomplete[$this->course2->id] = 5;
        $this->assertEquals(0, $DB->count_records('course_completions'));

        // Enrol some users and make them complete course1 and course2.
        $coursetocomplete = $this->coursestocomplete;
        for ($i = 1; $i <= 50; $i++) {
            if ($i <= 15) { // Users from 1 to 15 will complete both courses. Time started = now - 10 days.
                $timestarted = $now - (10 * DAYSECS);
            } else if ($i <= 36) { // Users from 16 to 36 will complete both course. Time started = now - 5 days.
                $timestarted = $now - (5 * DAYSECS);
                $coursetocomplete[$this->course1->id] = 3;
            } else { // Users from 37 to 50 will complete course2. Time started = now - 7 days.
                $timestarted = $now - (7 * DAYSECS);
                unset($coursetocomplete[$this->course1->id]);
                $coursetocomplete[$this->course2->id] = 1;
            }

            foreach ($coursetocomplete as $courseid => $completiondate) {
                $completionrpl = new completion_completion(array('userid' => $this->users[$i]->id, 'course' => $courseid, 'timestarted' => $timestarted));
                $completionrpl->rpl = 'completed via rpl';
                $completionrpl->status = COMPLETION_STATUS_COMPLETEVIARPL;
                $completionrpl->mark_complete($now - ($completiondate * DAYSECS));
            }
        }
        $this->assertEquals(86, $DB->count_records('course_completions'));

        // Enrol some users and make them complete some course3 and some course4.
        $coursetocomplete = array();
        for ($i = 1; $i <= 7; $i++) {
            $timestarted = $now;
            if ($i <= 2) {
                $coursetocomplete[$this->course3->id] = true;
                $coursetocomplete[$this->course4->id] = true;
            } else if ($i <= 4) {
                $coursetocomplete[$this->course3->id] = true;
                $coursetocomplete[$this->course4->id] = false;
            } else {
                $coursetocomplete[$this->course3->id] = false;
                $coursetocomplete[$this->course4->id] = false;
            }

            foreach ($coursetocomplete as $courseid => $completiondate) {
                $completion = new completion_completion(array('userid' => $this->users[$i]->id, 'course' => $courseid, 'timestarted' => $timestarted));
                if ($i <= 2) {
                    $completion->status = COMPLETION_STATUS_COMPLETE;
                    $completion->mark_complete();
                } else if ($i <= 4) {
                    if ($completiondate) {
                        $completion->status = COMPLETION_STATUS_COMPLETE;
                        $completion->mark_complete();
                    } else {
                        $completion->status = COMPLETION_STATUS_NOTYETSTARTED;
                        $completion->mark_enrolled();
                    }
                } else {
                    $completion->status = COMPLETION_STATUS_NOTYETSTARTED;
                    $completion->mark_enrolled();
                }
            }
        }
        $this->assertEquals(100, $DB->count_records('course_completions'));

        $completions = $DB->get_records('course_completions', ['status' => COMPLETION_STATUS_COMPLETE]);
        foreach ($completions as $completion) {
            $todb = new stdClass();
            $todb->courseid = $completion->course;
            $todb->userid = $completion->userid;
            $todb->timecompleted = $completion->timecompleted;
            $todb->grade = $completion->rplgrade;
            $DB->insert_record('course_completion_history', $todb);
        }
        $this->assertEquals(6, $DB->count_records('course_completion_history'));

        // Create cohort.
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->assertTrue($DB->record_exists('cohort', array('id' => $this->cohort->id)));
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Create ruleset.
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
    }

    /**
     * Data provider for course completion list rule.
     */
    public function data_history_course_completion_list() {
        $data = array(
            // If user has NOT completed ANY of courses
            array(array('operator' => COHORT_RULE_COMPLETION_OP_NONE), array('course3', 'course4'), 50),
            // If user has completed ANY of courses.
            array(array('operator' => COHORT_RULE_COMPLETION_OP_ANY), array('course3', 'course4'), 4),
            // If user has NOT completed ALL of courses
            array(array('operator' => COHORT_RULE_COMPLETION_OP_NOTALL), array('course3', 'course4'), 52),
            // If user has completed ALL of courses.
            array(array('operator' => COHORT_RULE_COMPLETION_OP_ALL), array('course3', 'course4'), 2),
        );
        return $data;
    }

    /**
     * @dataProvider data_history_course_completion_list
     */
    public function test_historycoursecompletion_list($params, $listofcourses, $usercount) {
        global $DB;
        $this->setAdminUser();

        // Process listofids.
        $listofids = array();
        foreach ($listofcourses as $course) {
            $listofids[] = $this->{$course}->id;
        }

        // Create rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'learning', 'coursecompletionhistorylist', $params, $listofids, 'listofids');
        cohort_rules_approve_changes($this->cohort);

        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for history course completion date rule.
     */
    public function data_history_course_completion_date() {
        $data = array(
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => time() + (1 * DAYSECS)),  array('course3'), 4),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => time() + (-1 * DAYSECS)), array('course3'), 4),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => time() + (1 * DAYSECS)),  array('course4'), 2),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => time() + (-1 * DAYSECS)), array('course4'), 2),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 2), array('course3','course4'), 2),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 2), array('course4'), 2),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 2), array('course3'), 4),
        );
        return $data;
    }

    /**
     * @dataProvider data_history_course_completion_date
     */
    public function test_historycoursecompletion_date_rule($params, $courses, $usercount) {
        global $DB;
        $this->setAdminUser();

        // Process listofids.
        $listofids = array();
        foreach ($courses as $course) {
            $listofids[] = $this->{$course}->id;
        }

        // Create course completion rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'learning', 'coursecompletionhistorydate', $params, $listofids, 'listofids');
        cohort_rules_approve_changes($this->cohort);

        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }
}