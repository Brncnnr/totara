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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\exception;

use moodle_exception;
use stdClass;

class cannot_obtain_token extends moodle_exception {
    /**
     * cannot_obtain_token constructor.
     * @param string              $error_code
     * @param string              $module
     * @param string              $link
     * @param null|stdClass|mixed $a
     * @param null|mixed          $debug_info
     */
    final protected function __construct(
        string $error_code,
        string $module = 'totara_contentmarketplace',
        $link = '',
        $a = null,
        $debug_info = null
    ) {
        parent::__construct($error_code, $module, $link, $a, $debug_info);
    }

    /**
     * @return cannot_obtain_token
     */
    public static function on_no_provided_key(string $key): cannot_obtain_token {
        return new static(
            'error:cannot_obtain_token_by_missing_field',
            'totara_contentmarketplace',
            '',
            $key
        );
    }

    /**
     * @param string $key
     * @return cannot_obtain_token
     */
    public static function on_invalid_value(string $key): cannot_obtain_token {
        return new static(
            'error:cannot_obtain_token_by_invalid_value',
            'totara_contentmarketplace',
            '',
            $key
        );
    }
}