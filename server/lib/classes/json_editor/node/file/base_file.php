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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\json_editor\node\file;

use core\json_editor\helper\node_helper;
use core\json_editor\node\node;

/**
 * Base class for file related node.
 * The base file node will look something like example below:
 * @example:
 *  [
 *      'type' => 'node_type',
 *      'attrs' => [
 *          'filename' => 'file_name',
 *          'url' => 'file_url'
 *      ]
 *  ]
 */
abstract class base_file extends node {
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $url;

    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        if (!array_key_exists('attrs', $node) || empty($node['attrs'])) {
            throw new \coding_exception("Invalid node parameter");
        }

        $attrs = (array) $node['attrs'];
        if (!isset($attrs['filename'])) {
            throw new \coding_exception("Missing attribute 'filename'");
        } else if (!isset($attrs['url'])) {
            throw new \coding_exception("Missing attribute 'url'");
        }

        /** @var base_file $file */
        $file = parent::from_node($node);

        $file->filename = $attrs['filename'];
        $file->url = $attrs['url'];

        return $file;
    }

    /**
     * @param bool $force_download
     * @return \moodle_url
     */
    public function get_file_url(bool $force_download = false): \moodle_url {
        if (false !== stripos($this->url, '@@PLUGINFILE@@', 0)) {
            throw new \coding_exception("The file url had not been rewritten yet");
        }

        $url = new \moodle_url($this->url);
        if ($force_download) {
            $url->param('forcedownload', 1);
        }

        return $url;
    }

    /**
     * Returning the file name of this file.
     *
     * @return string
     */
    public function get_filename(): string {
        return $this->filename;
    }

    /**
     * @param \stored_file $file
     * @return \moodle_url
     */
    final protected static function build_file_url_from_stored_file(\stored_file $file): \moodle_url {
        $file_component = $file->get_component();
        $file_area = $file->get_filearea();

        if ('user' === $file_component && 'draft' === $file_area) {
            return \moodle_url::make_draftfile_url(
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
        }

        return \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file_component,
            $file_area,
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
    }

    /**
     * For the file node, there should only be attributes `type` and `attrs`.
     *
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        if (!isset($raw_node['attrs']) || !is_array($raw_node['attrs'])) {
            return false;
        }

        $input_keys = array_keys($raw_node);
        $key_match = node_helper::check_keys_match($input_keys, ['type', 'attrs']);

        if (!$key_match) {
            return false;
        }

        $attrs = $raw_node['attrs'];
        foreach (['filename', 'url'] as $field) {
            if (!array_key_exists($field, $attrs)) {
                return false;
            }

            if (empty($attrs[$field])) {
                return false;
            }
        }

        // Note: we do not run key checks on this parent, as it should be done explicitly at the children,
        // because the children can have as many attributes as possible, as long as it is covered.
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

        if (!isset($cleaned_raw_node['attrs'])) {
            throw new \coding_exception("Invalid node structure", static::get_type());
        }

        $attrs = $cleaned_raw_node['attrs'];

        $url = $attrs['url'];
        $cleaned_url = clean_param($url, PARAM_URL);

        if (empty($cleaned_url)) {
            // Invalid url
            return null;
        }

        $filename = $attrs['filename'];
        $cleaned_file_name = clean_param($filename, PARAM_FILE);

        $cleaned_raw_node['attrs']['filename'] = $cleaned_file_name;
        $cleaned_raw_node['attrs']['url'] = $cleaned_url;

        return $cleaned_raw_node;
    }
}