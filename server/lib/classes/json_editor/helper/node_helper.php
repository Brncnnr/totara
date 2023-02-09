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
 * @package core
 */
namespace core\json_editor\helper;

use core\json_editor\schema;

/**
 * Helper class to be used within a node only.
 */
final class node_helper {
    /**
     * node_helper constructor.
     * Preventing this class from construction.
     */
    private function __construct() {
    }

    /**
     * This function will try to invoke {@see node_helper::check_keys_match()}. However the array
     * $data is a hash-map, and we will run check against this hash map with just one level of depth.
     * This means that if the hash-map has a nested hash map then this check will have nothing to do with it.
     *
     * @param array $data
     * @param array $expected_keys
     * @param array $optional_keys
     *
     * @return bool
     */
    public static function check_keys_match_against_data(array $data, array $expected_keys,
                                                         array $optional_keys = []): bool {
        $input_keys = array_keys($data);
        return self::check_keys_match($input_keys, $expected_keys, $optional_keys);
    }

    /**
     * A helper function to check the difference between keys from the actual input keys and
     * the expected keys we want it to be exactly.
     *
     * Note that the function will only debug the message if debugdeveloper mode is on.
     *
     * @param string[] $input_keys
     * @param string[] $expected_keys
     * @param string[] $optional_keys
     *
     * @return bool
     */
    public static function check_keys_match(array $input_keys, array $expected_keys,
                                            array $optional_keys = []): bool {
        global $CFG;

        $count_input = count($input_keys);
        $count_expect = count($expected_keys);

        if ($count_input < $count_expect) {
            if ($CFG->debugdeveloper) {
                debugging(
                    "The input keys and the expected keys are different: " .
                    "input {$count_input} - expect {$count_expect}",
                    DEBUG_DEVELOPER
                );
            }

            return false;
        }

        foreach ($input_keys as $input_key) {
            if (!in_array($input_key, $expected_keys) && !in_array($input_key, $optional_keys)) {
                if ($CFG->debugdeveloper) {
                    debugging(
                        "The input key '{$input_key}' does not exist within list of expected keys",
                        DEBUG_DEVELOPER
                    );
                }

                return false;
            }
        }

        return true;
    }

    /**
     * This function will try to invoke {@see node::sanitize_raw_node()} to sanitize the content
     * on the output.
     *
     * @deprecated since Totara 14.0
     * @param array $raw_nodes
     * @return array
     */
    public static function sanitize_raw_nodes(array $raw_nodes): array {
        debugging('The method \core\json_editor\helper\node_helper::sanitize_raw_nodes() is deprecated, there is no replacement.', DEBUG_DEVELOPER);

        $schema = schema::instance();

        return array_map(
            function (array $raw_node) use ($schema): array {
                if (!isset($raw_node['type'])) {
                    throw new \coding_exception("Invalid node structure");
                }

                $node_class = $schema->get_node_classname($raw_node['type']);
                if (null === $node_class) {
                    debugging("Cannot find node class for type '{$raw_node['type']}'", DEBUG_DEVELOPER);
                    return $raw_node;
                }

                return call_user_func([$node_class, 'sanitize_raw_node'], $raw_node);
            },
            $raw_nodes
        );
    }
}
