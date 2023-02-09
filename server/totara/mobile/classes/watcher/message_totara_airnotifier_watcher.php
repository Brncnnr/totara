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
 * @package totara_mobile
 */

namespace totara_mobile\watcher;

use message_totara_airnotifier\hook\airnotifier_device_discovery;
use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

/**
 * A hook watcher for airnotifier message output plugin hooks.
 */
final class message_totara_airnotifier_watcher {
    /**
     * A watcher to discover a user's devices.
     *
     * @param airnotifier_device_discovery $hook
     * @return void
     */
    public static function discover_mobile_devices(airnotifier_device_discovery $hook): void {
        // Do nothing if the mobile app is disabled.
        if (!get_config('totara_mobile', 'enable')) {
            return;
        }

        $user = $hook->get_user();

        // Find all of the user's FCM tokens, and add them to the hook.
        $devices = builder::table('totara_mobile_devices')
            ->where_not_null('fcmtoken')
            ->where('userid', $user->id)
            ->fetch();

        $tokens = [];
        foreach ($devices as $device) {
            $tokens[$device->fcmtoken] = $device->fcmtoken;
        }

        $hook->add_device_keys($tokens);
    }
}