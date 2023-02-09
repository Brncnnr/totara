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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package pathway_criteria_group
 */

namespace pathway_criteria_group\watcher;

use pathway_criteria_group\aggregation_helper;
use totara_criteria\hook\criteria_achievement_changed;
use totara_criteria\hook\criteria_validity_changed;

class criteria {

    /**
     * Listen to the criteria_achievement_changed event and mark all users for reaggregation affected
     *
     * @param criteria_achievement_changed $hook
     */
    public static function achievement_changed(criteria_achievement_changed $hook) {
        aggregation_helper::mark_for_reaggregate_from_criteria($hook->get_user_criteria_ids());
    }

    /**
     * Listen to the criteria_validity_changed event
     *
     * @param criteria_validity_changed $hook
     */
    public static function validity_changed(criteria_validity_changed $hook) {
        aggregation_helper::validate_and_mark_from_criteria($hook->get_criteria_ids());
    }

}
