<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package container_perform
 */

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * @group core_container
 */
class container_perform_backup_helper_testcase extends advanced_testcase {

    public function test_load_generic_helper_from_factory(): void {
        $course1 = self::getDataGenerator()->create_course(['containertype' => 'container_course']);
        $this->assertNotInstanceOf(
            \container_perform\backup\backup_helper::class,
            \core_container\factory::get_backup_helper($course1->id)
        );
        $this->assertNotInstanceOf(
            \container_perform\backup\restore_helper::class,
            \core_container\factory::get_restore_helper($course1->id)
        );
    }

    public function test_load_perform_helper_from_factory(): void {
        global $DB;
        $course = self::getDataGenerator()->create_course();
        $DB->update_record('course', ['id' => $course->id, 'containertype' => 'container_perform']);

        $this->assertInstanceOf(
            \container_perform\backup\backup_helper::class,
            \core_container\factory::get_backup_helper($course->id)
        );
        $this->assertInstanceOf(
            \container_perform\backup\restore_helper::class,
            \core_container\factory::get_restore_helper($course->id)
        );
    }

}
