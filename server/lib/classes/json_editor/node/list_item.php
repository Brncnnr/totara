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
use core\json_editor\schema;
use html_writer;

/**
 * A wrapper for any node that want to exist within a list.
 */
final class list_item extends base_list {
    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        $content = parent::to_html($formatter);

        // Required for firefox bug, description can be found in TL-30549
        if ($content === '<p />' || $content === '<p></p>') {
            return html_writer::tag('li', '');
        } 
        
        return html_writer::tag('li', $content);
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        $content = '';

        foreach ($this->children as $child) {
            $content .= $formatter->print_node($child, formatter::TEXT);
        }

        return $content;
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return "list_item";
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        $result = parent::validate_schema($raw_node);

        if (!$result) {
            return false;
        }

        if (is_array($raw_node['content'])) {
            $contents = $raw_node['content'];
            $schema = schema::instance();

            foreach ($contents as $raw_node_content) {
                if (!array_key_exists('type', $raw_node_content)) {
                    return false;
                }

                $node_type = $raw_node_content['type'];
                $node_class = $schema->get_node_classname($node_type);

                if (null === $node_class) {
                    debugging("Cannot find node class for type '{$node_type}'", DEBUG_DEVELOPER);
                    continue;
                }

                $inner_result = call_user_func([$node_class, 'validate_schema'], $raw_node_content);
                if (!$inner_result) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param string $text
     * @return array
     */
    public static function create_raw_node_from_text(string $text): array {
        return [
            'type' => static::get_type(),
            'content' => [
                paragraph::create_json_node_from_text($text)
            ]
        ];
    }
}