<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package totara_tui
 */

namespace totara_tui\local\scss;

use ScssPhp\ScssPhp\Block;
use ScssPhp\ScssPhp\Type;

class util {
    /**
     * @var int Special return value to tell traverse() to remove the current node from the tree.
     */
    public const TRAVERSE_REMOVE_NODE = -1;

    /**
     * Add transform to the path part of an import.
     *
     * @param array $raw_path Path part of import (i.e. $node[1] where $node[0] === 'import')
     * @param string $transform Name of transform to add.
     * @return array Modified path, or null if it's not a path we care about.
     */
    public static function add_transform_to_import_path(array $raw_path, string $transform): ?array {
        // @import "x";
        //         ^^^
        if ($raw_path[0] === Type::T_STRING) {
            $path = self::get_string_content($raw_path);
            // bail out if import is not a plain string
            if ($path === null) {
                return null;
            }

            // don't modify url imports
            if (substr($path, 0, 4) === 'http') {
                return null;
            }

            // skip adding transform if it is already present
            $transforms = explode('!', $path);
            array_pop($transforms); // remove file path
            if (in_array($transform, $transforms)) {
                return null;
            }

            $path = $transform . '!' . $path;
            return [Type::T_STRING, '"', [$path]];
        }

        // @import "x", "y";
        //         ^^^^^^^^
        if ($raw_path[0] === Type::T_LIST) {
            foreach ($raw_path[2] as $path) {
                if ($path[0] !== Type::T_STRING) {
                    return null;
                }
            }

            // @import "x", "y";
            //         ^^^  ^^^
            foreach ($raw_path[2] as &$path) {
                $changed_path = self::add_transform_to_import_path($path, $transform);
                if ($changed_path !== null) {
                    $path = $changed_path;
                }
            }

            return $raw_path;
        }

        return null;
    }

    /**
     * Get the content of a string node.
     *
     * @param array $node String node ($node[0] === Type::T_STRING)
     * @return string|null Content, or null if it was not a plain string.
     */
    public static function get_string_content(array $node): ?string {
        $parts = [];

        foreach ($node[2] as $part) {
            if (is_array($part) || $part instanceof \ArrayAccess) {
                return null;
            } else {
                $parts[] = $part;
            }
        }

        return implode('', $parts);
    }

    /**
     * Walk down the tree, calling $visitor for every node and its children (depth-first).
     *
     * @param \ScssPhp\ScssPhp\Block $tree
     * @param mixed $context
     * @param callable $visitor Function that gets passed the node and itself.
     */
    public static function traverse(Block $tree, $context, callable $visitor) {
        $filter = false;

        for ($i = 0; $i < count($tree->children); $i++) {
            $child = $tree->children[$i];

            // there's no easy way for a closure to reference itself in PHP, so
            // pass the visitor to itself from here.
            $result = $visitor($child, $context, $visitor);

            if ($result === self::TRAVERSE_REMOVE_NODE) {
                $tree->children[$i] = null;
                $filter = true;
            } else if ($result !== null && !is_int($result)) {
                $tree->children[$i] = $result;
            }
        }

        if ($filter) {
            $tree->children = array_values(array_filter($tree->children));
        }
    }
}
