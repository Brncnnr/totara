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
 * @package criteria_childcompetency
 */

namespace criteria_childcompetency;

use coding_exception;
use totara_criteria\criterion;
use totara_criteria\evaluators\competency_item_evaluator;
use totara_criteria\validators\competency_item_validator;

/**
 * Class containing information of child competency criteria
 */
class childcompetency extends criterion {

    /**
     * Get the type of items stored in this criterion
     */
    public function get_items_type() {
        return 'competency';
    }

    /**
     * Does this criterion have associated items
     *
     * @return bool
     */
    public function has_items(): bool {
        return false;
    }

    /**
     * Return the display class for this criterion
     *
     * @return string Class to use for displaying
     */
    protected function get_display_class(): string {
        return childcompetency_display::class;
    }

    /*******************************************************************************************************
     * Retrieve and Save
     *******************************************************************************************************/

    /**
     * Validate the criterion attributes
     * A childcompetency should contain the competency metadata
     * @return string|null Error description
     */
    protected function validate_attributes(): ?string {
        $competency_id = $this->get_competency_id();
        return is_null($competency_id) ? 'Competency id metadata is required in childcompetency criteria' : null;
    }

    /**
     * Update derived items
     * An item is added for each child competency
     */
    public function update_items(): criterion {
        global $DB;

        $competency_id = $this->get_competency_id();
        if (is_null($competency_id)) {
            throw new coding_exception('Competency id must be set before items are updated');
        }

        $child_competencies = $DB->get_fieldset_select('comp', 'id', 'parentid = :parentid', ['parentid' => $competency_id]);
        $this->set_item_ids($child_competencies);

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
        global $DB;

        if ($this->is_valid()) {
            return '';
        }

        $competency_id = $this->get_competency_id();
        if (is_null($competency_id)) {
            throw new coding_exception('Competency id must be set before children are retrieved');
        }

        $child_competencies = $DB->get_fieldset_select('comp', 'id', 'parentid = :parentid',
            ['parentid' => $competency_id]
        );
        if (empty($child_competencies)) {
            return get_string('error_no_children', 'criteria_childcompetency');
        }

        $num_required = $this->get_aggregation_num_required();
        if (count($child_competencies) < $num_required) {
            return get_string('error_not_enough_children', 'criteria_childcompetency');
        }

        return get_string('error_cant_become_proficient', 'criteria_childcompetency');
    }


    /**
     * Export the edit template name and data
     *
     * @return string
     */
    public function get_edit_template(): string {
        return 'criteria_childcompetency/childcompetency_edit';
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
