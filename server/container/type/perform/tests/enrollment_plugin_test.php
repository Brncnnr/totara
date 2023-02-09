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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package container_perform
 */

use container_perform\perform;

class container_perform_enrollment_plugin_testcase extends advanced_testcase {

    public function test_upgraded_containers_have_container_perform_enrollment_plugin(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/container/type/perform/db/upgradelib.php');

        $generator = self::getDataGenerator();
        self::setAdminUser();

        // Both the course and perform containers will have 3 enrol records associated with it
        // We create the perform container as a course then change the containertype field in order to simulate
        // how the cloning behavior worked before Totara 13.3 - where cloned activities had enrol records (when they shouldn't)
        $course_container = $generator->create_course();
        $perform_container = $generator->create_course();
        $DB->set_field('course', 'containertype', perform::get_type(), ['id' => $perform_container->id]);

        $this->assertEquals(3, $DB->count_records('enrol', ['courseid' => $course_container->id]));
        $this->assertEquals(3, $DB->count_records('enrol', ['courseid' => $perform_container->id]));

        container_perform_create_enrollment_plugin_records();

        $this->assertEquals(3, $DB->count_records('enrol', ['courseid' => $course_container->id]));
        $this->assertEquals(1, $DB->count_records('enrol', ['courseid' => $perform_container->id]));
        $this->assertEquals(
            'container_perform',
            $DB->get_field('enrol', 'enrol', ['courseid' => $perform_container->id])
        );

        // Should be able to run it again, and nothing should change.
        container_perform_create_enrollment_plugin_records();

        $this->assertEquals(3, $DB->count_records('enrol', ['courseid' => $course_container->id]));
        $this->assertEquals(1, $DB->count_records('enrol', ['courseid' => $perform_container->id]));
        $this->assertEquals(
            'container_perform',
            $DB->get_field('enrol', 'enrol', ['courseid' => $perform_container->id])
        );
    }

}
