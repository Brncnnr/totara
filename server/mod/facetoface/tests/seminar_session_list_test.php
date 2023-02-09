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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\{seminar, seminar_event, seminar_session, seminar_session_list};

class mod_facetoface_seminar_session_list_testcase extends advanced_testcase {
    /**
     * Create seminar_event with default setting of seminar
     * @return seminar_event
     */
    private function create_seminar_event(): seminar_event {
        $gen = $this->getDataGenerator();

        $course = $gen->create_course();
        $f2f = new seminar();
        $f2f->set_course($course->id);
        $f2f->save();

        $event = new seminar_event();
        $event->set_facetoface($f2f->get_id());
        $event->save();
        return $event;
    }

    /**
     * Provide test data for session's list calculation.
     * @return array
     */
    public function provide_session_data(): array {
        $time = time();
        return [
            [
                [
                    ['start' => $time + 3600, 'finish' => $time + (3600 * 2)],
                    ['start' => $time + (3600 * 4), 'finish' => $time + (3600 * 5)],
                    ['start' => $time + (3600 * 7), 'finish' => $time + (3600 * 8)],
                ],
                $time,
                [
                    'over' => 0,
                    'upcoming' => 3
                ]
            ],
            [
                [
                    ['start' => $time + 3600, 'finish' => $time + (3600 * 2)],
                    ['start' => $time + (3600 * 4), 'finish' => $time + (3600 * 5)],
                    ['start' => $time + (3600 * 7), 'finish' => $time + (3600 * 8)],
                ],
                $time + (3600 * 3),
                [
                    'over' => 1,
                    'upcoming' => 2
                ]
            ],
            [
                [
                    ['start' => $time + 3600, 'finish' => $time + (3600 * 2)],
                    ['start' => $time + (3600 * 4), 'finish' => $time + (3600 * 5)],
                    ['start' => $time + (3600 * 7), 'finish' => $time + (3600 * 8)],
                ],
                $time + (3600 * 10),
                [
                    'over' => 3,
                    'upcoming' => 0
                ]
            ],
            [
                // Waitlisted event
                [],
                $time,
                ['over' => 0, 'upcoming' => 0]
            ]
        ];
    }

    /**
     * Test suite of calculating the number of sessions that either upcoming or over.
     * @dataProvider provide_session_data
     * @param array $times
     * @param int $currenttime
     * @param array $result
     */
    public function test_calculate_session(array $times, int $currenttime, array $result): void {

        $event = $this->create_seminar_event();
        foreach ($times as $time) {
            $session = new seminar_session();
            $session->set_sessionid($event->get_id());
            $session->set_timestart($time['start']);
            $session->set_timefinish($time['finish']);
            $session->save();
        }

        $sessions = $event->get_sessions();

        $this->assertEquals($result['over'], $sessions->count_over($currenttime));
        $this->assertEquals($result['upcoming'], $sessions->count_upcoming($currenttime));
    }

    /**
     * Ensure that the get/count/is/to functions return something meaningful when the list is empty
     */
    public function test_readonly_functions_should_cope_with_empty_list() {
        // Create an empty seminar_session_list
        $list = new seminar_session_list();

        $this->assertSame(0, $list->count_upcoming());
        $this->assertSame(0, $list->count_over());
        $this->assertCount(0, $list->get_upcoming());
        $this->assertCount(0, $list->get_over());
        $this->assertFalse($list->is_everything_over());
        $this->assertNotEmpty($list->get_summary());
        $this->assertNull($list->get_last());
        $this->assertNull($list->get_first());
        $this->assertCount(0, $list->to_records());
        $list->sort('id', seminar_session_list::SORT_ASC);
        $list->sort('id', seminar_session_list::SORT_DESC);

        try {
            $list->sort('lorem#ipsum');
            $this->fail('coding_exception expected');
        } catch (coding_exception $e) {
        }
    }

    /**
     * Ensure that (get|count)_(over|upcoming) functions return up-to-date results without reload()
     * Also ensure that these functions do not access the database
     */
    public function test_over_and_upcoming_should_be_coherent() {
        $now = time();
        // Do not add these records to the database
        // Everything should be successful without any database access
        $list = seminar_session_list::from_records([
            (object)[
                'id' => 1,
                'timestart' => $now - HOURSECS * 2 - 1,
                'timefinish' => $now - HOURSECS - 1
            ],
            (object)[
                'id' => 2,
                'timestart' => $now + HOURSECS + 1,
                'timefinish' => $now + HOURSECS * 2 + 1
            ]
        ]);

        $this->assertCount(1, $list->get_over($now));
        $this->assertSame(1, $list->count_over($now));
        $this->assertCount(1, $list->get_upcoming($now));
        $this->assertSame(1, $list->count_upcoming($now));

        // Make sure that seminar_session_list does not cache results of outdated $time
        $this->assertCount(0, $list->get_over($now - HOURSECS * 2));
        $this->assertSame(0, $list->count_over($now - HOURSECS * 2));
        $this->assertCount(0, $list->get_upcoming($now + HOURSECS * 3));
        $this->assertSame(0, $list->count_upcoming($now + HOURSECS * 3));
    }

    /**
     * Ensure that get_first/get_last do not trash the internal structure of seminar_session_list
     */
    public function test_get_over_and_upcoming_inside_foreach_loop() {
        $now = time();
        $times = [ // ain't a magazine
            $now - HOURSECS * 4,
            $now - HOURSECS,
            $now + HOURSECS * 2,
        ];

        $event = $this->create_seminar_event();
        foreach ($times as $time) {
            (new seminar_session())
                ->set_sessionid($event->get_id())
                ->set_timestart($time)
                ->set_timefinish($time + HOURSECS * 2)
                ->save();
        }

        $sessions = $event->get_sessions()->sort('timestart', seminar_session_list::SORT_ASC);
        $count = 0;

        /** @var seminar_session $session */
        foreach ($sessions as $session) {
            $this->assertEquals($times[$count], $session->get_timestart());
            $sessions->get_first($now);
            $sessions->get_last($now);
            $count++;
        }

        $this->assertSame(3, $count);
    }

    public function test_has_date_changed(): void {
        $old_date = (object)[
            'timestart' => 111,
            'timefinish' => 222,
            'sessiontimezone' => 333,
            'roomids' => [1, 2],
            'facilitatorids' => [3, 4],
        ];
        $new_date = clone $old_date;
        self::assertFalse(seminar_session_list::has_date_changed($old_date, $new_date));

        $new_date->timestart ++;
        self::assertTrue(seminar_session_list::has_date_changed($old_date, $new_date));

        $new_date = clone $old_date;
        $new_date->timefinish ++;
        self::assertTrue(seminar_session_list::has_date_changed($old_date, $new_date));

        $new_date = clone $old_date;
        $new_date->sessiontimezone ++;
        self::assertTrue(seminar_session_list::has_date_changed($old_date, $new_date));

        $new_date = clone $old_date;
        $new_date->roomids = [5];
        self::assertTrue(seminar_session_list::has_date_changed($old_date, $new_date));

        $new_date = clone $old_date;
        $new_date->facilitatorids = [6];
        self::assertTrue(seminar_session_list::has_date_changed($old_date, $new_date));
    }
}
