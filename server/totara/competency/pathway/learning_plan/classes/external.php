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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package pathway_manual
 */

namespace pathway_learning_plan;

use context_system;
use totara_competency\achievement_configuration;
use totara_competency\entity\configuration_change;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency;

class external extends \external_api {
    /** create */
    public static function create_parameters() {
        return new \external_function_parameters(
            [
                'competency_id' => new \external_value(PARAM_INT, 'Competency id'),
                'sortorder' => new \external_value(PARAM_INT, 'Sortorder'),
                'actiontime' => new \external_value(PARAM_INT, 'Time user initiated the action. It is used to group changes done in single user action together'),
            ]
        );
    }

    public static function create(int $competency_id, int $sortorder, string $action_time) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/hierarchy:updatecompetency', context_system::instance());

        $competency = new competency($competency_id);
        $config = new achievement_configuration($competency);

        // Save history before making any changes - for now the action_time is used to ensure we do this only once per user 'Apply changes' action
        $config->save_configuration_history($action_time);

        $pathway = new learning_plan();
        $pathway->set_competency($competency)
            ->set_sortorder($sortorder)
            ->save();

        // Log the configuration change- for now the action_time is used to ensure we do this only once per user 'Apply changes' action
        configuration_change::add_competency_entry(
            $competency->id,
            configuration_change::CHANGED_CRITERIA,
            $action_time
        );

        return $pathway->get_id();
    }

    public static function create_returns() {
        return new \external_value(PARAM_INT, 'Pathway id');
    }

    /** update */
    public static function update_parameters() {
        return new \external_function_parameters(
            [
                'id' => new \external_value(PARAM_INT, 'Id of pathway'),
                'sortorder' => new \external_value(PARAM_INT, 'Sortorder'),
                'actiontime' => new \external_value(PARAM_INT, 'Time user initiated the action. It is used to group changes done in single user action together'),
            ]
        );
    }

    public static function update(int $id, int $sortorder, string $action_time) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/hierarchy:updatecompetency', context_system::instance());

        $pathway = learning_plan::fetch($id);
        $config = new achievement_configuration($pathway->get_competency());

        // Save history before making any changes - for now the action_time is used to ensure we do this only once per user 'Apply changes' action
        $config->save_configuration_history($action_time);

        $pathway->set_sortorder($sortorder)
            ->save();
        // Log the configuration change- for now the action_time is used to ensure we do this only once per user 'Apply changes' action
        configuration_change::add_competency_entry(
            $pathway->get_competency()->id,
            configuration_change::CHANGED_CRITERIA,
            $action_time
        );

        return $pathway->get_id();
    }

    public static function update_returns() {
        return new \external_value(PARAM_INT, 'Pathway id');
    }
}
