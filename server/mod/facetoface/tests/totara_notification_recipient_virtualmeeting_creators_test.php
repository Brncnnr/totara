<?php
/**
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_facetoface
 * @category totara_notification
 */

use core_phpunit\testcase;
use mod_facetoface\testing\generator as facetoface_generator;
use mod_facetoface\totara_notification\recipient\virtualmeeting_creators;
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use totara_job\job_assignment;
use totara_core\http\clients\simple_mock_client;
use totara_core\virtualmeeting\virtual_meeting as virtual_meeting_model;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_recipient_virtualmeeting_creators_test extends testcase {

    /**
     * Test that the function requires a seminar_event_id
     */
    public function test_missing_event(): void {
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Missing seminar_event_id');
        virtualmeeting_creators::get_user_ids([]);
    }

    /**
     * Test the function returns the given input.
     */
    public function test_result(): void {
        $generator = self::getDataGenerator();

        // Create a base user.
        $user = $generator->create_user(['lastname' => 'User1 last name']);

        // Create a manager.
        $manager = $generator->create_user(['lastname' => 'Manager1 last name']);

        // Assign the manager to the user.
        /** @var job_assignment $manager1job */
        $manager1job = job_assignment::create(['userid' => $manager->id, 'idnumber' => 'job1']);
        job_assignment::create([
            'userid' => $user->id,
            'idnumber' => 'userjob1',
            'managerjaid' => $manager1job->id
        ]);

        // Create a course.
        $course = $generator->create_course(['fullname' => 'The first course']);

        // Enroll users
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create a seminar.
        $f2f_gen = facetoface_generator::instance();
        $f2f = $f2f_gen->create_instance(['course' => $course->id, 'approvaltype' => \mod_facetoface\seminar::APPROVAL_ADMIN]);

        // Create an event.
        $now = time();
        $session_date = new stdClass();
        $session_date->timestart = $now + DAYSECS;
        $session_date->timefinish = $session_date->timestart + (DAYSECS * 2);
        $session_date->sessiontimezone = 'Pacific/Auckland';

        $session = new stdClass();
        $session->facetoface = $f2f->id;
        $session->sessiondates = array($session_date);
        $session->registrationtimestart = $now - 2000;
        $session->registrationtimefinish = $now + 2000;
        $session_id = $f2f_gen->add_session($session);
        $seminar_event = new \mod_facetoface\seminar_event($session_id);

        // Check the recipient class, there should be no users returned.
        $user_ids = virtualmeeting_creators::get_user_ids(['seminar_event_id' => $seminar_event->get_id()]);
        $this->assertEquals([], $user_ids);

        // Now add virtual room.
        $roomname = 'Room1';
        $adminid = get_admin()->id;
        $session =  $seminar_event->get_sessions()->get_first();

        $room = new room($f2f_gen->add_virtualmeeting_room(['name' => $roomname], ['userid' => $adminid, 'plugin' => 'poc_app'])->id);
        $client = new simple_mock_client();
        $vm = virtual_meeting_model::create('poc_app', $adminid, "<POC: $roomname>", DateTime::createFromFormat('U', $session->get_timestart()), DateTime::createFromFormat('U', $session->get_timefinish()), $client);
        $roomdate_vm = (new room_dates_virtualmeeting())->set_roomid($room->get_id())->set_sessionsdateid($session->get_id())->set_virtualmeetingid($vm->id);
        $roomdate_vm->save();

        // Check the recipient class again, there now should be now one users returned.
        $user_ids = virtualmeeting_creators::get_user_ids(['seminar_event_id' => $seminar_event->get_id()]);
        $this->assertEquals([$adminid => $adminid], $user_ids);
    }
}
