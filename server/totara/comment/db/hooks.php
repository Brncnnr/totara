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

defined('MOODLE_INTERNAL') || die();

$watchers = [
    [
        // Soft-delete the comment when the review has been marked as "removed"
        'hookname' => '\totara_reportedcontent\hook\remove_review_content',
        'callback' => '\totara_comment\watcher\reportedcontent_watcher::delete_comment',
        'priority' => 100,
    ],
    [
        'hookname' => '\editor_weka\hook\find_context',
        'callback' => ['\totara_comment\watcher\editor_weka_watcher', 'load_context']
    ],
    [
        // Totara Comments gives users the ability to view some users profile fields
        'hookname' => \core_user\hook\allow_view_profile_field::class,
        'callback' => [\totara_comment\watcher\core_user::class, 'handle_allow_view_profile_field'],
    ],
    [
        'hookname' => '\core\hook\phpunit_reset',
        'callback' =>  [\totara_comment\watcher\phpunit_reset_watcher::class, 'reset_data']
    ]
];