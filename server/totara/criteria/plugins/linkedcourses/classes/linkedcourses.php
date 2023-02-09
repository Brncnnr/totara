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

namespace criteria_linkedcourses;

use coding_exception;
use totara_competency\linked_courses;
use totara_criteria\criterion;
use totara_criteria\evaluators\course_item_evaluator;
use totara_criteria\validators\course_item_validator;

global $CFG;

require_once($CFG->dirroot . '/totara/plan/lib.php');

/**
 * Class containing information of linked courses criteria
 */
class linkedcourses extends criterion {

    /**
     * Get the type of items stored in this criterion
     */
    public function get_items_type() {
        return 'course';
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
        return linkedcourses_display::class;
    }

    /*******************************************************************************************************
     * Retrieve and Save
     *******************************************************************************************************/

    /**
     * Validate the criterion attributes
     * linkedcourse criteria should contain the competency metadata
     * @return string|null Error description
     */
    protected function validate_attributes(): ?string {
        return !($this->get_competency_id() > 0) ? 'Competency id metadata is required in linkedcourses criteria' : null;
    }

    /**
     * Update derived items
     * An item is added for each currently linked course - This is in anticipation of merging of TL-22455
     */
    public function update_items(): criterion {
        $competency_id = $this->get_competency_id();
        if (is_null($competency_id)) {
            throw new coding_exception('Competency id must be set before items are updated');
        }

        $linked_courses = linked_courses::get_linked_course_ids($competency_id);
        $this->set_item_ids($linked_courses);

        return $this;
    }

    /**
     * @return string|null Class name of item_validator for this criteria type.
     */
    public static function get_item_validator_class(): ?string {
        return course_item_validator::class;
    }


    /************************************************************************************
     * Evaluation
     ************************************************************************************/

    public static function item_evaluator(): string {
        return course_item_evaluator::class;
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

        $competency_id = $this->get_competency_id();
        if (is_null($competency_id)) {
            throw new coding_exception('Competency id must be set before linked courses are retrieved');
        }
        $linked_courses = linked_courses::get_linked_course_ids($competency_id);

        if (empty($linked_courses)) {
            return get_string('error_no_courses', 'criteria_linkedcourses');
        }

        $num_required = $this->get_aggregation_num_required();
        if ($num_required > count($this->get_item_ids())) {
            return get_string('error_not_enough_courses', 'criteria_linkedcourses');
        }

        return get_string('error_no_course_completion', 'criteria_linkedcourses');
    }

    /**
     * Export the edit template name and data
     *
     * @return string
     */
    public function get_edit_template(): string {
        return 'criteria_linkedcourses/linkedcourses_edit';
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
