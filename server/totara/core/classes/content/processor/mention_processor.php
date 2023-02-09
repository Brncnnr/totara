<?php
/**
 * This file is part of Totara LMS
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
 * @package totara_core
 */
namespace totara_core\content\processor;

use core\json_editor\document;
use core\json_editor\node\mention;
use totara_core\content\content;
use totara_core\content\processor;
use totara_core\task\user_mention_notify_task;
use core\task\manager as task_manager;

/**
 * Class mention_processor
 * @package totara_core\content\processor
 */
final class mention_processor extends processor {
    /**
     * @param int[] $userids
     * @param content $content
     * @return user_mention_notify_task
     */
    private function queue_task(array $userids, content $content): user_mention_notify_task {
        $task = new user_mention_notify_task();
        $task->set_userid($content->get_user_id());
        $task->set_component($content->get_component());

        $url = '';
        $contexturl = $content->get_contexturl();

        if (null !== $contexturl) {
            $url = $contexturl->out();
        }

        $task->set_custom_data(
            [
                'area' => $content->get_area(),
                'userids' => array_unique($userids),
                'contextid' => $content->get_contextid(),
                'instanceid' => $content->get_instanceid(),
                'title' => format_string($content->get_title()),
                'content' => format_text($content->get_content(), $content->get_contentformat()),
                'url' => $url
            ]
        );

        task_manager::queue_adhoc_task($task);
        return $task;
    }

    /**
     * Handling mention notification of the user.
     *
     * @param content $content
     * @return void
     */
    public function process_format_json_editor(content $content): void {
        $contentvalue = $content->get_content();
        if (empty($contentvalue)) {
            // Empty content. We will skip it.
            return;
        }

        $document = document::create($contentvalue);

        $nodes = $document->find_nodes(mention::get_type());
        if (empty($nodes)) {
            return;
        }

        // Array of users to notify.
        $userids = array_map(
            function (mention $node): int {
                return $node->get_userid();
            },

            $nodes
        );

        $this->queue_task($userids, $content);
    }

    /**
     * @param content $content
     * @return void
     */
    public function process_format_moodle(content $content): void {
        $this->process_format_text($content);
    }

    /**
     * @param content $content
     * @return void
     */
    public function process_format_html(content $content): void {
        $this->process_format_text($content);
    }

    /**
     * @param content $content
     * @return void
     */
    public function process_format_text(content $content): void {
        global $DB;

        $regex = '/(\@[\pL0-9]+)/i';
        $matches = [];

        preg_match($regex, $content->get_content(), $matches);
        $userids = [];

        foreach ($matches as $match) {
            $username = str_replace("@", "", $match);
            $userid = $DB->get_field('user', 'id', ['username' => $username]);

            if (!$userid) {
                debugging("Cannot get the user's id from username '{$username}'", DEBUG_DEVELOPER);
                continue;
            }

            $userids[] = $userid;
        }

        if (empty($userids)) {
            // Skip queing for the adhoc tasks if there are no user ids found.
            return;
        }

        $this->queue_task($userids, $content);
    }
}
