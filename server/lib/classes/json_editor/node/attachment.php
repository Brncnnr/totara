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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\json_editor\node;

use coding_exception;
use core\json_editor\formatter\formatter;
use core\json_editor\helper\node_helper;
use core\json_editor\node\file\base_file;
use html_writer;
use stored_file;

/**
 * Node type for attachment.
 */
final class attachment extends base_file {
    /**
     * @var int|null
     */
    private $size;

    /**
     * @var array
     */
    private $options;

    /**
     * @param array $node
     *
     * @return node|attachment
     * @throws coding_exception
     */
    public static function from_node(array $node): node {
        /** @var attachment $attachment */
        $attachment = parent::from_node($node);
        $attachment->options = [];

        $attrs = $node['attrs'];

        if (!array_key_exists('size', $attrs)) {
            throw new coding_exception("No attachment size was found");
        }

        if (array_key_exists('option', $attrs) && !is_null($attrs['option'])) {
            $attachment->options = $attrs['option'];
        }

        $attachment->size = (int) $attrs['size'];
        return $attachment;
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

        $attrs = $raw_node['attrs'];
        if (!array_key_exists('size', $attrs) || !isset($attrs['size'])) {
            // Key 'size' must be existing, and must not be null.
            return false;
        }

        return node_helper::check_keys_match_against_data($attrs, ['filename', 'url', 'size'], ['option']);
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
        $attrs['size'] = clean_param($attrs['size'], PARAM_INT);

        if (array_key_exists('option', $attrs)) {
            $option = $attrs['option'];

            if (array_key_exists('subtitle', $option) && is_array($option['subtitle'])) {
                $subtitle = $option['subtitle'];

                $subtitle['url'] = clean_param($subtitle['url'], PARAM_URL);
                $subtitle['filename'] = clean_param($subtitle['filename'], PARAM_FILE);

                $option['subtitle'] = $subtitle;
            }

            if (array_key_exists('transcript', $option) && is_array($option['transcript'])) {
                $transcript = $option['transcript'];

                $transcript['url'] = clean_param($transcript['url'], PARAM_URL);
                $transcript['filename'] = clean_param($transcript['filename'], PARAM_FILE);

                $option['transcript'] = $transcript;
            }

            $attrs['option'] = $option;
        }

        $cleaned_raw_node['attrs'] = $attrs;
        return $cleaned_raw_node;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        $download_url = $this->get_file_url(true);
        return html_writer::tag(
            'a',
            get_string(
                'file_with_size',
                'editor_weka',
                [
                    'filename' => $this->filename,
                    'size' => display_size($this->size),
                ]
            ),
            ['href' => $download_url->out()]
        );
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        $url = $this->get_file_url();
        return "[{$this->filename}]({$url->out()})\n\n";
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'attachment';
    }

    /**
     * @return int
     */
    public function get_file_size(): int {
        return $this->size;
    }

    /**
     * @param stored_file $file
     * @return array
     */
    public static function create_raw_node(stored_file $file): array {
        $type = static::get_type();
        $file_url = self::build_file_url_from_stored_file($file);

        return [
            'type' => $type,
            'attrs' => [
                'filename' => $file->get_filename(),
                'url' => $file_url->out(false),
                'size' => $file->get_filesize(),
                'option' => [],
            ],
        ];
    }
}