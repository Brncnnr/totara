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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
defined('MOODLE_INTERNAL') || die();

use totara_notification\task\delete_notification_logs_task;
use totara_notification\task\process_event_queue_task;
use totara_notification\task\process_notification_queue_task;
use totara_notification\task\process_scheduled_event_task;

$tasks = [
    [
        'classname' => process_event_queue_task::class,
        'blocking' => 1,
        'minute' => '*',
        'hour' => ' *',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
    [
        'classname' => process_notification_queue_task::class,
        'blocking' => 0,
        'minute' => '*',
        'hour' => ' *',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
    [
        // Make it run at 11:00 pm every day
        'classname' => process_scheduled_event_task::class,
        'blocking' => 0,
        'minute' => '0',
        'hour' => '23',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
    [
        'classname' => delete_notification_logs_task::class,
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
];