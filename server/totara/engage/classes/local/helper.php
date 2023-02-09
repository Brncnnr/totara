<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\local;

final class helper {
    /**
     * helper constructor.
     */
    private function __construct() {
        // Prevent the construction directly.
    }

    /**
     * @param string|object $item
     * @return string
     */
    public static function get_component_name($item): string {
        $cls = null;

        if (is_object($item)) {
            $cls = get_class($item);
        } else if (is_string($item) && class_exists($item)) {
            $cls = $item;
        } else {
            throw new \coding_exception("Invalid parameter \$item being passed in");
        }

        $parts = explode("\\", $cls);
        $component = reset($parts);

        $cleaned = clean_param($component, PARAM_COMPONENT);
        if ($cleaned !== $component) {
            throw new \coding_exception("Invalid component from \$item '{$component}'");
        }

        return $cleaned;
    }
}