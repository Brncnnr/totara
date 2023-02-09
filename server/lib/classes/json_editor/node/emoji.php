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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */
namespace core\json_editor\node;

use core\json_editor\formatter\formatter;
use core\json_editor\helper\node_helper;
use core\json_editor\node\abstraction\inline_node;
use html_writer;

/**
 * Emoji node.
 */
final class emoji extends node implements inline_node {
    /**
     * @var string
     */
    private $shortcode;

    /**
     * @param array $node
     *
     * @return node
     */
    public static function from_node(array $node): node {
        if (!array_key_exists('attrs', $node) || empty($node['attrs'])) {
            throw new \coding_exception("Invalid node parameter");
        }
        $attrs = (array) $node['attrs'];

        /** @var emoji $emoji */
        $emoji = parent::from_node($node);
        $emoji->shortcode = $attrs['shortcode'];

        return $emoji;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        return html_writer::tag(
            'span',
            "&#x{$this->shortcode};"
        );
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        return mb_convert_encoding("&#x{$this->shortcode};", 'UTF-8', 'HTML-ENTITIES');
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return "emoji";
    }

    /**
     * @return string
     */
    public function get_short_code(): string {
        return $this->shortcode;
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        if (!array_key_exists('attrs', $raw_node)) {
            return false;
        }

        $attrs = $raw_node['attrs'];
        if (!array_key_exists('shortcode', $attrs)) {
            return false;
        }

        if (!node_helper::check_keys_match_against_data($attrs, ['shortcode'])) {
            return false;
        }

        return node_helper::check_keys_match_against_data($raw_node, ['type', 'attrs']);
    }

    /**
     * @param string $short_code
     * @return array
     */
    public static function create_raw_node(string $short_code = '1F60D'): array {
        return [
            'type' => static::get_type(),
            'attrs' => [
                'shortcode' => $short_code
            ],
        ];
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

        $cleaned_raw_node['attrs']['shortcode'] = clean_param(
            $cleaned_raw_node['attrs']['shortcode'],
            PARAM_ALPHANUM
        );

        return $cleaned_raw_node;
    }
}