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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */
namespace core\link;

use coding_exception;
use moodle_url;

final class vimeo_reader implements reader {
    /**
     * @see https://developer.vimeo.com/api/oembed/videos
     *
     * @var string
     */
    const VIMEO_URL = 'https://vimeo.com/api/oembed.json?url=';

    /**
     * @param $url
     * @return metadata_info|null
     */
    public static function get_metadata_info($url): ?metadata_info {
        if (empty($url)) {
            throw new coding_exception("Cannot get metadata info of empty url");
        }

        $url = clean_param((string) $url, PARAM_URL);
        if (!$url) {
            return null;
        }

        $validator = new url_validator(new moodle_url($url));
        $ip_address = $validator->get_validated_ip();
        if (!$ip_address) {
            return null;
        }

        $response = self::send_request($url);
        $info = self::build_metadata($response, $url);

        return metadata_info::create_instance($info);
    }

    /**
     * @param string $url
     * @return array
     */
    private static function send_request(string $url): array {
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            $response = self::phpunit_get($url);
            if (!is_null($response)) {
                return $response;
            }

            debugging("For a better phpunit test environment, please use 'mock_vimeo_request' instead", DEBUG_DEVELOPER);
            return [];
        }

        try {
            $url = self::VIMEO_URL.urlencode($url);
            $ch = curl_init($url);
            if (!$ch) {
                throw new coding_exception("Cannot access to the url");
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);

            $result = curl_exec($ch);
            $response = json_decode($result, true);

            if (is_null($response)) {
                // When response is 404, 403 and 304, the response will be null if it's decoded into json.
                throw new coding_exception('Request not succeeded');
            }

            return $response;
        } finally {
            curl_close($ch);
        }
    }

    /**
     * @param array $metadata
     * @param string $url
     * @return string[]
     */
    private static function build_metadata(array $metadata, string $url): array {
        // Info is compatible with structure of metadata info.
        $info = [
            'url' => '',
            'title' => '',
            'image' => '',
            'description' => '',
            'video:height' => '',
            'video:width' => ''
        ];

        $info['url'] = $url;

        if (isset($metadata['thumbnail_url'])) {
            $info['image'] = $metadata['thumbnail_url'];
        }

        if (isset($metadata['title'])) {
            $info['title'] = $metadata['title'];
        }

        if (isset($metadata['description'])) {
            $info['description'] = $metadata['description'];
        }

        if (isset($metadata['thumbnail_height'])) {
            $info['video:height'] = $metadata['thumbnail_height'];
        }

        if (isset($metadata['thumbnail_width'])) {
            $info['video:width'] = $metadata['thumbnail_width'];
        }

        return $info;
    }

    /**
     * @param string $url
     * @return array|null
     */
    private static function phpunit_get(string $url): ?array {
        if (class_exists('mock_vimeo_request')) {
            return \mock_vimeo_request::get_body($url);
        }

        return null;
    }
}