<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_reportbuilder
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();
use mod_facetoface\{seminar, seminar_event, signup, role, signup_helper};
use mod_facetoface\signup\state\requestedadmin;
use totara_job\job_assignment;

global $CFG;

/**
 * @group totara_reportbuilder
 */
class mod_facetoface_rb_filter_userstatus_testcase extends \advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * @param int $numberofusers
     * @return array
     */
    private function create_users(int $numberofusers): array {
        $generator = $this->getDataGenerator();

        // Creating manager here
        $manager = $generator->create_user();
        $managerja = job_assignment::create_default($manager->id);

        $users = array();

        for ($i = 0; $i < $numberofusers; $i++) {
            $user = $generator->create_user();
            $jobassignment = job_assignment::create_default($user->id, [
                'managerjaid' => $managerja->id
            ]);

            $user->jobassignment = $jobassignment;
            $users[] = $user;
        }
        return $users;
    }

    public function test_filter() {
        global $DB, $USER;

        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(null, ['createsections' => true]);

        $f2f_generator = $generator->get_plugin_generator('mod_facetoface');
        // Seminar 1
        $f2f1 = $f2f_generator->create_instance([
            'course' => $course1->id,
            'approvaltype' => seminar::APPROVAL_MANAGER
        ]);

        $sessiondate1 = new stdClass();
        $sessiondate1->timestart = time() + (HOURSECS * 1);
        $sessiondate1->timefinish = time() + (HOURSECS * 2);
        $sessiondate1->sessiontimezone = 'Australia/Sydney';

        $sessiondata1 = array(
            'facetoface' => $f2f1->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate1),
        );
        $sessionid1 = $f2f_generator->add_session($sessiondata1);
        $seminar1event1 = new seminar_event($sessionid1);

        // Seminar 2
        $f2f2 = $f2f_generator->create_instance([
            'course' => $course1->id,
            'approvaltype' => seminar::APPROVAL_NONE
        ]);

        $sessiondate2 = new stdClass();
        $sessiondate2->timestart = time() + (DAYSECS * 3);
        $sessiondate2->timefinish = time() + (DAYSECS * 3) + (HOURSECS);
        $sessiondate2->sessiontimezone = 'Australia/Sydney';

        $sessiondata2 = array(
            'facetoface' => $f2f2->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate2),
        );
        $sessionid2 = $f2f_generator->add_session($sessiondata2);
        $seminar2event1 = new seminar_event($sessionid2);

        // Seminar 3
        $f2f3 = $f2f_generator->create_instance([
            'course' => $course1->id,
            'approvaltype' => seminar::APPROVAL_ADMIN,
            'approvaladmins' => $USER->id
        ]);

        $sessiondate3 = new stdClass();
        $sessiondate3->timestart = time() + (DAYSECS * 2);
        $sessiondate3->timefinish = time() + (DAYSECS * 2) + (HOURSECS);
        $sessiondate3->sessiontimezone = 'Australia/Sydney';

        $sessiondata3 = array(
            'facetoface' => $f2f3->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate3),
        );
        $sessionid3 = $f2f_generator->add_session($sessiondata3);
        $seminar3event1 = new seminar_event($sessionid3);

        // Seminar 4
        $trainerrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $trainers = $this->create_users(2);

        $f2f4 = $f2f_generator->create_instance([
            'course' => $course1->id,
            'approvaltype' => seminar::APPROVAL_ROLE,
            'approvalrole' => $trainerrole->id
        ]);

        $sessiondate4 = new stdClass();
        $sessiondate4->timestart = time() + (DAYSECS * 1);
        $sessiondate4->timefinish = time() + (DAYSECS * 1) + (HOURSECS);
        $sessiondate4->sessiontimezone = 'Australia/Sydney';

        $sessiondata4 = array(
            'facetoface' => $f2f4->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate4),
        );
        $sessionid4 = $f2f_generator->add_session($sessiondata4);
        $seminar4event1 = new seminar_event($sessionid4);

        // Setup trainer role for seminar
        foreach ($trainers as $trainer) {
            $generator->enrol_user($trainer->id, $course1->id);

            $role = new role();
            $role->set_sessionid($seminar4event1->get_id());
            $role->set_roleid($trainerrole->id);
            $role->set_userid($trainer->id);
            $role->save();
        }

        // Create users and enrol into course
        $users = $this->create_users(2);
        foreach ($users as $user) {
            $generator->enrol_user($user->id, $course1->id);
        }

        foreach ($users as $user) {
            // These end up in the requested (40) status
            $signup = signup::create($user->id, $seminar1event1);
            signup_helper::signup($signup);

            // These end up booked (70) as there is no approval on this f2f
            $signup = signup::create($user->id, $seminar2event1);
            signup_helper::signup($signup);

            // Booked as requested role (44)
            $signup = signup::create($user->id, $seminar4event1);
            signup_helper::signup($signup);

            // Booked as requested admin (45)
            $signup = signup::create($user->id, $seminar3event1);
            signup_helper::signup($signup);
            $signup->switch_state(requestedadmin::class);
        }

        // Create a report.
        $this->report = $this->create_report('facetoface_events', 'seminar_events_report');
        $config = (new rb_config())->set_nocache(true);

        // Add the enrolment type column and filter.
        $filter = new \stdClass();
        $filter->reportid = $this->report;
        $filter->advanced = 0;
        $filter->region = rb_filter_type::RB_FILTER_REGION_STANDARD;
        $filter->type = 'session';
        $filter->value = 'currentuserstatus';
        $filter->filtername = 'ViewerStatus';
        $filter->customname = 1;
        $filter->sortorder = 1;
        $DB->insert_record('report_builder_filters', $filter);

        $DB->insert_record("report_builder_columns", (object)[
            'reportid' => $this->report,
            'type' => 'session',
            'value' => 'currentuserstatus',
            'heading' => 'Viewers status',
            'sortorder' => 1,
            'hidden' => 0,
            'customheading' => ''
        ]);

        $user1 = $users[0];
        $this->setUser($user1);

        $_POST = array(
            'sesskey' => sesskey(),
            '_qf__report_builder_standard_search_form' => 1,
            'session-currentuserstatus_op' => 1,
            'session-currentuserstatus' => 1,
            'submitgroupstandard' => array(
                'addfilter' => 'Search'
            )
        );

        $report = \reportbuilder::create($this->report, $config);

        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(3, $records);

        $_POST = array(
            'sesskey' => sesskey(),
            '_qf__report_builder_standard_search_form' => 1,
            'session-currentuserstatus_op' => 2,
            'session-currentuserstatus' => 1,
            'submitgroupstandard' => array(
                'addfilter' => 'Search'
            )
        );

        $report = \reportbuilder::create($this->report, $config);

        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);

        $record = reset($records);
        $this->assertEquals(70, $record->session_currentuserstatus);
    }
}