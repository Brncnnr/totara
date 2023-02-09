<?php
/**
 * This file is part of Totara LMS
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
 * @package container_course
 */
defined('MOODLE_INTERNAL') || die();

use container_course\course;

class container_course_create_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_instance(): void {
        global $DB;
        $this->setAdminUser();

        $category = $DB->get_record('course_categories', [
            'id' => course::get_default_category_id()
        ]);

        $record = new \stdClass();
        $record->fullname = "Course 101";
        $record->shortname = 'c101';
        $record->category = $category->id;
        $record->summary = 'hello world';

        $course = course::create($record);
        $dbrecord = $DB->get_record('course', ['id' => $course->id]);

        $this->assertEquals(course::get_type(), $dbrecord->containertype);
    }

    public function test_create_instances_with_duedates(): void {
        global $DB;
        $this->setAdminUser();

        $category = $DB->get_record('course_categories', [
            'id' => course::get_default_category_id()
        ]);

        $record = new \stdClass();
        $record->fullname = "Course no duedate";
        $record->shortname = 'cnone';
        $record->category = $category->id;
        $record->summary = 'Course with no duedate';

        $course = course::create($record);
        $dbrecord = $DB->get_record('course', ['id' => $course->id]);

        $this->assertNull($dbrecord->duedate);
        $this->assertNull($dbrecord->duedateoffsetunit);
        $this->assertNull($dbrecord->duedateoffsetamount);

        $fixed_date = strtotime('+2 weeks', time());
        $record = new \stdClass();
        $record->fullname = "Fixed duedate";
        $record->shortname = 'cfixed';
        $record->category = $category->id;
        $record->summary = 'Course with fixed duedate';
        $record->duedate_op = course::DUEDATEOPERATOR_FIXED;
        $record->duedate = $fixed_date;

        $course = course::create($record);
        $dbrecord = $DB->get_record('course', ['id' => $course->id]);

        $this->assertEquals($fixed_date, $dbrecord->duedate);
        $this->assertNull($dbrecord->duedateoffsetunit);
        $this->assertNull($dbrecord->duedateoffsetamount);

        $record = new \stdClass();
        $record->fullname = "Relative duedate";
        $record->shortname = 'crelatove';
        $record->category = $category->id;
        $record->summary = 'Course with relative duedate';
        $record->duedate_op = course::DUEDATEOPERATOR_RELATIVE;
        $record->duedateoffsetunit = course::DUEDATEOFFSETUNIT_WEEKS;
        $record->duedateoffsetamount = 2;

        $course = course::create($record);
        $dbrecord = $DB->get_record('course', ['id' => $course->id]);

        $this->assertNull($dbrecord->duedate);
        $this->assertEquals(course::DUEDATEOFFSETUNIT_WEEKS, $dbrecord->duedateoffsetunit);
        $this->assertEquals(2, $dbrecord->duedateoffsetamount);
    }

}