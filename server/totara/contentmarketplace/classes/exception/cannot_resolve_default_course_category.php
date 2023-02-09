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
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\exception;

use coding_exception;

/**
 * An exception that helps to identify that the system is not able to resolve
 * the default category id for certain user.
 */
class cannot_resolve_default_course_category extends coding_exception {
    /**
     * cannot_resolve_default_course_category constructor.
     * @param int  $user_id
     * @param null $debuginfo
     */
    public function __construct(int $user_id, $debuginfo = null) {
        parent::__construct(
            "Cannot resolve the default course category for user with id '{$user_id}'",
            $debuginfo
        );
    }
}