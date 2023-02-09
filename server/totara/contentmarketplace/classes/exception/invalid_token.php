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

class invalid_token extends moodle_exception {
    /**
     * invalid_token constructor.
     * @param string              $error_code
     * @param string              $module
     * @param string|null         $link
     * @param null|stdClass|array $a
     * @param string|array|null   $debug_info
     */
    public function __construct(
        string $error_code = 'error:invalid_token',
        string $module = 'totara_contentmarketplace',
        ?string $link = '',
        $a = null, $debug_info = null
    ) {
        parent::__construct($error_code, $module, $link, $a, $debug_info);
    }
}