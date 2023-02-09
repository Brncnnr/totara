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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\watcher;

use container_workspace\discussion\discussion;
use container_workspace\discussion\discussion_helper;
use totara_comment\comment;
use totara_reportedcontent\hook\get_review_context;
use totara_reportedcontent\hook\remove_review_content;

/**
 * Get the content & context of a workspace discussion
 *
 * @package container_workspace\watcher
 */
final class reportedcontent_watcher {
    /**
     * Workspaces have discussions, comments & replies
     *
     * @param get_review_context $hook
     * @return void
     */
    public static function get_content(get_review_context $hook): void {
        // Valid for discussions only, for body & hook
        $area = $hook->area;
        if ('container_workspace' !== $hook->component || !in_array($area, ['discussion', 'comment', 'reply'])) {
            return;
        }

        // It's a discussion
        if ($area === 'discussion') {
            $discussion = discussion::from_id($hook->item_id);
            $workspace = $discussion->get_workspace();

            $hook->context_id = $workspace->get_context()->id;
            $hook->content = $discussion->get_content();
            $hook->format = $discussion->get_content_format();
            $hook->time_created = $discussion->get_time_created();
            $hook->user_id = $discussion->get_user_id();
        } else {
            // Nope, it's a comment or reply
            $comment = comment::from_id($hook->item_id);
            $discussion = discussion::from_id($comment->get_instanceid());
            $workspace = $discussion->get_workspace();

            $hook->context_id = $workspace->get_context()->id;
            $hook->content = $comment->get_content();
            $hook->format = $comment->get_format();
            $hook->time_created = $comment->get_timecreated();
            $hook->user_id = $comment->get_userid();
        }

        $hook->success = true;
    }

    /**
     * @param remove_review_content $hook
     * @return void
     */
    public static function delete_discussion(remove_review_content $hook): void {
        global $USER;
        // Valid for discussions only, for body & hook
        $area = $hook->review->get_area();
        if ('container_workspace' !== $hook->review->get_component() || 'discussion' !== $area) {
            return;
        }
        $discussion = discussion::from_id($hook->review->get_item_id());

        // Delete it with the helper. We're going for a softish-delete here to keep the master record.
        discussion_helper::soft_delete_discussion($discussion, $USER->id, discussion::REASON_DELETED_REPORTED);
        $hook->success = true;
    }
}