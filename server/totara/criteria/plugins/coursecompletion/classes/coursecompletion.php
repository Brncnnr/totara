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

namespace criteria_coursecompletion;

use totara_criteria\criterion;
use totara_criteria\evaluators\course_item_evaluator;
use totara_criteria\validators\course_item_validator;
use context_course;

/**
 * Class containing information of course completion criteria instances
 */
class coursecompletion extends criterion {

    /**
     * Get the type of items stored in this criterion
     * V1 - Assuming that a criterion can only store items of a single type
     */
    public function get_items_type() {
        return 'course';
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
        return coursecompletion_display::class;
    }


    /*******************************************************************************************************
     * Retrieve and Save
     *******************************************************************************************************/

    /**
     * Update derived items
     */
    public function update_items(): criterion {
        // Course completion has no derived items
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

        if (empty($this->get_item_ids())) {
            return get_string('error_no_courses', 'criteria_coursecompletion');
        }

        $num_required = $this->get_aggregation_num_required();
        if ($num_required > count($this->get_item_ids())) {
            return get_string('error_not_enough_courses', 'criteria_coursecompletion');
        }

        // Some courses can not be completed
        return get_string('error_invalid_configuration', 'totara_criteria');
    }


    /**
     * Return the name of the template for defining this criterion
     *
     * @return string Edit template name
     */
    public function get_edit_template(): string {
        return 'criteria_coursecompletion/coursecompletion_edit';
    }

    /**
     * Export definition item data
     *
     * @return  array Array of item detail
     */
    public function export_edit_items(): array {
        global $DB;

        $items = [];

        foreach ($this->get_item_ids() as $course_id) {
            $item_detail = [
                'type' => $this->get_items_type(),
                'id' => $course_id,
                'value' => $course_id,
            ];

            $course = $DB->get_record('course', ['id' => $course_id]);
            if ($course) {
                $item_detail['text'] = format_string(get_course_display_name_for_list($course), true, ['context' => context_course::instance($course_id)]);
                $item_detail['error'] = $course->enablecompletion
                    ? ''
                    : get_string('error_no_course_completion', 'criteria_coursecompletion');
            } else {
                $item_detail['text'] = '';
                $item_detail['error'] = get_string('error_no_course', 'criteria_coursecompletion');
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
