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

namespace totara_tui\local\scss\transforms;

use totara_tui\local\scss\util;
use ScssPhp\ScssPhp\Block;
use ScssPhp\ScssPhp\Type;

/**
 * Base implementation for {@see output_only} and {@see definitions_only}
 *
 * Provides a protected visitor to filter to only emit code that results in either output or definitions.
 *
 * In:
 * $link-color: red; a { color: $link-color }
 *
 * Out (only_output = false):
 * $link-color: red;
 *
 * Out (only_output = true):
 * a { color: $link-color }
 */
abstract class base_output_filter extends transform {
    /**
     * @var string[] nodes where all of the children are okay to output for only_output = true
     */
    private $only_output_children_ok = [
        // assign may use non-output-only nodes, which is okay
        Type::T_ASSIGN,
    ];

    /**
     * @var string[] nodes where all of the children are okay to output for only_output = false
     */
    private $only_definitions_children_ok = [
        // mixin and function don't output anything even though they contain output only nodes
        Type::T_MIXIN,
        Type::T_FUNCTION
    ];

    /**
     * @var string[] nodes that are output-only
     */
    private $output_nodes = [
        Type::T_ASSIGN,
        Type::T_BLOCK,
        Type::T_MEDIA,
        Type::T_AT_ROOT,
        Type::T_DIRECTIVE,
    ];

    /**
     * Traversal visitor that filters tree to either only define things or only output.
     *
     * @param array $node AST node
     * @param object $context context object identifying the mode and transform name
     * @param callable $visitor Reference to self
     * @return int|null|array Replacement node or flags for traverser.
     */
    protected function output_filter_visitor(array $node, object $context, callable $visitor) {
        // rewrite imports to match
        if ($node[0] === Type::T_IMPORT) {
            $new_path = util::add_transform_to_import_path($node[1], $context->name);
            if ($new_path === null) {
                // no change needed
                return null;
            }
            $node[1] = $new_path;
            return $node;
        }

        // work out whether node is an output node or not
        $is_output_node = in_array($node[0], $this->output_nodes);
        // assign is normally output (i.e. `a: b`) but if the left side is a variable it is not (i.e. `$a: b`)
        if ($node[0] === Type::T_ASSIGN && $node[1][0] === Type::T_VARIABLE) {
            $is_output_node = false;
        }

        // remove node if it doesn't match the mode we're in
        if ($context->only_output !== $is_output_node) {
            return util::TRAVERSE_REMOVE_NODE;
        }

        // process children
        if (isset($node[1]) && $node[1] instanceof Block) {
            // some nodes' children are always okay, even if they don't match the output type,
            // so we should skip processing those
            if (!in_array($node[0], $context->only_output ? $this->only_output_children_ok : $this->only_definitions_children_ok)) {
                util::traverse($node[1], $context, $visitor);
            }
        }
    }
}
