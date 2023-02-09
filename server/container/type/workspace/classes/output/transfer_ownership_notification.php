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

use container_workspace\workspace;
use core\output\template;

/**
 * Notification templating for new owner of the workspace.
 */
final class transfer_ownership_notification extends template {
    /**
     * @param workspace $workspace
     * @param int       $actor_id   The user who made the transfer ownership transaction.
     *
     * @return transfer_ownership_notification
     */
    public static function create(workspace $workspace, int $actor_id): transfer_ownership_notification {
        if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
            throw new \coding_exception("This notification template is for cron only");
        }

        $actor = \core_user::get_user($actor_id);
        $a = [
            'author' => fullname($actor),
            'workspace' => $workspace->get_name(),
        ];

        $data = [
            'message' => get_string('transfer_ownership_message','container_workspace', $a),
            'url' => $workspace->get_view_url()->out(),
        ];

        return new static($data);
    }
}