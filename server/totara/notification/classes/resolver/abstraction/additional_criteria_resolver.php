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
namespace totara_notification\resolver\abstraction;

use stdClass;
use totara_core\extended_context;

/**
 * Implement this interface if your resolver needs to provide additional configuration to the admin.
 *
 * The additional configuration will be presented when notifications are configured. When notifications are
 * being generated, the event data is evaluated against the additional configuration to see if it meets
 * the criteria, and will not be sent if it does not.
 */
interface additional_criteria_resolver {
    /**
     * Get the path to the tui component which contains the form elements that need to be presented to
     * configure additional criteria which must be met to allow the notification to be sent.
     *
     * @return string
     */
    public static function get_additional_criteria_component(): string;

    /**
     * Returns true if the provided additional data is in a valid state.
     *
     * Additional data may be in an invalid state if it is dependent on something else in
     * the system, such as the selection of a record from a table. The record in the table may have
     * existed at the time that the notification was configured, but was deleted at some later point
     * in time. If the additional data is invalid, the notification will not be sent.
     *
     * @param array $additional_criteria
     * @param extended_context $extended_context
     * @return bool
     */
    public static function is_valid_additional_criteria(array $additional_criteria, extended_context $extended_context): bool;

    /**
     * Returns true if the given notification event data meets the criteria which was configured in
     * the additional data.
     *
     * @param array $additional_criteria
     * @param array $event_data
     * @return bool
     */
    public static function meets_additional_criteria(array $additional_criteria, array $event_data): bool;
}