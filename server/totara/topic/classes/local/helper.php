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
 * @package totara_topic
 */
namespace totara_topic\local;

/**
 * A class with a set of utility functions for component totara_topic.
 */
final class helper {
    /**
     * Returning any item that are duplicated in a given array.
     *
     * @param string[] $values
     * @return string[]
     */
    public static function get_duplicated(array $values): array {
        $items = [];
        $duplicated = [];

        // Checking the duplications in the array itself. when it is not db
        foreach ($values as $value) {
            if (in_array($value, $items)) {
                $duplicated[] = $value;
            } else {
                $items[] = $value;
            }
        }

        return $duplicated;
    }

    /**
     * Returning an array of items that already existing in the database.
     *
     * @param string[] $values
     * @return string[]
     */
    public static function get_duplicated_against_system(array $values): array {
        global $CFG;

        // The data format that this function return is something similar to below:
        // $data = [
        //  'tag-name' => null,
        //  'tag-name-2' => (\core_tag_tag) $instance
        // ];

        $tags = \core_tag_tag::get_by_name_bulk($CFG->topic_collection_id, $values);

        if (empty($tags)) {
            return [];
        }

        $duplicated = [];
        foreach ($tags as $tagname => $tag) {
            if (null === $tag) {
                continue;
            }

            // If it is not null, then there are such tag existing in topic_collection
            $duplicated[] = $tagname;
        }

        return $duplicated;
    }
}