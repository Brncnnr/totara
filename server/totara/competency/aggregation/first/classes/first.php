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
 * @package aggregation_first
 */

namespace aggregation_first;

use totara_competency\overall_aggregation;
use totara_competency\pathway;

class first extends overall_aggregation {

    /**
     * Aggregate the user and get the first achievement the user has
     *
     * @param int $user_id
     * @return void
     */
    protected function do_aggregation(int $user_id): void {
        /** @var pathway[] $ordered_pathways */
        $ordered_pathways = [];
        foreach ($this->get_pathways() as $pathway) {
            $ordered_pathways[$pathway->get_sortorder()] = $pathway;
        }
        ksort($ordered_pathways);

        $current_achievements = $this->get_current_pathway_achievements_for_user($ordered_pathways, $user_id);
        foreach ($ordered_pathways as $pathway) {
            $achievement = $this->get_or_create_current_pathway_achievement($current_achievements, $pathway, $user_id);
            $value_achieved = $achievement->exists() ? $achievement->scale_value : null;
            if ($value_achieved) {
                $this->set_user_achievement($user_id, [$achievement], $value_achieved);
                break;
            }
        }
    }

    /**
     * Return the name of the javascript function handling pathway aggregation editing
     *
     * @return string|null Javascript function name. In v1, this must be the name of an existing
     *                     function in achievement_paths.js. Null or an empty string indicates
     *                     that no user interaction is required / allowed when changing to this
     *                     aggregation type
     */
    public function get_aggregation_js_function(): ?string {
        return 'calculateSortOrderFromDisplay';
    }

}
