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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\orm\query\builder;

class core_orm_query_builder_count_testcase extends advanced_testcase {
    /**
     * The only way we can test whether it is working with fetching count or not is to have
     * a scenario where we want to fetch a course that has an enrolled user named similar to
     * such pattern.
     *
     * @return void
     */
    public function test_fetch_count_with_distinct(): void {
        $generator = $this->getDataGenerator();

        $course_one = $generator->create_course();
        $course_two = $generator->create_course();

        $user_one = $generator->create_user(['firstname' => 'Boblobala']);
        $user_two = $generator->create_user(['firstname' => 'Boblookala']);

        $user_three = $generator->create_user(['firstname' => uniqid()]);

        $generator->enrol_user($user_one->id, $course_one->id);
        $generator->enrol_user($user_two->id, $course_one->id);

        $generator->enrol_user($user_three->id, $course_two->id);

        // Start fetching for records of course that has user enrolment similar to 'Boblo'
        $builder = builder::table('course', 'c');
        $builder->select_raw('DISTINCT c.id, c.fullname');
        $builder->join(['enrol', 'e'], 'id', 'courseid');
        $builder->join(['user_enrolments', 'ue'], 'e.id', 'enrolid');
        $builder->join(['user', 'u'], 'ue.userid', 'id');

        $builder->where('u.firstname', 'ilike', 'boblo');

        // There should be 1 course record only.
        self::assertEquals(1, $builder->count());
    }

    /**
     * Same scenario with searching for courses that has user enrolled with firstname as
     * such pattern.
     *
     * @return void
     */
    public function test_fetch_count_with_group_by(): void {
        $generator = $this->getDataGenerator();

        $course_one = $generator->create_course();
        $course_two = $generator->create_course();

        $user_one = $generator->create_user(['firstname' => 'Boblobala']);
        $user_two = $generator->create_user(['firstname' => 'Boblookala']);

        $user_three = $generator->create_user(['firstname' => uniqid()]);

        $generator->enrol_user($user_one->id, $course_one->id);
        $generator->enrol_user($user_two->id, $course_one->id);

        $generator->enrol_user($user_three->id, $course_two->id);

        // Start fetching for records of course that has user enrolment similar to 'Boblo'
        $builder = builder::table('course', 'c');
        $builder->select_raw('c.id, c.fullname');
        $builder->join(['enrol', 'e'], 'id', 'courseid');
        $builder->join(['user_enrolments', 'ue'], 'e.id', 'enrolid');
        $builder->join(['user', 'u'], 'ue.userid', 'id');

        $builder->where('u.firstname', 'ilike', 'boblo');
        $builder->group_by(['c.id', 'c.fullname']);

        // There should be 1 course record only.
        self::assertEquals(1, $builder->count());
    }

    /**
     * Same scenario with searching for courses that has user enrolled with firstname as
     * such pattern.
     *
     * @return void
     */
    public function test_fetch_count_without_distinct_and_group_by(): void {
        $generator = $this->getDataGenerator();

        $course_one = $generator->create_course();
        $course_two = $generator->create_course();

        $user_one = $generator->create_user(['firstname' => 'Boblobala']);
        $user_two = $generator->create_user(['firstname' => 'Boblookala']);

        $user_three = $generator->create_user(['firstname' => uniqid()]);

        $generator->enrol_user($user_one->id, $course_one->id);
        $generator->enrol_user($user_two->id, $course_one->id);

        $generator->enrol_user($user_three->id, $course_two->id);

        // Start fetching for records of course that has user enrolment similar to 'Boblo'
        $builder = builder::table('course', 'c');
        $builder->select_raw('c.id, c.fullname');
        $builder->join(['enrol', 'e'], 'id', 'courseid');
        $builder->join(['user_enrolments', 'ue'], 'e.id', 'enrolid');
        $builder->join(['user', 'u'], 'ue.userid', 'id');

        $builder->where('u.firstname', 'ilike', 'boblo');

        // There should be 1 course record only. However, the sql will give us another duplicated
        // record as it found 2 users with the name similar to boblo and we are expecting that.
        self::assertEquals(2, $builder->count());
    }
}