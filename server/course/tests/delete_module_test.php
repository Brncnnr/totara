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
 * @package core_course
 */
defined('MOODLE_INTERNAL') || die();

class core_course_delete_module_testcase extends advanced_testcase {
    /**
     * Preparing a module, and then return that course's module id
     * @return int
     */
    private function prepare_module(): int {
        global $DB, $USER;

        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $this->executeAdhocTasks();

        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2f = $gen->create_module('facetoface', ['course' => $course->id]);

        $event = new \stdClass();
        $event->facetoface = $f2f->id;
        $event->capacity = 10;
        $event->allowoverbook = 1;
        $event->timecreated = time();
        $event->timemodified = time();
        $event->usermodified = $USER->id;

        $event->id = $DB->insert_record('facetoface_sessions', $event);

        $dt = new \stdClass();
        $dt->sessionid = $event->id;
        $dt->sessiontimezone = '99';
        $dt->timestart = time() + 3600;
        $dt->timefinish = time() + 7200;

        $DB->insert_record('facetoface_sessions_dates', $dt);
        $cm = get_coursemodule_from_instance('facetoface', $f2f->id, $course->id);

        return $cm->id;
    }

    /**
     * Test delete module without async
     * @return void
     */
    public function test_delete_module(): void {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/course/modlib.php");

        $cmid = $this->prepare_module();
        $cm = $DB->get_record('course_modules', ['id' => $cmid]);

        course_delete_module($cmid);

        $this->assertFalse($DB->record_exists('facetoface', ['id' => $cm->instance]));
        $this->assertEmpty(
            $DB->get_records_sql(
                'SELECT * FROM "ttr_facetoface_sessions" s
                INNER JOIN "ttr_facetoface_sessions_dates" sd ON sd.sessionid = s.id
                WHERE s.facetoface = :facetoface',
                ['facetoface' => $cm->instance]
            )
        );
    }

    /**
     * Test delete module with async
     * @return void
     */
    public function test_delete_module_async(): void {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/course/modlib.php");

        set_config('coursebinenable', 1, 'tool_recyclebin');
        $cmid = $this->prepare_module();

        course_delete_module($cmid, true);
        $cm = $DB->get_record('course_modules', ['id' => $cmid]);

        $this->assertEquals(1, $cm->deletioninprogress);
        $this->assertTrue($DB->record_exists('facetoface', ['id' => $cm->instance]));
        $this->assertNotEmpty(
            $DB->get_records_sql(
                'SELECT * FROM "ttr_facetoface_sessions" s
                INNER JOIN "ttr_facetoface_sessions_dates" sd ON sd.sessionid = s.id
                WHERE s.facetoface = :facetoface',
                ['facetoface' => $cm->instance]
            )
        );

        $task = \core\task\manager::get_next_adhoc_task(time());
        $this->assertNotNull($task);
        $this->assertInstanceOf(\core_course\task\course_delete_modules::class, $task);

        $task->execute();

        // Truly deleted
        $this->assertFalse($DB->record_exists('facetoface', ['id' => $cm->instance]));
        $this->assertEmpty(
            $DB->get_records_sql(
                'SELECT * FROM "ttr_facetoface_sessions" s
                INNER JOIN "ttr_facetoface_sessions_dates" sd ON sd.sessionid = s.id
                WHERE s.facetoface = :facetoface',
                ['facetoface' => $cm->instance]
            )
        );
    }
}