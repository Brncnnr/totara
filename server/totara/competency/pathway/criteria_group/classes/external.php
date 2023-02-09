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
 * @package totara_competency
 */

namespace pathway_criteria_group;

defined('MOODLE_INTERNAL') || die;

use context_system;
use totara_competency\achievement_configuration;
use totara_competency\entity\configuration_change;
use totara_core\advanced_feature;
use totara_criteria\criterion_factory;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

class external extends \external_api {

    /** create */
    public static function create_parameters() {
        return new \external_function_parameters(
            [
                'competency_id' => new \external_value(PARAM_INT, 'Competency id'),
                'sortorder' => new \external_value(PARAM_INT, 'Sortorder'),
                'scalevalue' => new \external_value(PARAM_INT, 'Scale value id.'),
                'criteria' => new \external_multiple_structure(
                    new \external_single_structure([
                        'type' => new \external_value(PARAM_ALPHAEXT, 'Criterion type'),
                        'id' => new \external_value(PARAM_INT, 'Criterion id', VALUE_OPTIONAL),
                        'itemids' => new \external_multiple_structure(
                            new \external_value(PARAM_INT, 'Id of the item'),
                            'Item ids. Items in itemsbasketkey will overwrite this list if both are provided',
                            VALUE_OPTIONAL
                        ),
                        'aggregation' => new \external_single_structure(
                            [
                                'method' => new \external_value(PARAM_INT, 'Aggregation method'),
                                'reqitems' => new \external_value(PARAM_INT, 'Number or items required for fulfillment'),
                            ],
                            'Aggregation detail',
                            VALUE_OPTIONAL
                        ),
                        'metadata' => new \external_multiple_structure(
                            new \external_single_structure([
                                'metakey' => new \external_value(PARAM_TEXT, 'Metadata key'),
                                'metavalue' => new \external_value(PARAM_TEXT, 'Metadata value'),
                            ]),
                            'Metadata associated with the criterion',
                            VALUE_OPTIONAL
                        ),
                    ])
                ),
                'actiontime' => new \external_value(PARAM_INT, 'Time user initiated the action. It is used to group changes done in single user action together'),
            ]
        );
    }

    public static function create(int $competency_id, int $sortorder, int $scalevalue, array $criteria, int $action_time) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/hierarchy:updatecompetency', context_system::instance());

        // If there are no criteria linked to this pathway, don't create
        if (empty($criteria)) {
            return 0;
        }

        $competency = new competency($competency_id);
        $config = new achievement_configuration($competency);

        // Save history before making any changes - for now the action_time is used to ensure we do this only once per user 'Apply changes' action
        $config->save_configuration_history($action_time);

        $pathway = new criteria_group();

        $criterion_array = [];

        foreach ($criteria as $crit) {
            if (empty($crit['id'])) {
                $criterion = criterion_factory::create($crit['type']);
            } else {
                $criterion = criterion_factory::fetch($crit['type'], $crit['id']);
            }

            if ($criterion->has_items()) {
                $criterion->set_item_ids($crit['itemids'] ?? []);
            }

            if (!empty($crit['aggregation'])) {
                $criterion->set_aggregation_method($crit['aggregation']['method']);
                $criterion->set_aggregation_params(['req_items' => $crit['aggregation']['reqitems'] ?? 1]);
            }

            if (!empty($crit['metadata'])) {
                $criterion->set_metadata($crit['metadata']);
            }

            $criterion_array[] = $criterion;
        }

        $pathway->set_competency($competency);
        $pathway->set_scale_value(new scale_value($scalevalue));
        $pathway->set_sortorder($sortorder);
        $pathway->replace_criteria($criterion_array);
        $pathway->save();

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


    /**
     * update_criteria
     */
    public static function update_parameters() {
        return new \external_function_parameters(
            [
                'id' => new \external_value(PARAM_INT, 'Id of pathway'),
                'sortorder' => new \external_value(PARAM_INT, 'Sortorder'),
                'scalevalue' => new \external_value(PARAM_INT, 'Scale value id.'),
                'criteria' => new \external_multiple_structure(
                    new \external_single_structure([
                        'type' => new \external_value(PARAM_ALPHAEXT, 'Criterion type'),
                        'id' => new \external_value(PARAM_INT, 'Criterion id'),
                        'itemids' => new \external_multiple_structure(
                            new \external_value(PARAM_INT, 'Id of the item'),
                            'Item ids. Items in itemsbasketkey will overwrite this list if both are passed',
                            VALUE_OPTIONAL
                        ),
                        'aggregation' => new \external_single_structure(
                            [
                                'method' => new \external_value(PARAM_INT, 'Aggregation method'),
                                'reqitems' => new \external_value(
                                    PARAM_INT, 'Number or items required for fulfillment', VALUE_OPTIONAL, 1
                                ),
                            ],
                            'Aggregation detail',
                            VALUE_OPTIONAL
                        ),
                        'metadata' => new \external_multiple_structure(
                            new \external_single_structure([
                                'metakey' => new \external_value(PARAM_TEXT, 'Metadata key'),
                                'metavalue' => new \external_value(PARAM_TEXT, 'Metadata value'),
                            ]),
                            'Metadata associated with the criterion',
                            VALUE_OPTIONAL
                        ),
                    ])
                ),
                'actiontime' => new \external_value(PARAM_INT, 'Time user initiated the action. It is used to group changes done in single user action together'),
            ]
        );
    }

    public static function update(int $id, int $sortorder, int $scalevalue, array $criteria, int $action_time) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/hierarchy:updatecompetency', context_system::instance());

        $pathway = criteria_group::fetch($id);

        $config = new achievement_configuration($pathway->get_competency());

        // Save history before making any changes - for now the action_time is used to ensure we do this only once per user 'Apply changes' action
        $config->save_configuration_history($action_time);

        if (empty($criteria)) {
            $pathway->delete();

            configuration_change::add_competency_entry(
                $config->get_competency()->id,
                configuration_change::CHANGED_CRITERIA,
                $action_time
            );
            return 0;
        }

        $criterion_array = [];

        foreach ($criteria as $crit) {
            if (empty($crit['id'])) {
                $criterion = criterion_factory::create($crit['type']);
            } else {
                $criterion = criterion_factory::fetch($crit['type'], $crit['id']);
            }

            if ($criterion->has_items()) {
                $criterion->set_item_ids($crit['itemids'] ?? []);
            }

            if (!empty($crit['aggregation'])) {
                $criterion->set_aggregation_method($crit['aggregation']['method']);
                $criterion->set_aggregation_params(['req_items' => $crit['aggregation']['reqitems'] ?? 1]);
            }
            $criterion_array[] = $criterion;

            if (!empty($crit['metadata'])) {
                $criterion->set_metadata($crit['metadata']);
            }
        }

        $pathway->set_sortorder($sortorder)
            ->replace_criteria($criterion_array)
            ->save();

        // Log the configuration change- for now the action_time is used to ensure we do this only once per user 'Apply changes' action
        configuration_change::add_competency_entry(
            $config->get_competency()->id,
            configuration_change::CHANGED_CRITERIA,
            $action_time
        );

        return $pathway->get_id();
    }

    public static function update_returns() {
        return new \external_value(PARAM_INT, 'Pathway id');
    }

    /**
     * get_criteria_types
     */
    public static function get_criteria_types_parameters() {
        return new \external_function_parameters([]);
    }

    public static function get_criteria_types() {
        advanced_feature::require('competency_assignment');
        require_capability('totara/hierarchy:viewcompetency', context_system::instance());

        return criteria_group::export_criteria_types();
    }

    public static function get_criteria_types_returns() {
        return new \external_multiple_structure(
            new \external_single_structure([
                'type' => new \external_value(PARAM_TEXT, 'Criterion type'),
                'title' => new \external_value(PARAM_TEXT, 'Criterion title'),
                'singleuse' => new \external_value(PARAM_INT, 'Indication whether this is a single-use criterion type'),
                'criterion_templatename' => new \external_value(PARAM_TEXT, 'Template to use to display and manage instances of this criterion'),
            ])
        );
    }
}
