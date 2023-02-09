<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state\activity\condition;

use mod_perform\models\activity\activity;
use mod_perform\state\condition;

defined('MOODLE_INTERNAL') || die();

/**
 * The activity has at least one section with at least one valid question and one relationship
 */
class at_least_one_section_with_question_and_answering_relationship extends condition {

    public function pass(): bool {
        /** @var activity $activity */
        $activity = $this->object;

        $sections = $activity->get_sections_ordered_with_respondable_element_count();
        if ($sections->count() == 0) {
            return false;
        }

        // Check whether the activity has at least one respondable section element
        // and one answering relationship for a section
        foreach ($sections as $section) {
            $relationships = $section->get_answering_section_relationships();
            if ($relationships->count() < 1 || $section->get_respondable_element_count() < 1) {
                // One of the section does not meet criteria.
                return false;
            }
        }
        return true;
    }
}
