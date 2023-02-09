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
 * Node for video
 */
final class video extends base_file implements block_node, has_extra_linked_file {
    /**
     * @var string
     */
    private $mime_type;

    /**
     * @var extra_linked_file|null
     */
    private $subtitle;

    /**
     * @param array $node
     * @return video
     */
    public static function from_node(array $node): node {
        /** @var video $video */
        $video = parent::from_node($node);
        $attrs = $node['attrs'];

        if (!array_key_exists('mime_type', $attrs)) {
            throw new coding_exception("Unable to find mime_type");
        }

        $video->mime_type = $attrs['mime_type'];
        $video->subtitle = null;

        if (array_key_exists('subtitle', $attrs) && !empty($attrs['subtitle'])) {
            // Subtitle.
            $subtitle = $attrs['subtitle'];
            if (!is_array($subtitle)) {
                throw new coding_exception("Expecting 'subtitle' attribute to be an array");
            }

            $video->subtitle = new extra_linked_file(
                $subtitle['url'],
                $subtitle['filename']
            );
        }

        return $video;
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

        if (array_key_exists('subtitle', $attrs) && !is_null($attrs['subtitle'])) {
            // Do the validation for subtitle.
            if (!is_array($attrs['subtitle'])) {
                // Expecting subtitle is like a hashmap that contains file url and file name.
                return false;
            }

            $subtitle_keys = array_keys($attrs['subtitle']);
            if (!node_helper::check_keys_match($subtitle_keys, ['url', 'filename'])) {
                return false;
            }
        }

        $input_keys = array_keys($attrs);
        return node_helper::check_keys_match($input_keys, ['mime_type', 'url', 'filename'], ['subtitle']);
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

        $mime_type = $cleaned_raw_node['attrs']['mime_type'];
        $cleaned_raw_node['attrs']['mime_type'] = clean_param($mime_type, PARAM_TEXT);

        if (array_key_exists('subtitle', $cleaned_raw_node['attrs'])) {
            $subtitle = $cleaned_raw_node['attrs']['subtitle'];

            if (!is_null($subtitle)) {
                $subtitle['url'] = clean_param($subtitle['url'], PARAM_URL);
                $subtitle['filename'] = clean_param($subtitle['filename'], PARAM_FILE);

                $cleaned_raw_node['attrs']['subtitle'] = $subtitle;
            }
        }

        return $cleaned_raw_node;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        return html_writer::tag(
            'div',
            html_writer::tag('video', null, [
                'src' => $this->get_file_url()->out(false),
                'controls' => true,
                'data-grow' => true,
            ])
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
        return 'video';
    }

    /**
     * @return string
     */
    public function get_mime_type(): string {
        return $this->mime_type;
    }

    /**
     * @param stored_file      $video_file
     * @param stored_file|null $subtitle_file
     *
     * @return array
     */
    public static function create_raw_node(stored_file $video_file, ?stored_file $subtitle_file = null): array {
        $video_file_url = static::build_file_url_from_stored_file($video_file);
        $attributes = [
            'url' => $video_file_url->out(false),
            'filename' => $video_file->get_filename(),
            'mime_type' => $video_file->get_mimetype(),
        ];

        if (null !== $subtitle_file) {
            $subtitle_file_url = static::build_file_url_from_stored_file($subtitle_file);
            $attributes['subtitle'] = [
                'url' => $subtitle_file_url->out(false),
                'filename' => $subtitle_file->get_filename()
            ];
        }

        return [
            'type' => static::get_type(),
            'attrs' => $attributes
        ];
    }

    /**
     * Returning the subtitle file metadata.
     * @return extra_linked_file|null
     */
    public function get_extra_linked_file(): ?extra_linked_file {
        return $this->subtitle;
    }
}