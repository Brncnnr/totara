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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_notification
 */

global $CFG;
require_once("{$CFG->dirroot}/totara/notification/tests/fixtures/totara_notification_mock_built_in_notification.php");

/**
 * Class totara_notification_mock_additional_criteria_notification
 *
 * This mock notification is for the additional criteria resolver. It specifies that it is valid by default.
 */
class totara_notification_mock_additional_criteria_notification extends totara_notification_mock_built_in_notification {

    /**
     * @return string
     */
    public static function get_resolver_class_name(): string {
        global $CFG;

        if (!class_exists('totara_notification_mock_additional_criteria_resolver')) {
            require_once(
                "{$CFG->dirroot}/totara/notification/tests/fixtures/totara_notification_mock_additional_criteria_resolver.php"
            );
        }

        return totara_notification_mock_additional_criteria_resolver::class;
    }

    /**
     * @return string
     */
    public static function get_title(): string {
        return 'Mock additional criteria notification';
    }

    public static function get_default_additional_criteria(): string {
        return json_encode([
            'valid' => true,
        ]);
    }
}