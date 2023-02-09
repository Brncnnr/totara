<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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

use container_workspace\member\member_request;
use core\output\template;

/**
 * Using for creating a notification message to notify the workspace's owner
 * that there is a new join request to user owner's workspace.
 */
final class join_request_notification extends template {
    /**
     * @param member_request $request
     * @return join_request_notification
     */
    public static function create(member_request $request): join_request_notification {
        $requester = $request->get_user();
        $workspace = $request->get_workspace();

        $a = [
            'user' => fullname($requester),
            'workspace_name' => $workspace->get_name()
        ];

        $data = [
            'workspace_url' => $workspace->get_workspace_url('members')->out(false),
            'message' => get_string('member_request_message', 'container_workspace', $a)
        ];

        return new static($data);
    }
}