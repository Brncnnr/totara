<?php
/*
 * This file is part of Totara LMS
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

/*
 * Testing of send notification tasks
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}
global $CFG;
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/tests/facetoface_testcase.php');

class mod_facetoface_send_notification_task_testcase extends mod_facetoface_facetoface_testcase {
    /**
     * Test simple run
     */
    public function test_send_notifications_task() {
        $cron = new \mod_facetoface\task\send_notifications_task();
        $cron->testing = true;
        $cron->execute();
        $this->executeAdhocTasks();
    }

    /**
     * Test manual notifications
     *
     * @dataProvider data_provider_test_sending
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_send_manual_notifications(bool $site_allow_legacy, bool $use_legacy) {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $seed = $this->seed_data($use_legacy);

        $sink = $this->redirectEmails();
        $cron = new \mod_facetoface\task\send_notifications_task();
        $cron->testing = true;

        // Signup, and clear automated message (booking confirmation).
        $signup = \mod_facetoface\signup::create($seed['users'][0]->id, $seed['seminarevent']);
        \mod_facetoface\signup_helper::signup($signup);
        $cron->execute();
        $this->executeAdhocTasks();

        $sink->clear();

        // Make notification manual
        $notificationrec = $DB->get_record('facetoface_notification', ['conditiontype'=> 32]);
        $notificationrec->type = MDL_F2F_NOTIFICATION_MANUAL;
        $notificationrec->issent = 0;
        $notificationrec->status = 1;
        $notificationrec->booked = 1;
        $notificationrec->title = 'TEST';
        $DB->update_record('facetoface_notification', $notificationrec);

        $cron->execute();
        $this->executeAdhocTasks();

        // Manual notifications (adhoc messages) in facetoface_notification are sent even if the site doesn't allow legacy notifications.
        // This is to allow notifications queued BEFORE switching systems

        $messages = $sink->get_messages();
        $sink->clear();

        $this->assertCount(1, $messages);
        $message = current($messages);
        $this->assertEquals('TEST', $message->subject);
        $this->assertEquals('test@example.com', $message->to);

        // Confirm that messages sent only once
        $cron->execute();
        $this->executeAdhocTasks();
        $this->assertEmpty($sink->get_messages());
        $sink->close();
    }

    /**
     * Test scheduled notifications
     *
     * @dataProvider data_provider_test_sending
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_send_scheduled_notifications(bool $site_allow_legacy, bool $use_legacy) {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $seed = $this->seed_data($use_legacy);

        $sink = $this->redirectEmails();
        $cron = new \mod_facetoface\task\send_notifications_task();
        $cron->testing = true;

        // Signup, and clear automated message (booking confirmation).
        $signup = \mod_facetoface\signup::create($seed['users'][0]->id, $seed['seminarevent']);
        \mod_facetoface\signup_helper::signup($signup);

        // Move it back in time a bit.
        $DB->execute(
            "UPDATE {facetoface_signups_status} SET timecreated = :timecreated ",
            ['timecreated' => time()-100]
            );
        $cron->execute();
        $this->executeAdhocTasks();
        $sink->clear();

        // Make notification manual
        $notificationrec = $DB->get_record('facetoface_notification', ['conditiontype'=> 32]);
        $notificationrec->type = MDL_F2F_NOTIFICATION_SCHEDULED;
        $notificationrec->scheduletime = DAYSECS+2;
        $notificationrec->conditiontype = MDL_F2F_CONDITION_BEFORE_SESSION;
        $notificationrec->issent = 0;
        $notificationrec->status = 1;
        $notificationrec->booked = 1;
        $notificationrec->title = 'TEST';
        $DB->update_record('facetoface_notification', $notificationrec);
        $cron->execute();
        $this->executeAdhocTasks();

        if (!$site_allow_legacy || ($site_allow_legacy && !$use_legacy)) {
            $this->assertEmpty($sink->get_messages());
            $sink->close();
            return;
        }

        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertCount(1, $messages);
        $message = current($messages);
        $this->assertEquals('TEST', $message->subject);
        $this->assertEquals('test@example.com', $message->to);

        // Confirm that messages sent only once
        $cron->execute();
        $this->executeAdhocTasks();
        $this->assertEmpty($sink->get_messages());
        $sink->close();
    }

    /**
     * Test custom scheduled notification is sent for more han one session
     */
    public function test_send_custom_scheduled_notification() {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', 1);
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        // Create data needed for the test (PART 1).
        // * A course with a Seminar and an event that starts in an hour.
        // * 2 users to later add them as attendees in the events.
        // * A custom scheduled notification to be send 1 hour before the event start
        // * An event that starts in the next hour.
        // Now proceed to to add user1 as attendee in the event previously created.
        // Run send_notifications_task task and check user1 has received the custom notification.

        // Course, Seminar and learners.
        $course1 = $this->getDataGenerator()->create_course();
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course1->id, 'multiplesessions' => 1));
        $user1 = $this->getDataGenerator()->create_user(array('firstname' => 'user1'));
        $user2 = $this->getDataGenerator()->create_user(array('firstname' => 'user2'));
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);

        // Event1 to start in an hour.
        $sessiondate1 = new stdClass();
        $sessiondate1->timestart = time() + (HOURSECS * 1);
        $sessiondate1->timefinish = time() + (HOURSECS * 2);
        $sessiondate1->sessiontimezone = 'Australia/Sydney';

        $sessiondata1 = array(
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate1),
        );
        $sessionid1 = $facetofacegenerator->add_session($sessiondata1);
        $seminarevent1 = new \mod_facetoface\seminar_event($sessionid1);

        $sink = $this->redirectEmails();
        $cron = new \mod_facetoface\task\send_notifications_task();
        $cron->testing = true;

        // Add user1 as attendee in event1.
        $signup = \mod_facetoface\signup::create($user1->id, $seminarevent1);
        \mod_facetoface\signup_helper::signup($signup);

        $cron->execute();
        $this->executeAdhocTasks();
        $sink->clear();

        // Custom scheduled notification to be send 1 hour before the event start to booked.
        $notification1 = new facetoface_notification();
        $notification1->courseid = $course1->id;
        $notification1->facetofaceid = $facetoface->id;
        $notification1->ccmanager = 0;
        $notification1->status = 1;
        $notification1->title = 'Custom notification 1 hour before';
        $notification1->body = get_string('placeholder:firstname', 'facetoface').' 1 hours before';
        $notification1->managerprefix = '';
        $notification1->type = MDL_F2F_NOTIFICATION_SCHEDULED;
        $notification1->conditiontype = MDL_F2F_CONDITION_BEFORE_SESSION;
        $notification1->scheduleunit = MDL_F2F_SCHEDULE_UNIT_HOUR;
        $notification1->scheduleamount = 1;
        $notification1->recipients = json_encode(
            [
                'past_events' => 0,
                'events_in_progress' => 0,
                'upcoming_events' => 1,
                'fully_attended' => 0,
                'partially_attended' => 0,
                'unable_to_attend' => 0,
                'no_show' => 0,
                'waitlisted' => 0,
                'user_cancelled' => 0,
                'requested' => 0,
            ]
        );
        $notification1->save();

        // Run send_notifications_task task and check user1 has received the custom notification.
        $cron->execute();
        $this->executeAdhocTasks();

        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertCount(1, $messages);
        $message = current($messages);
        $this->assertEquals('Custom notification 1 hour before', $message->subject);
        $this->assertEquals($user1->email, $message->to);

        // Create data needed for the test (PART 2).
        // Create another event that also starts in the next hour (Basically a copy of the first event)
        $sessiondate2 = new stdClass();
        $sessiondate2->timestart = time() + (HOURSECS * 1);
        $sessiondate2->timefinish = time() + (HOURSECS * 2);
        $sessiondate2->sessiontimezone = 'Australia/Sydney';

        $sessiondata2 = array(
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate2),
        );
        $sessionid2 = $facetofacegenerator->add_session($sessiondata2);
        $seminarevent2 = new \mod_facetoface\seminar_event($sessionid2);

        // Move back the notification time window a bit so the custom notification is not sent when user signup.
        $DB->execute(
            "UPDATE {facetoface_notification} SET scheduletime = :scheduletime WHERE id = :notificationid",
            ['scheduletime' => 30 * MINSECS, 'notificationid' => $notification1->id]
        );

        // Now proceed to add user1 as attendee in the second event.
        // Run send_notifications_task task and check user1 has received the custom notification.
        $signup = \mod_facetoface\signup::create($user1->id, $seminarevent2);
        $signup->set_ignoreconflicts();
        \mod_facetoface\signup_helper::signup($signup);

        $cron->execute();
        $this->executeAdhocTasks();
        $sink->clear();

        // Move forward the notification time window again so the custom notification is  sent.
        $DB->execute(
            "UPDATE {facetoface_notification} SET scheduletime = :scheduletime WHERE id = :notificationid",
            ['scheduletime' => HOURSECS, 'notificationid' => $notification1->id]
        );

        $cron->execute();
        $this->executeAdhocTasks();
        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertCount(1, $messages);
        $message = current($messages);
        $this->assertEquals('Custom notification 1 hour before', $message->subject);
        $this->assertEquals($user1->email, $message->to);

        // Move back the notification time window a bit so the custom notification is not sent when user signup.
        $DB->execute(
            "UPDATE {facetoface_notification} SET scheduletime = :scheduletime WHERE id = :notificationid",
            ['scheduletime' => 30 * MINSECS, 'notificationid' => $notification1->id]
        );

        // Now proceed to add user2 as attendee in the second event.
        // Run send_notifications_task task and check user2 has received the custom notification.
        $signup = \mod_facetoface\signup::create($user2->id, $seminarevent2);
        \mod_facetoface\signup_helper::signup($signup);

        $cron->execute();
        $this->executeAdhocTasks();
        $sink->clear();

        // Move forward the notification time window again so the custom notification is  sent.
        $DB->execute(
            "UPDATE {facetoface_notification} SET scheduletime = :scheduletime WHERE id = :notificationid",
            ['scheduletime' => HOURSECS, 'notificationid' => $notification1->id]
        );

        $cron->execute();
        $this->executeAdhocTasks();
        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertCount(1, $messages);
        $message = current($messages);
        $this->assertEquals('Custom notification 1 hour before', $message->subject);
        $this->assertEquals($user2->email, $message->to);
    }

    /**
     * Test registration ended
     *
     * @dataProvider data_provider_test_sending
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_registration_ended(bool $site_allow_legacy, bool $use_legacy) {
        global $CFG, $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $seed = $this->seed_data($use_legacy);

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $CFG->facetoface_session_rolesnotify = $teacherrole->id;

        // Add user to session with role that will receive expiring notification
        $sessrole = new stdClass();
        $sessrole->roleid = $teacherrole->id;
        $sessrole->sessionid = $seed['seminarevent']->get_id();
        $sessrole->userid = $seed['users'][0]->id;
        $DB->insert_record('facetoface_session_roles', $sessrole);

        $time = time();

        // Set last cron run timestamp before registration expired.
        $conditions = array('component' => 'mod_facetoface', 'classname' => '\mod_facetoface\task\send_notifications_task');
        $DB->set_field('task_scheduled', 'lastruntime', $time-100, $conditions);
        $DB->set_field('facetoface_sessions', 'registrationtimefinish', $time-50, ['id' => $seed['seminarevent']->get_id()]);

        $notificationrec = $DB->get_record('facetoface_notification', ['conditiontype'=> MDL_F2F_CONDITION_REGISTRATION_DATE_EXPIRED]);
        $notificationrec->title = 'TEST';
        $DB->update_record('facetoface_notification', $notificationrec);

        $sink = $this->redirectEmails();
        $helper = new \mod_facetoface\notification\notification_helper();
        $helper->notify_registration_ended();
        $this->executeAdhocTasks();

        if (!$site_allow_legacy || ($site_allow_legacy && !$use_legacy)) {
            $this->assertEmpty($sink->get_messages());
            $sink->close();
            return;
        }

        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertCount(1, $messages);
        $message = current($messages);
        $this->assertEquals('TEST', $message->subject);
        $this->assertEquals('test@example.com', $message->to);

        // Confirm that messages not sent again
        $helper->notify_registration_ended();
        $this->executeAdhocTasks();
        $this->assertEmpty($sink->get_messages());
        $sink->close();

    }

    /**
     * Test of cleaning reservations after dead line
     *
     * @dataProvider data_provider_test_sending
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_remove_reservations_after_deadline (bool $site_allow_legacy, bool $use_legacy) {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $seed = $this->seed_data($use_legacy);

        \mod_facetoface\reservations::add($seed['seminarevent'], $seed['users'][0]->id, 1, 0);

        $DB->set_field('facetoface', 'reservecanceldays', 2, ['id' => $seed['seminarevent']->get_facetoface()]);

        $notificationrec = $DB->get_record('facetoface_notification', ['conditiontype'=> MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED]);
        $notificationrec->title = 'TEST';
        $DB->update_record('facetoface_notification', $notificationrec);

        $sink = $this->redirectEmails();
        \mod_facetoface\reservations::remove_after_deadline(true);
        $this->executeAdhocTasks();

        if (!$site_allow_legacy || ($site_allow_legacy && !$use_legacy)) {
            $this->assertEmpty($sink->get_messages());
            $sink->close();
            return;
        }

        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertCount(1, $messages);
        $message = current($messages);
        $this->assertEquals('TEST', $message->subject);
        $this->assertEquals('test@example.com', $message->to);

        // Confirm that messages not sent again
        ob_start();
        \mod_facetoface\reservations::remove_after_deadline(true);
        $this->executeAdhocTasks();
        ob_get_clean();
        $this->assertEmpty($sink->get_messages());
        $sink->close();
    }

    /**
     * Prepare course, seminar, event, session, three users enrolled on course.
     * @param bool $use_legacy
     */
    protected function seed_data(bool $use_legacy = true) {
        $course1 = $this->getDataGenerator()->create_course();
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course1->id,
            'legacy_notifications' => (int)$use_legacy
        );
        $facetoface1 = $facetofacegenerator->create_instance($facetofacedata);

        // Session that starts in 24hrs time.
        // This session should trigger a mincapacity warning now as cutoff is 24:01 hrs before start time.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $sessiondata = array(
            'facetoface' => $facetoface1->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $student1 = $this->getDataGenerator()->create_user(['email' => 'test@example.com']);
        $student2 = $this->getDataGenerator()->create_user();
        $student3 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student3->id, $course1->id, 'student');

        return [
            'course' => $course1,
            'seminarevent' => new \mod_facetoface\seminar_event($sessionid),
            'users' => [$student1, $student2, $student3]
        ];
    }

    public function data_provider_test_sending(): array {
        return [
            [true, true],
            [true, false],
            [false, true],
            [false, false],
        ];
    }

}