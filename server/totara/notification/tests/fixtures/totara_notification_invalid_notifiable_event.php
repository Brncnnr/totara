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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use totara_core\extended_context;
use totara_notification\event\notifiable_event;

/**
 * This class should only be used for any sort of testing that you
 * would want to give an error and expect the error rather than
 * expecting a successfull result.
 *
 * Ideally that this fixture file does not come with the resolver class.
 */
class totara_notification_invalid_notifiable_event implements notifiable_event {
    /**
     * @var int
     */
    private $context_id;

    /**
     * @return string
     */
    public static function get_notification_title(): string {
        return "This is invalid title";
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
    public static function get_notification_available_schedules(): array {
        return [];
    }

    /**
     * @return array
     */
    public static function get_notification_default_delivery_channels(): array {
        return [];
    }

    /**
     * @return array
     */
    public static function get_notification_available_placeholder_options(): array {
        return [];
    }

    /**
     * @return array
     */
    public function get_notification_event_data(): array {
        return [];
    }
}