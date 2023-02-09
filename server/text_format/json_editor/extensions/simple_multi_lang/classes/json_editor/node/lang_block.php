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
use core\json_editor\node\heading;
use core\json_editor\node\node;
use core\json_editor\node\paragraph;

/**
 * A single block node for multi lang block.
 */
class lang_block extends node implements block_node {
    /**
     * @var string
     */
    public const MAX_LANG_LENGTH = 5;

    /**
     * The attribute langs
     * @var string
     */
    private $lang;

    /**
     * An array of paragraph | heading nodes.
     * @var array[]
     */
    private $contents;

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        if (empty($this->contents)) {
            return '';
        }
        $content = '';
        foreach ($this->contents as $single_node) {
            $content .= $formatter->print_node($single_node, formatter::HTML);
        }
        return $content;
    }

    /**
     * There is no supports for multi lang in simple text yet. And this implementation
     * is only about outputing the content to text.
     *
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        if (empty($this->lang)) {
            return '';
        }
        if (empty($this->contents)) {
            return "```{$this->lang}```";
        }

        $content = '';
        foreach ($this->contents as $single_node) {
            $content .= $formatter->print_node($single_node, formatter::TEXT) . ' ';
        }

        return "```{$this->lang}\n{$content}```";
    }

    public static function get_type(): string {
        return 'weka_simple_multi_lang_' . static::do_get_type();
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'lang_block';
    }

    /**
     * @param array $node
     * @return lang_block|node
     */
    public static function from_node(array $node): node {
        /** @var lang_block $lang_block */
        $lang_block = parent::from_node($node);
        $lang_block->lang = $node['attrs']['lang'] ?? null;

        // Default to 1 because the minimum that we can have is 1.
        $lang_block->siblings_count = $node['attrs']['siblings_count'] ?? 1;
        $lang_block->contents = $node['content'] ?? [];

        return $lang_block;
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        if (!node_helper::check_keys_match_against_data($raw_node, ['type'], ['attrs', 'content'])) {
            return false;
        }

        $attrs = $raw_node['attrs'] ?? [];
        // note: siblings_count may exist but it is no longer used
        if (!node_helper::check_keys_match_against_data($attrs, [], ['lang', 'siblings_count'])) {
            return false;
        } else if (isset($attrs['lang']) && strlen($attrs['lang']) > static::MAX_LANG_LENGTH) {
            // We can only accept the 5 characters as max length of lang keyword.
            return false;
        }

        $content_nodes = $raw_node['content'] ?? null;
        if (!empty($content_nodes)) {
            foreach ($content_nodes as $single_node) {
                if (!isset($single_node['type'])) {
                    return false;
                }

                if (paragraph::get_type() === $single_node['type']) {
                    if (!paragraph::validate_schema($single_node)) {
                        return false;
                    }

                    continue;
                }

                if (heading::get_type() === $single_node['type']) {
                    if (!heading::validate_schema($single_node)) {
                        return false;
                    }

                    continue;
                }

                return false;
            }
        }

        return true;
    }

    /**
     * @deprecated since Totara 14.0
     * @param array $raw_node
     * @return array
     */
    public static function sanitize_raw_node(array $raw_node): array {
        $raw_node = parent::sanitize_raw_node($raw_node);

        // Trim down the lang value, as it is invalid
        $lang = $raw_node['attrs']['lang'];
        $raw_node['attrs']['lang'] = substr($lang, 0, static::MAX_LANG_LENGTH);

        $content_nodes = $raw_node['content'];
        foreach ($content_nodes as $i => $single_node) {
            if (!isset($single_node['type'])) {
                throw new coding_exception("Invalid single node does not have attribute 'type'");
            }

            if (paragraph::get_type() === $single_node['type']) {
                $raw_node['content'][$i] = paragraph::sanitize_raw_node($single_node);
                continue;
            }

            if (heading::get_type() === $single_node['type']) {
                $raw_node['content'][$i] = heading::sanitize_raw_node($single_node);
                continue;
            }

            throw new coding_exception("The node within lang block is not supported");
        }

        return $raw_node;
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

        $content = static::clean_raw_node_content($cleaned_raw_node['content'] ?? []);
        if ($content === null) {
            return null;
        }
        $cleaned_raw_node['content'] = $content;

        // Clean the attribute of the raw node. Note that we do not want to use clean with
        // PARAM_LANG because that would strip out the language value from the node.
        // The rendering will make sure that the invalid lang pack is stripped,
        // hence we need to keep it for the record.
        $lang = $cleaned_raw_node['attrs']['lang'];
        $cleaned_raw_node['attrs']['lang'] = clean_param($lang, PARAM_ALPHAEXT);

        if (isset($cleaned_raw_node['attrs']['siblings_count'])) {
            $cleaned_raw_node['attrs']['siblings_count'] = clean_param(
                $cleaned_raw_node['attrs']['siblings_count'],
                PARAM_INT
            );
        }

        return $cleaned_raw_node;
    }

    /**
     * This function is mainly for phpunit tests, please do not use it esle where outside unit test environment.
     *
     * @param string $lang
     * @param string $content
     *
     * @return array
     */
    public static function create_raw_json_node(string $lang, string $content): array {
        $paragraphs = [$content];
        if (false !== strpos($content, "\n")) {
            $paragraphs = explode("\n", $content);
        }

        $paragraph_nodes = array_map(
            function (string $paragraph): array {
                return paragraph::create_json_node_from_text($paragraph);
            },
            $paragraphs
        );

        return static::create_raw_json_node_from_paragraph_nodes(
            $lang,
            $paragraph_nodes,
        );
    }

    /**
     * This function is mainly for phpunit tests, please do not use it esle where outside unit test environment.
     *
     * @param string $lang
     * @param array  $paragraph_nodes
     *
     * @return array
     */
    public static function create_raw_json_node_from_paragraph_nodes(
        string $lang,
        array $paragraph_nodes
    ): array {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            $fn = __FUNCTION__;
            throw new coding_exception("The function {$fn} is only available for phpunit tests");
        }

        return [
            'type' => static::get_type(),
            'attrs' => [
                'lang' => $lang,
                'siblings_count' => 1,
            ],
            'content' => $paragraph_nodes
        ];
    }
}