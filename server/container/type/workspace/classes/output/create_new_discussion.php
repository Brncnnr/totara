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

use container_workspace\discussion\discussion;
use container_workspace\discussion\discussion as model_discussion;
use container_workspace\workspace;
use core\output\template;
use coding_exception;
use core_text;

/**
 * This is a template for notification on creating new discussion in the workspace.
 */
final class create_new_discussion extends template {
    /**
     * @param discussion $discussion
     * @param string $workspace_name
     * @return create_new_discussion
     */
    public static function create(discussion $discussion, string $workspace_name): create_new_discussion {
        global $CFG;

        if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
            throw new coding_exception("Cannot instantiate the template for web page usage");
        }

        require_once($CFG->dirroot . '/lib/filelib.php');

        $text = \file_rewrite_pluginfile_urls(
            $discussion->get_content_text(),
            'pluginfile.php',
            $discussion->get_context()->id,
            workspace::get_type(),
            model_discussion::AREA,
            $discussion->get_id()
        );

        if (core_text::strlen($text) > 75) {
            $array = explode("\n", $text);
            $len = 0;
            $text = '';
            foreach ($array as $ele) {
                if (core_text::strlen($ele) == 0) {
                    continue;
                }
                $len += core_text::strlen($ele);
                $text .= markdown_to_html($ele);
                if ($len > 75) {
                    $text .= '...';
                    break;
                }
            }
        } else {
            $text = markdown_to_html($text);
        }

        $a = [
            'author' => fullname($discussion->get_user()),
            'workspace' => $workspace_name,
            'discussion' => $text
        ];

        $data = [
            'discussion_url' => $discussion->get_url()->out(),
            'message' => get_string('create_new_discussion_message', 'container_workspace', $a),
        ];

        return new static($data);
    }
}