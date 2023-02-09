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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\watcher;

use context_user;
use core_user\hook\allow_view_profile;
use core_user\hook\allow_view_profile_field;
use core_user\profile\display_setting;
use pathway_manual\models\rating;
use totara_competency\helpers\capability_helper;
use totara_core\advanced_feature;
use totara_core\hook\base;

class core_user {

    /**
     * User access hook to check if one user can view another users profile field in the context of mod perform.
     *
     * @param allow_view_profile_field $hook
     */
    public static function allow_view_profile_field(allow_view_profile_field $hook): void {
        if ($hook->has_permission()) {
            return;
        }

        // Competencies are in the system context,
        // so if the context is in the course context then the competencies permissions aren't relevant.
        if ($hook->get_course() !== null) {
            return;
        }

        if (advanced_feature::is_disabled('competency_assignment')) {
            return;
        }

        if (self::is_allowed_field($hook->field) && self::can_view_user($hook)) {
            $hook->give_permission();
            return;
        }
    }

    /**
     * User access hook to check if one user can view another users profile data in the context of competencies.
     *
     * @param base|allow_view_profile|allow_view_profile_field $hook
     * @return bool
     */
    private static function can_view_user(base $hook): bool {
        $user_id = $hook->target_user_id;
        $context = context_user::instance($user_id);

        if (capability_helper::can_view_profile($user_id, $context)) {
            return true;
        }

        if (capability_helper::can_assign($user_id, $context)) {
            return true;
        }

        if (has_capability('totara/competency:rate_other_competencies', $context)) {
            return true;
        }

        if (rating::users_share_rating($hook->viewing_user_id, $hook->target_user_id)) {
            return true;
        }

        if (rating::can_see_raters_of_staff_members($hook->viewing_user_id, $hook->target_user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Can the specified field be resolved in the context of competencies?
     *
     * @param string $field
     * @return bool
     */
    private static function is_allowed_field(string $field): bool {
        $allowed_fields = [
            'id',
            'fullname',
        ];

        $allowed_fields = array_merge($allowed_fields, display_setting::get_display_fields());
        $allowed_fields = array_merge($allowed_fields, display_setting::get_default_display_picture_fields());

        return in_array($field, $allowed_fields, true);
    }

}
