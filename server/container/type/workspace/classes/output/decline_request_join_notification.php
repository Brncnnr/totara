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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\output;

use container_workspace\member\member_request;
use core\output\template;

final class decline_request_join_notification extends template {
    /**
     * @param member_request $request
     * @return decline_request_join_notification
     */
    public static function create(member_request $request): decline_request_join_notification {
        $content = $request->get_decline_content();
        $workspace = $request->get_workspace();

        $a = [
            'content' => $content,
            'workspace_name' => $workspace->get_name()
        ];

        $data = [
            'message' => get_string('decline_request_message', 'container_workspace', $a)
        ];

        return new static($data);
    }

}