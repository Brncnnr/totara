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
 * @package criteria_childcompetency;
 */

namespace criteria_childcompetency;

use totara_criteria\criterion_display;

/**
 * Display class for childcompetency criteria
 */

class childcompetency_display extends criterion_display {

    /**
     * Return the display type of items associated with the criterion
     * TODO: make protected when all UI is on vueJs
     *
     * @return string
     */
    public function get_display_items_type(): string {
        return $this->criterion->get_title();
    }

    /**
     * Return a summarized view of the criterion items for display
     *
     * @return string[]
     */
    protected function get_display_configuration_items(): array {
        global $DB;

        if ($this->criterion->is_valid()) {
            return [];
        }

        $competency_id = $this->criterion->get_competency_id();
        if (is_null($competency_id)) {
            throw new coding_exception('Competency id must be set before children are retrieved');
        }

        $child_competencies = $DB->get_fieldset_select('comp', 'id', 'parentid = :parentid',
            ['parentid' => $competency_id]
        );
        if (empty($child_competencies)) {
            $error = get_string('error_no_children', 'criteria_childcompetency');
        } else {
            $num_required = $this->criterion->get_aggregation_num_required();
            if (count($child_competencies) < $num_required) {
                $error = get_string('error_not_enough_children', 'criteria_childcompetency');
            } else {
                $error = get_string('error_cant_become_proficient', 'criteria_childcompetency');
            }
        }

        return [
            (object)[
                'description' => '',
                'error' => $error,
            ],
        ];
    }

}
