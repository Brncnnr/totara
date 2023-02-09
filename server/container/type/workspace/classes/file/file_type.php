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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\file;


final class file_type {
    /**
     * @var int
     */
    public const VIDEO = 1;

    /**
     * @var int
     */
    public const IMAGE = 2;

    /**
     * @var int
     */
    public const AUDIO = 3;

    /**
     * OTHERS is for displaying fileicon
     * @var int
     */
    public const OTHERS = 4;

    /**
     * file_type constructor.
     */
    private function __construct() {
    }

    /**
     * @param string $extension
     * @return string
     */
    public static function get_code(string $extension): string {

        if (static::is_image($extension))  {
            return 'IMAGE';
        }

        if (static::is_audio($extension)) {
            return 'AUDIO';
        }

        if (static::is_video($extension)) {
            return 'VIDEO';
        }

        return 'OTHERS';
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_image(string $extension): bool {
        return in_array($extension, [ 'svg', 'jpg', 'png', 'jpeg', 'gif']);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_video(string $extension): bool {
        return in_array($extension, ['mp4', 'ogv', 'webm']);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_audio(string $extension): bool {
        return in_array($extension, ['mp3', 'wav', 'ogg']);
    }
}