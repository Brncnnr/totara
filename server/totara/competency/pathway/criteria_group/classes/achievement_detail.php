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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace pathway_criteria_group;

use totara_competency\base_achievement_detail;
use totara_criteria\criterion;

class achievement_detail extends base_achievement_detail {

    /**
     * @inheritDoc
     */
    public function get_achieved_via_strings(): array {
        $criteria_met = [];
        foreach ($this->related_info as $criteria_plugin_name) {
            $criteria_met[] = $this->get_achievement_via_string($criteria_plugin_name);
        }

        return $criteria_met;
    }

    /**
     * Used by get_achieved_via_strings() to get a string for given criteria plugin.
     *
     * @param $criteria_plugin_name
     * @return string
     */
    public function get_achievement_via_string($criteria_plugin_name): string {
        return get_string('achievement_via', 'criteria_' . $criteria_plugin_name);
    }

    /**
     * If a criterion has been achieved, it should be added here. This will store the appropriate data
     * to be used when processing the information on how a value was achieved.
     *
     * @param criterion $criterion
     * @return achievement_detail
     */
    public function add_completed_criterion(criterion $criterion): achievement_detail {
        $this->related_info[] = $criterion->get_plugin_type();
        return $this;
    }
}