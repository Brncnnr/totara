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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\event;

use container_workspace\workspace;
use core\event\base;
use core_ml\event\interaction_event;
use core_ml\event\public_access_aware_event;

/**
 * Class workspace_viewed
 *
 * @package container_workspace\event
 */
final class workspace_viewed extends base implements interaction_event, public_access_aware_event {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = 'course';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * @param workspace $workspace
     * @param int|null $actor_id
     *
     * @return workspace_viewed
     */
    public static function from_workspace(workspace $workspace, ?int $actor_id = null): workspace_viewed {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $owner_id = $workspace->get_user_id();
        $workspace_id = $workspace->get_id();

        /** @var workspace_viewed $event */
        $event = static::create([
            'courseid' => $workspace_id,
            'objectid' => $workspace_id,
            'userid' => $actor_id,
            'relateduserid' => $owner_id,
            'context' => $workspace->get_context(),
            'other' => ['is_public' => $workspace->is_public()]
        ]);

        return $event;
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('workspace_viewed', 'container_workspace');
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return workspace::get_type();
    }

    /**
     * @return string|null
     */
    public function get_area(): ?string {
        return null;
    }

    /**
     * @return string
     */
    public function get_interaction_type(): string {
        return 'view';
    }

    /**
     * @return int
     */
    public function get_rating(): int {
        return 1;
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->userid;
    }

    /**
     * @return int
     */
    public function get_item_id(): int {
        return $this->objectid;
    }

    /**
     * @return bool
     */
    public function is_public(): bool {
        return $this->other['is_public'];
    }
}