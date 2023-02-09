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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\interactor;

use coding_exception;
use totara_engage\access\access;
use totara_engage\access\accessible;
use totara_engage\share\provider as share_provider;

abstract class interactor {

    /** @var array */
    protected $resource_data;

    /** @var int */
    protected $actor_id;

    /**
     * interactor constructor.
     *
     * @param array $resource_data
     * @param int|null $actor_id
     */
    public function __construct(array $resource_data, ?int $actor_id = null) {
        global $USER;

        // Its required to pass in the resource access.
        if (!isset($resource_data['access'])) {
            throw new coding_exception('Resource access is required');
        }

        // Its required to pass in the user ID.
        if (empty($resource_data['userid'])) {
            throw new coding_exception('ID of user who owns the resource is required');
        }

        $this->resource = $resource_data;
        $this->actor_id = empty($actor_id) ? $USER->id : $actor_id;
    }

    /**
     * @return array
     */
    public function get_resource(): array {
        return $this->resource;
    }

    /**
     * @return int
     */
    public function get_actor_id(): int {
        return $this->actor_id;
    }

    /**
     * Check if the logged in user can comment on the resource.
     *
     * @return bool
     */
    public function can_comment(): bool {
        // Do not allow guest users to comment.
        return !isguestuser($this->actor_id);
    }

    /**
     * Check if the logged in user can react on the resource.
     *
     * @return bool
     */
    public function can_react(): bool {
        // Do not allow guest users to react.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        // Private resources can not be liked.
        if (access::is_private($this->resource['access'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if the logged in user can re-share the resource.
     *
     * @return bool
     */
    public function can_share(): bool {
        // Do not allow guest users to share.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        // Only public resources are shareable.
        if (!access::is_public($this->resource['access'])) {
            return false;
        }

        if (isset($this->resource['resourcetype']) && isset($this->resource['id'])) {
            $provider = share_provider::create($this->resource['resourcetype']);
            $instance = $provider->get_item_instance($this->resource['id']);
            return $instance->can_share($this->actor_id);
        }

        return true;
    }

    /**
     * Check if the logged in user can bookmark the resource.
     *
     * @return bool
     */
    public function can_bookmark(): bool {
        // Do not allow guest users to bookmark the resource.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        // Private resources cannot be bookmarked.
        if (access::is_private($this->resource['access'])) {
            return false;
        }

        // Owners cannot bookmark their own resources.
        if ($this->actor_id == $this->resource['userid']) {
            return false;
        }

        return true;
    }

    /**
     * Check the share button can display or not on sidepanel.
     * @return bool
     */
    public function show_share_button(): bool {
        if (isguestuser($this->actor_id)) {
            return false;
        }

        if (isset($this->resource['resourcetype']) && isset($this->resource['id'])) {
            $provider = share_provider::create($this->resource['resourcetype']);
            $instance = $provider->get_item_instance($this->resource['id']);
            if ($instance->can_share($this->actor_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'user_id' => $this->get_actor_id(),
            'can_bookmark' => $this->can_bookmark(),
            'can_comment' => $this->can_comment(),
            'can_react' => $this->can_react(),
            'can_share' => $this->can_share(),
            'show_share_button' => $this->show_share_button()
        ];
    }

    /**
     * Get interactor for accessible resource.
     *
     * @param accessible $resource
     * @param int|null $actor_id
     * @return interactor
     */
    abstract public static function create_from_accessible(accessible $resource, ?int $actor_id = null): interactor;

}