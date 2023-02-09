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
use cache;

defined('MOODLE_INTERNAL') || die();

/**
 * Regenerate visibility maps used to optimise visibility checks.
 */
class visibility_map_regenerate_all extends \core\task\scheduled_task {

    /**
     * Queues this scheduled task to ensure it is run when CRON runs next.
     */
    public static function queue() {
        global $DB;
        $sql = "UPDATE {task_scheduled}
                   SET nextruntime = 0, lastruntime = CASE WHEN lastruntime < :now THEN lastruntime ELSE 0 END
                 WHERE classname = :classname AND nextruntime <> 0";
        $params = [
            'now' => time(),
            'classname' => '\\' . __CLASS__
        ];
        $DB->execute($sql, $params);
    }

    /**
     * A description of what this task does for administrators.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_visibility_map_regenerate_all', 'totara_core');
    }

    /**
     * Regenerate maps.
     */
    public function execute() {
        // Suppress output during tests.
        $quiet = PHPUNIT_TEST;
        if (!$quiet) {
            mtrace('Updating visibility maps at ' . time() . ' ...');
        }
        $start = microtime(true);
        foreach (\totara_core\visibility_controller::get_all_maps() as $type => $map) {
            $map_start = microtime(true);
            if (!$quiet) {
                mtrace('    updating ' . $type, ' ... ');
            }
            $map->recalculate_complete_map();
            if (!$quiet) {
                mtrace(' done in ' . (microtime(true) - $map_start) . 's');
            }
        }
        // Purge content cache.
        $cache = cache::make('totara_core', 'visible_content');
        $cache->purge();
        $end = microtime(true);
        if (!$quiet) {
            mtrace('Complete at ' . time() . ' in ' . ceil($end - $start) . 's');
        }
    }
}
