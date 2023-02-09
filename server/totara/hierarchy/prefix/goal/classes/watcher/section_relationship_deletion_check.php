<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 */

namespace hierarchy_goal\watcher;

use core\format;
use core\orm\collection;
use core\webapi\formatter\field\string_field_formatter;
use hierarchy_goal\performelement_linked_review\company_goal_assignment;
use hierarchy_goal\performelement_linked_review\personal_goal_assignment;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\section_element;
use mod_perform\hook\pre_section_relationship_deleted;
use mod_perform\models\activity\section_element as section_element_model;

class section_relationship_deletion_check {

    /**
     * @param pre_section_relationship_deleted $hook
     */
    public static function can_delete(pre_section_relationship_deleted $hook): void {
        if ($hook->get_ignore_conflicts_on_same_section()) {
            return;
        }
        $section_relationship = $hook->get_section_relationship();
        $linked_review_elements = self::get_linked_review_section_relationships($section_relationship->section_id);

        foreach ($linked_review_elements as $linked_review_element) {
            $formatter = new string_field_formatter(format::FORMAT_PLAIN, $section_relationship->section->activity->get_context());
            $title = $formatter->format($linked_review_element->element->title);

            if (self::is_element_using_status_changer_relationship(
                $linked_review_element,
                $section_relationship->core_relationship_id
            )) {
                $hook->add_reason(
                    'section_relationship_used_in_linked_review',
                    get_string(
                        'section_relationship_used_in_linked_review',
                        'performelement_linked_review',
                        $hook->get_section_relationship()->core_relationship->get_name()
                    ),
                    [$title]
                );
            }
        }
    }

    /**
     * @param section_element_model $linked_review_element
     * @param int $core_relationship_id
     * @return bool
     */
    private static function is_element_using_status_changer_relationship(
        section_element_model $linked_review_element,
        int $core_relationship_id
    ): bool {
        $element_data = json_decode($linked_review_element->element->data);
        $goal_content_types = [company_goal_assignment::get_identifier(), personal_goal_assignment::get_identifier()];
        if (!in_array($element_data->content_type, $goal_content_types)) {
            return false;
        }
        if (
            !isset(
                $element_data->content_type_settings,
                $element_data->content_type_settings->enable_status_change,
                $element_data->content_type_settings->status_change_relationship,
            )
            || $element_data->content_type_settings->enable_status_change !== true
        ) {
            return false;
        }

        return $core_relationship_id === (int)$element_data->content_type_settings->status_change_relationship;
    }

    /**
     * @param int $section_id
     * @return collection
     */
    private static function get_linked_review_section_relationships(int $section_id): collection {
        return section_element::repository()
            ->where('section_id', $section_id)
            ->join([element::TABLE, 'e'], 'element_id', 'id')
            ->where('e.plugin_name', 'linked_review')
            ->with('element')
            ->get()
            ->map_to(section_element_model::class);
    }
}