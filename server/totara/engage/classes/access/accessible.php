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
namespace totara_engage\access;

/**
 * An interface for the instance type that want to use the same access logical within totara_engage.
 */
interface accessible {
    /**
     * @return bool
     */
    public function is_public(): bool;

    /**
     * @return bool
     */
    public function is_private(): bool;

    /**
     * @return bool
     */
    public function is_restricted(): bool;

    /**
     * Returning the owner's id of the item that extending this interface.
     * @return int
     */
    public function get_userid(): int;

    /**
     * @return string
     */
    public static function get_resource_type(): string;

    /**
     * @return string
     */
    public function get_name(): string;

    /**
     * @return string
     */
    public function get_url(): string;
}