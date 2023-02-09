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
 * @package engage_article
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

$observers = [
    [
        'eventname' => \engage_article\event\article_created::class,
        'callback'  => 'engage_article\totara_catalog\article::object_update_observer',
    ],
    [
        'eventname' => \engage_article\event\article_updated::class,
        'callback'  => 'engage_article\totara_catalog\article::object_update_observer',
    ],
    [
        'eventname' => \engage_article\event\article_deleted::class,
        'callback'  => 'engage_article\totara_catalog\article::object_update_observer',
    ],
    [
        'eventname' => '\engage_article\event\tag_added',
        'callback'  => 'engage_article\totara_catalog\article::object_update_observer',
    ],
    [
        'eventname' => '\engage_article\event\tag_removed',
        'callback'  => 'engage_article\totara_catalog\article::object_update_observer',
    ],
    [
        'eventname' => '\engage_article\event\tag_updated',
        'callback'  => 'engage_article\totara_catalog\article::object_update_observer',
    ],
    [
        'eventname' => \engage_article\event\article_created::class,
        'callback'  => [\engage_article\observer\article_observer::class, 'on_created']
    ],
    [
        'eventname' => \engage_article\event\article_updated::class,
        'callback'  => [\engage_article\observer\article_observer::class, 'on_updated']
    ],
    [
        'eventname' => \totara_comment\event\comment_created::class,
        'callback' => [\engage_article\observer\comment_observer::class, 'on_comment_created']
    ],
    [
        'eventname' => \totara_comment\event\reply_created::class,
        'callback' => [\engage_article\observer\comment_observer::class, 'on_reply_created']
    ],
    [
        'eventname' => \totara_comment\event\comment_updated::class,
        'callback' => [\engage_article\observer\comment_observer::class, 'on_comment_updated']
    ],
    [
        'eventname' => '\totara_reaction\event\reaction_created',
        'callback' => ['engage_article\observer\reaction_observer', 'on_reaction_created']
    ],
    [
        'eventname' => '\engage_article\event\article_viewed',
        'callback' => ['engage_article\observer\article_observer', 'on_view_created']
    ],
    [
        'eventname' => \core\event\user_deleted::class,
        'callback' => [\engage_article\totara_catalog\article::class, 'object_update_observer']
    ],
];
