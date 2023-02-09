<?php
/*
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

namespace totara_competency\watcher;

use aggregation_highest\highest;
use aggregation_latest_achieved\latest_achieved;
use coding_exception;
use core\hook\admin_setting_changed;
use totara_competency\admin_setting_legacy_aggregation_method;
use totara_competency\aggregation_users_table;
use totara_core\advanced_feature;

class settings {

    /**
     * @param admin_setting_changed $hook
     */
    public static function admin_setting_changed(admin_setting_changed $hook) {
        global $DB;

        if ($hook->name !== 'legacy_aggregation_method') {
            return;
        }

        // This should not be available if Perform is enabled
        if (advanced_feature::is_enabled('competency_assignment')) {
            return;
        }

        $type = null;
        switch ($hook->newvalue) {
            case admin_setting_legacy_aggregation_method::LATEST_ACHIEVEMENT:
                $type = latest_achieved::aggregation_type();
                break;
            case admin_setting_legacy_aggregation_method::HIGHEST_ACHIEVEMENT:
                $type = highest::aggregation_type();
                break;
            default:
                throw new coding_exception(
                    'Invalid setting value for '.admin_setting_legacy_aggregation_method::NAME.' detected'
                );
        }

        // Change the aggregation type for all current competencies
        $sql = "
            UPDATE {totara_competency_scale_aggregation}
            SET type = '{$type}'
        ";
        $DB->execute($sql);

        // Make sure we queue all competencies for all users who have achievements already
        $table = new aggregation_users_table();
        $has_changed_column = $table->get_has_changed_column()
            ? ", {$table->get_has_changed_column()}"
            : '';
        $has_changed_value = $table->get_has_changed_column()
            ? ", 1"
            : '';

        $sql = "
            INSERT INTO {{$table->get_table_name()}}
                ({$table->get_user_id_column()}, {$table->get_competency_id_column()} {$has_changed_column})
            SELECT DISTINCT tca.user_id, tca.competency_id {$has_changed_value}
            FROM {totara_competency_achievement} tca
        ";
        $DB->execute($sql);
    }

}
