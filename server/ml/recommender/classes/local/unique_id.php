<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package ml_recommender
 */
namespace ml_recommender\local;

use coding_exception;

class unique_id {
    /**
     * unique_id constructor.
     */
    private function __construct() {
        // Preventing this class from construction.
    }

    /**
     * We are only expecting a string with the pattern as "hello_world42"
     *
     * @param string $unique_id
     * @return array [$component, $id_number, $raw_component]
     */
    public static function normalise_unique_id(string $unique_id): array {
        $matches = [];
        preg_match('/([a-z]+_[a-z]+)(\d+)/i', $unique_id, $matches);

        if (empty($matches)) {
            throw new coding_exception("Cannot extract the component name from unique id string");
        }

        $raw_component = $component = $matches[1];
        $id_number = $matches[2];

        if ('' === clean_param($component, PARAM_COMPONENT)) {
            throw new coding_exception("Invalid component name '{$component}'");
        }

        // The engine refers to microlearning recommendations independently, but we need to
        if ($component === 'engage_microlearning') {
            $component = 'engage_article';
        }

        $id_number = clean_param($id_number, PARAM_INT);
        return [$component, $id_number, $raw_component];
    }
}