<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer.
 *
 * NOTE: Performance is very important here!
 */
final class observer {
    /**
     * Event observer.
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        device::delete($event->objectid);
    }

    public static function fcmtoken_rejected(\message_totara_airnotifier\event\fcmtoken_rejected $event) {
        device::invalidate_fcmtoken($event->other['fcmtoken']);
    }
}
