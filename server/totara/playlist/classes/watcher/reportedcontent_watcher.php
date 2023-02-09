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
 * @package totara_playlist
 */

namespace totara_playlist\watcher;

use totara_comment\comment;
use totara_playlist\playlist;
use totara_reportedcontent\hook\get_review_context;

/**
 * Get the context & content for a playlist comment
 *
 * @package totara_playlist\watcher
 */
final class reportedcontent_watcher {
    /**
     * @param get_review_context $hook
     * @return void
     */
    public static function get_context(get_review_context $hook): void {
        $area = $hook->area;
        if ('totara_playlist' !== $hook->component || !in_array($area, ['comment', 'reply'])) {
            return;
        }

        $comment = comment::from_id($hook->item_id);
        $instance_id = $comment->get_instanceid();

        $hook->content = $comment->get_content();
        $hook->format = $comment->get_format();
        $hook->time_created = $comment->get_timecreated();
        $hook->user_id = $comment->get_userid();

        // Get the playlist for the context
        $playlist = playlist::from_id($instance_id);
        $hook->context_id = $playlist->get_context()->id;

        $hook->success = true;
    }
}