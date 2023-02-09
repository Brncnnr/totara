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

final class bullet_list extends base_list implements block_node {
    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        $result = parent::validate_schema($raw_node);

        if (!$result) {
            return false;
        }

        $contents = $raw_node['content'];
        $list_item_type = list_item::get_type();

        foreach ($contents as $raw_node_content) {
            if (!array_key_exists('type', $raw_node_content)) {
                return false;
            }

            $type = $raw_node_content['type'];
            if ($list_item_type !== $type) {
                // Only accept the list item node.
                return false;
            }

            if (!list_item::validate_schema($raw_node_content)) {
                return false;
            }
        }

        return node_helper::check_keys_match_against_data($raw_node, ['content', 'type']);
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
                    "There is one child node of the bullet list is not a list-item",
                    DEBUG_DEVELOPER
                );

                continue;
            }

            $content .= $formatter->print_node($child, formatter::HTML);
        }

        return html_writer::tag("ul", $content);
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
                    "There is one child node of the bullet list is not a list-item",
                    DEBUG_DEVELOPER
                );

                continue;
            }

            $content .= "* {$formatter->print_node($child, formatter::TEXT)}\n";
        }

        return $content;
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return "bullet_list";
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
            'content' => []
        ];

        foreach ($texts as $text) {
            $raw_node['content'][] = list_item::create_raw_node_from_text($text);
        }

        return $raw_node;
    }
}