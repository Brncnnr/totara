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
 * @author  Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\notification\abstraction;

/**
 * Implement this interface if your built-in notification is based on an additional_criteria_resolver.
 */
interface additional_criteria_notification {
    /**
     * Get a json encoded string representing the default additional criteria configuration for the notification.
     *
     * @return string json encoded
     */
    public static function get_default_additional_criteria(): string;
}