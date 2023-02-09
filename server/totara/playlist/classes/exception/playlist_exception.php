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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_playlist
 */
namespace totara_playlist\exception;

final class playlist_exception extends \moodle_exception {
    /**
     * playlist_exception constructor.
     *
     * @param string $errorcode
     * @param null   $a
     * @param null   $debuginfo
     */
    private function __construct(string $errorcode, $a = null, $debuginfo = null) {
        parent::__construct($errorcode, 'totara_playlist', '', $a, $debuginfo);
    }

    /**
     * @param string $errorcode
     * @param null   $a
     * @param null   $debuginfo
     *
     * @return playlist_exception
     */
    public static function create(string $errorcode, $a = null, $debuginfo = null): playlist_exception {
        $code = $errorcode;

        if (false === stripos($errorcode, 'error:')) {
            $code = "error:{$errorcode}";
        }

        return new static($code, $a, $debuginfo);
    }
}