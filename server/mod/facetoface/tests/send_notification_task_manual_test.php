<?php
/*
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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/tests/facetoface_testcase.php');

use core_phpunit\phpmailer_sink;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\user_cancelled;
use mod_facetoface\signup\state\waitlisted;
use mod_facetoface\signup_helper;
use mod_facetoface\seminar_event;
use mod_facetoface\task\send_notifications_task;

/*
 * Test custom notifications and combinations of their recipient status and event time settings.
 */
class mod_facetoface_send_notification_task_manual_testcase extends mod_facetoface_facetoface_testcase {

    public function upcoming_recipient_status_data_provider(): array {
        return [
            'upcoming event booked' => [
                ['upcoming_events' => 1],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],
            'upcoming event booked_seminar_cn' => [
                ['upcoming_events' => 1],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => false,
            ],
            'upcoming event booked_site_cn_seminar_legacy' => [
                ['upcoming_events' => 1],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => true,
            ],
            'upcoming event booked_site_cn_seminar_cn' => [
                ['upcoming_events' => 1],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => false,
            ],

            'in progress or past event booked' => [
                [
                    'events_in_progress' => 1,
                    'past_events' => 1,
                ],
                [],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],

            'all booked status' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],
            'all booked status_seminar_cn' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => false,
            ],
            'all booked status_site_cn_seminar_legacy' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => true,
            ],
            'all booked status_site_cn_seminar_cn' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => false,
            ],

            'in progress or past event booked, waitlisted or cancelled' => [
                [
                    'events_in_progress' => 1,
                    'past_events' => 1,
                    'user_cancelled' => 1,
                    'waitlisted' => 1,
                ],
                [],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],

            'upcoming event booked, waitlisted or cancelled' => [
                [
                    'upcoming_events' => 1,
                    'user_cancelled' => 1,
                    'waitlisted' => 1,
                ],
                ['user1', 'user2', 'user3'],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],
            'upcoming event booked, waitlisted or cancelled_seminar_cn' => [
                [
                    'upcoming_events' => 1,
                    'user_cancelled' => 1,
                    'waitlisted' => 1,
                ],
                ['user1', 'user2', 'user3'],
                'site_allow_legacy' => true,
                'use_legacy' => false,
            ],
            'upcoming event booked, waitlisted or cancelled_site_cn_seminar_legacy' => [
                [
                    'upcoming_events' => 1,
                    'user_cancelled' => 1,
                    'waitlisted' => 1,
                ],
                ['user1', 'user2', 'user3'],
                'site_allow_legacy' => false,
                'use_legacy' => true,
            ],
            'upcoming event booked, waitlisted or cancelled_site_cn_seminar_cn' => [
                [
                    'upcoming_events' => 1,
                    'user_cancelled' => 1,
                    'waitlisted' => 1,
                ],
                ['user1', 'user2', 'user3'],
                'site_allow_legacy' => false,
                'use_legacy' => false,
            ],
        ];
    }

    /**
     * @dataProvider upcoming_recipient_status_data_provider
     */
    public function test_send_upcoming_notifications(array $recipient_status_override, array $expected_recipients,
        bool $site_allow_legacy, bool $use_legacy
    ): void {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $seed = $this->seed_data($use_legacy);
        $sink = $seed['sink'];
        $cron = $seed['cron'];

        // Make notification manual - should always be sent
        $notificationrec = $DB->get_record('facetoface_notification', ['conditiontype'=> 32]);
        $notificationrec->type = MDL_F2F_NOTIFICATION_MANUAL;
        $notificationrec->issent = 0;
        $notificationrec->status = 1;
        $notificationrec->recipients = json_encode(
            array_merge(self::all_recipient_status_unset(), $recipient_status_override)
        );
        $notificationrec->title = 'TEST';
        $DB->update_record('facetoface_notification', $notificationrec);

        $this->assert_users_receive_notifications($expected_recipients, $cron, $sink);
    }

    public function past_recipient_status_data_provider(): array {
        return [
            'past event booked' => [
                ['past_events' => 1],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],
            'past event booked_seminar_cn' => [
                ['past_events' => 1],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => false
            ],
            'past event booked_site_cn_seminar_legacy' => [
                ['past_events' => 1],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => true,
            ],
            'past event booked_site_cn_seminar_cn' => [
                ['past_events' => 1],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => false,
            ],

            'in progress or upcoming event booked' => [
                [
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                [],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],

            'all booked status' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],
            'all booked status_seminar_cn' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => false,
            ],
            'all booked status_site_cn_seminar_legacy' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => true,
            ],
            'all booked status_seminar_site_cn_seminar_cn' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => false,
            ],

            'past event booked or cancelled' => [
                [
                    'past_events' => 1,
                    'user_cancelled' => 1,
                ],
                ['user1', 'user2'],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],
            'past event booked or cancelled_seminar_cn' => [
                [
                    'past_events' => 1,
                    'user_cancelled' => 1,
                ],
                ['user1', 'user2'],
                'site_allow_legacy' => true,
                'use_legacy' => false,
            ],
            'past event booked or cancelled_site_cn_seminar_legacy' => [
                [
                    'past_events' => 1,
                    'user_cancelled' => 1,
                ],
                ['user1', 'user2'],
                'site_allow_legacy' => false,
                'use_legacy' => true,
            ],
            'past event booked or cancelled_site_cn_seminar_cn' => [
                [
                    'past_events' => 1,
                    'user_cancelled' => 1,
                ],
                ['user1', 'user2'],
                'site_allow_legacy' => false,
                'use_legacy' => false,
            ],
        ];
    }

    /**
     * @dataProvider past_recipient_status_data_provider
     */
    public function test_send_past_notifications(array $recipient_status_override, array $expected_recipients,
        bool $site_allow_legacy, bool $use_legacy
    ): void {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $seed = $this->seed_data($use_legacy);
        $sink = $seed['sink'];
        $cron = $seed['cron'];

        $sessiondate = $DB->get_record('facetoface_sessions_dates', ['sessionid' => $seed['seminarevent']->get_id()]);
        $sessiondate->timestart = time() - DAYSECS;
        $sessiondate->timefinish = time() - DAYSECS + 60;
        $DB->update_record('facetoface_sessions_dates', $sessiondate);

        // Make notification manual - should always be sent
        $notificationrec = $DB->get_record('facetoface_notification', ['conditiontype'=> 32]);
        $notificationrec->type = MDL_F2F_NOTIFICATION_MANUAL;
        $notificationrec->issent = 0;
        $notificationrec->status = 1;
        $notificationrec->recipients = json_encode(
            array_merge(self::all_recipient_status_unset(), $recipient_status_override)
        );
        $notificationrec->title = 'TEST';
        $DB->update_record('facetoface_notification', $notificationrec);

        $this->assert_users_receive_notifications($expected_recipients, $cron, $sink);
    }

    public function in_progress_recipient_status_data_provider(): array {
        return [
            'in progress booked' => [
                ['events_in_progress' => 1],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],
            'in progress booked_seminar_cn' => [
                ['events_in_progress' => 1],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => false,
            ],
            'in progress booked_site_cn_seminar_legacy' => [
                ['events_in_progress' => 1],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => true,
            ],
            'in progress booked_site_cn_seminar_cn' => [
                ['events_in_progress' => 1],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => false,
            ],

            'past or upcoming event booked' => [
                [
                    'past_events' => 1,
                    'upcoming_events' => 1,
                ],
                [],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],

            'all booked status' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],
            'all booked status_seminar_cn' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => true,
                'use_legacy' => false,
            ],
            'all booked status_site_cn_seminar_legacy' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => true,
            ],
            'all booked status_site_cn_seminar_cn' => [
                [
                    'past_events' => 1,
                    'events_in_progress' => 1,
                    'upcoming_events' => 1,
                ],
                ['user1'],
                'site_allow_legacy' => false,
                'use_legacy' => false,
            ],

            'in progress booked or waitlisted' => [
                [
                    'events_in_progress' => 1,
                    'waitlisted' => 1,
                ],
                ['user1', 'user3'],
                'site_allow_legacy' => true,
                'use_legacy' => true,
            ],
            'in progress booked or waitlisted_seminar_cn' => [
                [
                    'events_in_progress' => 1,
                    'waitlisted' => 1,
                ],
                ['user1', 'user3'],
                'site_allow_legacy' => true,
                'use_legacy' => false,
            ],
            'in progress booked or waitlisted_site_cn_seminar_legacy' => [
                [
                    'events_in_progress' => 1,
                    'waitlisted' => 1,
                ],
                ['user1', 'user3'],
                'site_allow_legacy' => false,
                'use_legacy' => true,
            ],
            'in progress booked or waitlisted_site_cn_seminar_cn' => [
                [
                    'events_in_progress' => 1,
                    'waitlisted' => 1,
                ],
                ['user1', 'user3'],
                'site_allow_legacy' => false,
                'use_legacy' => false,
            ],
        ];
    }

    /**
     * @dataProvider in_progress_recipient_status_data_provider
     */
    public function test_send_in_progress_notifications(array $recipient_status_override, array $expected_recipients,
        bool $site_allow_legacy, bool $use_legacy
    ): void {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $seed = $this->seed_data($use_legacy);
        $sink = $seed['sink'];
        $cron = $seed['cron'];

        $sessiondate = $DB->get_record('facetoface_sessions_dates', ['sessionid' => $seed['seminarevent']->get_id()]);
        $sessiondate->timestart = time() - DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $DB->update_record('facetoface_sessions_dates', $sessiondate);

        // Make notification manual - should always be sent
        $notificationrec = $DB->get_record('facetoface_notification', ['conditiontype'=> 32]);
        $notificationrec->type = MDL_F2F_NOTIFICATION_MANUAL;
        $notificationrec->issent = 0;
        $notificationrec->status = 1;
        $notificationrec->recipients = json_encode(
            array_merge(self::all_recipient_status_unset(), $recipient_status_override)
        );
        $notificationrec->title = 'TEST';
        $DB->update_record('facetoface_notification', $notificationrec);

        $this->assert_users_receive_notifications($expected_recipients, $cron, $sink);
    }

    private static function all_recipient_status_unset(): array {
        return [
            'past_events' => 0,
            'events_in_progress' => 0,
            'upcoming_events' => 0,
            'fully_attended' => 0,
            'partially_attended' => 0,
            'unable_to_attend' => 0,
            'no_show' => 0,
            'waitlisted' => 0,
            'user_cancelled' => 0,
            'requested' => 0,
        ];
    }

    /**
     * @param array $expected_users
     * @param send_notifications_task $cron
     * @param phpmailer_sink $sink
     */
    private function assert_users_receive_notifications(array $expected_users, send_notifications_task $cron, phpmailer_sink $sink): void {
        $cron->execute();
        self::executeAdhocTasks();

        $messages = $sink->get_messages();
        $sink->clear();

        // Make sure only the expected users got the message.
        $this->assertCount(count($expected_users), $messages);
        $actual_users = [];
        foreach ($messages as $message) {
            $this->assertEquals('TEST', $message->subject);
            $actual_users[] = str_replace('@example.com', '', $message->to);
        }
        $this->assertEqualsCanonicalizing($expected_users, $actual_users);

        // Confirm that messages sent only once
        $cron->execute();
        self::executeAdhocTasks();
        $this->assertEmpty($sink->get_messages());
        $sink->close();
    }

    /**
     * Prepare course, seminar, event, session, three users enrolled on course.
     *
     * @param bool $use_legacy
     */
    protected function seed_data(bool $use_legacy = true): array {
        $course1 = self::getDataGenerator()->create_course();
        $facetofacegenerator = self::getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course1->id,
            'legacy_notifications' => (int)$use_legacy,
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
            'capacity' => 1,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $student1 = self::getDataGenerator()->create_user(['email' => 'user1@example.com']);
        $student2 = self::getDataGenerator()->create_user(['email' => 'user2@example.com']);
        $student3 = self::getDataGenerator()->create_user(['email' => 'user3@example.com']);

        self::getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        self::getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');
        self::getDataGenerator()->enrol_user($student3->id, $course1->id, 'student');

        $seminarevent = new seminar_event($sessionid);

        // Signup one user.
        $signup1 = signup::create($student1->id, $seminarevent);
        signup_helper::signup($signup1);
        $this->assertInstanceOf(booked::class, $signup1->get_state());

        // Signup another user and cancel immediately.
        $signup2 = signup::create($student2->id, $seminarevent);
        signup_helper::signup($signup2);
        signup_helper::user_cancel($signup2);
        $this->assertInstanceOf(user_cancelled::class, $signup2->get_state());

        // Have another user on the waiting list.
        $signup3 = signup::create($student3->id, $seminarevent);
        signup_helper::signup($signup3);
        $this->assertInstanceOf(waitlisted::class, $signup3->get_state());

        // Init email sink.
        $sink = self::redirectEmails();
        $cron = new send_notifications_task();
        $cron->testing = true;
        // Clear automated messages (booking confirmation etc.).
        $cron->execute();
        self::executeAdhocTasks();
        $sink->clear();

        return [
            'sink' => $sink,
            'cron' => $cron,
            'course' => $course1,
            'seminarevent' => $seminarevent,
            'users' => [$student1, $student2, $student3]
        ];
    }
}