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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\helpers;

use container_perform\perform;
use mod_perform\models\activity\activity;

/**
 * Class activity_clone
 *
 * Responsible for handling the duplication of a perform activity and it's child records.
 */
class activity_clone {

    /**
     * @var activity
     */
    protected $activity;

    /**
     * activity_clone constructor.
     *
     * @param activity $activity
     */
    public function __construct(activity $activity) {
        $this->activity = $activity;
    }

    /**
     * Clone current perform activity
     *
     * @param bool $is_cloning
     * @return activity
     */
    public function clone(bool $is_cloning = true): activity {
        global $USER, $CFG, $DB;

        require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');

        $course = get_course($this->activity->course);

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = \backup::LOG_NONE;

        // Create the directory and not zip it.
        $backup_controller = new \backup_controller(
            \backup::TYPE_1COURSE,
            $this->activity->course,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            $USER->id
        );
        $backup_id = $backup_controller->get_backupid();

        $default_settings = [
            'anonymize' => 0,
            'blocks' => 0,
            'filters' => 0,
            'comments' => 0,
            'badges' => 0,
            'calendarevents' => 0,
            'userscompletion' => 0,
            'logs' => 0,
            'grade_histories' => 0,
            'questionbank' => 0,
            'groups' => 0,
            'users' => 0,
            'activities' => 1,
            'role_assignments' => 0,
        ];
        foreach ($default_settings as $setting => $value) {
            $plan = $backup_controller->get_plan();
            $plan->get_setting($setting)->set_status(\base_setting::NOT_LOCKED);
            $plan->set_setting($setting, $value);
        }
        $backup_controller->execute_plan();
        $file = $backup_controller->get_results()['backup_destination'];
        $backup_controller->destroy();

        $backup_base_path = $backup_controller->get_plan()->get_basepath();
        // Check if we need to unzip the file because the backup temp dir does not contains backup files.
        if (!file_exists($backup_base_path . "/moodle_backup.xml")) {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $backup_base_path);
        }

        // Do restore to new course with default settings.
        $new_course = perform::create(clone $course);
        $new_course_id = $new_course->id;

        $context = \context_course::instance($new_course_id);
        if (!empty($CFG->performanceactivitycreatornewroleid) && !is_viewing($context, null, 'mod/perform:manage_activity')) {
            role_assign($CFG->performanceactivitycreatornewroleid, $USER->id, $context);
        }

        $restore_controller = new \restore_controller(
            $backup_id,
            $new_course_id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );

        if ($restore_controller->get_plan()->setting_exists('is_cloning')) {
            $setting = $restore_controller->get_plan()->get_setting('is_cloning');
            if ($setting->get_status() == \backup_setting::NOT_LOCKED) {
                $setting->set_value($is_cloning);
            }
        }

        foreach ($default_settings as $setting => $value) {
            if (!$restore_controller->get_plan()->setting_exists($setting)) {
                continue;
            }
            $setting = $restore_controller->get_plan()->get_setting($setting);
            if ($setting->get_status() == \backup_setting::NOT_LOCKED) {
                $setting->set_value($value);
            }
        }
        $restore_controller->execute_precheck();
        $restore_controller->execute_plan();
        $restore_controller->destroy();

        return activity::load_by_container_id($new_course_id);
    }
}