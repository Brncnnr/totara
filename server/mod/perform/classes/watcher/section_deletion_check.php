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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\watcher;

use coding_exception;
use mod_perform\hook\pre_section_deleted;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\section_element_reference;

/**
 * Check if a section can be deleted
 *
 * @package performelement_redisplay\watcher
 */
class section_deletion_check extends deletion_check_base {

    /**
     * Section only can be deleted if it is not referenced by any reference element of a different section.
     *
     * @param pre_section_deleted $hook
     * @throws coding_exception
     */
    public static function can_delete(pre_section_deleted $hook): void {
        $section_id = $hook->get_section_id();
        $referencing_section_elements = section_element_reference::get_section_elements_that_reference_section($section_id)
            ->filter(function (section_element $section_element) use ($section_id) {
                return $section_element->section->get_id() !== $section_id;
            });

        $can_delete = $referencing_section_elements->count() < 1;

        if (!$can_delete) {
            $hook->add_reason(
                'is_referenced_by_element',
                get_string('modal_can_not_delete_section_message', 'mod_perform'),
                self::get_data($referencing_section_elements)
            );
        }
    }
}
