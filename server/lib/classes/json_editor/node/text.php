<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\json_editor\node;

use core\json_editor\helper\node_helper;
use core\json_editor\node\abstraction\inline_node;
use html_writer;
use core\json_editor\formatter\formatter;

final class text extends node implements inline_node {
    /**
     * @var array
     */
    private $marks;

    /**
     * @var string
     */
    private $value;

    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        /** @var text $text */
        $text = parent::from_node($node);

        $text->marks = [];
        $text->value = '';

        if (array_key_exists('marks', $node) && is_array($node['marks'])) {
            $text->marks = $node['marks'];
        }

        if (array_key_exists('text', $node)) {
            $text->value = (string) $node['text'];
        }

        return $text;
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        if (!array_key_exists('text', $raw_node)) {
            return false;
        }

        if (array_key_exists('marks', $raw_node)) {
            // Attribute `marks` is our optional attribute.
            if (null !== $raw_node['marks'] && !is_array($raw_node['marks'])) {
                return false;
            }

            $marks = $raw_node['marks'];
            foreach ($marks as $mark_item) {
                if (!is_array($mark_item)) {
                    return false;
                }

                if (!array_key_exists('type', $mark_item)) {
                    // Mark item is wrong.
                    return false;
                }

                if (!node_helper::check_keys_match_against_data($mark_item, ['type'], ['attrs'])) {
                    return false;
                }

                $mark_type = $mark_item['type'];
                if ('link' == $mark_type) {
                    // It is a link - check for field `attrs`
                    if (!array_key_exists('attrs', $mark_item) || !is_array($mark_item['attrs'])) {
                        return false;
                    }

                    $attrs = $mark_item['attrs'];
                    if (!array_key_exists('href', $attrs)) {
                        return false;
                    }
                }
            }
        }

        return node_helper::check_keys_match_against_data($raw_node, ['type', 'text'], ['marks']);
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

        if (array_key_exists('marks', $cleaned_raw_node) && !is_array($cleaned_raw_node['marks'])) {
            $cleaned_raw_node['marks'] = [];
        }

        if (!empty($cleaned_raw_node['marks'])) {
            $marks = $cleaned_raw_node['marks'];
            $cleaned_marks = [];
            foreach ($marks as $mark) {
                if (!is_array($mark)) {
                    debugging("Invalid item within list of marks that is not an array", DEBUG_DEVELOPER);
                    continue;
                }

                $cleaned_type = clean_param($mark['type'], PARAM_ALPHA);
                $mark['type'] = $cleaned_type;

                if ('link' == $cleaned_type && isset($mark['attrs'])) {
                    // This is an assumption that if the text mark is a link. As the href needs to have a value
                    // in order to make the link works, otherwise it is just an invalid json node.
                    $mark['attrs']['href'] = self::clean_url($mark['attrs']['href']);
                }

                $cleaned_marks[] = $mark;
            }

            $cleaned_raw_node['marks'] = $cleaned_marks;
        }

        return $cleaned_raw_node;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        $str = s($this->value);

        if (empty($this->marks)) {
            return $str;
        }

        foreach ($this->marks as $mark) {
            $type = $mark['type'];
            switch ($type) {
                case 'underline':
                    $str = html_writer::tag('u', $str);
                    break;

                case 'strong':
                case 'em':
                    $str = html_writer::tag($type, $str);
                    break;

                case 'link':
                    $url = $mark['attrs']['href'] ?? null;
                    $url = self::clean_url($url);
                    if (isset($mark['attrs']['open_in_new_window']) && $mark['attrs']['open_in_new_window']) {
                        $str = html_writer::tag('a', $str, ['href' => $url, 'target' => '_blank']);
                    } else {
                        $str = html_writer::tag('a', $str, ['href' => $url]);
                    }
                    break;

                default:
                    debugging("Invalid mark type '{$type}'", DEBUG_DEVELOPER);
            }
        }

        return $str;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        if (empty($this->marks)) {
            return $this->value;
        }

        $str = $this->value;

        foreach ($this->marks as $mark) {
            $type = $mark['type'];
            switch ($type) {
                case 'underline':
                    $str = "__{$str}__";
                    break;

                case 'strong':
                    $str = "**{$str}**";
                    break;

                case 'em':
                    $str = "_{$str}_";
                    break;

                case 'link':
                    $url = $mark['attrs']['href'] ?? null;
                    $url = self::clean_url($url);
                    $str = "{$str} ({$url})";
                    break;

                default:
                    debugging("Invalid mark type '{$type}'", DEBUG_DEVELOPER);
            }
        }

        return $str;
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'text';
    }

    /**
     * @param string $text
     * @return array
     */
    public static function create_json_node_from_text(string $text): array {
        return [
            'type' => static::get_type(),
            'text' => $text,
            'marks' => []
        ];
    }

    /**
     * @param string $text
     * @param string|\moodle_url $url
     * @return array
     */
    public static function create_json_node_from_link(string $text, $url): array {
        if ($url instanceof \moodle_url) {
            $url = $url->out(false);
        }
        return [
            'type' => static::get_type(),
            'text' => $text,
            'marks' => [
                [
                    'type' => 'link',
                    'attrs' => [
                        'href' => (string)$url
                    ]
                ]
            ]
        ];
    }
}