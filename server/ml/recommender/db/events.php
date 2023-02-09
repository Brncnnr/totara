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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */

use ml_recommender\observer\interaction_observer;

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\totara_playlist\event\playlist_viewed',
        'callback'  => [interaction_observer::class, 'watch_interaction'],
    ],
    [
        'eventname' => '\totara_playlist\event\playlist_deleted',
        'callback'  => [interaction_observer::class, 'watch_delete'],
    ],
    [
        'eventname' => '\engage_article\event\article_viewed',
        'callback'  => [interaction_observer::class, 'watch_interaction'],
    ],
    [
        'eventname' => '\engage_article\event\article_deleted',
        'callback'  => [interaction_observer::class, 'watch_delete'],
    ],
    [
        'eventname' => '\engage_survey\event\survey_viewed',
        'callback'  => [interaction_observer::class, 'watch_interaction'],
    ],
    [
        'eventname' => '\engage_survey\event\survey_deleted',
        'callback'  => [interaction_observer::class, 'watch_delete'],
    ],
    [
        'eventname' => '\container_workspace\event\workspace_viewed',
        'callback'  => [interaction_observer::class, 'watch_interaction'],
    ],
    [
        'eventname' => '\container_workspace\event\workspace_deleted',
        'callback'  => [interaction_observer::class, 'watch_core_delete'],
    ],
    [
        'eventname' => '\core\event\course_viewed',
        'callback'  => [interaction_observer::class, 'watch_core'],
    ],
    [
        'eventname' => '\core\event\course_deleted',
        'callback'  => [interaction_observer::class, 'watch_core_delete'],
    ],
    [
        'eventname' => '\totara_program\event\program_viewed',
        'callback'  => [interaction_observer::class, 'watch_core'],
    ],
    [
        'eventname' => '\totara_program\event\program_deleted',
        'callback'  => [interaction_observer::class, 'watch_core_delete'],
    ],
    [
        'eventname' => '\totara_engage\event\rating_created',
        'callback'  => [interaction_observer::class, 'watch_interaction'],
    ],
    [
        'eventname' => '\totara_comment\event\comment_created',
        'callback'  => [interaction_observer::class, 'watch_interaction'],
    ],
    [
        'eventname' => '\totara_reaction\event\reaction_created',
        'callback'  => [interaction_observer::class, 'watch_interaction'],
    ],
    [
        'eventname' => '\totara_reaction\event\reaction_removed',
        'callback'  => [interaction_observer::class, 'watch_interaction'],
    ],
    [
        'eventname' => '\totara_playlist\event\playlist_reshared',
        'callback'  => [interaction_observer::class, 'watch_interaction'],
    ],
    [
        'eventname' => '\engage_article\event\article_reshared',
        'callback'  => [interaction_observer::class, 'watch_interaction'],
    ],
    [
        'eventname' => '\engage_survey\event\survey_reshared',
        'callback'  => [interaction_observer::class, 'watch_interaction'],
    ],
    [
        'eventname' => \totara_playlist\event\playlist_viewed::class,
        'callback'  => [\totara_playlist\observer\playlist_observer::class, 'on_viewed'],
    ],
];