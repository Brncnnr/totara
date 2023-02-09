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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package aggregation_latest_achieved
 */

namespace aggregation_latest_achieved;


use core\collection;
use totara_competency\entity\pathway_achievement;
use totara_competency\overall_aggregation;
use totara_competency\pathway;

class latest_achieved extends overall_aggregation {

    /**
     * Aggregate the user and return the latest value achieved by the user
     * If the user achieved a value through multiple paths during the same aggregation cycle (cron run)
     * the highest achieved value is used
     *
     * @param int $user_id
     * @return void
     */
    protected function do_aggregation(int $user_id): void {
        // We load all current achievement for all the pathways in one go
        // to reduce the number of queries
        $current_achievements = $this->get_current_pathway_achievements_for_user($this->pathways, $user_id);
        $achievements = collection::new($this->pathways)->map(function (pathway $pathway) use ($current_achievements, $user_id) {
            return $this->get_or_create_current_pathway_achievement($current_achievements, $pathway, $user_id);
        });
        $achievements = $achievements->filter(function (pathway_achievement $achievement) {
            return $achievement->last_aggregated !== null && $achievement->date_achieved > 0;
        });

        $sorted_achievements = $achievements->sort(function (pathway_achievement $a, pathway_achievement $b) {
            // Achieved on the same date - use highest scale value (i.e. smallest sortorder)
            if ($a->date_achieved === $b->date_achieved) {
                // No achieved value
                if ($a->scale_value === null && $b->scale_value === null) {
                    return 0;
                }

                // Only 1 achieved a scale value
                if ($a->scale_value === null && $b->scale_value !== null) {
                    return -1;
                }
                if ($a->scale_value !== null && $b->scale_value === null) {
                    return 1;
                }

                // Both achieved a value. Lower sortorder == higher value
                return $b->scale_value->sortorder - $a->scale_value->sortorder;
            }

            return $a->date_achieved - $b->date_achieved;
        });

        /** @var pathway_achievement $latest_achievement */
        $latest_achievement = $sorted_achievements->last();

        if ($latest_achievement) {
            // More than one pathway may have resulted in the achievement
            $achieved_via = $achievements->filter(function (pathway_achievement $achievement) use ($latest_achievement) {
                return $achievement->date_achieved === $latest_achievement->date_achieved
                    && $achievement->scale_value === $latest_achievement->scale_value;
            })->all();

            $this->set_user_achievement($user_id, $achieved_via, $latest_achievement->scale_value);
        }
    }

}
