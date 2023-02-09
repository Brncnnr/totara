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

use core\json_editor\node\abstraction\block_node;
use core\json_editor\schema;
use html_writer;
use core\json_editor\formatter\formatter;

/**
 * Class figure
 * @package core\json_editor\node
 */
final class figure extends node implements block_node {
    /**
     * Array of content.
     * @var array
     */
    protected $content;

    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        /** @var figure $figure */
        $figure = parent::from_node($node);
        $figure->content = [];

        if (!array_key_exists('content', $node) || !is_array($node['content'])) {
            debugging("No property 'content' found for the node", DEBUG_DEVELOPER);
            return $figure;
        }

        $figure->content = $node['content'];
        return $figure;
    }
    /**
    * @param array $raw_node
    * @return bool
    */
    public static function validate_schema(array $raw_node): bool {
        if (!array_key_exists('content', $raw_node)) {
            return false;
        }

        $contents = $raw_node['content'];

        if (!is_array($contents) || count($contents) !== 2) {
            return false;
        }

        $image_type = image::get_type();
        $caption_type = figure_caption::get_type();

        if (($contents[0]['type'] ?? null) === $image_type
            && ($contents[1]['type'] ?? null) === $caption_type) {
            return image::validate_schema($contents[0]) && figure_caption::validate_schema($contents[1]);
        }

        return false;
    }


    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        $contents = $formatter->print_nodes($this->content, formatter::HTML);

        $parts = [
            html_writer::start_tag('figure'),
            $contents,
            html_writer::end_tag('figure')
        ];

        return implode('', $parts);
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        return $formatter->print_nodes($this->content, formatter::TEXT) . "\n\n";
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'figure';
    }
}