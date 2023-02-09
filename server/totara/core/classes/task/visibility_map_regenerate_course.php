<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\task;

use totara_core\visibility_controller;
use cache;

defined('MOODLE_INTERNAL') || die();

/**
 * Regenerate visibility maps used to optimise visibility checks.
 */
class visibility_map_regenerate_course extends \core\task\adhoc_task {

    /**
     * Queues this adhoc task to update a course instance, or all courses.
     *
     * @param int|null $instanceid
     */
    public static function queue(int $instanceid = null) {
        $task = new self();
        if ($instanceid) {
            $data = new \stdClass();
            $data->instanceid = $instanceid;
            $task->set_custom_data($data);
        }
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * A description of what this task does for administrators.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_visibility_map_regenerate_course', 'totara_core');
    }

    /**
     * Regenerate type map for either role or item.
     */
    public function execute() {
        // Suppress output during tests.
        $quiet = PHPUNIT_TEST;

        $roleid = $this->get_custom_data()->roleid ?? null;
        $instanceid = $this->get_custom_data()->instanceid ?? null;

        $map = visibility_controller::course()->map();
        $start = microtime(true);
        if (!$quiet) {
            mtrace('Updating course visibility map at '.time() . ' ...');
        }
        if ($instanceid !== null) {
            $map->recalculate_map_for_instance($instanceid);
        } else {
            $map->recalculate_complete_map();
        }
        // Purge content cache.
        $cache = cache::make('totara_core', 'visible_content');
        $cache->purge();
        $end = microtime(true);
        if (!$quiet) {
            mtrace('Complete at '.time() . ' in ' . ceil($end - $start) . 's');
        }
    }
}
