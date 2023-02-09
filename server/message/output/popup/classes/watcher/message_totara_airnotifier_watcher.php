<?php
/*
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package message_popup
 */

namespace message_popup\watcher;

use message_totara_airnotifier\hook\airnotifier_message_count_discovery;
use message_popup\api;

defined('MOODLE_INTERNAL') || die();

/**
 * A hook watcher for airnotifier message output plugin hooks.
 */
final class message_totara_airnotifier_watcher {
    /**
     * A watcher to discover unread message count for pushing as app badge.
     *
     * @param airnotifier_message_count_discovery $hook
     * @return void
     */
    public static function discover_message_count(airnotifier_message_count_discovery $hook): void {

        $user = $hook->get_user();

        $message_count = api::count_unread_popup_notifications($user->id);
        if (is_numeric($message_count) && $message_count > 0) {
            $hook->add_to_count($message_count);
        }
    }
}