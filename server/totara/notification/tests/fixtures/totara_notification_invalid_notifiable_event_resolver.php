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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use totara_core\extended_context;
use totara_notification\resolver\notifiable_event_resolver;

/**
 * Use this class to test any sort of expectation from invalid notifiable event resolver.
 */
class totara_notification_invalid_notifiable_event_resolver extends notifiable_event_resolver {
    /**
     * @return string
     */
    public static function get_notification_title(): string {
        return 'Invalid notifiable event';
    }

    /**
     * @return array
     */
    public static function get_notification_available_recipients(): array {
        return [];
    }

    /**
     * @return array
     */
    public static function get_notification_default_delivery_channels(): array {
        return ['email', 'popup'];
    }

    /**
     * @return array
     */
    public static function get_notification_available_placeholder_options(): array {
        return [];
    }

    public function get_extended_context(): extended_context {
        return extended_context::make_system();
    }
}