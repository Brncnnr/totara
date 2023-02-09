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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace performelement_linked_review\watcher;

use core\format;
use core\webapi\formatter\field\string_field_formatter;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\section_element;
use mod_perform\hook\pre_section_relationship_deleted;
use mod_perform\models\activity\section_element as section_element_model;

class section_relationship_deletion_check {

    public static function can_delete(pre_section_relationship_deleted $hook) {
        if ($hook->get_ignore_conflicts_on_same_section()) {
            return;
        }
        $section_relationship = $hook->get_section_relationship();
        $linked_review_elements = self::get_linked_review_section_relationships($section_relationship->section_id);

        foreach ($linked_review_elements as $linked_review_element) {
            $element_data = json_decode($linked_review_element->element->data);

            $formatter = new string_field_formatter(format::FORMAT_PLAIN, $section_relationship->section->activity->get_context());
            $title = $formatter->format($linked_review_element->element->title);

            if (in_array($section_relationship->core_relationship_id, $element_data->selection_relationships)) {
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

    private static function get_linked_review_section_relationships(int $section_id) {
        return section_element::repository()->where('section_id', $section_id)
            ->join([element::TABLE, 'e'], 'element_id', 'id')
            ->where('e.plugin_name', 'linked_review')
            ->with('element')
            ->get()
            ->map_to(section_element_model::class);
    }
}