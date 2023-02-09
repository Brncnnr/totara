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

namespace totara_engage\bookmark;

use totara_engage\access\access_manager;
use totara_engage\entity\engage_bookmark;
use totara_engage\event\bookmark_added;
use totara_engage\event\bookmark_removed;
use totara_engage\interactor\interactor_factory;
use totara_engage\repository\bookmark_repository;

class bookmark {

    /** @var int */
    protected $userid;

    /** @var int */
    protected $itemid;

    /** @var string */
    protected $component;

    /**
     * bookmark constructor.
     * @param int $userid
     * @param int $itemid
     * @param string $component
     */
    public function __construct(int $userid, int $itemid, string $component) {
        $this->userid = $userid;
        $this->itemid = $itemid;
        $this->component = $component;
    }

    /**
     * @param int|null $user_id
     * @return bool
     */
    public function can_bookmark(?int $user_id = null): bool {
        global $USER;
        if (empty($user_id)) {
            $user_id = $USER->id;
        }

        $resource = provider::create($this->component)->get_item_instance($this->itemid);

        // Check if interactor can bookmark this resource.
        $interactor = interactor_factory::create_from_accessible($resource, $user_id);
        if (!$interactor->can_bookmark()) {
            return false;
        }

        return access_manager::can_access($resource, $user_id);
    }
    /**
     * Add bookmark for the item.
     */
    public function add_bookmark(): void {
        // Check if item is already bookmarked by user. Do not throw exception as this
        // is not a blocker for the process to continue.
        $bookmarked = $this->is_bookmarked();
        if ($bookmarked) {
            debugging('Item is already bookmarked by user', DEBUG_DEVELOPER);
            return;
        }

        $bookmark = new engage_bookmark();
        $bookmark->itemid = $this->itemid;
        $bookmark->component = $this->component;
        $bookmark->userid = $this->userid;
        $bookmark->save();

        // Create bookmark event.
        bookmark_added::from_bookmark($this);
    }

    /**
     * Remove bookmark for item.
     */
    public function remove_bookmark(): void {
        /** @var bookmark_repository $repo */
        $repo = engage_bookmark::repository();
        $repo->delete_bookmark($this->userid, $this->itemid, $this->component);

        // Create bookmark event.
        bookmark_removed::from_bookmark($this);
    }

    /**
     * Confirm if a user has already bookmarked an item.
     *
     * @return bool
     */
    public function is_bookmarked(): bool {
        /** @var bookmark_repository $repo */
        $repo = engage_bookmark::repository();

        return $repo->is_bookmarked($this->userid, $this->itemid, $this->component);
    }

    public function get_itemid(): int {
        return $this->itemid;
    }

    public function get_userid(): int {
        return $this->userid;
    }

    public function get_component(): string {
        return $this->component;
    }
}