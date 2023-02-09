<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\task;

use cache;
use core\task\scheduled_task;
use mod_approval\data_provider\application\role_map\role_map_controller;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Regenerate role capability maps used to optimise building user capability maps.
 */
class role_map_regenerate_all extends scheduled_task {

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
        return get_string('task_role_map_regenerate_all', 'mod_approval');
    }

    /**
     * Regenerate maps.
     */
    public function execute() {
        // Check on/off switch.
        if (advanced_feature::is_disabled('approval_workflows')) {
            return;
        }

        // Suppress output during tests.
        $quiet = PHPUNIT_TEST;
        if (!$quiet) {
            mtrace('Updating role capability maps at ' . time() . ' ...');
        }
        $role_cache = cache::make('mod_approval', 'role_map');

        // Prevent multiple processes from recalculating at the same time.
        if ($role_cache->get('recalculating')) {
            if (!$quiet) {
                mtrace('... but maps are already being recalculated.');
            }
            return;
        }
        $role_cache->set('recalculating', 1);

        $start = microtime(true);
        foreach (role_map_controller::get_all_maps() as $type => $map) {
            $map_start = microtime(true);
            if (!$quiet) {
                mtrace('    updating ' . $type, ' ... ');
            }
            $map->recalculate_complete_map();
            if (!$quiet) {
                mtrace(' done in ' . (microtime(true) - $map_start) . 's');
            }
        }

        // Set the clean flag and clear the recalculating one.
        $role_cache->set_many(['maps_clean' => 1, 'recalculating' => 0]);

        $end = microtime(true);
        if (!$quiet) {
            mtrace('Complete at ' . time() . ' in ' . ceil($end - $start) . 's');
        }
    }
}
