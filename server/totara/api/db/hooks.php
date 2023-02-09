<?php
/**
* This file is part of Totara Core
*
* Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
* @author  Michael Ivanov <michael.ivanov@totaralearning.com>
* @package totara_api
*/

use totara_api\watcher\handle_request_post_watcher;
use totara_webapi\hook\handle_request_post_hook;
use totara_api\watcher\core_user_access_controller;
use core_user\hook\allow_view_profile;
use totara_webapi\hook\handle_request_pre_hook;
use totara_api\watcher\handle_request_pre_watcher;

$watchers = [
    [
        'hookname' => handle_request_post_hook::class,
        'callback' => handle_request_post_watcher::class . '::watch'
    ],
    [

        'hookname' => allow_view_profile::class,
        'callback' => core_user_access_controller::class . '::allow_view_profile'
    ],
    [
        'hookname' => handle_request_pre_hook::class,
        'callback' => handle_request_pre_watcher::class . '::watch'
    ]
];