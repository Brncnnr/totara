<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2023 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totara.com>
 * @package core_enrol
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group core_enrol
 */
class core_enrol_locallib_testcase extends \core_phpunit\testcase {
    public function test_get_users_for_display() {
        global $CFG;
        require_once($CFG->dirroot . '/enrol/locallib.php');

        $gen = $this->getDataGenerator();

        $user1 = $gen->create_user(['firstname' => 'Z', 'lastname' => 'Z']);
        $user2 = $gen->create_user(['firstname' => 'A', 'lastname' => 'A']);
        $course = $gen->create_course(['shortname' => 'C']);

        $gen->enrol_user($user1->id, $course->id, 0);
        $gen->enrol_user($user2->id, $course->id, 'student');

        $page = new moodle_page();
        $page->set_url('/');
        $manager = new \course_enrolment_manager(
            $page,
            $course
        );
        $users = $manager->get_users_for_display($manager, 'firstname', 'ASC', 0, 10);

        self::assertCount(2, $users);
        self::assertArrayHasKey($user1->id, $users);
        self::assertArrayHasKey($user2->id, $users);

        reset($users);
        self::assertEquals($user2->id, key($users));
        self::assertCount(1, $users[$user2->id]['roles']);

        next($users);
        self::assertEquals($user1->id, key($users));
        self::assertCount(0, $users[$user1->id]['roles']);
    }
}