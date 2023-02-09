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
 * @package totara_engage
 */
namespace totara_engage\exception;

final class resource_exception extends \moodle_exception {
    /**
     * resource_exception constructor.
     *
     * @param string                    $errorcode
     * @param string                    $module
     * @param null|\stdClass|array      $a
     * @param null|array|\stdClass      $debuginfo
     */
    private function __construct(string $errorcode, string $module, $a = null, $debuginfo = null) {
        parent::__construct($errorcode, $module, '', $a, $debuginfo);
    }

    /**
     * @param string $code
     * @param string $resourcetype
     * @param null   $a
     * @param null   $debug
     *
     * @return resource_exception
     */
    public static function create(string $code, string $resourcetype, $a = null, $debug = null): resource_exception {
        $errorcode = $code;
        if (false === stripos($code, 'error:')) {
            // Concat the prefix "error:" if the error code does not have it.
            $errorcode = "error:{$code}";
        }

        $module = 'totara_engage';
        $manager = get_string_manager();

        if ($manager->string_exists($errorcode, $resourcetype)) {
            $module = $resourcetype;
        } else {
            if (null == $a) {
                // Only injecting the string parameter if the caller does not provide it.
                $a = new \stdClass();
                $a->type = $resourcetype;
            }
        }

        return new static($errorcode, $module, $a, $debug);
    }
}