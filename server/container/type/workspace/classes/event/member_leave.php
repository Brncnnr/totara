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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\event;

use container_workspace\member\member;
use core\event\base;
use totara_core\identifier\component_area;
use totara_core\identifier\instance_identifier;
use totara_engage\event\clear_bookmark;
use container_workspace\workspace;
use container_workspace\totara_engage\share\recipient\library;

/**
 * Event to trigger when user leave the workspace.
 */
class member_leave extends base implements clear_bookmark {
    /**
     * For leaving the workspace, the user member should be the actor who is
     * leaving the workspace.
     *
     * @param member $member
     * @return member_leave
     */
    public static function from_member(member $member): member_leave {
        $user_id = $member->get_user_id();
        $workspace = $member->get_workspace();

        $context = $workspace->get_context();

        $data = [
            'courseid' => $workspace->get_id(),
            'userid' => $user_id,
            'relateduserid' => $user_id,
            'objectid' => $member->get_id(),
            'context' => $context,
            'other' => [
                'workspace_is_not_public' => !$workspace->is_public(),
                'share' => [
                    'component' => workspace::get_type(),
                    'area' => library::AREA,
                    'context_id' => $context->id,
                    'instance_id' => $workspace->get_id()
                ]
            ]
        ];

        /** @var member_leave $event */
        $event = static::create($data);
        return $event;
    }

    /**
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['objecttable'] = 'user_enrolments';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * @return instance_identifier
     */
    public function get_instance_identifier(): instance_identifier {
        $share_data = $this->other['share'];

        return new instance_identifier(
            new component_area($share_data['component'], $share_data['area']),
            $share_data['instance_id'],
            $share_data['context_id']
        );
    }

    /**
     * @return bool
     */
    public function is_to_clear(): bool {
        return $this->other['workspace_is_not_public'] ?? false;
    }

    /**
     * @return int[]
     */
    public function get_target_user_ids(): array {
        return [$this->relateduserid];
    }

    /**
     * @return string
     */
    public static function get_name() {
        return get_string('event_member_left', 'container_workspace');
    }
}