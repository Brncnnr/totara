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

use core\json_editor\helper\node_helper;
use core\json_editor\node\abstraction\inline_node;
use html_writer;
use moodle_url;
use core\json_editor\formatter\formatter;

/**
 * Hash tag node.
 */
final class hashtag extends node implements inline_node {
    /**
     * @var string
     */
    private $text;

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

        /** @var hashtag $hashtag */
        $hashtag = parent::from_node($node);
        $hashtag->text = $attrs['text'];

        return $hashtag;
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
        if (!array_key_exists('text', $attrs)) {
            return false;
        }

        $attrs_key = array_keys($attrs);
        if (!node_helper::check_keys_match($attrs_key, ['text'])) {
            return false;
        }

        $input_keys = array_keys($raw_node);
        return node_helper::check_keys_match($input_keys, ['type', 'attrs']);
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        $url = new moodle_url(
            "/totara/catalog/index.php",
            ['catalog_fts' => $this->text]
        );

        return html_writer::tag(
            'span',
            html_writer::tag(
                'a',
                get_string('hashtag', 'editor_weka', s($this->text)),
                ['href' => $url->out(false)]
            )
        );
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        return "#{$this->text}";
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return "hashtag";
    }

    /**
     * @return string
     */
    public function get_text(): string {
        return $this->text;
    }

    /**
     * @param string $text
     * @return array
     */
    public static function create_raw_node(string $text): array {
        return [
            'type' => static::get_type(),
            'attrs' => [
                'text' => $text
            ],
        ];
    }
}