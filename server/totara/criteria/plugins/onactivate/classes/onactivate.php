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
 * @package totara_criteria
 */

namespace criteria_onactivate;

use coding_exception;
use totara_criteria\criterion;
use totara_criteria\evaluators\assignment_item_evaluator;

/**
 * Class containing information of course completion criteria instances
 */
class onactivate extends criterion {

    /**
     * Get the type of items stored in this criterion
     * V1 - Assuming that a criterion can only store items of a single type
     */
    public function get_items_type() {
        return 'onactivate';
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
     * Does this criterion allow for aggregation between items
     *
     * @return bool
     */
    public function has_aggregation(): bool {
        return false;
    }

    /**
     * Is this a single-use criterion type
     *
     * @return bool
     */
    public function is_singleuse(): bool {
        return true;
    }

    /**
     * Return the display class for this criterion
     *
     * @return string Class to use for displaying
     */
    protected function get_display_class(): string {
        return onactivate_display::class;
    }

    /*******************************************************************************************************
     * Retrieve and Save
     *******************************************************************************************************/

    /**
     * Validate the criterion attributes
     * onactivate criteria should contain the competency metadata
     * @return string|null Error description
     */
    protected function validate_attributes(): ?string {
        return !($this->get_competency_id() > 0) ? 'Competency id metadata is required in onactivate criteria' : null;
    }

    /**
     * Update derived items
     * We create a single item with the competency_id as item_id to allow for the rest of the process
     * to work in the same manner as all other plugins
     */
    public function update_items(): criterion {
        $competency_id = $this->get_competency_id();
        if (is_null($competency_id)) {
            throw new coding_exception('Competency id must be set before items are updated');
        }

        $this->set_item_ids([$competency_id]);

        return $this;
    }


    /************************************************************************************
     * Evaluation
     ************************************************************************************/

    public static function item_evaluator(): string {
        return assignment_item_evaluator::class;
    }


    /************************************************************************************
     * Data exporting
     * TODO - remove once all APIs have been replaced by GraphQL
     ************************************************************************************/

    /**
     * Return the name of the template for editing this criterion
     *
     * @return string Edit template name
     */
    public function get_edit_template(): string {
        return 'criteria_onactivate/onactivate_edit';
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
