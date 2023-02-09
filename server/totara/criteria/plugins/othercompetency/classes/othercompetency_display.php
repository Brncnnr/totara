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
 * @package criteria_othercompetency;
 */

namespace criteria_othercompetency;

use Exception;
use totara_competency\achievement_configuration;
use totara_criteria\criterion_display;
use totara_hierarchy\entity\competency;

/**
 * Display class for othercompetency criteria
 */

class othercompetency_display extends criterion_display {

    /**
     * Return the display type of items associated with the criterion
     * TODO: make protected when all UI is on vueJs
     *
     * @return string
     */
    public function get_display_items_type(): string {
        return get_string('other_competencies', 'criteria_othercompetency');
    }

    /**
     * Return a summarized view of the criterion items for display
     *
     * @return string[]
     */
    protected function get_display_configuration_items(): array {

        $competency_ids = $this->criterion->get_item_ids();
        if (empty($competency_ids)) {
            return [
                (object)[
                    'description' => '',
                    'error' => get_string('error_no_competencies', 'criteria_othercompetency'),
                ],
            ];
        }

        $items = [];
        $num_required = $this->criterion->get_aggregation_num_required();
        if ($num_required > count($competency_ids)) {
            $items[] = (object)[
                'description' => '',
                'error' => get_string('error_not_enough_competencies', 'criteria_othercompetency'),
            ];
        }

        foreach ($competency_ids as $competency_id) {
            $item_detail = [];
            try {
                $competency = new competency($competency_id);
                $config = new achievement_configuration($competency);
                $item_detail['description'] = $competency->fullname;

                if (!$config->user_can_become_proficient()) {
                    $item_detail['error'] = get_string('error_competency_cannot_proficient', 'criteria_othercompetency');
                }
            } catch (Exception $e) {
                $item_detail['description'] = '';
                $item_detail['error'] = get_string('error_no_competency', 'criteria_othercompetency');
            }

            $items[] = (object)$item_detail;
        }

        return $items;
    }

}
