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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @package core
 */
namespace core\json_editor\node;

use core\json_editor\formatter\formatter;
use core\json_editor\helper\node_helper;
use core\json_editor\schema;
use core\json_editor\node\abstraction\block_node;
use html_writer;

final class ordered_list extends base_list implements block_node {
    /**
     * @var string
     */
    private $order;

    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        /** @var ordered_list $ordered_list */
        $ordered_list = parent::from_node($node);
        $ordered_list->order = '';

        if (!array_key_exists('attrs', $node) || empty($node['attrs'])) {
            throw new \coding_exception("Invalid node parameter");
        }

        $attrs = $node['attrs'];
        $ordered_list->order = $attrs['order'];

        return $ordered_list;
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        if (!array_key_exists('content', $raw_node) || !array_key_exists('attrs', $raw_node)) {
            return false;
        }

        $attrs = $raw_node['attrs'];
        if (!array_key_exists('order', $attrs)) {
            return false;
        }

        if (!node_helper::check_keys_match_against_data($attrs, ['order'])) {
            return false;
        }

        if (is_array($raw_node['content'])) {
            $contents = $raw_node['content'];
            $list_item_type = list_item::get_type();

            foreach ($contents as $raw_node_content) {
                if (!array_key_exists('type', $raw_node_content)) {
                    return false;
                }

                $node_type = $raw_node_content['type'];
                if ($list_item_type !== $node_type) {
                    return false;
                }

                if (!list_item::validate_schema($raw_node_content)) {
                    return false;
                }
            }
        }

        return node_helper::check_keys_match_against_data($raw_node, ['content', 'type', 'attrs']);
    }

    /**
     * @param array $raw_node
     * @return array|null
     */
    public static function clean_raw_node(array $raw_node): ?array {
        $cleaned_raw_node = parent::clean_raw_node($raw_node);
        if (null === $cleaned_raw_node) {
            return null;
        }

        if (!array_key_exists('attrs', $cleaned_raw_node)) {
            throw new \coding_exception("Invalid node structure", static::get_type());
        }

        $attrs = $cleaned_raw_node['attrs'];
        if (!in_array($attrs['order'], [1, ''])) {
            // Keep it empty.
            $attrs['order'] = '';
        } else {
            $attrs['order'] = (string) $attrs['order'];
        }

        $cleaned_raw_node['attrs']  = $attrs;
        return $cleaned_raw_node;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        $content = '';
        $schema = schema::instance();

        foreach ($this->children as $child) {
            $node = $schema->get_node($child['type'], $child);
            if (!($node instanceof list_item)) {
                debugging(
                    "There is one child node of the ordered list is not a list-item",
                    DEBUG_DEVELOPER
                );

                continue;
            }

            $content .= $formatter->print_node($child, formatter::HTML);
        }

        return html_writer::tag("ol", $content, ['type' => $this->order]);
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        $content = '';
        $schema = schema::instance();

        foreach ($this->children as $child) {
            $node = $schema->get_node($child['type'], $child);

            if (!($node instanceof list_item)) {
                debugging(
                    "There is one child node of the ordered list is not a list-item",
                    DEBUG_DEVELOPER
                );

                continue;
            }

            $content .= "# {$formatter->print_node($child, formatter::TEXT)}";
        }

        return $content;
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return "ordered_list";
    }

    /**
     * Create a raw node schema from an array of string(s).
     *
     * @param string[] $texts
     * @return array
     */
    public static function create_raw_node_from_texts(array $texts): array {
        $raw_node = [
            'type' => static::get_type(),
            'content' => [],
            'attrs' => [
                'order' => 1
            ]
        ];

        foreach ($texts as $text) {
            $raw_node['content'][] = list_item::create_raw_node_from_text($text);
        }

        return $raw_node;
    }
}