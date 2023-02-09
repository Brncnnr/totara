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

final class accept_notification extends template {
    /**
     * @param member_request $member_request
     * @return accept_notification
     */
    public static function create(member_request $member_request): accept_notification {
        if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
            throw new \coding_exception(
                "This template is only for templating an email which means to send to the user"
            );
        }

        $workspace = $member_request->get_workspace();
        $workspace_name = $workspace->get_name();

        $data = [
            'message' => get_string(
                'approved_request_message',
                'container_workspace',
                format_string($workspace_name)
            ),
            'workspace_url' => $workspace->get_workspace_url()->out(false)
        ];

        return new static($data);
    }
}