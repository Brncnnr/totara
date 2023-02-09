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

class mock_vimeo_request {
    /**
     * @var array
     */
    private static $url;

    /**
     * @param string $url
     * @return void
     */
    public static function add_mock_url(string $url): void {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            debugging("Not a unit test environment", DEBUG_DEVELOPER);
            return;
        }

        self::$url = $url;
    }

    /**
     * @param string $url
     * @return array|null
     */
    public static function get_body(string $url): ?array {
        if (!isset(static::$url)) {
            return null;
        }

        if (static::$url === $url) {
            return self::get_mock_response();
        }

        return null;
    }

    /**
     * @return void
     */
    public static function clear(): void {
        static::$url = null;
    }

    /**
     * @return array
     */
    private static function get_mock_response(): array {
        /**
         * This is part of response data from vimeo api
         *
         *  {
         *      "title": "The New Vimeo Player (You Know, For Videos)",
         *      "description": "It may look (mostly) the same on the surface, but under the hood we totally
         *                     rebuilt our player. Here’s a quick rundown of some of the coolest new
         *                     features:\n\n• Lightning fast playback\n• Redesigned Share screen\n• Closed
         *                     caption and subtitle compatible\n• HTML5 by default\n• Purchase-from-player
         *                     functionality for embedded Vimeo On Demand trailers\n• More responsive than e
         *                     ver (go ahead, resize it, we dare you!!!)\n\nWe’re really proud of these updates.
         *                     So proud that we made a spiffy new page to showcase all the reasons why we have
         *                     the best video player in the galaxy.",
         *      "thumbnail_url": "https://i.vimeocdn.com/video/452001751_295x166.webp",
         *      "thumbnail_width": 295,
         *      "thumbnail_height": 166,
         *  }
         *
         */
        return [
            'thumbnail_url' => 'test.jpg',
            'thumbnail_width' => 100,
            'thumbnail_height' => 100,
            'title' => 'Test title',
            'description' => 'Test description',
            'url' => "https://vimeo.com/1"
        ];
    }
}