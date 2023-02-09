<?php
/**
 * This file is part of Totara LMS
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
 * @author  Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_comment
 */

namespace totara_comment\watcher;

use core_user\hook\allow_view_profile_field;
use core_user\profile\display_setting;
use totara_core\advanced_feature;

/**
 * Class to handle user hooks.
 */
final class core_user {

    /**
     * User access hook to check if one user can view another users profile field.
     *
     * @param allow_view_profile_field $hook
     */
    public static function handle_allow_view_profile_field(allow_view_profile_field $hook): void {
        if ($hook->has_permission()) {
            return;
        }

        // TODO this is currently based on whether engage is enabled. It should probably be independent.
        //      Note that changing this will cause a bunch of tests to fail, because this watcher needs
        //      to be disabled in order for them to be able to properly test what they are testing.
        if (!advanced_feature::is_enabled('engage_resources')) {
            return;
        }

        $course = $hook->get_course();
        if ($course) {
            // Course does exist in the context - hence those course related component should handle it.
            return;
        }

        // Comments could be made in any type of context, so we don't examine the hook course property.

        // We allow users to see some hard-coded user properties of other users. The comment could appear in
        // any context, so we just have to allow this in every context.
        if ($hook->field == 'fullname'
            || in_array($hook->field, display_setting::get_default_display_picture_fields())
        ) {
            $hook->give_permission();
            return;
        }
    }
}
