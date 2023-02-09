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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\task\check_notification_trigger_task;
use mod_perform\task\cleanup_unused_element_identifiers_task;
use mod_perform\task\close_activity_subject_instances_on_due_date_task;
use mod_perform\task\create_manual_participant_progress_task;
use mod_perform\task\create_subject_instance_task;
use mod_perform\task\expand_all_assignments_task;
use mod_perform\task\expand_assignments_task;
use mod_perform\task\sync_all_track_schedules_task;
use mod_perform\task\sync_participant_instances_task;
use mod_perform\task\sync_track_schedule_changes_task;

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => expand_all_assignments_task::class,
        'blocking' => 0,
        'minute' => 45,
        'hour' => 1,
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
    [
        'classname' => sync_all_track_schedules_task::class,
        'blocking' => 0,
        'minute' => 45,
        'hour' => 2,
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
    [
        'classname' => sync_track_schedule_changes_task::class,
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
    [
        'classname' => expand_assignments_task::class,
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
    [
        'classname' => create_subject_instance_task::class,
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
    [
        'classname' => create_manual_participant_progress_task::class,
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
    [
        'classname' => check_notification_trigger_task::class,
        'blocking' => 0,
        'minute' => '7,22,37,52',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'disabled' => 1,
        'month' => '*'
    ],
    [
        'classname' => cleanup_unused_element_identifiers_task::class,
        'blocking' => 0,
        'minute' => '52',
        'hour' => '4',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
    [
        'classname' => close_activity_subject_instances_on_due_date_task::class,
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
    [
        'classname' => sync_participant_instances_task::class,
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
];
