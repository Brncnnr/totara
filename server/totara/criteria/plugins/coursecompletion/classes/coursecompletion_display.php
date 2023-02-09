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
 * @package criteria_coursecompletion;
 */

namespace criteria_coursecompletion;

use totara_criteria\criterion_display;
use context_course;

/**
 * Display class for coursecompletion criteria
 */

class coursecompletion_display extends criterion_display {

    /**
     * Return the display type of items associated with the criterion
     * TODO: make protected when all UI is on vueJs
     *
     * @return string
     */
    public function get_display_items_type(): string {
        return get_string('courses', 'criteria_coursecompletion');
    }

    /**
     * Return a summarized view of the criterion items for display
     *
     * @return string[]
     */
    protected function get_display_configuration_items(): array {
        global $DB;

        $course_ids = $this->criterion->get_item_ids();
        if (empty($course_ids)) {
            return [
                (object)[
                    'description' => '',
                    'error' => get_string('error_no_courses', 'criteria_coursecompletion'),
                ],
            ];
        }

        $items = [];
        $num_required = $this->criterion->get_aggregation_num_required();
        if ($num_required > count($course_ids)) {
            $items[] = (object)[
                'description' => '',
                'error' => get_string('error_not_enough_courses', 'criteria_coursecompletion'),
            ];
        }

        foreach ($course_ids as $course_id) {
            $item_detail = [];
            $course = $DB->get_record('course', ['id' => $course_id]);
            if ($course) {
                $item_detail['description'] = format_string(
                    get_course_display_name_for_list($course),
                    true,
                    [
                        'context' => context_course::instance($course->id)
                    ]
                );

                if (!$course->enablecompletion) {
                    $item_detail['error'] = get_string('error_no_course_completion', 'criteria_coursecompletion');
                }
            } else {
                $item_detail['description'] = '';
                $item_detail['error'] = get_string('error_no_course', 'criteria_coursecompletion');
            }

            $items[] = (object)$item_detail;
        }

        return $items;
    }

}
