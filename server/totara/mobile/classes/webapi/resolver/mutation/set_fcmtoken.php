<?php
/*
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\webapi\resolver\mutation;

use totara_mobile\local\device;
use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use core\webapi\middleware\require_login;
use totara_mobile\event\fcmtoken_received;
use core\orm\query\builder;

/**
 * Mutation to set an FCM Token for a device
 */
class set_fcmtoken extends mutation_resolver {

    /**
     * Sets the fcm token for the given device.
     *
     * @param array $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec) {
        global $DB, $USER;

        require_capability('totara/mobile:use', \context_user::instance($USER->id));

        // Only allow mobile contexts to set fcm tokens.
        if (!($ec instanceof \totara_mobile\webapi\execution_context)) {
            return false;
        }

        // Make sure the context is linked to a valid device.
        $deviceid = $ec->get_device_id();
        if (!$deviceid) {
            return false;
        }

        // Make sure the device belongs to the logged in user... just in case.
        $device = builder::table('totara_mobile_devices')->find($deviceid);
        if (empty($device->userid) || $device->userid != $USER->id) {
            return false;
        }

        // The token can be empty (reset) but the argument has to be handed through.
        if (!isset($args['token'])) {
            return false;
        }

        // Update the field.
        $device->fcmtoken = $args['token'];
        $result = builder::table('totara_mobile_devices')
            ->where('id', $deviceid)
            ->update(['fcmtoken' => $device->fcmtoken]);

        // Are there other devices which are using the same token? Log them out!
        // But only if token is not null.
        if (!empty($device->fcmtoken)) {
            $other_devices = builder::table('totara_mobile_devices')
                ->where('fcmtoken', $device->fcmtoken)
                ->where('id', '<>', $device->id)
                ->get();
            foreach ($other_devices as $logout) {
                device::delete($logout->userid, $logout->id);
            }
        }

        // Trigger a token-received event.
        fcmtoken_received::create_from_device($device)->trigger();

        return true;
    }


    // You can't do this unless you're logged in.
    public static function get_middleware(): array {
        return [
            new require_login()
        ];
    }
}
