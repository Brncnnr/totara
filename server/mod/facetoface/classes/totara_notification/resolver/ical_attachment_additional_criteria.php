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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\totara_notification\resolver;

use totara_core\extended_context;

trait ical_attachment_additional_criteria {

    /**
     * @return string
     */
    public static function get_additional_criteria_component(): string {
        return 'mod_facetoface/components/notification/IcalAttachment';
    }

    /**
     * @param array $additional_criteria
     * @param extended_context $extended_context
     * @return bool
     */
    public static function is_valid_additional_criteria(array $additional_criteria, extended_context $extended_context): bool {
        if (!isset($additional_criteria['ical'])) {
            return true;
        }

        if (is_array($additional_criteria['ical'])) {
            return true;
        }

        return false;
    }

    /**
     * @param array|null $additional_criteria
     * @param array $event_data
     * @return bool
     */
    public static function meets_additional_criteria(?array $additional_criteria, array $event_data): bool {
        return  true;
    }
}