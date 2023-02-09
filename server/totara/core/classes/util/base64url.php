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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\util;

/**
 * Encode to/from the Base64-URL format.
 */
final class base64url {
    /**
     * Encode data with base64url.
     *
     * @param string $data
     * @return string
     */
    public static function encode(string $data): string {
        if ($data === '') {
            return '';
        }
        $base64 = base64_encode($data);
        $base64 = rtrim($base64, '=');
        $base64url = strtr($base64, '+/', '-_');
        return $base64url;
    }

    /**
     * Decode base64url encoding.
     *
     * @param string $base64url
     * @return string|false
     */
    public static function decode(string $base64url) {
        if ($base64url === '') {
            return '';
        }
        // An incoming string must contain only valid characters.
        if (!preg_match('/^[A-Za-z0-9\-_=]+$/', $base64url)) {
            return false;
        }
        // Add '=' paddings.
        $remainder = strlen($base64url) % 4;
        if ($remainder) {
            $base64url .= str_repeat('=', 4 - $remainder);
        }
        return @base64_decode(strtr($base64url, '-_', '+/'), true);
    }
}
