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
namespace totara_engage\query\option;

use totara_engage\local\helper as local_helper;
use totara_engage\query\provider\helper as provider_helper;
use totara_engage\query\query;

final class type {
    /**
     * type constructor.
     */
    private function __construct() {
        // Preventing this class from constructed.
    }

    /**
     * This will returning all the sub-plugins of totara-engage and also other component that wants
     * to inject their usage into totara engage.
     *
     * This function will try to invoke {@see queryable::provide_query_type()}
     * @param query $query
     * @return array
     */
    public static function get_all(query $query): array {
        $rtn = [];

        $classes = provider_helper::get_providers();

        foreach ($classes as $cls) {
            $result = call_user_func([$cls, 'provide_query_type'], $query);
            if (!$result) {
                continue;
            }

            $component = local_helper::get_component_name($cls);
            if (in_array($component, $rtn)) {
                debugging("The component '{$component}' was already within the list", DEBUG_DEVELOPER);
                continue;
            }

            $rtn[] = $component;
        }

        return $rtn;
    }

    /**
     * @param string $component
     * @return string
     */
    public static function get_string(string $component): string {
        $cleaned = clean_param($component, PARAM_COMPONENT);
        if ($cleaned !== $component) {
            throw new \coding_exception("Invalid component name '{$component}'");
        }

        // Allow the child component to modify the label string via identifier below.
        $identifier = 'filterlabeltype';
        $manager = get_string_manager();

        if ($manager->string_exists($identifier, $component)) {
            return get_string($identifier, $component);
        }

        return get_string('pluginname', $component);
    }
}