<?php
/*
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\{seminar_event, signup, seminar_session};
use mod_facetoface\signup\state\{booked, attendance_state, not_set};

class mod_facetoface_attendees_list_testcase extends advanced_testcase {
    /**
     * @return seminar_event
     */
    private function create_seminar_event(): seminar_event {
        $gen = $this->getDataGenerator();

        $course = $gen->create_course();

        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $e = new seminar_event();
        $e->set_facetoface($f2f->id);
        $e->save();

        return $e;
    }

    /**
     * A test suite that assuring the deleted users should not be included in embedded report builder for event's attendees.
     * And within this test, the actor that viewing the embedded report is an editing trainer, and this user does not have the
     * permission to view the deleted user.
     *
     * @return void
     */
    public function test_attendee_list_should_not_include_deleted_user(): void {
        global $DB, $PAGE;
        $PAGE->set_url('/');

        $this->setAdminUser();

        $e = $this->create_seminar_event();
        $s = new seminar_session();
        $s->set_sessionid($e->get_id());
        $s->set_timestart(time() - 7200);
        $s->set_timefinish(time() - 3600);
        $s->save();

        // Use statically defined names to avoid collision.
        $usernames = [
            [ 'Jacob', 'Smith' ],
            [ 'Eliška', 'Novák' ],
            [ 'Даниил', 'Морозова' ],
            [ '秀英', '李' ],
            [ 'さくら', '佐藤' ],
        ];

        $gen = $this->getDataGenerator();
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $gen->create_user([
                'firstname' => $usernames[$i][0],
                'lastname' => $usernames[$i][1],
                'middlename' => '',
                'alternatename' => ''
            ]);
            $gen->enrol_user($user->id, $e->get_seminar()->get_course());

            $signup = signup::create($user->id, $e);
            $signup->save();

            $signup->switch_state(booked::class);

            $users[] = $user;
        }

        // Delete user #0
        delete_user($users[0]);

        $trainer = $gen->create_user();
        $gen->enrol_user($trainer->id, $e->get_seminar()->get_course(), 'editingteacher');
        $this->setUser($trainer);

        $cfg = new rb_config();
        $cfg->set_reportfor($trainer->id);
        $cfg->set_embeddata(
            [
                'sessionid' => $e->get_id(),
                'status' => attendance_state::get_all_attendance_code_with([booked::class, not_set::class]),
            ]
        );

        $record = $DB->get_record('report_builder', ['shortname' => 'facetoface_sessions']);
        $cfg->set_global_restriction_set(is_object($record) ? $record : null);
        $report = reportbuilder::create_embedded('facetoface_sessions', $cfg);

        /** @var totara_reportbuilder_renderer $output */
        $output = $PAGE->get_renderer('totara_reportbuilder');
        [$reporthtml, $debughtml] = $output->report_html($report, 0);

        // As user #0 had been deleted earlier, therefore, we are expecting this user to not be included in the
        // embedded report builder.
        $this->assertStringNotContainsString(fullname($users[0]), $reporthtml);

        // User #1 .. #4 must be included
        $this->assertStringContainsString(fullname($users[1]), $reporthtml);
        $this->assertStringContainsString(fullname($users[2]), $reporthtml);
        $this->assertStringContainsString(fullname($users[3]), $reporthtml);
        $this->assertStringContainsString(fullname($users[4]), $reporthtml);
    }

    /**
     *
     * @return void
     */
    public function test_attendee_list_to_include_deleted_user(): void {
        global $DB, $PAGE, $USER;
        $PAGE->set_url('/');

        $this->setAdminUser();

        $e = $this->create_seminar_event();
        $s = new seminar_session();
        $s->set_sessionid($e->get_id());
        $s->set_timestart(time() - 7200);
        $s->set_timefinish(time() - 3600);
        $s->save();

        // Use statically defined names to avoid collision.
        $usernames = [
            [ 'Jacob', 'Smith' ],
            [ 'Eliška', 'Novák' ],
            [ 'Даниил', 'Морозова' ],
            [ '秀英', '李' ],
            [ 'さくら', '佐藤' ],
        ];

        $gen = $this->getDataGenerator();

        $users = [];

        for ($i = 0; $i < 5; $i++) {
            $user = $gen->create_user([
                'firstname' => $usernames[$i][0],
                'lastname' => $usernames[$i][1],
                'middlename' => '',
                'alternatename' => ''
            ]);
            $gen->enrol_user($user->id, $e->get_seminar()->get_course());

            $signup = signup::create($user->id, $e);
            $signup->save();

            $signup->switch_state(booked::class);

            $users[] = $user;
        }

        // Start preparing the new permission for the role editingteacher. So that our trainer is able to see the deleted users
        // within this test here.
        $role = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $cm = get_coursemodule_from_instance('facetoface', $e->get_facetoface(), $e->get_seminar()->get_course());
        $ctx = context_module::instance($cm->id);
        $PAGE->set_context($ctx);

        assign_capability('totara/core:seedeletedusers', CAP_ALLOW, $role->id, $ctx->id);

        // Delete user #0
        delete_user($users[0]);
        $trainer = $gen->create_user();
        $gen->enrol_user($trainer->id, $e->get_seminar()->get_course(), 'editingteacher');
        $this->setUser($trainer);

        $cfg = new rb_config();
        $cfg->set_reportfor($trainer->id);
        $cfg->set_embeddata(
            [
                'sessionid' => $e->get_id(),
                'status' => attendance_state::get_all_attendance_code_with([booked::class, not_set::class]),
            ]
        );

        $record = $DB->get_record('report_builder', ['shortname' => 'facetoface_sessions']);
        $cfg->set_global_restriction_set(is_object($record) ? $record : null);
        $report = reportbuilder::create_embedded('facetoface_sessions', $cfg);

        /** @var totara_reportbuilder_renderer $output */
        $output = $PAGE->get_renderer('totara_reportbuilder');
        [$reporthtml, $debughtml] = $output->report_html($report, 0);

        // All users must be included
        $this->assertStringContainsString(fullname($users[0]), $reporthtml);
        $this->assertStringContainsString(fullname($users[1]), $reporthtml);
        $this->assertStringContainsString(fullname($users[2]), $reporthtml);
        $this->assertStringContainsString(fullname($users[3]), $reporthtml);
        $this->assertStringContainsString(fullname($users[4]), $reporthtml);
    }

    public function test_attendee_added_after_decliened(): void {
        global $DB;
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $event = $this->create_seminar_event();

        $time = time() + DAYSECS;
        $session = (new seminar_session())->set_sessionid($event->get_id())->set_timestart($time)->set_timefinish($time + HOURSECS);
        $session->save();

        $user = $gen->create_user(['firstname' => 'One', 'lastname' => 'Uno']);
        $this->setUser($user);
        $signup = signup::create($user->id, $event)->set_managerid(42)->set_jobassignmentid(64)->set_bookedby(77)->save();
        mod_facetoface\signup_status::create($signup, new mod_facetoface\signup\state\declined($signup))->save();
        $signup = $DB->get_record('facetoface_signups', ['userid' => $user->id, 'sessionid' => $event->get_id()], '*', MUST_EXIST);
        $this->assertEquals(42, $signup->managerid);
        $this->assertEquals(64, $signup->jobassignmentid);
        $this->assertEquals(77, $signup->bookedby);
        $this->assertInstanceOf(mod_facetoface\signup\state\declined::class, signup::create($user->id, $event)->get_state());

        $listid = sprintf('f2f%x', random_int(0, PHP_INT_MAX));
        $currenturl = new moodle_url('/mod/facetoface/attendees/list/add.php', array('s' => $event->get_facetoface(), 'listid' => $listid));

        $list = new mod_facetoface\bulk_list($listid, $currenturl, 'add');
        $list->set_user_ids([$user->id]);
        $signup = signup::create($user->id, $event);
        $list->set_validaton_results([]);

        $list = new mod_facetoface\bulk_list($listid);
        $userlist = $list->get_user_ids();
        $this->assertNotCount(0, $userlist);
        mod_facetoface\form\attendees_add_confirm::mock_submit([
            's' => $event->get_id(),
            'listid' => $listid,
            'notifyuser' => 1,
            'notifymanager' => 1,
        ]);

        $mform = new mod_facetoface\form\attendees_add_confirm(null, [
            's' => $event->get_id(),
            'listid' => $listid,
            'isapprovalrequired' => false,
            'enablecustomfields' => !$list->has_user_data(),
            'ignoreconflicts' => 0,
            'is_notification_active' => 0
        ]);
        $data = $mform->get_data();
        mod_facetoface\attendees_list_helper::add($data);

        $signup = $DB->get_record('facetoface_signups', ['userid' => $user->id, 'sessionid' => $event->get_id()], '*', MUST_EXIST);
        $this->assertNull($signup->managerid);
        $this->assertNull($signup->jobassignmentid);
        $this->assertNull($signup->bookedby);
        $this->assertInstanceOf(booked::class, signup::create($user->id, $event)->get_state());
    }
}