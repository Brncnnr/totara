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
use container_workspace\totara_engage\share\recipient\library;
use core\event\base;
use totara_core\identifier\component_area;
use totara_core\identifier\instance_identifier;
use totara_engage\event\clear_bookmark;
use container_workspace\workspace;

/**
 * An event to trigger when a user member is removed from the workspace.
 */
class removed_member extends base implements clear_bookmark {
    /**
     * @param member $member
     * @param int    $actor_id
     *
     * @return removed_member
     */
    public static function from_member(member $member, int $actor_id): removed_member {
        $workspace = $member->get_workspace();
        $context = $workspace->get_context();

        $data = [
            'courseid' => $workspace->get_id(),
            'objectid' => $member->get_id(),
            'context' => $context,
            'userid' => $actor_id,
            'relateduserid' => $member->get_user_id(),
            'other' => [
                'workspace_is_not_public' => !$workspace->is_public(),
                'share' => [
                    'context_id' => $context->id,
                    'instance_id' => $workspace->get_id()
                ]
            ]
        ];

        /** @var removed_member $event */
        $event = static::create($data);
        return $event;
    }

    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'user_enrolments';
    }

    /**
     * @return instance_identifier
     */
    public function get_instance_identifier(): instance_identifier {
        $share_data = $this->other['share'];
        return new instance_identifier(
            new component_area(workspace::get_type(), library::AREA),
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
     * @return array
     */
    public function get_target_user_ids(): array {
        return [$this->relateduserid];
    }

    /**
     * @return string
     */
    public static function get_name() {
        return get_string('event_member_removed', 'container_workspace');
    }
}