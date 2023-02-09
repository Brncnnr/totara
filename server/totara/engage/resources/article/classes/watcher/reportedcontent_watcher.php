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
 * @package engage_article
 */

namespace engage_article\watcher;

use engage_article\totara_engage\resource\article;
use totara_comment\comment;
use totara_reportedcontent\hook\get_review_context;
use totara_reportedcontent\hook\remove_review_content;

/**
 * Get the content & context of a resource/article comment
 *
 * @package engage_article\watcher
 */
final class reportedcontent_watcher {
    /**
     * Articles can have either comments or the whole thing reported
     *
     * @param get_review_context $hook
     * @return void
     */
    public static function get_content(get_review_context $hook): void {
        // Valid for articles only, for body & hook
        $area = $hook->area;
        $valid_areas = ['', 'comment', 'reply'];
        if ('engage_article' !== $hook->component || !in_array($area, $valid_areas)) {
            return;
        }

        if ($hook->area === '') {
            // It's the article itself
            $article = article::from_resource_id($hook->item_id);

            $content = $article->get_name(FORMAT_PLAIN);
            $format = FORMAT_PLAIN;
            $time_created = $article->get_timecreated();
            $user_id = $article->get_userid();
        } else {
            // It's a comment on an article
            $comment = comment::from_id($hook->item_id);
            $instance_id = $comment->get_instanceid();

            $content = $comment->get_content();
            $format = $comment->get_format();
            $time_created = $comment->get_timecreated();
            $user_id = $comment->get_userid();

            $article = article::from_resource_id($instance_id);
        }

        $hook->context_id = $article->get_context()->id;
        $hook->content = $content;
        $hook->format = $format;
        $hook->time_created = $time_created;
        $hook->user_id = $user_id;

        $hook->success = true;
    }

    /**
     * @param remove_review_content $hook
     * @return void
     */
    public static function delete_article(remove_review_content $hook): void {
        global $DB;

        // Only valid for articles, comments are handled by the comment plugin
        if ('engage_article' !== $hook->review->get_component() || '' !== $hook->review->get_area()) {
            return;
        }

        // It's possible this resource may have been removed already, so if it has we're going to
        // just accept it.
        if (!$DB->record_exists('engage_resource', ['id' => $hook->review->get_item_id()])) {
            $hook->success = true;
            return;
        }

        $article = article::from_resource_id($hook->review->get_item_id());
        $article->delete();

        $hook->success = true;
    }
}