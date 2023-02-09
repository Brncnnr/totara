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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package core
 */
namespace core\json_editor\node;

use core\json_editor\formatter\formatter;
use core\json_editor\helper\node_helper;
use core\json_editor\node\abstraction\inline_node;
use core\json_editor\schema;
use core\json_editor\node\abstraction\block_node;
use html_writer;

/**
 * Node for paragraph.
 */
final class paragraph extends node implements block_node {
    /**
     * @var array
     */
    private $contents;

    /**
     * @var string
     */
    private $align;

    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        /** @var paragraph $innernode */
        $innernode = parent::from_node($node);
        $innernode->contents = [];

        if (array_key_exists('content', $node)) {
            if (!is_array($node['content'])) {
                debugging("The raw node's content is not an array", DEBUG_DEVELOPER);
            } else {
                $innernode->contents = $node['content'];
            }
        }

        $attrs = $node['attrs'] ?? [];

        if (isset($attrs['align'])) {
            $innernode->align = $attrs['align'];
        }

        return $innernode;
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        if (
            array_key_exists('content', $raw_node) &&
            (null !== $raw_node['content'] && !is_array($raw_node['content']))
        ) {
            return false;
        }

        if (!empty($raw_node['content'])) {
            // Sometimes it is null. Which it will not work.
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

                if (!is_subclass_of($node_class, inline_node::class)) {
                    // Invalid node being placed.
                    return false;
                }

                $inner_result = call_user_func([$node_class, 'validate_schema'], $raw_node_content);
                if (!$inner_result) {
                    return false;
                }
            }
        }

        $input_keys = array_keys($raw_node);
        return node_helper::check_keys_match($input_keys, ['type'], ['content', 'attrs']);
    }

    /**
     * @param array $raw_node
     * @return array|null
     */
    public static function clean_raw_node(array $raw_node): ?array {
        $cleaned_raw_node = parent::clean_raw_node($raw_node);
        if ($cleaned_raw_node === null) {
            return null;
        }

        // Unset attrs when it is empty to avoid converting to an array
        if (empty($cleaned_raw_node['attrs'])) {
            unset($cleaned_raw_node['attrs']);
        }

        if (!array_key_exists('content', $cleaned_raw_node)) {
            // Make it as an array and skip the rest of the code.
            $cleaned_raw_node['content'] = [];
            return $cleaned_raw_node;
        }

        $content = static::clean_raw_node_content($cleaned_raw_node['content']);
        if ($content === null) {
            return null;
        }
        $cleaned_raw_node['content'] = $content;

        return $cleaned_raw_node;
    }

    /**
     * @deprecated since Totara 14.0
     * @param array $raw_node
     * @return array
     */
    public static function sanitize_raw_node(array $raw_node): array {
        $sanitized_node = parent::sanitize_raw_node($raw_node);
        $content_nodes = $sanitized_node['content'] ?? [];

        $sanitized_node['content'] = node_helper::sanitize_raw_nodes($content_nodes);
        return $sanitized_node;
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'paragraph';
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        if (empty($this->contents)) {
            // place a '<br/>' inside to match prosemirrors approach (and some other editors)
            return html_writer::tag('p', html_writer::empty_tag('br'));
        }

        // This is for debugging.
        $str = $formatter->print_nodes($this->contents, formatter::HTML);

        $props = [];

        if (!empty($this->align)) {
            $props['style'] = 'text-align:' . $this->align . ';';
        }

        $parts = [
            html_writer::start_tag('p', $props),
            $str,
            html_writer::end_tag('p')
        ];

        return implode("", $parts);
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        if (empty($this->contents)) {
            return '';
        }

        $schema = schema::instance();
        $str = "";

        foreach ($this->contents as $rawnode) {
            $node = $schema->get_node($rawnode['type'], $rawnode);

            if (null === $node) {
                debugging("The node for type '{$rawnode['type']}' was not found", DEBUG_DEVELOPER);
                continue;
            }

            $str .= $node->to_text($formatter);
        }

        return $str. "\n\n";
    }

    /**
     * @param string $text
     * @return array
     */
    public static function create_json_node_from_text(string $text): array {
        return [
            'type' => self::get_type(),
            'content' => [text::create_json_node_from_text($text)]
        ];
    }

    /**
     * Mainly this function is to create a mock json node of itself for test only.
     * The array $content_nodes is the collection of nodes.
     *
     * @param array $content_nodes
     * @return array
     */
    public static function create_json_node_with_content_nodes(array $content_nodes): array {
        return [
            'type' => paragraph::get_type(),
            'content' => $content_nodes
        ];
    }
}