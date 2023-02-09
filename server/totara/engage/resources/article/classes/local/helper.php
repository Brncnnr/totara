<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package engage_article
 */
namespace engage_article\local;

use engage_article\totara_engage\resource\article;
use totara_comment\comment_helper;
use totara_engage\share\manager;
use totara_reaction\reaction_helper;

final class helper {
    /**
     * helper constructor.
     */
    private function __construct() {
        // Prevent the construction directly.
    }

    /**
     * @param \context $context
     * @return array
     */
    public static function get_editor_options(\context $context): array {
        global $CFG;

        $options = [
            'subdirs' => 1,
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => -1,
            'changeformat' => 1,
            'context' => $context,
            'overflowdiv' => 1
        ];

        if (get_config('engageresource_article', 'allowxss')) {
            $options['allowxss'] = 1;
        }

        return $options;
    }

    /**
     * As the method is for purging, we do not need capability check.
     *
     * @param article $article
     */
    public static function purge_article(article $article): void {
        global $DB;

        // Delete resource.
        $DB->delete_records('engage_resource', ['id' => $article->get_id()]);

        // Delete shares.
        manager::delete($article->get_id(), article::get_resource_type());

        // Deleting comments.
        comment_helper::purge_area_comments(
            article::get_resource_type(),
            'comment',
            $article->get_id()
        );

        // Deleting reaction from the article.
        reaction_helper::purge_area_reactions(
            article::get_resource_type(),
            'media',
            $article->get_id()
        );

        // Delete files.
        self::delete_files($article);

        // Delete the attached image file.
        $processor = image_processor::make($article->get_id(), $article->get_context_id());
        $processor->delete_existing_image();

        // Delete itself.
        $DB->delete_records('engage_article', ['id' => $article->get_instanceid()]);
    }

    /**
     * @param article $article
     * @return bool
     */
    public static function delete_files(article $article): bool {
        $fs = get_file_storage();

        return $fs->delete_area_files(
            $article->get_context_id(),
            article::get_resource_type(),
            false,
            $article->get_id()
        );
    }
}