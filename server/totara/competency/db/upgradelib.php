<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

/**
 * Contains upgrade and install functions for totara_competency.
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Set the new setting value for the aggregation method
 * @return void
 */
function totara_competency_upgrade_update_aggregation_method_setting() {
    global $DB;
    $dbman = $DB->get_manager();
    $table = new xmldb_table('totara_competency_achievement');
    // If there are records present it means the data was migrated
    // previously or created with the wrong aggregation method.
    // To avoid changing behaviour in this case we set the aggregation method to latest.
    // Otherwise we use highest, to make sure the behaviour matches the pre-13 behaviour
    if (!advanced_feature::is_enabled('competency_assignment')
        && $dbman->table_exists($table)
        && $DB->count_records('totara_competency_achievement') > 0
    ) {
        $default_aggregation_type = 1;
        $type = 'latest_achieved';
    } else {
        $default_aggregation_type = 2;
        $type = 'highest';
    }

    $existing_value = get_config('totara_competency', 'legacy_aggregation_method');
    if ($existing_value === false) {
        set_config('legacy_aggregation_method', $default_aggregation_type, 'totara_competency');

        if (!advanced_feature::is_enabled('competency_assignment')) {
            // Now just make sure all existing competencies have the right type set
            $sql = "
                UPDATE {totara_competency_scale_aggregation}
                SET type = '{$type}'
            ";
            $DB->execute($sql);
        }
    }
}

