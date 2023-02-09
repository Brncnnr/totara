<?php
/*
 * This file is part of Totara Perform
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task;

use core\task\adhoc_task;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\subject_instance;
use moodle_exception;

/**
 * Adhoc task to close all subject and participant instances for all suspended users.
 */
class close_instances_for_suspended_users_task extends adhoc_task {

    /**
     * Create the task.
     *
     * @param int $user_id the user to use when running this task.
     *
     * @return self the created task.
     */
    public static function create(int $user_id): self {
        $task = new self();
        $task->set_component('mod_perform');
        $task->set_userid($user_id);

        return $task;
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        // Close all subject instances for suspended users.
        $subject_instances = subject_instance::get_subject_instances_to_close_for_suspended_users();
        $exceptions = [];
        $exception_summary = '';
        foreach ($subject_instances as $subject_instance) {
            // Catch exceptions so the task can still close subsequent subject instances.
            try {
                subject_instance::load_by_entity($subject_instance)->manually_close(true);
            } catch (moodle_exception $e) {
                $exceptions[$subject_instance->id] = $e->getMessage();
            }
        }

        if (count($exceptions) > 0) {
            $exception_summary = "There were exceptions trying to close some subject instances:\n";
            foreach ($exceptions as $subject_instance_id => $individual_exception_message) {
                $exception_summary .= "{$subject_instance_id}: {$individual_exception_message}\n";
            }
        }

        // Now close all participant instance that haven't been closed yet.
        $participant_instances = participant_instance::get_participant_instances_to_close_for_suspended_users();
        $exceptions = [];
        foreach ($participant_instances as $participant_instance) {
            // Catch exceptions so the task can still close subsequent participant instances.
            try {
                participant_instance::load_by_entity($participant_instance)->manually_close();
            } catch (moodle_exception $e) {
                $exceptions[$participant_instance->id] = $e->getMessage();
            }
        }

        if (count($exceptions) > 0) {
            $exception_summary .= "\nThere were exceptions trying to close some participant instances:\n";
            foreach ($exceptions as $participant_instance_id => $individual_exception_message) {
                $exception_summary .= "{$participant_instance_id}: {$individual_exception_message}\n";
            }
        }
        if (!empty($exception_summary)) {
            throw new moodle_exception($exception_summary);
        }

        $is_test = defined('PHPUNIT_TEST') && PHPUNIT_TEST;
        if (!$is_test) {
            mtrace("Successfully closed subject/participant instances for suspended users.");
        }
    }
}