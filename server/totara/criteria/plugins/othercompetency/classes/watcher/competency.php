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
 * @author Marco Song <marco.song@totaralearning.com>

 * @package criteria_othercompetency
 */

namespace criteria_othercompetency\watcher;

use totara_competency\hook\competency_configuration_changed;
use totara_competency\hook\competency_validity_changed;
use totara_criteria\competency_item_helper;

class competency {

    /**
     * @param competency_configuration_changed $hook
     */
    public static function configuration_changed(competency_configuration_changed $hook) {
        $competency_id = $hook->get_competency_id();
        competency_item_helper::configuration_changed($competency_id, 'othercompetency');
    }

    /**
     * @param competency_validity_changed $hook
     */
    public static function validity_changed(competency_validity_changed $hook) {
        $competency_ids = $hook->get_competency_ids();
        foreach ($competency_ids as $competency_id) {
            // We currently do exactly the same processing as when configuration changes
            competency_item_helper::configuration_changed($competency_id, 'othercompetency');
        }
    }

}
