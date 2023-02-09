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

/**
 * Class workspace_updated
 * @package container_workspace\event
 */
final class workspace_deleted extends base {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = 'course';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * @param workspace $workspace
     * @param int|null $actor_id
     *
     * @return workspace_deleted
     */
    public static function from_workspace(workspace $workspace, ?int $actor_id = null): workspace_deleted {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $owner_id = $workspace->get_user_id();
        $workspace_id = $workspace->get_id();

        /** @var workspace_deleted $event */
        $event = static::create([
            'courseid' => $workspace_id,
            'objectid' => $workspace_id,
            'userid' => $actor_id,
            'relateduserid' => $owner_id,
            'context' => $workspace->get_context()
        ]);

        return $event;
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('workspace_deleted', 'container_workspace');
    }
}