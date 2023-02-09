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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package criteria_othercompetency
 */

namespace criteria_othercompetency;

use totara_competency\achievement_configuration;
use totara_criteria\criterion;
use totara_criteria\evaluators\competency_item_evaluator;
use totara_criteria\validators\competency_item_validator;
use totara_hierarchy\entity\competency as competency_entity;
use Exception;
use context_system;

/**
 * Class containing information of other competencies criteria instances
 */
class othercompetency extends criterion {

    /**
     * Get the type of items stored in this criterion
     * V1 - Assuming that a criterion can only store items of a single type
     */
    public function get_items_type() {
        return 'competency';
    }

    /**
     * Does this criterion have associated metadata
     *
     * @return bool
     */
    public function has_metadata(): bool {
        return false;
    }

    /**
     * Return the display class for this criterion
     *
     * @return string Class to use for displaying
     */
    protected function get_display_class(): string {
        return othercompetency_display::class;
    }


    /*******************************************************************************************************
     * Retrieve and Save
     *******************************************************************************************************/

    /**
     * Update derived items
     */
    public function update_items(): criterion {
        // Other Competency has no derived items
        return $this;
    }

    /**
     * @return string|null Class name of item_validator for this criteria type.
     */
    public static function get_item_validator_class(): ?string {
        return competency_item_validator::class;
    }

    /************************************************************************************
     * Evaluation
     ************************************************************************************/


    public static function item_evaluator(): string {
        return competency_item_evaluator::class;
    }

    /************************************************************************************
     * Data exporting
     * TODO - remove once all APIs have been replaced by GraphQL
     ************************************************************************************/

    /**
     * @return string
     */
    public function export_configuration_error_description(): string {
        if ($this->is_valid()) {
            return '';
        }

        if (empty($this->get_item_ids())) {
            return get_string('error_no_competencies', 'criteria_othercompetency');
        }

        $num_required = $this->get_aggregation_num_required();
        if ($num_required > count($this->get_item_ids())) {
            return get_string('error_not_enough_competencies', 'criteria_othercompetency');
        }

        // Some competencies invalid
        return get_string('error_invalid_configuration', 'totara_criteria');
    }

    /**
     * Return the name of the template for defining this criterion
     *
     * @return string Edit template name
     */
    public function get_edit_template(): string {
        return 'criteria_othercompetency/othercompetency_edit';
    }

    /**
     * Export definition item data
     *
     * @return  array Array of item detail
     */
    public function export_edit_items(): array {
        $items = [];

        foreach ($this->get_item_ids() as $competency_id) {
            $item_detail = [
                'type' => $this->get_items_type(),
                'id'   => $competency_id,
                'value' => $competency_id,
            ];

            try {
                $competency = new competency_entity($competency_id);
                $config = new achievement_configuration($competency);
                $item_detail['text'] = format_string($competency->fullname, true, ['context' => context_system::instance()]);
                $item_detail['error'] = $config->user_can_become_proficient()
                    ? ''
                    : get_string('error_competency_cannot_proficient', 'criteria_othercompetency');
            } catch (Exception $e) {
                $item_detail['name'] = '';
                $item_detail['error'] = get_string('error_no_competency', 'criteria_othercompetency');
            }

            $items[] = $item_detail;
        }

        return $items;
    }

    /************************************************************************************
     * Copying
     ************************************************************************************/
    /**
     * @inheritDoc
     */
    public function make_template(): ?self {
        return (new self())
            ->set_aggregation_method($this->get_aggregation_method())
            ->set_aggregation_params($this->get_aggregation_params());
    }
}
