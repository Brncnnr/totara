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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\task;

use core\task\scheduled_task;
use null_progress_trace;
use progress_trace;
use text_progress_trace;
use totara_notification\manager\scheduled_event_manager;
use totara_notification\schedule\schedule_before_event;
use totara_notification\schedule\schedule_after_event;

class process_scheduled_event_task extends scheduled_task {
    /**
     * The configuration name.
     * @var string
     */
    public const LAST_RUN_TIME_NAME = 'last_scheduled_event_task_run_time';

    /**
     * The current time now - epoch time.
     * This variable will be tweaked by the generator.
     * @var void
     */
    private $time_now;

    /**
     * @var progress_trace
     */
    private $trace;

    /**
     * process_scheduled_event_task constructor.
     */
    public function __construct() {
        $this->time_now = time();
        $trace = new null_progress_trace();

        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            $trace = new text_progress_trace();
        }

        $this->trace = $trace;
    }

    /**
     * @param int $time_now
     * @return void
     */
    public function set_time_now(int $time_now): void {
        $this->time_now = $time_now;
    }

    /**
     * @return string
     */
    public function get_name() {
        return get_string('process_scheduled_event_task', 'totara_notification');
    }

    /**
     * @param progress_trace $trace
     * @return void
     */
    public function set_trace(progress_trace $trace): void {
        $this->trace = $trace;
    }

    /**
     * @return void
     */
    public function execute() {
        // Note that this is different from last_cron_time. Because we stored it completely different
        // from last cron run time, just in case the last cron time get reset.
        $last_run_time = get_config('totara_notification', static::LAST_RUN_TIME_NAME);
        if (empty($last_run_time)) {
            // First time running the tasks most likely. Hence we are using the time now.
            $last_run_time = $this->time_now;
        }

        $manager = new scheduled_event_manager($this->trace);
        $manager->execute($this->time_now, $last_run_time);

        // Update the last run time.
        set_config(static::LAST_RUN_TIME_NAME, $this->time_now, 'totara_notification');
    }
}