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
 * @package container_course
 */

use container_course\course;

class container_course_update_course_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_update_course_record_should_bump_cache_revision(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $course = course::from_record($course_record);
        $old_cache_revision = $course->cacherev;

        $new_data = new stdClass();
        $new_data->fullname = 'Wow this is new';
        $new_data->shortname = 'abcd';

        // Update the course's base on the data.
        $course->update($new_data);

        // Reload from database.
        $course->reload();

        $new_cache_revision = $course->cacherev;
        self::assertNotEquals($old_cache_revision, $new_cache_revision);
    }

    /**
     * @return void
     */
    public function test_fail_update_course_record_for_site(): void {
        global $SITE;

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $course = course::from_record($course_record);

        // Update via function update(), but we are set the id for site.
        $new_data = new stdClass();
        $new_data->fullname = 'wow';
        $new_data->shortname = 'some_course_site';
        $new_data->id = $SITE->id;

        // Which we will be expecting an exception of different id being used for upgrade.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Id between data and the container are different");

        $course->update($new_data);
    }
}