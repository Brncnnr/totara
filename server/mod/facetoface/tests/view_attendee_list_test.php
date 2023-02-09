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

use mod_facetoface\{seminar_event, seminar_session, signup};
use mod_facetoface\signup\state\{attendance_state, booked, not_set};

class mod_facetoface_view_attendee_list_testcase extends advanced_testcase {
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
     * Test suite to ensure that the actor as a trainer should not see the column managing the attendee's note, if the actor
     * does not have the ability to manage it.
     *
     * @return void
     */
    public function test_view_attendees_list_as_a_trainer_role(): void {
        global $PAGE;
        $PAGE->set_url('/');

        $this->setAdminUser();

        $e = $this->create_seminar_event();

        $s = new seminar_session();
        $s->set_sessionid($e->get_id());
        $s->set_timestart(time() + 3600);
        $s->set_timefinish(time() + 7200);
        $s->save();

        $gen = $this->getDataGenerator();

        for ($i = 0; $i < 5; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $e->get_seminar()->get_course(), 'student');

            $signup = signup::create($user->id, $e);
            $signup->save();
            $signup->switch_state(booked::class);
        }

        $cm = get_coursemodule_from_instance('facetoface', $e->get_facetoface(), $e->get_seminar()->get_course());
        $context = context_module::instance($cm->id);
        $PAGE->set_context($context);

        $trainer = $gen->create_user();
        $gen->enrol_user($trainer->id, $e->get_seminar()->get_course(), 'teacher');
        $this->setUser($trainer);

        $cfg = new rb_config();
        $cfg->set_embeddata(
            [
                'sessionid' => $e->get_id(),
                'status' => attendance_state::get_all_attendance_code_with([booked::class, not_set::class])
            ]
        );

        $report = reportbuilder::create_embedded('facetoface_sessions', $cfg);
        $renderer = $PAGE->get_renderer('totara_reportbuilder');

        // Expecting the header actions should not appear here for the report html. Because the current actor does not have the
        // ability to manage the attendee's signup note.
        [$reporthtml, $debughtml] = $renderer->report_html($report, false);
        $this->assertStringNotContainsString('facetoface_signup_manage_custom_field_edit_all', $reporthtml);
    }

    public function test_view_attendees_list_as_an_editingtrainer_role() {
        global $PAGE;
        $PAGE->set_url('/');

        $this->setAdminUser();

        $e = $this->create_seminar_event();
        $s = new seminar_session();
        $s->set_sessionid($e->get_id());
        $s->set_timestart(time() + 3600);
        $s->set_timefinish(time() + 7200);
        $s->save();

        $gen = $this->getDataGenerator();
        for ($i = 0; $i < 5; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $e->get_seminar()->get_course(), 'student');

            $signup = signup::create($user->id, $e);
            $signup->save();

            $signup->switch_state(booked::class);
        }

        $cm = get_coursemodule_from_instance('facetoface', $e->get_facetoface(), $e->get_seminar()->get_course());
        $context = context_module::instance($cm->id);
        $PAGE->set_context($context);

        $trainer = $gen->create_user();
        $gen->enrol_user($trainer->id, $e->get_seminar()->get_course(), 'editingteacher');
        $this->setUser($trainer);

        $cfg = new rb_config();
        $cfg->set_embeddata(
            [
                'sessionid' => $e->get_id(),
                'status' => attendance_state::get_all_attendance_code_with([booked::class, not_set::class])
            ]
        );

        $report = reportbuilder::create_embedded('facetoface_sessions', $cfg);

        $renderer = $PAGE->get_renderer('totara_reportbuilder');
        [$reporthtml, $debughtml] = $renderer->report_html($report, false);

        // Expecting the column 'actions' to appear in the $reporthtml, because the user has the permission to manage
        // the attendee's singup notes.
        $this->assertStringContainsString('facetoface_signup_manage_custom_field_edit_all', $reporthtml);
    }
}