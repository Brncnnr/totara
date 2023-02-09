<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package criteria_childcompetency
 */

use core\hook\admin_setting_changed;
use core_user\hook\allow_view_profile_field;
use totara_competency\hook\competency_achievement_updated_bulk;
use totara_competency\hook\competency_configuration_changed;
use totara_competency\watcher\configuration as configuration_watcher;
use totara_competency\watcher\core_user;
use totara_competency\watcher\notify_users_of_proficiency_change;
use totara_competency\watcher\settings;

defined('MOODLE_INTERNAL') || die();

$watchers = [
    [
        'hookname' => allow_view_profile_field::class,
        'callback' => [core_user::class, 'allow_view_profile_field'],
    ],
    [
        'hookname' => competency_configuration_changed::class,
        'callback' => [configuration_watcher::class, 'configuration_changed'],
    ],
    [
        'hookname' => competency_achievement_updated_bulk::class,
        'callback' => [notify_users_of_proficiency_change::class, 'send_notification'],
    ],
    [
        'hookname' => admin_setting_changed::class,
        'callback' => settings::class.'::admin_setting_changed',
    ]
];
