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
use mod_perform\hook\pre_activity_deleted;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\section_element_reference;

/**
 * Check If an activity can be deleted
 *
 * @package performelement_redisplay\watcher
 */
class activity_deletion_check extends deletion_check_base {

    /**
     * Add a warning to the hook if activity is referenced by any reference element of a different activity
     *
     * Note this method is named 'can_delete' for legacy reasons - it used to prevent deletion instead of adding a warning.
     *
     * @param pre_activity_deleted $hook
     * @throws coding_exception
     */
    public static function can_delete(pre_activity_deleted $hook): void {
        $activity_id = $hook->get_activity_id();
        $section_elements_from_other_activities = section_element_reference::get_section_elements_that_reference_activity($activity_id)
            ->filter(function (section_element $section_element) use ($activity_id) {
                return (int)$section_element->section->activity_id !== $activity_id;
            });

        $has_reference = $section_elements_from_other_activities->count() > 0;

        if ($has_reference) {
            $hook->add_warning(
                'is_referenced_by_element',
                get_string('modal_warning_delete_activity_message', 'mod_perform'),
                self::get_warning_data($section_elements_from_other_activities)
            );
        }
    }

}
