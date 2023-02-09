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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\json_editor\node;

use core\json_editor\formatter\formatter;
use core\json_editor\helper\node_helper;
use core\json_editor\node\abstraction\inline_node;
use core\json_editor\schema;
use core\json_editor\node\abstraction\block_node;

/**
 * Node for heading
 */
final class heading extends node implements block_node {
    /**
     * @var int
     */
    public const LEVEL_ONE = 1;

    /**
     * @var int
     */
    public const LEVEL_TWO = 2;

    /**
     * @var int
     */
    private $level;

    /**
     * @var array
     */
    private $content;

    /**
     * @var string
     */
    private $align;

    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        /** @var heading $heading */
        $heading = parent::from_node($node);
        if (!array_key_exists('attrs', $node)) {
            throw new \coding_exception("No heading attributes");
        }

        // Default to level one.
        $heading->level = static::LEVEL_ONE;
        $heading->align = null;

        $attrs = $node['attrs'];

        if (isset($attrs['level'])) {
            $heading->level = $attrs['level'];
        }

        if (isset($attrs['align'])) {
            $heading->align = $attrs['align'];
        }

        $heading->content = [];

        if (isset($node['content'])) {
            $heading->content = (array) $node['content'];
        }

        return $heading;
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
        if (!array_key_exists('level', $attrs)) {
            return false;
        }

        // Make sure that there is attribute 'level' within the document.
        if (!node_helper::check_keys_match_against_data($attrs, ['level'], ['align'])) {
            return false;
        }

        if (!array_key_exists('level', $attrs)) {
            return false;
        }

        if (!array_key_exists('content', $raw_node)) {
            return false;
        }

        if (is_array($raw_node['content'])) {
            // Sometimes content can be null.
            $schema = schema::instance();
            $contents = $raw_node['content'];

            foreach ($contents as $raw_node_content) {
                if (!array_key_exists('type', $raw_node_content)) {
                    return false;
                }

                $node_type = $raw_node_content['type'];
                $node_class = $schema->get_node_classname($node_type);

                if (null === $node_class) {
                    // Skip the invalid node for now.
                    debugging("Cannot find class for node type '{$node_type}'", DEBUG_DEVELOPER);
                    continue;
                }

                $interfaces = class_implements($node_class);
                if (!in_array(inline_node::class, $interfaces)) {
                    return false;
                }

                $inner_result = call_user_func([$node_class, 'validate_schema'], $raw_node_content);
                if (!$inner_result) {
                    return false;
                }
            }
        }

        $input_keys = array_keys($raw_node);
        return node_helper::check_keys_match($input_keys, ['type', 'content', 'attrs']);
    }

    /**
     * @deprecated since Totara 14.0
     * @param array $raw_node
     * @return array
     */
    public static function sanitize_raw_node(array $raw_node): array {
        $sanitized_node = parent::sanitize_raw_node($raw_node);
        $content_nodes = $sanitized_node['content'] ?? [];

        $sanitized_node['content'] = node_helper::sanitize_raw_nodes($content_nodes);
        return $sanitized_node;
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

        if (!array_key_exists('content', $cleaned_raw_node) || !array_key_exists('attrs', $cleaned_raw_node)) {
            throw new \coding_exception("Invalid node structure", static::get_type());
        }

        // Cleaning attrs first.
        $attrs = $cleaned_raw_node['attrs'];
        if (!in_array($attrs['level'], [static::LEVEL_TWO, static::LEVEL_ONE])) {
            // Unknown level.
            $attrs['level'] = static::LEVEL_TWO;
        } else {
            $attrs['level'] = (int) $attrs['level'];
        }

        $cleaned_raw_node['attrs'] = $attrs;

        $content = static::clean_raw_node_content($cleaned_raw_node['content']);
        if ($content === null) {
            return null;
        }
        $cleaned_raw_node['content'] = $content;

        return $cleaned_raw_node;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        $content = $formatter->print_nodes($this->content, formatter::HTML);

        $props = [];

        if (!empty($this->align)) {
            $props['style'] = 'text-align:' . $this->align;
        }

        switch ($this->level) {
            case static::LEVEL_ONE:
                return \html_writer::tag('h4', $content, $props);

            case static::LEVEL_TWO:
                return \html_writer::tag('h5', $content, $props);

            default:
                // Default to level one, but we need debugging it here.
                debugging("Invalid level of heading: {$this->level}", DEBUG_DEVELOPER);
                return \html_writer::tag('h4', $content, $props);
        }
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        $content = "";
        $schema = schema::instance();

        foreach ($this->content as $item) {
            if (!isset($item['type'])) {
                debugging("Invalid item without any type", DEBUG_DEVELOPER);
                continue;
            }

            $node = $schema->get_node($item['type'], $item);
            if (null === $node) {
                debugging("Cannot find node for type '{$item['type']}'", DEBUG_DEVELOPER);
                continue;
            }

            $content .= $node->to_text($formatter);
        }

        switch ($this->level) {
            case static::LEVEL_ONE:
                return "# {$content}\n";

            case static::LEVEL_TWO:
                return "## {$content}\n";

            default:
                // Default to level one, but we need debugging it here.

                debugging("Invalid level of heading: {$this->level}", DEBUG_DEVELOPER);
                return "# {$content}\n";
        }
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'heading';
    }

    /**
     * @param string $text
     * @param int    $level
     * @return array
     */
    public static function create_raw_node(string $text, int $level = 1, string $align = 'left'): array {
        return [
            'type' => self::get_type(),
            'attrs' => [
                'level' => $level,
                'align' => $align
            ],
            'content' => [
                [
                    'type' => 'text',
                    'text' => $text
                ]
            ]
        ];
    }
}