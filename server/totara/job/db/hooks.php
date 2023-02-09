<?php
/**
 *
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_job
 *
 */

$watchers = [
    [
        // Hook into profile viewing checks to add support for access via job relationship
        'hookname' => '\core_user\hook\allow_view_profile',
        'callback' => 'totara_job\watcher\core_user_access_controller::allow_view_profile',
        'priority' => 100,
    ],
    [
        // Hook into profile field viewing checks to add support for access via job relationship
        'hookname' => '\core_user\hook\allow_view_profile_field',
        'callback' => 'totara_job\watcher\core_user_access_controller::allow_view_profile_field',
        'priority' => 100,
    ],
];

