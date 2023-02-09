<?php
/**
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

use mod_facetoface\facilitator_helper;
use mod_facetoface\facilitator_user;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\seminar_session;
use mod_facetoface\seminar_session_list;

class mod_facetoface_notify_facilitator_testcase extends advanced_testcase {
    /** @var \mod_facetoface\testing\generator */
    private $f2fgen;

    /** @var stdClass */
    private $course;

    /** @var stdClass */
    private $f2f;

    /** @var stdClass */
    private $user1;

    /** @var stdClass */
    private $user2;

    /** @var stdClass */
    private $user3;

    /** @var facilitator_user */
    private $fac1;

    /** @var facilitator_user */
    private $fac2;

    /** @var facilitator_user */
    private $fac3;

    /** @var facilitator_user */
    private $fac4;

    /** @var seminar_event */
    private $seminarevent;

    /** @var stdClass[] */
    private $sessiondates;

    /** @var \core_phpunit\message_sink */
    private $sink;

    public function setUp(): void {
        set_config('facetoface_allow_legacy_notifications', 1);

        $gen = $this->getDataGenerator();
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $this->f2fgen = $f2fgen;
        /** @var \mod_facetoface\testing\generator $f2fgen */

        $this->redirect_messages();
        $this->course = $gen->create_course();
        $this->f2f = $f2fgen->create_instance(['course' => $this->course->id]);
        $this->user1 = $gen->create_user();
        $this->user2 = $gen->create_user();
        $this->user3 = $gen->create_user();

        $eventid = $f2fgen->add_session([
            'facetoface' => $this->f2f->id,
            'capacity' => 5,
            'sessiondates' => [
                (object)[
                    'sessiontimezone' => '99',
                    'timestart' => strtotime('2 Feb next year 2am'),
                    'timefinish' => strtotime('2 Feb next year 2pm'),
                ],
                (object)[
                    'sessiontimezone' => '99',
                    'timestart' => strtotime('4 Apr next year 4am'),
                    'timefinish' => strtotime('4 Apr next year 4pm'),
                ],
                (object)[
                    'sessiontimezone' => '99',
                    'timestart' => strtotime('6 Jun next year 6am'),
                    'timefinish' => strtotime('6 Jun next year 6pm'),
                ]
            ],
        ]);
        $this->seminarevent = new seminar_event($eventid);
        $this->sessiondates = seminar_session_list::from_seminar_event($this->seminarevent)->sort('timestart')->to_records(false);
        $this->assertCount(3, $this->sessiondates);
        $this->fac1 = new facilitator_user($f2fgen->add_internal_facilitator([], $this->user1));
        $this->fac2 = new facilitator_user($f2fgen->add_internal_facilitator([], $this->user2));
        $this->fac3 = new facilitator_user($f2fgen->add_custom_facilitator([]));
        $this->fac4 = new facilitator_user($f2fgen->add_site_wide_facilitator([]));

        $this->executeAdhocTasks();

        // Make sure no notifications are sent at the moment.
        $messages = $this->get_messages();
        $this->assertEmpty($messages);
    }

    public function tearDown(): void {
        $this->f2fgen = null;
        $this->course = null;
        $this->f2f = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->user3 = null;
        $this->fac1 = null;
        $this->fac2 = null;
        $this->fac3 = null;
        $this->fac4 = null;
        $this->seminarevent = null;
        $this->sessiondates = null;
    }

    /**
     * Start message redirection.
     */
    private function redirect_messages() {
        if ($this->sink) {
            throw new coding_exception('do not call redirect_messages again');
        }
        $this->sink = $this->redirectMessages();
    }

    /**
     * Finish message redirection and return messages received.
     *
     * @return stdClass[]
     */
    private function get_messages(): array {
        if (!$this->sink) {
            throw new coding_exception('get_messages is called prior to redirect_messages');
        }
        $this->sink->close();
        $messages = $this->sink->get_messages();
        usort($messages, function ($x, $y) {
            return strcmp($x->subject, $y->subject) ?: ((int)$x->useridto <=> (int)$y->useridto);
        });
        $this->sink = null;
        return $messages;
    }

    /**
     * Assign facilitators without sending notifications.
     */
    private function assign_facilitators_quietly() {
        $this->redirect_messages();
        $facs = [$this->fac1->get_id(), $this->fac2->get_id(), $this->fac3->get_id(), $this->fac4->get_id()];
        facilitator_helper::sync($this->sessiondates[0]->id, $facs);
        facilitator_helper::sync($this->sessiondates[1]->id, $facs);
        $this->sessiondates[0]->facilitatorids = $facs;
        $this->sessiondates[1]->facilitatorids = $facs;
        $this->executeAdhocTasks();

        // Make sure no notifications are sent at the moment.
        $messages = $this->get_messages();
        $this->assertEmpty($messages);
    }

    /**
     * Change session dates without sending notifications.
     *
     * @param array $dates
     */
    private function change_session_date_quietly(array $dates) {
        $this->redirect_messages();
        for ($i = 0; $i < 3; $i++) {
            $this->sessiondates[$i]->timestart = $dates[$i][0];
            $this->sessiondates[$i]->timefinish = $dates[$i][1];
            $sess = new seminar_session();
            $sess->from_record($this->sessiondates[$i], false);
            $sess->save();
        }
        $this->executeAdhocTasks();

        // Make sure no notifications are sent at the moment.
        $messages = $this->get_messages();
        $this->assertEmpty($messages);
    }

    public function data_dates(): array {
        return [
            'future' => [[
                [
                    strtotime('2 Feb next year 2am'),
                    strtotime('2 Feb next year 2pm'),
                ],
                [
                    strtotime('4 Apr next year 4am'),
                    strtotime('4 Apr next year 4pm'),
                ],
                [
                    strtotime('6 Jun next year 6am'),
                    strtotime('6 Jun next year 6pm'),
                ],
            ]],
            'present' => [[
                [
                    strtotime('2 Feb last year 2am'),
                    strtotime('2 Feb next year 2pm'),
                ],
                [
                    strtotime('4 Apr last year 4am'),
                    strtotime('4 Apr next year 4pm'),
                ],
                [
                    strtotime('6 Jun last year 6am'),
                    strtotime('6 Jun next year 6pm'),
                ],
            ]],
            'past' => [[
                [
                    strtotime('2 Feb last year 2am'),
                    strtotime('2 Feb last year 2pm'),
                ],
                [
                    strtotime('4 Apr last year 4am'),
                    strtotime('4 Apr last year 4pm'),
                ],
                [
                    strtotime('6 Jun last year 6am'),
                    strtotime('6 Jun last year 6pm'),
                ],
            ]],
        ];
    }

    public function data_dates_future(): array {
        // Future only.
        $array = $this->data_dates();
        $array['future'][] = true;
        $array['present'][] = false;
        $array['past'][] = false;
        return $array;
    }

    public function data_dates_ongoing(): array {
        // Future and present.
        $array = $this->data_dates();
        $array['future'][] = true;
        $array['present'][] = true;
        $array['past'][] = false;
        return $array;
    }

    /**
     * @param array $dates
     * @param boolean $notice
     * @dataProvider data_dates_future
     */
    public function test_event_is_cancelled(array $dates, bool $notice) {
        $this->assign_facilitators_quietly();
        $this->change_session_date_quietly($dates);
        $this->redirect_messages();
        $this->seminarevent->cancel();
        $this->executeAdhocTasks();

        // Make sure two notifications are sent, one for each facilitator user.
        $messages = $this->get_messages();
        if ($notice) {
            $this->assertCount(2, $messages);
            $this->assertEquals($this->user1->id, $messages[0]->useridto);
            $this->assertStringContainsString('Seminar session facilitator cancellation', $messages[0]->subject);
            $this->assertEquals($this->user2->id, $messages[1]->useridto);
            $this->assertStringContainsString('Seminar session facilitator cancellation', $messages[1]->subject);
        } else {
            $this->assertEmpty($messages);
        }
    }

    /**
     * @param array $dates
     * @param boolean $notice
     * @dataProvider data_dates_ongoing
     */
    public function test_event_is_deleted(array $dates, bool $notice) {
        $this->assign_facilitators_quietly();
        $this->change_session_date_quietly($dates);
        $this->redirect_messages();
        $this->seminarevent->delete();
        $this->executeAdhocTasks();

        // Make sure two notifications are sent, one for each facilitator user.
        $messages = $this->get_messages();
        if ($notice) {
            $this->assertCount(2, $messages);
            $this->assertEquals($this->user1->id, $messages[0]->useridto);
            $this->assertStringContainsString('Seminar session facilitator cancellation', $messages[0]->subject);
            $this->assertEquals($this->user2->id, $messages[1]->useridto);
            $this->assertStringContainsString('Seminar session facilitator cancellation', $messages[1]->subject);
        } else {
            $this->assertEmpty($messages);
        }
    }

    /**
     * @param array $dates
     * @param boolean $notice
     * @dataProvider data_dates_ongoing
     */
    public function test_session_is_deleted(array $dates, bool $notice) {
        $this->assign_facilitators_quietly();
        $this->change_session_date_quietly($dates);
        $dates = $this->sessiondates;
        $deleted = current(array_splice($dates, 0, 1));
        $this->redirect_messages();
        seminar_event_helper::merge_sessions($this->seminarevent, $dates);
        $this->executeAdhocTasks();

        // Make sure two notifications are sent, one for each facilitator user.
        $messages = $this->get_messages();
        if ($notice) {
            $this->assertCount(2, $messages);
            $this->assertEquals($this->user1->id, $messages[0]->useridto);
            $this->assertStringContainsString('Seminar session facilitator cancellation', $messages[0]->subject);
            $this->assertEquals($this->user2->id, $messages[1]->useridto);
            $this->assertStringContainsString('Seminar session facilitator cancellation', $messages[1]->subject);
        } else {
            $this->assertEmpty($messages);
        }
    }

    /**
     * @param array $dates
     * @param boolean $notice
     * @dataProvider data_dates_ongoing
     */
    public function test_facilitator_assigned(array $dates, bool $notice) {
        $this->change_session_date_quietly($dates);
        $this->redirect_messages();
        $dates = $this->sessiondates;
        $dates[0]->facilitatorids = [$this->fac1->get_id(), $this->fac3->get_id(), $this->fac4->get_id()];
        seminar_event_helper::merge_sessions($this->seminarevent, $dates);
        $this->executeAdhocTasks();

        // Make sure two notifications are sent, one for each facilitator user.
        $messages = $this->get_messages();
        if ($notice) {
            $this->assertCount(1, $messages);
            $this->assertEquals($this->user1->id, $messages[0]->useridto);
            $this->assertStringContainsString('Seminar session facilitator confirmation', $messages[0]->subject);
        } else {
            $this->assertEmpty($messages);
        }
    }

    /**
     * @param array $dates
     * @param boolean $notice
     * @dataProvider data_dates_ongoing
     */
    public function test_facilitator_unassigned(array $dates, bool $notice) {
        $this->assign_facilitators_quietly();
        $this->change_session_date_quietly($dates);
        $this->redirect_messages();
        $dates = $this->sessiondates;
        $dates[0]->facilitatorids = [$this->fac2->get_id(), $this->fac3->get_id(), $this->fac4->get_id()];
        seminar_event_helper::merge_sessions($this->seminarevent, $dates);
        $this->executeAdhocTasks();

        // Make sure two notifications are sent, one for each facilitator user.
        $messages = $this->get_messages();
        if ($notice) {
            $this->assertCount(1, $messages);
            $this->assertEquals($this->user1->id, $messages[0]->useridto);
            $this->assertStringContainsString('Seminar session facilitator unassigned', $messages[0]->subject);
        } else {
            $this->assertEmpty($messages);
        }
    }

    /**
     * @param array $dates
     * @dataProvider data_dates
     */
    public function test_session_time_is_changed(array $dates) {
        $this->assign_facilitators_quietly();
        $this->change_session_date_quietly($dates);
        $this->redirect_messages();
        $dates = $this->sessiondates;
        $dates[0]->timestart = strtotime('8 Aug next year 8am');
        $dates[0]->timefinish = strtotime('8 Aug next year 8pm');
        seminar_event_helper::merge_sessions($this->seminarevent, $dates);
        $this->executeAdhocTasks();

        // Make sure two notifications are sent, one for each facilitator user.
        $messages = $this->get_messages();
        $this->assertCount(2, $messages);
        $this->assertEquals($this->user1->id, $messages[0]->useridto);
        $this->assertStringContainsString('Seminar session date/time changed', $messages[0]->subject);
        $this->assertEquals($this->user2->id, $messages[1]->useridto);
        $this->assertStringContainsString('Seminar session date/time changed', $messages[1]->subject);

        // No notifications when changing the event time to the past.
        $this->redirect_messages();
        $dates = $this->sessiondates;
        $dates[0]->timestart = strtotime('7 Jul last year 7am');
        $dates[0]->timefinish = strtotime('7 Jul last year 7pm');
        seminar_event_helper::merge_sessions($this->seminarevent, $dates);
        $this->executeAdhocTasks();
        $messages = $this->get_messages();
        $this->assertEmpty($messages);
    }
}
