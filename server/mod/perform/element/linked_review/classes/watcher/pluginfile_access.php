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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\watcher;

use mod_perform\entity\activity\element;
use mod_perform\entity\activity\section_element;
use mod_perform\models\activity\helpers\external_participant_token_validator;
use totara_core\advanced_feature;
use totara_evidence\hook\pluginfile_access as pluginfile_access_hook;
use performelement_linked_review\entity\linked_review_content as linked_review_content_entity;

/**
 * Class watches for pluginfile access requests that might be linked to an activity.
 *
 * @package performelement_linked_review\watcher
 */
class pluginfile_access {

    /**
     * Process hook.
     *
     * @param pluginfile_access_hook $hook
     *
     * @return void
     */
    public static function can_view(pluginfile_access_hook $hook): void {
        // Leave if perform is not enabled.
        if (!advanced_feature::is_enabled('performance_activities')) {
            return;
        }

        $args = $hook->get_args();
        if (count($args) >= 3) {
            // Get participant token.
            $token = $args[2];

            // Validate external participant token.
            $validator = new external_participant_token_validator($token);
            if (!$validator->is_valid()) {
                return;
            }

            // Validate subject instance linked to external participant.
            if ($validator->is_subject_instance_closed()) {
                return;
            }

            // Get entities.
            $participant_instance = $validator->get_participant_instance();
            $subject_instance = $participant_instance->subject_instance;

            if ($subject_instance->should_be_hidden()) {
                return;
            }

            $exists = linked_review_content_entity::repository()
                ->join([section_element::TABLE, 'se'], 'section_element_id', 'id')
                ->join([element::TABLE, 'e'], 'se.element_id', 'id')
                ->where('e.plugin_name', 'linked_review')
                ->where('content_type', $hook->get_component())
                ->where('content_id', $hook->get_instance_id())
                ->where('subject_instance_id', $subject_instance->id)
                ->exists();
            if ($exists) {
                $hook->allow_view();
            }
        }
    }

}