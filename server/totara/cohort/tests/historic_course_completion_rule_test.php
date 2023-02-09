<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');

class totara_cohort_historic_course_completion_rule_testcase extends reportcache_advanced_testcase {

    private $user1 = null;
    private $user2 = null;
    private $user3 = null;
    private $user4 = null;
    private $admin = null;
    private $course1 = null;
    private $course2 = null;
    private $cohort = null;
    private $ruleset = 0;
    private $cohort_generator = null;
    private $now_time = null;
    private $one_days_ago_time = null;
    private $two_days_ago_time = null;
    private $three_days_ago_time= null;
    private $five_days_ago_time = null;

    protected function tearDown(): void {
        $this->user1 = null;
        $this->user2 = null;
        $this->user3 = null;
        $this->user4 = null;
        $this->admin = null;
        $this->cohort = null;
        $this->course1 = null;
        $this->course2 = null;
        $this->ruleset = null;
        $this->cohort_generator = null;
        $this->now_time = null;
        $this->one_days_ago_time = null;
        $this->two_days_ago_time = null;
        $this->three_days_ago_time = null;
        $this->five_days_ago_time = null;

        parent::tearDown();
    }

    public function setUp(): void {
        global $DB;

        parent::setup();
        set_config('enablecompletion', 1);
        $this->setAdminUser();
        $this->admin = get_admin();

        // Set totara_cohort generator.
        $this->cohort_generator = \totara_cohort\testing\generator::instance();

        // Create 4 learner accounts.
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->user3 = $this->getDataGenerator()->create_user();
        $this->user4 = $this->getDataGenerator()->create_user();

        // Create 2 courses
        $this->course1 = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $this->course2 = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);

        // Enrol users into courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->user3->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->user4->id, $this->course1->id);

        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course2->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course2->id);

        $time = time();
        $this->now_time = $time;
        $this->one_days_ago_time = $time + (-1 * DAYSECS);
        $this->two_days_ago_time = $time + (-2 * DAYSECS);
        $this->three_days_ago_time = $time + (-3 * DAYSECS);
        $this->five_days_ago_time = $time + (-5 * DAYSECS);

        // Make user1 and user2 complete course1 (5 days ago and 3 days ago respectively)
        $cc = new completion_completion(['userid' => $this->user1->id, 'course' => $this->course1->id]);
        $cc->mark_complete($this->five_days_ago_time);

        $cc = new completion_completion(['userid' => $this->user2->id, 'course' => $this->course1->id]);
        $cc->mark_complete($this->three_days_ago_time);

        // Reset completions.
        archive_course_completion($this->user1->id, $this->course1->id);
        archive_course_completion($this->user2->id, $this->course1->id);

        $historic_records = $DB->get_records('course_completion_history', [], 'id');
        $this->assertCount(2, $historic_records);

        # Lets user1 complete course1 again (time completed 2 days ago).
        $cc = new completion_completion(['userid' => $this->user1->id, 'course' => $this->course1->id]);
        $cc->mark_complete($this->two_days_ago_time);

        // Reset completions again.
        archive_course_completion($this->user1->id, $this->course1->id);

        # Lets user2 complete course1 again (Time completed now) - No archived.
        $cc = new completion_completion(['userid' => $this->user2->id, 'course' => $this->course1->id]);
        $cc->mark_complete($this->now_time);

        # Lets user3 complete course1 - No archived.
        $cc = new completion_completion(['userid' => $this->user3->id, 'course' => $this->course1->id]);
        $cc->mark_complete($this->now_time);

        # Lets user2 and user3 complete course2 and reset completion
        $cc = new completion_completion(['userid' => $this->user2->id, 'course' => $this->course2->id]);
        $cc->mark_complete($this->two_days_ago_time);

        $cc = new completion_completion(['userid' => $this->user3->id, 'course' => $this->course2->id]);
        $cc->mark_complete($this->now_time);

        archive_course_completion($this->user2->id, $this->course2->id);
        archive_course_completion($this->user3->id, $this->course2->id);

        // Create cohort.
        $this->cohort = $this->cohort_generator->create_cohort(['cohorttype' => cohort::TYPE_DYNAMIC]);
        $this->assertTrue($DB->record_exists('cohort', ['id' => $this->cohort->id]));
        $this->assertEquals(0, $DB->count_records('cohort_members', ['cohortid' => $this->cohort->id]));

        // Create ruleset.
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
    }

    /**
     * Data provider for course completion list rule.
     */
    public function data_historic_course_completion_list() {
        $data = [
            // If user has completed ANY of courses.
            [['operator' => COHORT_RULE_COMPLETION_OP_ANY], ['course1'], ['user1', 'user2']],
            [['operator' => COHORT_RULE_COMPLETION_OP_ANY], ['course1', 'course2'], ['user1', 'user2', 'user3']],
            // If user has NOT completed ANY Historic (archived) records of courses
            [['operator' => COHORT_RULE_COMPLETION_OP_NONE], ['course2'], ['admin', 'user1', 'user4']],
            [['operator' => COHORT_RULE_COMPLETION_OP_NONE], ['course1', 'course2'], ['admin', 'user4']],
            // If user has NOT completed ALL Historic (archived) records of courses
            [['operator' => COHORT_RULE_COMPLETION_OP_NOTALL], ['course1', 'course2'], ['admin', 'user1', 'user3','user4']],
            [['operator' => COHORT_RULE_COMPLETION_OP_NOTALL], ['course1'], ['admin', 'user3','user4']],
            [['operator' => COHORT_RULE_COMPLETION_OP_NOTALL], ['course2'], ['admin', 'user1','user4']],
            // If user has completed ALL Historic (archived) records of courses.
            [['operator' => COHORT_RULE_COMPLETION_OP_ALL], ['course1', 'course2'], ['user2']],
            [['operator' => COHORT_RULE_COMPLETION_OP_ALL], ['course1'], ['user1', 'user2']],
            [['operator' => COHORT_RULE_COMPLETION_OP_ALL], ['course2'], ['user2', 'user3']],
        ];
        return $data;
    }

    /**
     * @dataProvider data_historic_course_completion_list
     */
    public function test_historic_course_completion_rule($params, $listofcourses, $users) {
        global $DB;
        $this->setAdminUser();

        // Process listofids.
        $listofids = [];
        foreach ($listofcourses as $course) {
            $listofids[] = $this->{$course}->id;
        }

        // Process list of users.
        $userlist = [];
        foreach ($users as $user) {
            $userlist[] = $this->{$user}->id;
        }

        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'learning', 'coursecompletionhistorylist', $params, $listofids, 'listofids');
        cohort_rules_approve_changes($this->cohort);

        // Count cohort members.
        $this->assertEquals(count($userlist), $DB->count_records('cohort_members', ['cohortid' => $this->cohort->id]));

        // Check we have the right users as members.
        $params = ['cohortid' => $this->cohort->id];
        list($sqlin, $paramin) = $DB->get_in_or_equal($userlist, SQL_PARAMS_NAMED);
        $sql = "SELECT COUNT(*) FROM {cohort_members} WHERE cohortid =:cohortid AND userid " . $sqlin;
        $params = array_merge($params, $paramin);

        $this->assertEquals(count($userlist), $DB->count_records_sql($sql, $params));
    }

    /**
     * Data provider for hisctoric course completion date rule.
     *
     * I have found that the range operators for this rule are not right as all of them take into account the = date
     * so COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, for example, is less than or equal to the date passed.
     */
    public function data_historic_course_completion_date() {
        $data = [
            // User's course completion history date is before [date].
            [['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => 'two_days_ago_time'], ['course1'], ['user1', 'user2']],
            [['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => 'three_days_ago_time'], ['course1'], ['user1', 'user2']],
            [['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => 'one_days_ago_time'], ['course2'], ['user2']],
            [['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => 'now_time'], ['course2'], ['user2', 'user3']],
            // User's course completion history date is on or after [date].
            [['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => 'two_days_ago_time'], ['course1'], ['user1']],
            [['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => 'one_days_ago_time'], ['course2'], ['user3']],
            [['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => 'two_days_ago_time'], [ 'course2'], ['user2', 'user3']],
            [['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => 'now_time'], ['course2'], ['user3']],
            // User's course completion history date is more than [x days ago]
            [['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'duration' => 3], ['course1'], ['user1', 'user2']],
            [['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'duration' => 1], ['course1'], ['user1', 'user2']],
            [['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'duration' => 1], ['course1', 'course2'], ['user2']],
            [['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'duration' => 0], ['course2'], ['user2', 'user3']],
        ];
        return $data;
    }

    /**
     * @dataProvider data_historic_course_completion_date
     */
    public function test_historic_course_completion_date_rule($params, $listofcourses, $users) {
        global $DB;
        $this->setAdminUser();

        // Process listofids.
        $listofids = [];
        foreach ($listofcourses as $course) {
            $listofids[] = $this->{$course}->id;
        }

        // Process list of users.
        $userlist = [];
        foreach ($users as $user) {
            $userlist[] = $this->{$user}->id;
        }

        // Fix date param.
        $params['date'] = $this->get_actual_date($params);

        // Create historic course completion date rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'learning', 'coursecompletionhistorydate', $params, $listofids, 'listofids');
        cohort_rules_approve_changes($this->cohort);

        // Count cohort members.
        $this->assertEquals(count($userlist), $DB->count_records('cohort_members', ['cohortid' => $this->cohort->id]));

        // Check we have the right users as members.
        $params = ['cohortid' => $this->cohort->id];
        list($sqlin, $paramin) = $DB->get_in_or_equal($userlist, SQL_PARAMS_NAMED);
        $sql = "SELECT COUNT(*) FROM {cohort_members} WHERE cohortid =:cohortid AND userid " . $sqlin;
        $params = array_merge($params, $paramin);

        $this->assertEquals(count($userlist), $DB->count_records_sql($sql, $params));
    }

    private function get_actual_date($params) {
        if (isset($params['date'])) {
            return $this->{$params['date']};
        } elseif (isset($params['duration'])) {
            return $params['duration'];
        }
    }
}