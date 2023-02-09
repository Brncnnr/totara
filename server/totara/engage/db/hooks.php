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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_engage
 */

defined('MOODLE_INTERNAL') || die;

$watchers = [
    [
        // Totara Engage gives users the ability to view other users profiles by default if Engage features are turn
        'hookname' => \core_user\hook\allow_view_profile::class,
        'callback' => [\totara_engage\watcher\core_user::class, 'handle_allow_view_profile'],
    ],
    [
        // Totara Engage gives users the ability to view some users profile fields by default if Engage features are turned on
        'hookname' => \core_user\hook\allow_view_profile_field::class,
        'callback' => [\totara_engage\watcher\core_user::class, 'handle_allow_view_profile_field'],
    ],
    [
        'hookname' => \editor_weka\hook\search_users_by_pattern::class,
        'callback' => [\totara_engage\watcher\editor_weka_watcher::class, 'on_search_users']
    ],
    [
        'hookname' => '\core\hook\phpunit_reset',
        'callback' =>  [\totara_engage\watcher\phpunit_reset_watcher::class, 'reset_data']
    ]
];