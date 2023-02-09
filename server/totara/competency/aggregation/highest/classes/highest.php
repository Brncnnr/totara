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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package aggregation_highest
 */

namespace aggregation_highest;


use totara_competency\entity\pathway_achievement;
use totara_competency\overall_aggregation;

class highest extends overall_aggregation {

    private $scale_values_cache = [];

    /**
     * Aggregate the user and return the highest value the user got
     *
     * @param int $user_id
     * @return void
     */
    protected function do_aggregation(int $user_id): void {
        /** @var pathway_achievement|null $highest_achievement */
        $highest_achievement = null;
        $highest_value = null;
        $achieved_via = [];

        // We load all current achievement for all the pathway in one go
        // to reduce the number of queries
        $current_achievements = $this->get_current_pathway_achievements_for_user($this->pathways, $user_id);
        foreach ($this->pathways as $pathway) {
            $achievement = $this->get_or_create_current_pathway_achievement($current_achievements, $pathway, $user_id);

            $value_achieved = $achievement->exists() ? $achievement->scale_value : null;

            if (!is_null($value_achieved)) {
                if (is_null($highest_achievement)) {
                    $highest_achievement = $achievement;
                    $highest_value = $value_achieved;
                    $achieved_via = [$achievement];
                } else {
                    if ($value_achieved->sortorder < $highest_value->sortorder) {
                        $highest_achievement = $achievement;
                        $highest_value = $value_achieved;
                        $achieved_via = [$achievement];
                    } else if ($value_achieved->sortorder == $highest_value->sortorder) {
                        $achieved_via[] = $achievement;
                    }
                }
            }
        }

        if (isset($highest_achievement)) {
            $this->set_user_achievement($user_id, $achieved_via, $highest_value);
        }
    }

    /**
     * Let's make sure we only request each scale_value only once
     *
     * @param pathway_achievement|null $achievement
     * @return scale_value|null
     */
    private function get_scale_value(?pathway_achievement $achievement): ?scale_value {
        if (is_null($achievement->scale_value)) {
            return null;
        }

        if (!isset($this->scale_values_cache[$achievement->scale_value_id])) {
            $this->scale_values_cache[$achievement->scale_value_id] = $achievement->scale_value;
        }

        return $this->scale_values_cache[$achievement->scale_value_id];
    }
}
