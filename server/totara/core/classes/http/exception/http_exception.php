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

namespace totara_core\http\exception;

use Exception;
use Throwable;

/**
 * A base exception class for the HTTP client.
 */
class http_exception extends Exception {
    /**
     * Constructor.
     *
     * @param string         $errorcode
     * @param string         $message
     * @param string         $debugmessage
     * @param Throwable|null $previous
     */
    public function __construct(string $errorcode, string $message, string $debugmessage = '', ?Throwable $previous = null) {
        global $CFG;
        $message = "{$errorcode}: {$message}";
        if (((defined('PHPUNIT_TEST') && PHPUNIT_TEST) || !empty($CFG->debugdeveloper)) && $debugmessage !== '') {
            $message = "{$message} ({$debugmessage})";
        }
        parent::__construct($message, 0, $previous);
    }
}