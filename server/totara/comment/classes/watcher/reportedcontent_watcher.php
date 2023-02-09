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
 * @package totara_comment
 */

namespace totara_comment\watcher;

use totara_comment\comment;
use totara_comment\comment_helper;
use totara_reportedcontent\hook\remove_review_content;

/**
 * Watch for any comments reported via inappropriate content report
 * and remove them if requested.
 *
 * @package totara_comment\watcher
 */
final class reportedcontent_watcher {
    /**
     * @param remove_review_content $hook
     * @return void
     */
    public static function delete_comment(remove_review_content $hook): void {
        global $DB;

        // These components all use comments in the same way, so are removed here
        $valid_components = [
            'engage_article',
            'totara_playlist',
            'container_workspace',
            'test_component',
        ];
        $valid_areas = [comment::COMMENT_AREA, comment::REPLY_AREA];

        $component = $hook->review->get_component();
        $area = $hook->review->get_area();

        if (!in_array($component, $valid_components) || !in_array($area, $valid_areas)) {
            return;
        }

        // It's possible this comment may have been removed already, so if it has we're going to
        // just accept it.
        if (!$DB->record_exists('totara_comment', ['id' => $hook->review->get_item_id()])) {
            $hook->success = true;
            return;
        }

        $comment = comment::from_id($hook->review->get_item_id());
        if ($comment->is_reply() || 'container_workspace' === $component) {
            // All replies & workspace comments themselves are just deleted
            comment_helper::delete($comment);
        } else {
            // Otherwise comments are deleted, but their child replies are removed
            comment_helper::delete_replies_of_comment($comment);

            // Now soft-delete the comment but remove the content itself
            comment_helper::soft_delete($comment->get_id(), null, comment::REASON_DELETED_REPORTED);
        }
        $hook->success = true;
    }
}