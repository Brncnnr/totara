<?php
/**
 * This file is part of Totara LMS
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
use core\json_editor\node\abstraction\block_node;
use core\json_editor\node\abstraction\has_extra_linked_file;
use core\json_editor\node\attribute\extra_linked_file;
use core\json_editor\node\file\base_file;
use html_writer;
use stored_file;

/**
 * Class audio
 * @package core\json_editor\node
 */
final class audio extends base_file implements block_node, has_extra_linked_file {
    /**
     * @var string
     */
    protected $mime_type;

    /**
     * @var extra_linked_file|null
     */
    private $transcript;

    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        /** @var audio $audio */
        $audio = parent::from_node($node);
        $attrs = $node['attrs'];

        if (!array_key_exists('mime_type', $attrs)) {
            throw new coding_exception("No mime type was set");
        }

        $audio->mime_type = $attrs['mime_type'];
        $audio->transcript = null;

        if (array_key_exists('transcript', $attrs) && !empty($attrs['transcript'])) {
            $transcript = $attrs['transcript'];
            if (!is_array($transcript)) {
                throw new coding_exception("Expecting 'transcript' attribute to be an array");
            }

            $audio->transcript = new extra_linked_file(
                $transcript['url'],
                $transcript['filename']
            );
        }

        return $audio;
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
        if (!array_key_exists('mime_type', $attrs)) {
            return false;
        }

        // Validate transcript if it exist in the node data.
        if (array_key_exists('transcript', $attrs) && !empty($attrs['transcript'])) {
            if (!is_array($attrs['transcript'])) {
                return false;
            }

            $transcript_keys = array_keys($attrs['transcript']);
            if (!node_helper::check_keys_match($transcript_keys, ['url', 'filename'])) {
                return false;
            }
        }

        // Validate on attribute keys.
        $input_keys = array_keys($attrs);
        return node_helper::check_keys_match($input_keys, ['filename', 'url', 'mime_type'], ['transcript']);
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

        // Cleanning mime type.
        $attrs['mime_type'] = clean_param($attrs['mime_type'], PARAM_TEXT);

        // Cleaning the transcript object, if there is any.
        if (array_key_exists('transcript', $attrs)) {
            $transcript = $attrs['transcript'];
            if (empty($transcript)) {
                unset($attrs['transcript']);
            } else {
                $transcript['url'] = clean_param($transcript['url'], PARAM_URL);
                $transcript['filename'] = clean_param($transcript['filename'], PARAM_FILE);

                $attrs['transcript'] = $transcript;
            }
        }

        $cleaned_raw_node['attrs'] = $attrs;
        return $cleaned_raw_node;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        return html_writer::tag(
            'div',
            html_writer::tag(
                'audio',
                null,
                ['src' => $this->url, 'controls' => true]
            )
        );
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        $url = $this->get_file_url();
        return "[{$this->filename}]({$url->out(false)})\n\n";
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'audio';
    }

    /**
     * @return string
     */
    public function get_mime_type(): string {
        return $this->mime_type;
    }

    /**
     * @param stored_file      $audio_file
     * @param stored_file|null $transcript_file
     * @return array
     */
    public static function create_raw_node(stored_file $audio_file, ?stored_file $transcript_file = null): array {
        $audio_file_url = self::build_file_url_from_stored_file($audio_file);
        $attrs = [
            'filename' => $audio_file->get_filename(),
            'url' => $audio_file_url->out(false),
            'mime_type' => $audio_file->get_mimetype()
        ];

        if (null !== $transcript_file) {
            $transcript_file_url = self::build_file_url_from_stored_file($transcript_file);
            $attrs['transcript'] = [
                'url' => $transcript_file_url->out(),
                'filename' => $transcript_file->get_filename()
            ];
        }

        return [
            'type' => static::get_type(),
            'attrs' => $attrs
        ];
    }

    /**
     * Returning the transcript file metadata.
     * @return extra_linked_file|null
     */
    public function get_extra_linked_file(): ?extra_linked_file {
        return $this->transcript;
    }
}