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
namespace container_workspace\output;

use container_workspace\member\member;
use core\output\template;

/**
 * Notification template for event when user was added to a workspace.
 * @deprecated since Totara 16.0
 */
final class added_to_workspace_notification extends template {
    /**
     * @deprecated since Totara 16.0
     * @param member $member
     * @return added_to_workspace_notification
     */
    public static function create(member $member): added_to_workspace_notification {
        debugging('The added_to_workspace_notification notification is deprecated, please check your code.', DEBUG_DEVELOPER);

        $workspace = $member->get_workspace();

        $data = [
            'message' => get_string('member_added_message', 'container_workspace', $workspace->get_name()),
            'workspace_url' => $workspace->get_view_url()
        ];

        return new static($data);
    }
}