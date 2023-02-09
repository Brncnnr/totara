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
use totara_notification\manager\event_queue_manager;

class process_event_queue_task extends scheduled_task {
    /**
     * @var progress_trace
     */
    private $trace;

    /**
     * process_event_queue_task constructor.
     */
    public function __construct() {
        $is_test = defined('PHPUNIT_TEST') && PHPUNIT_TEST;
        $this->trace = $is_test ? new null_progress_trace() : new text_progress_trace();
    }

    /**
     * @param progress_trace $trace
     * @return void
     */
    public function set_trace(progress_trace $trace): void {
        $this->trace = $trace;
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return get_string('process_event_queue_task', 'totara_notification');
    }

    /**
     * @return void
     */
    public function execute() {
        $manager = new event_queue_manager($this->trace);
        $manager->process_queues();
    }
}