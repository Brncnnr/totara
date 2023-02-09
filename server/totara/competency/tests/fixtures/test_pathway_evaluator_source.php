<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

namespace pathway_test_pathway;

use totara_competency\pathway;
use totara_competency\pathway_evaluator_user_source;

class test_pathway_evaluator_user_source extends pathway_evaluator_user_source {

    /**
     * Mark users who needs to be reaggregated
     *
     * @param pathway $pathway
     */
    public function mark_users_to_reaggregate(pathway $pathway) {
        global $DB;

        $temp_has_changed_column = $this->temp_user_table->get_has_changed_column();
        if (empty($temp_has_changed_column)) {
            // Not specified - so nothing to do
            return;
        }

        // Re-aggregate all
        $temp_table_name = $this->temp_user_table->get_table_name();
        $temp_user_id_column = $this->temp_user_table->get_user_id_column();
        [$temp_set_sql, $temp_set_params] = $this->temp_user_table->get_set_has_changed_sql_with_params(1);
        [$temp_wh, $temp_wh_params] = $this->temp_user_table->get_filter_sql_with_params('', false, null);
        if (!empty($temp_wh)) {
            $temp_wh = " WHERE {$temp_wh}";
        }

        $sql =
            "UPDATE {" . $temp_table_name . "}
                SET {$temp_set_sql} 
                    {$temp_wh}";

        $params = array_merge(
            $temp_set_params,
            $temp_wh_params
        );

        $DB->execute($sql, $params);
    }

}
