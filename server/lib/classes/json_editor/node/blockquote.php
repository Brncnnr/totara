<?php
/**
 * This file is part of Totara TXP
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
 * @author Aaron Machin <aaron.machin@totaralearning.com>
 * @package core
 */

namespace core\json_editor\node;

use core\json_editor\formatter\formatter;
use core\json_editor\helper\node_helper;
use core\json_editor\node\abstraction\block_node;
use core\json_editor\schema;
use html_writer;

/**
 * Node for blockquote.
 */
final class blockquote extends node implements block_node {
    /**
     * @var array
     */
    private $contents;

    /**
     * @param array $node
     * @return node
     * @throws \coding_exception
     */
    public static function from_node(array $node): node {
        /** @var blockquote $blockquote */
        $blockquote = parent::from_node($node);
        $blockquote->contents = [];

        if (array_key_exists('content', $node) && is_array($node['content'])) {
            $blockquote->contents = $node['content'];
        }

        return $blockquote;
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        if (array_key_exists('content', $raw_node)) {
            if ($raw_node['content'] !== null && !is_array($raw_node['content'])) {
                return false;
            }
        }

        if (!empty($raw_node['content'])) {
            $contents = $raw_node['content'];
            $schema = schema::instance();

            foreach ($contents as $raw_node_content) {
                if (!is_array($raw_node_content) || !array_key_exists('type', $raw_node_content)) {
                    // Invalid node content that is not an array.
                    return false;
                }

                $node_type = $raw_node_content['type'];
                $node_class = $schema->get_node_classname($node_type);

                if (null === $node_class) {
                    // Skip the invalid node for now.
                    debugging("Class for node type '{$node_type}' does not exist", DEBUG_DEVELOPER);
                    continue;
                }

                $inner_result = call_user_func([$node_class, 'validate_schema'], $raw_node_content);
                if (!$inner_result) {
                    return false;
                }
            }
        }

        $input_keys = array_keys($raw_node);
        return node_helper::check_keys_match($input_keys, ['type'], ['content']);
    }

    /**
     * @param array $raw_node
     * @return array|null
     * @throws \coding_exception
     */
    public static function clean_raw_node(array $raw_node): ?array {
        $cleaned_raw_node = parent::clean_raw_node($raw_node);
        if ($cleaned_raw_node === null) {
            return null;
        }

        $content = self::clean_raw_node_content($cleaned_raw_node['content'] ?? []);
        if ($content === null) {
            return null;
        }
        $cleaned_raw_node['content'] = $content;

        return $cleaned_raw_node;
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'blockquote';
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        if (empty($this->contents)) {
            // place a '<br/>' inside to match prosemirrors approach (and some other editors)
            return html_writer::tag('blockquote', html_writer::tag('p', html_writer::empty_tag('br')));
        }

        // This is for debugging.
        $contents = $formatter->print_nodes($this->contents, formatter::HTML);

        $parts = [
            html_writer::start_tag('blockquote'),
            $contents,
            html_writer::end_tag('blockquote')
        ];

        return implode("", $parts);
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        if (empty($this->contents)) {
            return '> ';
        }

        $schema = schema::instance();
        $str = "";

        foreach ($this->contents as $rawnode) {
            $node = $schema->get_node($rawnode['type'], $rawnode);

            if (null === $node) {
                debugging("The node for type '{$rawnode['type']}' was not found", DEBUG_DEVELOPER);
                continue;
            }

            $str .= '> ';
            $str .= $node->to_text($formatter);
        }

        return $str;
    }

}