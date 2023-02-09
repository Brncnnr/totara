<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package jsoneditor_simple_multi_lang
 */
namespace jsoneditor_simple_multi_lang\json_editor\node;

use coding_exception;
use core\json_editor\formatter\formatter;
use core\json_editor\helper\node_helper;
use core\json_editor\node\abstraction\block_node;
use core\json_editor\node\node;
use html_writer;

/**
 * A collection node for multiple single lang block.
 */
class lang_blocks extends node implements block_node {
    /**
     * A collection of lang_block raw nodes.
     * @var array[]
     */
    private $blocks;

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        $content = '';

        $blocks = array_values(array_filter($this->blocks, function (array $block): bool {
            return !empty($block['attrs']['lang']);
        }));

        if (count($blocks) === 1) {
            // There is only one node, therefore we only render the content without the
            // span tag. This is happening because we might be able to go thru the filter json,
            // and it strips out all the other children except the one that matches with the
            // current language. Hence we can leave the `<span>` tags outside it.
            return $formatter->print_node($blocks[0], formatter::HTML);
        }

        foreach ($blocks as $single_block_node) {
            $content .= html_writer::span(
                $formatter->print_node($single_block_node, formatter::HTML),
                "multilang",
                ['lang' => $single_block_node['attrs']['lang']]
            );
        }

        return $content;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        $content = '';

        foreach ($this->blocks as $single_block_node) {
            $content .= $formatter->print_node($single_block_node, formatter::TEXT) . "\n";
        }

        return $content;
    }

    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        /** @var lang_blocks $lang_blocks */
        $lang_blocks = parent::from_node($node);
        $lang_blocks->blocks = $node['content'];

        return $lang_blocks;
    }

    public static function get_type(): string {
        return 'weka_simple_multi_lang_' . static::do_get_type();
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'lang_blocks';
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        if (!node_helper::check_keys_match_against_data($raw_node, ['content', 'type'])) {
            return false;
        }

        $lang_block_type = lang_block::get_type();
        $content_nodes = $raw_node['content'];

        foreach ($content_nodes as $single_node) {
            if (!isset($single_node['type']) || $lang_block_type !== $single_node['type']) {
                return false;
            }

            if (!lang_block::validate_schema($single_node)) {
                return false;
            }
        }

        return true;
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

        $content = $cleaned_raw_node['content'] ?? [];
        foreach ($content as $i => $raw_node) {
            $cleaned_single_node = lang_block::clean_raw_node($raw_node);
            if (null === $cleaned_single_node) {
                return null;
            }

            $cleaned_raw_node['content'][$i] = $cleaned_single_node;
        }

        return $cleaned_raw_node;
    }

    /**
     * This function is mainly for PHPUNIT test environment.
     * @param array $block_nodes
     * @return array
     */
    public static function create_raw_node(array $block_nodes): array {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            $fn = __FUNCTION__;
            throw new coding_exception("The function {$fn} is mainly for phpunit tests environment");
        }

        return [
            'type' => static::get_type(),
            'content' => $block_nodes
        ];
    }
}