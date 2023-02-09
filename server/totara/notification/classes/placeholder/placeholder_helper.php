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
namespace totara_notification\placeholder;

use totara_notification\placeholder\abstraction\placeholder;

class placeholder_helper {
    /**
     * placeholder_helper constructor.
     */
    private function __construct() {
        // Prevent this class from construction.
    }

    /**
     * @param string $placeholder_class
     * @return bool
     */
    public static function is_valid_placeholder_class(string $placeholder_class): bool {
        if (!class_exists($placeholder_class)) {
            return false;
        }

        $interface_names = class_implements($placeholder_class);
        return is_array($interface_names) && in_array(placeholder::class, $interface_names);
    }
}