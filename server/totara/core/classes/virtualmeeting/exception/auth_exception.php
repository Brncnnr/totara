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

namespace totara_core\virtualmeeting\exception;

use Throwable;

/**
 * authorisation/authentication error exception
 */
class auth_exception extends base_exception {
    /**
     * An invalid token exception
     *
     * @return self
     */
    public static function invalid_token(): self {
        return new self('invalid token');
    }

    /**
     * An expired token exception
     *
     * @return self
     */
    public static function expired_token(): self {
        return new self('expired token');
    }

    /**
     * An invalid request exception
     *
     * @return self
     */
    public static function invalid_request(): self {
        return new self('invalid request');
    }

    /**
     * An invalid response exception
     *
     * @param Throwable $previous
     * @return self
     */
    public static function invalid_response(Throwable $previous = null): self {
        return new self($previous ? $previous->getMessage() : 'invalid response', 0, $previous);
    }
}
