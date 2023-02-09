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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\totara_notification;

/**
 *  Allows some specific workspace notifications to be silenced on-the-fly.
 */
class workspace_muter {

    const UNMUTED = 1;
    const MUTED = 2;
    const FULL_MUTED = 3;

    /**
     * @var array Collection of muted workspace notifications
     */
    private static $muted = [];

    /**
     * @return array
     */
    public static function muted(): array {
        return self::$muted;
    }

    /**
     * Full mute the named notification. A full mute will remain muted for the request lifetime until removed.
     *
     * @param string $notification_class
     * @param int $workspace_id
     * @param int $user_id
     */
    public static function full_mute(string $notification_class, int $workspace_id, int $user_id): void {
        self::set($notification_class, $workspace_id, $user_id, self::FULL_MUTED);
    }

    /**
     * Check to see if this specific notification is muted or not.
     * If it's a regular mute, it will be immediately removed.
     *
     * @param string $notification_class
     * @param int $workspace_id
     * @param int $user_id
     * @return bool
     */
    public static function is_muted(string $notification_class, int $workspace_id, int $user_id): bool {
        $muted = self::set($notification_class, $workspace_id, $user_id);

        if ($muted === self::MUTED) {
            self::unmute($notification_class, $workspace_id, $user_id);
        }

        return $muted !== self::UNMUTED;
    }

    /**
     * Mute the named notification. A mute will remain in place once, and be automatically removed once read.
     *
     * @param string $notification_class
     * @param int $workspace_id
     * @param int $user_id
     */
    public static function mute(string $notification_class, int $workspace_id, int $user_id): void {
        self::set($notification_class, $workspace_id, $user_id, self::MUTED);
    }

    /**
     * Reset the stored muted notification settings
     *
     * @return void
     */
    public static function reset(): void {
        self::$muted = [];
    }

    /**
     * Unmute the named notification
     *
     * @param string $notification_class
     * @param int $workspace_id
     * @param int $user_id
     */
    public static function unmute(string $notification_class, int $workspace_id, int $user_id): void {
        self::set($notification_class, $workspace_id, $user_id, self::UNMUTED);
    }

    /**
     * @param string $notification_class
     * @param int $workspace_id
     * @param int $user_id
     * @param int|null $state
     * @return int
     */
    private static function set(string $notification_class, int $workspace_id, int $user_id, ?int $state = null): int {
        if (empty(self::$muted[$workspace_id][$user_id][$notification_class])) {
            self::$muted[$workspace_id][$user_id][$notification_class] = self::UNMUTED;
        }

        if ($state !== null) {
            self::$muted[$workspace_id][$user_id][$notification_class] = $state;
        }

        return self::$muted[$workspace_id][$user_id][$notification_class];
    }

}