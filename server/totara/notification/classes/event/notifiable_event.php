<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\event;

use totara_core\extended_context;

/**
 * An interface to help us integrating the centralised notification system's event with the
 * current system's event.
 *
 * If you would like to queue up the notification right away after what has been hapened
 * in your plugin system then the event that you trigger to notify the rest of system what
 * had happened should implement this interface and therefore one of centralised notification
 * system's observer will queue it for you.
 */
interface notifiable_event {
    /**
     * Returns a hash-map of data attributes that the event should be using to feed to all the
     * notifications, that can be produced by this event.
     *
     * @return array
     */
    public function get_notification_event_data(): array;
}