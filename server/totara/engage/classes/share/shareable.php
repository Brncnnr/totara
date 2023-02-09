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
* @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
* @package totara_engage
*/
namespace totara_engage\share;

interface shareable {

    /**
     * @return \context
     */
    public function get_context(): \context;

    /**
     * Validates if the user has capability to share this resource.
     *
     * @param int $userid
     * @return bool
     */
    public function can_share(int $userid): bool;

    /**
     * Validate if the sharable item is in a sharable state.
     *
     * @return shareable_result
     */
    public function get_shareable(): shareable_result;

    /**
     * Post share functionality.
     *
     * @param share $share
     */
    public function shared(share $share): void;

    /**
     * Indicate if the user is able to reshare this resource.
     *
     * @param int $userid
     * @return bool
     */
    public function can_reshare(int $userid): bool;

    /**
     * Trigger a reshare event
     *
     * @param int $userid
     */
    public function reshare(int $userid): void;

    /**
     * Get the shareable instance ID.
     *
     * @return int
     */
    public function get_id(): int;

    /**
     * Get the component of the shareable instance.
     *
     * @return string
     */
    public static function get_resource_type(): string;

    /**
     * Validates if the user has capability to unshare this resource.
     *
     * @param int $sharer_id
     * @param bool|null $is_container
     * @return bool
     */
    public function can_unshare(int $sharer_id, ?bool $is_container = false): bool;

    /**
     * Returns the owning user id.
     * @return int
     */
    public function get_userid(): int;
}