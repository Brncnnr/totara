<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_course
 */

use core_phpunit\testcase;
use core\entity\user;
use core\orm\query\builder;

class core_course_backup_restore_course_testcase extends testcase {
    /**
     * @return void
     */
    public function test_restore_user_from_course_should_respect_suspended_flag(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/user/lib.php");
        require_once("{$CFG->dirroot}/backup/util/includes/backup_includes.php");
        require_once("{$CFG->dirroot}/backup/util/includes/restore_includes.php");

        $CFG->backup_file_logger_level = backup::LOG_NONE;
        $generator = self::getDataGenerator();

        $user = $generator->create_user();
        $course = $generator->create_course();

        $generator->enrol_user($user->id, $course->id);

        // Now that suspended the user.
        user_suspend_user($user->id);
        self::setAdminUser();

        // Create a backup file.
        $backup_controller = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id
        );

        // This is a bit hacky way, because with mode import our settings will be reset to false.
        // Hence, no users record will be added.
        /** @var backup_users_setting $b_settings */
        $b_settings = $backup_controller->get_plan()->get_setting("users");
        $b_settings->set_status(base_setting::NOT_LOCKED);
        $b_settings->set_value(true);
        $b_settings->set_status(base_setting::LOCKED_BY_PERMISSION);

        $backup_id = $backup_controller->get_backupid();
        $backup_controller->execute_plan();
        $backup_controller->destroy();

        $old_username = $user->username;
        user_delete_user($user);

        $db = builder::get_db();

        // User with old username will not be existing in the system.
        self::assertFalse($db->record_exists(user::TABLE, ["username" => $old_username]));

        $new_course_id = restore_dbops::create_new_course(
            $course->fullname,
            $course->shortname . "_2",
            $course->category
        );

        $restore_controller = new restore_controller(
            $backup_id,
            $new_course_id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );

        // This is a bit hacky way, because with mode import our settings will be reset to false.
        // Hence, no users record will be added.
        /** @var backup_users_setting $r_settings */
        $r_settings = $restore_controller->get_plan()->get_setting("users");
        $r_settings->set_status(base_setting::NOT_LOCKED);
        $r_settings->set_value(true);
        $r_settings->set_status(base_setting::LOCKED_BY_PERMISSION);

        self::assertTrue($restore_controller->execute_precheck(true));
        $restore_controller->execute_plan();
        $restore_controller->destroy();

        self::assertTrue($db->record_exists("course", ["id" => $new_course_id]));

        // Check that this user is suspended.
        self::assertTrue(
            $db->record_exists(
                user::TABLE,
                [
                    "username" => $old_username,
                    "suspended" => 1
                ]
            )
        );
    }
}