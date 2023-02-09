<?php
/*
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_playlist
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

$observers = [
    [
        'eventname' => \totara_playlist\event\playlist_created::class,
        'callback'  => 'totara_playlist\totara_catalog\playlist::object_update_observer',
    ],
    [
        'eventname' => \totara_playlist\event\playlist_updated::class,
        'callback'  => 'totara_playlist\totara_catalog\playlist::object_update_observer',
    ],
    [
        'eventname' => \totara_playlist\event\playlist_deleted::class,
        'callback'  => 'totara_playlist\totara_catalog\playlist::object_update_observer',
    ],
    [
        'eventname' => \totara_playlist\event\playlist_created::class,
        'callback'  => [\totara_playlist\observer\playlist_observer::class, 'on_created'],
    ],
    [
        'eventname' => \totara_playlist\event\playlist_updated::class,
        'callback'  => [\totara_playlist\observer\playlist_observer::class, 'on_updated'],
    ],
    [
        'eventname' => '\totara_playlist\event\tag_added',
        'callback'  => 'totara_playlist\totara_catalog\playlist::object_update_observer',
    ],
    [
        'eventname' => '\totara_playlist\event\tag_removed',
        'callback'  => 'totara_playlist\totara_catalog\playlist::object_update_observer',
    ],
    [
        'eventname' => '\totara_playlist\event\tag_updated',
        'callback'  => 'totara_playlist\totara_catalog\playlist::object_update_observer',
    ],
    [
        'eventname' => '\engage_article\event\article_deleted',
        'callback'  => 'totara_playlist\observer\playlist_observer::resource_article_deleted',
    ],
    [
        'eventname' => '\engage_survey\event\survey_deleted',
        'callback'  => 'totara_playlist\observer\playlist_observer::resource_survey_deleted',
    ],
    [
        'eventname' => '\engage_article\event\article_updated',
        'callback' => 'totara_playlist\observer\image_observer::article_updated',
    ],
    [
        'eventname' => '\engage_article\event\article_deleted',
        'callback' => 'totara_playlist\observer\image_observer::article_deleted',
    ],
    [
        'eventname' => '\totara_comment\event\comment_created',
        'callback' => ['totara_playlist\observer\comment_observer', 'on_comment_created']
    ],
    [
        'eventname' => '\totara_comment\event\reply_created',
        'callback' => ['totara_playlist\observer\comment_observer', 'on_reply_created']
    ],
    [
        'eventname' => '\totara_comment\event\comment_updated',
        'callback' => ['totara_playlist\observer\comment_observer', 'on_comment_updated']
    ],
    [
        'eventname' => '\core\event\user_deleted',
        'callback' => ['\totara_playlist\totara_catalog\playlist', 'object_update_observer']
    ],
];