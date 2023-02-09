<?php
/*
 * This file is part of Totara LMS
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
 * @author Riana Rossouw <riana.rossouw@totara.com>
 * @package mod_facetoface
 */

use core\performance_statistics\collector;
use mod_facetoface\seminar_event;
use mod_facetoface\signup;
use mod_facetoface\signup\condition\user_has_no_conflicts;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup_status;

defined('MOODLE_INTERNAL') || die();

/**
 * A unit test for the user_has_no_conflicts condition.
 *
 * Class mod_facetoface_user_has_no_conflicts_testcase
 */
class mod_facetoface_user_has_no_conflicts_testcase extends advanced_testcase {
    protected function setUp(): void {
        $this->setAdminUser();
        set_config('perfdebug', 15);
    }

    public function test_pass_caching(): void {
        $gen = self::getDataGenerator();
        /** @var mod_facetoface_generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $user = $gen->create_user();

        $course = $gen->create_course();

        $gen->enrol_user($user->id, $course->id);

        $now = time();
        $start_time = $now + DAYSECS;

        // One course, 2 conflicting sessions
        $f2f_1 = $f2fgen->create_instance(['course' => $course->id]);
        $eventid = $f2fgen->add_session(
            [
                'facetoface' => $f2f_1->id,
                'capacity' => 5,
                'sessiondates' => [
                    (object)[
                        'sessiontimezone' => '99',
                        'timestart' => $start_time,
                        'timefinish' => $start_time + (2 * HOURSECS),
                        'roomids' => [],
                        'assetids' => [],
                        'facilitatorids' => [],
                    ],
                ],
            ]
        );
        $seminar_event_1 = new seminar_event($eventid);

        $f2f_2 = $f2fgen->create_instance(['course' => $course->id]);
        $eventid = $f2fgen->add_session(
            [
                'facetoface' => $f2f_2->id,
                'capacity' => 5,
                'sessiondates' => [
                    (object)[
                        'sessiontimezone' => '99',
                        'timestart' => $start_time + HOURSECS,
                        'timefinish' => $start_time + (2 * HOURSECS),
                        'roomids' => [],
                        'assetids' => [],
                        'facilitatorids' => [],
                    ],
                ],
            ]
        );
        $seminar_event_2 = new seminar_event($eventid);

        // First verify caching when there are no conflicts
        $signup_1 = signup::create($user->id, $seminar_event_1)->save();

        $n_db_reads_1 = self::get_num_db_reads();
        $cond1 = new user_has_no_conflicts($signup_1);
        self::assertTrue($cond1->pass());
        $n_db_reads_2 = self::get_num_db_reads();
        self::assertGreaterThan($n_db_reads_1, $n_db_reads_2);
        self::assertTrue($cond1->pass());
        $n_db_reads_3 = self::get_num_db_reads();
        self::assertSame($n_db_reads_3, $n_db_reads_2);

        // Now clear the user_has_no_conflicts cache
        $cache = $cond1->get_cache();
        $cache->purge();
        self::assertTrue($cond1->pass());
        $n_db_reads_4 = self::get_num_db_reads();
        self::assertGreaterThan($n_db_reads_3, $n_db_reads_4);

        // User books for first session and then we check with the conflicting session
        signup_status::create($signup_1, new booked($signup_1))->save();
        $signup_2 = signup::create($user->id, $seminar_event_2)->save();
        $cond2 = new user_has_no_conflicts($signup_2);

        $n_db_reads_20 = self::get_num_db_reads();
        self::assertFalse($cond2->pass());
        $n_db_reads_21 = self::get_num_db_reads();
        self::assertGreaterThan($n_db_reads_20, $n_db_reads_21);
        self::assertFalse($cond2->pass());
        $n_db_reads_22 = self::get_num_db_reads();
        self::assertSame($n_db_reads_21, $n_db_reads_22);

        // Now clear the user_has_no_conflicts cache
        $cache = $cond2->get_cache();
        $cache->purge();
        self::assertFalse($cond2->pass());
        $n_db_reads_23 = self::get_num_db_reads();
        self::assertGreaterThan($n_db_reads_22, $n_db_reads_23);
    }

    private static function get_num_db_reads(): int {
        $collector = new collector();
        $data = $collector->all();

        if (empty($data) || empty($data->core) || empty($data->core->db)) {
            return 0;
        }

        return $data->core->db->reads;
    }

}
