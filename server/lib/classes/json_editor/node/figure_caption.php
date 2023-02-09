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
use core\json_editor\node\abstraction\inline_node;
use core\json_editor\schema;
use html_writer;

/**
 * Class figure_caption
 * @package core\json_editor\node
 */
final class figure_caption extends node {
    /**
     * @var array
     */
    private $contents;

    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        /** @var figure_caption $figure_caption */
        $figure_caption = parent::from_node($node);
        $figure_caption->contents = [];

        if (array_key_exists('content', $node)) {
            if (!is_array($node['content'])) {
                debugging("The raw node's content is not an array", DEBUG_DEVELOPER);
            } else {
                $figure_caption->contents = $node['content'];
            }
        }

        return $figure_caption;
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        if (empty($raw_node['content'])) {
            return true;
        }

        $contents = $raw_node['content'];
        $schema = schema::instance();
        foreach ($contents as $raw_node_content) {
            if (!array_key_exists('type', $raw_node_content)) {
                return false;
            }

            $node_type = $raw_node_content['type'];
            $node_class = $schema->get_node_classname($node_type);

            if ($node_class === null) {
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

        return true;
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'figure_caption';
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {

        if (empty($this->contents)) {
            return '';
        }

        // This is for debugging.
        $contents = $formatter->print_nodes($this->contents, formatter::HTML);

        return html_writer::tag(
            'figcaption',
            $contents,
            [
                'class' => 'jsoneditor-figcaption',
            ]
        );
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
        $contents = '';

        foreach ($this->contents as $rawnode) {
            $node = $schema->get_node($rawnode['type'], $rawnode);

            if ($node === null) {
                debugging("The node for type '{$rawnode['type']}' was not found", DEBUG_DEVELOPER);
                continue;
            }

            $contents .= $node->to_text($formatter);
        }

        return $contents;
    }
}