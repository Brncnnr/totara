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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */
namespace core\json_editor\node;

use core\json_editor\helper\node_helper;
use core\json_editor\node\abstraction\block_node;
use html_writer;
use core\json_editor\formatter\formatter;

/**
 * Ruler node.
 */
final class ruler extends node implements block_node {
    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        return parent::from_node($node);
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        return html_writer::empty_tag('hr');
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        return "\n---\n";
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'ruler';
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        $input_keys = array_keys($raw_node);
        return node_helper::check_keys_match($input_keys, ['type']);
    }

    /**
     * @return array
     */
    public static function create_raw_node(): array {
        return [
            'type' => static::get_type()
        ];
    }
}