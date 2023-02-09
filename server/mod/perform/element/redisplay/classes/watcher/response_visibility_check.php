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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_redisplay
 */

namespace performelement_redisplay\watcher;

use mod_perform\hook\element_response_visibility;
use mod_perform\models\activity\participant_source;
use mod_perform\entity\activity\section_element_reference;
use performelement_redisplay\redisplay;

class response_visibility_check {

    /**
     * Override whether a participant instance can view a response if it is a redisplay element that they are allowed to view.
     *
     * @param element_response_visibility $hook
     */
    public static function can_view(element_response_visibility $hook): void {
        if ($hook->get_can_view()) {
            return;
        }

        $is_participating_in_section_with_redisplay = section_element_reference::repository()
            ->join('perform_element', 'referencing_element_id', 'id')
            ->join('perform_section_element', 'perform_element.id', 'element_id')
            ->join('perform_participant_section', 'perform_section_element.section_id', 'section_id')
            // We only want to look at what is re-displaying the original response
            ->where('source_section_element_id', $hook->get_element_response()->section_element_id)
            ->where('perform_element.plugin_name', redisplay::get_plugin_name());

        if ($hook->get_viewing_participant_instance()) {
            $is_participating_in_section_with_redisplay
                ->where('perform_participant_section.participant_instance_id', $hook->get_viewing_participant_instance()->id);
        } else {
            $is_participating_in_section_with_redisplay
                ->join('perform_participant_instance', 'perform_participant_section.participant_instance_id', 'id')
                ->where('perform_participant_instance.participant_id', $hook->get_viewing_user_id())
                ->where('perform_participant_instance.participant_source', participant_source::INTERNAL);
        }

        if ($is_participating_in_section_with_redisplay->exists()) {
            $hook->set_can_view();
        }
    }

}
