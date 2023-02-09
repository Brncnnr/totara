<?php
/**
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task;

use mod_perform\models\activity\subject_instance;
use core\task\scheduled_task;
use moodle_exception;

class close_activity_subject_instances_on_due_date_task extends scheduled_task {

    /**
     * @inheritDoc
     */
    public function get_name() {
        return get_string('close_activity_subject_instances_on_due_date_task', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        $subject_instances = subject_instance::get_subject_instances_due_to_be_closed();

        $exceptions = [];
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
            throw new moodle_exception($exception_summary);
        }
    }
}