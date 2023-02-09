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
 * @author  Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author  Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\watcher;

use core_user\hook\allow_view_profile;
use core_user\hook\allow_view_profile_field;
use core_user\profile\display_setting;
use totara_engage\engage_core;

/**
 * Class to handle user hooks.
 */
final class core_user {

    /**
     * Handles the allow_view_profile hook call.
     *
     * This method will give access to the users profile if any of the Engage features requiring it have been enabled.
     *
     * @param allow_view_profile $hook
     */
    public static function handle_allow_view_profile(allow_view_profile $hook) {
        global $CFG;

        if ($hook->has_permission()) {
            // They already have permission.
            return;
        }

        // Fastest resolution. This is never set through the interface, and must be defined in config.php
        // explicit type checks on true|false ensure that it did not come from the database.
        if (isset($CFG->totara_engage_allow_view_profiles)) {
            if ($CFG->totara_engage_allow_view_profiles === true) {
                $hook->give_permission();
                return;
            } else if ($CFG->totara_engage_allow_view_profiles === false) {
                return;
            }
        }

        $course = $hook->get_course();
        if ($course) {
            // Nope, we are within course context - need to stay away from it.
            return;
        }

        // Finally, check if any of the engage features have been turned on.
        if (engage_core::allow_view_user_profile()) {
            $hook->give_permission();
            return;
        }
    }

    /**
     * User access hook to check if one user can view another users profile field.
     *
     * @param allow_view_profile_field $hook
     */
    public static function handle_allow_view_profile_field(allow_view_profile_field $hook): void {
        if ($hook->has_permission()) {
            return;
        }

        if (!engage_core::allow_view_user_profile()) {
            return;
        }

        $course = $hook->get_course();
        if ($course) {
            // We assume that all queries relating to access between users in relation to engage happen
            // in the context of a user, and not in the context of a course container. When the resource
            // relates to a workspace, the workspace user watcher will grant permission where appropriate.
            return;
        }

        // We allow all users to see all profile card fields of all other users. This allows users
        // to share resources. We don't know that this operation is happening in the context of a
        // resource, so we have to assume that it could be.
        if (in_array($hook->field, display_setting::get_display_fields())
            || in_array($hook->field, display_setting::get_display_picture_fields())
        ) {
            $hook->give_permission();
            return;
        }

        // If there are other properties, beyond the profile card, that need to be accessed due to
        // some situation in engage then we should check both the field accessed and that access
        // should be allow (such as users having some measurable/testable relationship), if posible,
        // here.
        // TODO try setting user profile card to show ONLY email AND disable profile watcher above and then run all engage tests.
        return;
    }
}
