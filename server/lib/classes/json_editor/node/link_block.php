<?php
/*
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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package core
 */
namespace core\json_editor\node;

use core\json_editor\formatter\formatter;
use core\json_editor\helper\node_helper;
use html_writer;
use core\json_editor\node\abstraction\block_node;

/**
 * Link block node.
 */
final class link_block extends base_link implements block_node {
    /**
     * @var string|null
     */
    private $title;

    /**
     * @var string|null
     */
    private $image;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        /** @var link_block $block */
        $block = parent::from_node($node);

        $block->title = null;
        $block->image = null;
        $block->description = null;

        $attrs = $node['attrs'];

        if (array_key_exists('title', $attrs)) {
            $block->title = $attrs['title'];
        }

        if (array_key_exists('image', $attrs)) {
            $block->image = clean_param($attrs['image'], PARAM_URL);
        }

        if (array_key_exists('description', $attrs)) {
            $block->description = $attrs['description'];
        }

        if (array_key_exists('open_in_new_window', $attrs)) {
            $block->open_in_new_window = $attrs['open_in_new_window'];
        }

        return $block;
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

        // Most of the fields inside the link block are pretty much optionals except for the url fields
        $attrs = $raw_node['attrs'];
        $input_keys = array_keys($attrs);

        return node_helper::check_keys_match($input_keys, ['url'], ['image', 'title', 'description', 'open_in_new_window']);
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

        $attrs = $cleaned_raw_node['attrs'];

        if (isset($attrs['image'])) {
            $attrs['image'] = clean_param($attrs['image'], PARAM_URL);
        }

        $cleaned_raw_node['attrs'] = $attrs;
        return $cleaned_raw_node;
    }

    /**
     * @return string|null
     */
    public function get_title(): ?string {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function get_image(): ?string {
        return $this->image;
    }

    /**
     * @return string|null
     */
    public function get_description(): ?string {
        return $this->description;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        $title = $this->url;
        if (null !== $this->title && '' !== $this->title) {
            $title = $this->title;
        }

        $attrs = ['href' => $this->url];
        if ($this->open_in_new_window) {
            $attrs['target'] = '_blank';
        }

        return html_writer::tag('a', $title, $attrs);
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        return "{$this->title} ({$this->url})\n\n";
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'link_block';
    }

    /**
     * @param string|null $title
     * @param string|null $description
     * @param bool $with_image
     *
     * @return array
     */
    public static function create_raw_node(?string $title = null, ?string $description = null,
                                           bool $with_image = false, bool $new_window = false): array {
        $image_url = null;
        if ($with_image) {
            $image_url = "http://example.com?with-image=1";
        }

        return [
            'type' => static::get_type(),
            'attrs' => [
                'description' => $description,
                'title' => $title,
                'url' => 'http://example.com',
                'image' => $image_url,
                'open_in_new_window' => $new_window,
            ]
        ];
    }
}