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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_tui
 */
namespace totara_tui\json_editor\formatter;

use core\json_editor\formatter\formatter as base;
use core\json_editor\node\node;
use totara_tui\json_editor\output_node\output_node;

/**
 * JSON rendering implmentation for tui.
 */
final class formatter extends base {
    /**
     * @var array
     */
    private $output_map;

    /**
     * Populate the output node map.
     * @return void
     */
    protected function init(): void {
        $this->output_map = [];

        $classes_map = \core_component::get_component_classes_in_namespace(
            'totara_tui',
            'json_editor\\output_node'
        );

        $classes = array_keys($classes_map);

        foreach ($classes as $class_name) {
            if (!is_subclass_of($class_name, output_node::class)) {
                // Skip those classes that are not child of output_node
                continue;
            }

            $for_node_type = call_user_func([$class_name, 'get_node_type']);
            $this->output_map[$for_node_type] = $class_name;
        }
    }

    /**
     * @param node $node
     * @return string
     */
    protected function print_html_node(node $node): string {
        $node_type = $node::get_type();
        if (!isset($this->output_map[$node_type])) {
            // There is no such output node for the specific node.
            // Hence we will fallback to use the original output to html.
            return $node->to_html($this);
        }

        $output_node_class = $this->output_map[$node_type];

        /** @var output_node $output_node */
        $output_node = new $output_node_class($node);
        return $output_node->render_tui_component_content();
    }

    /**
     * @param array $document
     * @return string
     */
    public function to_html(array $document): string {
        $content = parent::to_html($document);
        return \html_writer::div($content, 'tui-rendered');
    }
}