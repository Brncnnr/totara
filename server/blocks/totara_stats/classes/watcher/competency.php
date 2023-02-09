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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package block_totara_stats
 */

namespace block_totara_stats\watcher;

use totara_competency\hook\competency_achievement_updated_bulk;

class competency {

    public static function achievement_updated_bulk(competency_achievement_updated_bulk $hook) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/blocks/totara_stats/locallib.php');

        $competency_id = $hook->get_competency_id();
        $user_ids_proficient = $hook->get_user_ids_proficiency_data();

        foreach ($user_ids_proficient as $user_id => $proficiency_data) {
            $count = $DB->count_records(
                'block_totara_stats',
                ['userid' => $user_id, 'eventtype' => STATS_EVENT_COMP_ACHIEVED, 'data2' => $competency_id]
            );

            // Check the proficiency is set to "proficient" and check for duplicate data.
            if ($proficiency_data['is_proficient'] && $count == 0) {
                totara_stats_add_event(time(), $user_id, STATS_EVENT_COMP_ACHIEVED, '', $competency_id);
            } else if ($proficiency_data['is_proficient'] == 0 && $count > 0) {
                totara_stats_remove_event($user_id, STATS_EVENT_COMP_ACHIEVED, $competency_id);
            }
        }
    }

}
