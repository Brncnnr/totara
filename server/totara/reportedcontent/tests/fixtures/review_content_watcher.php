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
 * @package totara_reportedcontent
 */

use totara_reportedcontent\hook\get_review_context;
use totara_reportedcontent\hook\remove_review_content;

/**
 * Simple watcher to return some fake content
 */
final class review_content_watcher {
    /**
     * Return the requested fake comment
     *
     * @param get_review_context $hook
     * @return void
     */
    public static function get_content(get_review_context $hook): void {
        // Valid only for unit tests
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new coding_exception("Cannot run the code outside of phpunit environment");
        }

        if ($hook->component !== 'test_component') {
            return;
        }

        // It should be a comment (we're not testing the comment, rather the hook reacts appropriately)
        $comment = \totara_comment\comment::from_id($hook->item_id);

        $hook->context_id = CONTEXT_SYSTEM; // Fake
        $hook->content = $comment->get_content();
        $hook->format = $comment->get_format();
        $hook->time_created = $comment->get_timecreated();
        $hook->user_id = $comment->get_userid();

        $hook->success = true;
    }
}