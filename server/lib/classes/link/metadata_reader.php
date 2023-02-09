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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */
namespace core\link;

use core\link\http\request;
use moodle_url;

final class metadata_reader implements reader {
    /**
     * @var array
     */
    private const ELEMENTS = [
        'title',
        'description',
        'url',
        'image',
        'video:height',
        'video:width',
    ];

    /**
     * @var array
     */
    private const PREFIXES = [
        'og',
        'twitter'
    ];

    /**
     * @param string|moodle_url $url
     * @return metadata_info|null
     */
    public static function get_metadata_info($url): ?metadata_info {
        if (empty($url)) {
            throw new \coding_exception("Cannot get metadata info of empty url");
        }
        $url = clean_param((string) $url, PARAM_URL);
        if (!$url) {
            return null;
        }

        $url = new moodle_url($url);

        // Skip this for unit tests, it's covered separately
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            $final_destination = new url_final_destination();
            $url = $final_destination($url);
        }

        $validator = new url_validator($url);
        $ip_address = $validator->get_validated_ip();
        if (!$ip_address) {
            return null;
        }

        // To prevent DNS rebinding attacks we use the validated IP address to resolve the request
        $response = request::get((string) $url, 0, $url->get_host(), $ip_address);
        if (!$response->is_ok() || !$response->is_html()) {
            return null;
        }

        $html_document = $response->get_body();
        $meta_tags = $html_document->getElementsByTagName('meta');

        if (empty($meta_tags)) {
            return null;
        }

        $metadata = [];
        foreach ($meta_tags as $meta_tag) {
            $property = $meta_tag->getAttribute('property');
            if (!empty($property)) {
                $metadata[$property] = $meta_tag->getAttribute('content');
            }

            $name = $meta_tag->getAttribute('name');
            if (!empty($name)) {
                $metadata[$name] = $meta_tag->getAttribute('content');
            }
        }

        $info = self::build_metadata($metadata);

        if (count($info) === count(static::ELEMENTS)) {
            return metadata_info::create_instance($info);
        }

        // Either we are missing the title or description.
        if (empty($info['description']) && isset($metadata['description'])) {
            $info['description'] = $metadata['description'];
        }

        if (empty($info['title'])) {
            if (isset($metadata['title'])) {
                $info['title'] = $metadata['title'];
            } else {
                // Try to fetch from html document.
                $title = $html_document->getElementsByTagName('title');
                if ($title->length > 0) {
                    $info['title'] = $title->item(0)->textContent;
                }
            }
        }

        return metadata_info::create_instance($info);
    }

    /**
     * @param array $metadata
     * @return array
     */
    private static function build_metadata(array $metadata): array {
        $info = [];
        foreach (static::PREFIXES as $prefix) {
            if (count($info) === count(static::ELEMENTS)) {
                return $info;
            }

            foreach (static::ELEMENTS as $element) {
                $tag_name = "{$prefix}:{$element}";

                if (array_key_exists($tag_name, $metadata) && !isset($info[$element])) {
                    $info[$element] = $metadata[$tag_name];
                }
            }
        }

        return $info;
    }

}