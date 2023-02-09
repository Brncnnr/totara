<?php
/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_facetoface
 */

$watchers = [
    [
        // Called during the sign-up process of a seminar event.
        // Used by Totara to redirect to the correct sign-up page.
        'hookname' => '\mod_facetoface\hook\alternative_signup_link',
        'callback' => 'enrol_totara_facetoface\watcher\seminar_watcher::alter_signup_link',
        'priority' => 100,
    ],
];
