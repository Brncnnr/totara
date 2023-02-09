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

$watchers = [
    [
        'hookname' => '\totara_reportedcontent\hook\get_review_context',
        'callback' => ['\engage_article\watcher\reportedcontent_watcher', 'get_content']
    ],
    [
        'hookname' => '\totara_reportedcontent\hook\remove_review_content',
        'callback' => ['\engage_article\watcher\reportedcontent_watcher', 'delete_article']
    ],
    [
        'hookname' => '\editor_weka\hook\find_context',
        'callback' => ['\engage_article\watcher\editor_weka_watcher', 'load_context']
    ],
    [
        'hookname' => '\totara_topic\hook\get_deleted_topic_usages',
        'callback' => ['\engage_article\watcher\totara_topic_watcher', 'on_deleted_topic_get_usage']
    ],
    [
        'hookname' => '\editor_weka\hook\search_users_by_pattern',
        'callback' => ['\engage_article\watcher\editor_weka_watcher', 'on_search_users']
    ]
];