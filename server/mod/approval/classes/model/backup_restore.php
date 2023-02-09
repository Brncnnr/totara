<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model;

use core\entity\user;
use core\orm\query\builder;
use core_container\container;
use container_approval\approval as approval_container;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\exception\model_exception;

/** @var core_config $CFG */
global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/restore_activity_task.class.php');

/**
 * Class backup_restore
 *
 * Execute from other.php
 * $workflow_id = $this->get_required_param('workflow_id', PARAM_INT);
 * $is_cloning = true; //$this->get_required_param('is_cloning', PARAM_BOOL);
 * $workflow = workflow::load_by_id($workflow_id);
 * $clone = new backup_restore(user::logged_in());
 * $new_course = $clone->execute($workflow->course_id, $is_cloning);
 */
class backup_restore {
    /** @var user $user */
    public $user;

    /** @var array $default_settings */
    private $default_settings = [];

    public function __construct(user $user) {
        $this->default_settings = [
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
        $this->user = $user;
    }

    /**
     * Execure mod_approval activity backup and restore
     * @param int $course
     * @param bool $is_cloning
     * @return container
     * @throws model_exception
     */
    public function execute(int $course, bool $is_cloning = false): container {
        $backup_id = $this->backup($course);
        $container = $this->restore($backup_id, $course, $is_cloning);
        return $container;
    }

    /**
     * Backup mod_appropval activity.
     *
     * @param int $course
     * @return string $backup_id
     */
    public function backup(int $course): string {
        global $CFG;

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = \backup::LOG_NONE;

        // Create the directory and not zip it.
        $backup_controller = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            $this->user->id
        );
        $backup_id = $backup_controller->get_backupid();

        foreach ($this->default_settings as $setting => $value) {
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
        return $backup_id;
    }

    /**
     * Restore mod_approval activitry.
     *
     * @param string $backup_id
     * @param stdClass|approval_container|int $course Course instance or id
     * @param bool $is_cloning
     * @return container
     */
    public function restore(string $backup_id, $course, bool $is_cloning = false): container {
        // Allow either a course database object or straight id be handed through.
        if ($course instanceof stdClass || $course instanceof approval_container) {
            $course = $course->id;
        }
        if (empty($course)) {
            throw new model_exception('Course cannot be empty');
        }

        // Do restore to new course with default settings.
        $new_course = approval_container::create(clone get_course($course));

        $restore_controller = new \restore_controller(
            $backup_id,
            $new_course->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            $this->user->id,
            \backup::TARGET_NEW_COURSE
        );

        foreach ($this->default_settings as $setting => $value) {
            if (!$restore_controller->get_plan()->setting_exists($setting)) {
                continue;
            }
            $setting = $restore_controller->get_plan()->get_setting($setting);
            if ($setting->get_status() == \backup_setting::NOT_LOCKED) {
                $setting->set_value($value);
            }
        }

        $assignments = builder::table(assignment_entity::TABLE, 'assignment')
            ->select(['assignment.*', 'cm.id AS section_id'])
            ->join(['course_modules', 'cm'], 'assignment.id', '=', 'cm.instance')
            ->where('cm.course', $course)
            ->fetch();
        $tasks = $restore_controller->get_plan()->get_tasks();
        foreach($tasks as $task) {
            if ($task instanceof \restore_approval_activity_task) {
                $settings = $task->get_settings();
                foreach ($settings as $setting) {
                    foreach ($assignments as $assignment) {
                        $backup_activity_id = "approval_{$assignment->section_id}_included";
                        if ($backup_activity_id == $setting->get_name() && (bool) $assignment->is_default == false) {
                            $setting->set_value(0);
                        }
                    }
                }
            }
        }

        if (!$restore_controller->execute_precheck()) {
            echo("\n something went wrong \n");
        }
        $restore_controller->execute_plan();
        $restore_controller->destroy();

        return $new_course;
    }
}