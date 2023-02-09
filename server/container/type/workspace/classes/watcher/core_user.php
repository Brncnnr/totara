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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\watcher;

use core_user\hook\allow_view_profile_field;
use container_workspace\workspace;
use core_user\profile\display_setting;
use totara_core\advanced_feature;

/**
 * This is for resolving all the profile field view request.
 */
final class core_user {
    /**
     * @param allow_view_profile_field $hook
     * @return void
     */
    public static function watch_allow_profile_field(allow_view_profile_field $hook): void {
        global $DB;

        if ($hook->has_permission()) {
            return;
        }

        if (!advanced_feature::is_enabled('container_workspace')) {
            return;
        }

        $course = $hook->get_course();
        if (null === $course || workspace::get_type() !== $course->containertype) {
            // Context is not appearing.
            return;
        }

        // We are only allowing several fields within workspace, but not all.
        $field = $hook->field;
        $valid_fields = array_merge(
            array_values(display_setting::get_display_fields()),
            display_setting::get_default_display_picture_fields(),
            ['fullname']
        );

        if (!in_array($field, $valid_fields)) {
            return;
        }

        // Note: this just a temporary solution to help by-pass any profile fields. So that
        // it can be unblocked from this access controller. The right way to fix it is to fix
        // it within access_controller class itself.
        if (is_siteadmin($hook->viewing_user_id)) {
            // It is a hack for pretty much site_admin.
            $hook->give_permission();
        }

        // Note: we do not check for member here or any public/private/hidden workspaces.
        // This is intentional as those checks should have been performed before this point.

        if ('email' === $field) {
            // We have to respect the mail display settings from user record.
            // Note: this is a temporary solution, ideally the access_controller should handle this for us.
            $mail_display = $DB->get_field('user', 'maildisplay', ['id' => $hook->target_user_id]);
            $valid_settings = [
                \core_user::MAILDISPLAY_EVERYONE,
                \core_user::MAILDISPLAY_COURSE_MEMBERS_ONLY
            ];

            if (in_array($mail_display, $valid_settings)) {
                $hook->give_permission();
            }

            return;
        }

        // The rest of fields will be given with permission.
        $hook->give_permission();
    }
}