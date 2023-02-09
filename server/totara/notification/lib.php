<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */

use core_user\output\myprofile;
use core_user\output\myprofile\category;
use totara_core\extended_context;
use totara_notification\interactor\notification_audit_interactor;

/**
 * Add notification logs to myprofile page.
 *
 * @param myprofile\tree $tree Tree object
 * @param object $user user object
 * @param bool $iscurrentuser
 * @param object $course Course object
 *
 * @return bool
 */
function totara_notification_myprofile_navigation(myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $USER;

    $user_context = context_user::instance($user->id);
    $extended_context = extended_context::make_with_context($user_context);
    $audit_interactor = new notification_audit_interactor($extended_context, $USER->id, $user->id);

    if (!$audit_interactor->has_any_capability_for_context() || isguestuser($user)) {
        return false;
    }

    $category = new category('notifications', get_string('notifications', 'totara_notification'), 'mylearning');
    $tree->add_category($category);

    $tree->add_node(
        new myprofile\node(
            'notifications',
            'notification_logs',
            get_string('viewnotificationlogs', 'totara_notification'),
            null,
            new moodle_url('/totara/notification/notification_event_log.php', ['user_id' => $user->id])
        )
    );

    return true;
}
