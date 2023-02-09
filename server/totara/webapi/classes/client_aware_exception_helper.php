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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi;

use require_login_exception;
use require_login_session_timeout_exception;
use Throwable;

/**
 * Class client_aware_exception_helper
 *
 * @package totara_webapi
 */
class client_aware_exception_helper {

    /**
     * List of registered client aware exceptions.
     *
     * @var array[]
     */
    private static $registered_exceptions = [
        require_login_exception::class => [
            'category' => 'require_login',
        ],
        require_login_session_timeout_exception::class => [
            'category' => 'require_login',
        ],
    ];

    /**
     * Gets the exception data registered if available.
     *
     * @param Throwable $exception_class
     * @return array|null
     */
    public static function get_exception_data(Throwable $exception_class): ?array {
        return self::$registered_exceptions[get_class($exception_class)] ?? null;
    }

    /**
     * Checks if the exception is registered.
     *
     * @param Throwable $exception_class
     * @return bool
     */
    public static function exception_registered(Throwable $exception_class): bool {
        return in_array(get_class($exception_class), self::get(), true);
    }

    /**
     * Gets the list of registered exception classes.
     *
     * @return array
     */
    private static function get(): array {
        return array_keys(self::$registered_exceptions);
    }

    /**
     * Parses acceptable exception in as client_aware.
     *
     * @param Throwable $exception
     * @return client_aware_exception
     */
    public static function create(Throwable $exception): client_aware_exception {
        $exception_data = self::get_exception_data($exception);

        return empty($exception_data)
            ? new client_aware_exception($exception)
            : new client_aware_exception($exception, $exception_data);
    }
}
