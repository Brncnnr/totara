<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\watcher;

use performelement_linked_review\content_type_factory;
use performelement_linked_review\entity\linked_review_repository;
use totara_core\advanced_feature;
use totara_core\hook\component_access_check;

class component_access {
    /**
     * Hook to check if a user is a selecting participant in any linked review element in the subject's activities
     *
     * @param component_access_check $hook
     */
    public static function check_selecting_participants(component_access_check $hook): void {
        if ($hook->has_permission()) {
            return;
        }

        if (!advanced_feature::is_enabled('performance_activities')) {
            return;
        }

        $content_type = self::get_content_type($hook);
        if (empty($content_type)) {
            return;
        }

        $subject_user_id = $hook->get_target_user_id();
        $participant_user_id = $hook->get_viewing_user_id();

        // Handle site admins explicitly (performance optimisation)
        if ($participant_user_id == get_admin()->id) {
            $hook->give_permission();
            return;
        }

        if (linked_review_repository::user_is_selecting_participant($content_type, $participant_user_id, $subject_user_id)){
            $hook->give_permission();
        }
    }

    /**
     * Get the linked element content type.
     *
     * @param component_access_check $hook
     * @return string|null
     */
    private static function get_content_type(component_access_check $hook): ?string {
        $enabled_content_types = content_type_factory::get_all_enabled();
        foreach ($enabled_content_types as $enabled_type) {
            if ($enabled_type::is_for_access_hook($hook)) {
                return $enabled_type::get_identifier();
            }
        }

        return null;
    }
}
