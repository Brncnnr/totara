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
 * @package totara_msteams
 */

namespace totara_msteams\botfw\auth;

/**
 * Validate OAuth2 Bearer in the Authorization header.
 */
class bearer {
    /**
     * Validate OAuth2 Bearer in the Authorization header.
     * See RFC 6750 for more information.
     *
     * @param array $headers of [field_name => field_value]
     * @param integer|null $time
     * @return jwt|null
     */
    public static function validate_header(array $headers, int $time = null): ?jwt {
        $header = self::get_authorization_header($headers);
        // According to RFC 2617 section 1.2, 'bearer' can be case insensitive.
        // However, RFC 6750 section 2.1 states it is 'Bearer' :(
        if (strcasecmp(substr($header, 0, 7), 'Bearer ')) {
            return null;
        }
        $token = substr($header, 7);
        if (!preg_match('/^[A-Za-z0-9\-\._~\+\/=]+$/', $token)) {
            return null;
        }
        // Verify the integrity of the JWT.
        // The verification will fail if the token is expired.
        return jwt::try_load($token, $time);
    }

    /**
     * Extract the value of the Authorization header.
     *
     * @param array $headers of [field_name => field_value]
     * @return string
     */
    private static function get_authorization_header(array $headers): string {
        if (isset($headers['Authorization'])) {
            return $headers['Authorization'];
        }
        // Header field names are case insensitive according to RFC 7230 section 3.2.
        foreach ($headers as $name => $value) {
            if (!strcasecmp($name, 'Authorization')) {
                return $value;
            }
        }
        return '';
    }
}
