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

use container_workspace\discussion\discussion;
use core\output\template;
use totara_comment\comment;

/**
 * This is a template for notification on adding new comments to a discussion within a workspace.
 */
final class comment_on_discussion extends template {
    /**
     * @param discussion    $discussion
     * @param comment       $comment
     *
     * @return comment_on_discussion
     */
    public static function create(discussion $discussion, comment $comment): comment_on_discussion {
        // Note that we are leaving spaces here for the future improvement on replying notifcation output.
        if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
            throw new \coding_exception("Cannot instantiate the template for web page usage");
        }

        $author = $comment->get_user();
        $workspace = $discussion->get_workspace();

        $a = [
            'author' => fullname($author),
            'workspace_name' => $workspace->get_name()
        ];

        $data = [
            'discussion_url' => $discussion->get_url()->out(),
            'message' => get_string('comment_on_discussion_message', 'container_workspace', $a),
        ];

        return new static($data);
    }
}