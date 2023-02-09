<?php
/**
 * This file is part of Totara Core
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
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\exception;

use moodle_exception;

class learning_object_not_found extends moodle_exception {
    /**
     * learning_object_not_found constructor.
     * @param string $marketplace_component
     * @param null|mixed $debuginfo
     */
    public function __construct(string $marketplace_component, $debuginfo = null) {
        parent::__construct(
            'error:cannot_find_learning_object',
            'contentmarketplace',
            null,
            $marketplace_component,
            $debuginfo
        );
    }
}