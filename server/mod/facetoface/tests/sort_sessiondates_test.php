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

use mod_facetoface\{seminar, seminar_event, seminar_session};

class mod_facetoface_sort_sessiondates_testcase extends advanced_testcase {
    /**
     * @return seminar_event
     */
    private function create_seminar_event(): seminar_event {
        $gen = static::getDataGenerator();
        $course = $gen->create_course();

        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $seminar = new seminar($f2f->id);
        $seminar->set_attendancetime(seminar::ATTENDANCE_TIME_END);
        $seminar->save();

        $event = new seminar_event();
        $event->set_facetoface($seminar->get_id());
        $event->save();

        return $event;
    }

    /**
     * @return void
     */
    public function test_sort_sessiondates_with_associated_keys(): void {
        static::setAdminUser();

        $event = $this->create_seminar_event();
        $time = time();

        $records = [
            [
                'start' => $time - (3600 * 6),
                'finish' => $time - (3600 * 5)
            ],
            [
                'start' => $time - (3600 * 4),
                'finish' => $time - (3600 * 3)
            ],
            [
                'start' => $time - (3600 * 2),
                'finish' => $time - (3600 * 1)
            ]
        ];

        /** @var seminar_session[] $actualitems */
        $actualitems = [];

        foreach ($records as $record) {
            $s = new seminar_session();
            $s->set_timestart($record['start']);
            $s->set_timefinish($record['finish']);
            $s->set_sessionid($event->get_id());
            $s->save();

            $actualitems[] = $s;
        }

        $sessions = $event->get_sessions();

        $last = $sessions->get_last();
        $first = $sessions->get_first();

        // Calling get_first and get_last from seminar_session_list will cause the list to sort order of the sessions.
        // However, the associated keys of sorted list should be indexed and maintained.
        /** @var seminar_session $resultfirst */
        $resultfirst = $sessions->get($first->get_id());
        static::assertNotNull($resultfirst);
        static::assertEquals($first->get_timestart(), $resultfirst->get_timestart());
        static::assertEquals($first->get_timefinish(), $resultfirst->get_timefinish());

        /** @var seminar_session $resultlast */
        $resultlast = $sessions->get($last->get_id());
        static::assertNotNull($resultlast);
        static::assertEquals($last->get_timestart(), $resultlast->get_timestart());
        static::assertEquals($last->get_timefinish(), $resultlast->get_timefinish());

        // Start assertion the whole assertions of list. After sorted.
        foreach ($actualitems as $expecteditem) {
            /** @var seminar_session $result */
            $result = $sessions->get($expecteditem->get_id());

            static::assertNotNull($result);
            static::assertEquals($expecteditem->get_timestart(), $result->get_timestart());
            static::assertEquals($expecteditem->get_timefinish(), $result->get_timefinish());
        }
    }
}