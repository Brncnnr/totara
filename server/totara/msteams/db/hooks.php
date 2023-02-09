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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

use theme_msteams\hook\get_page_navigation_hook;
use totara_msteams\watcher\watchers;
use totara_msteams\hook\bot_command_list_hook;
use totara_msteams\my\watcher as mywatcher;

$watchers = [
    [
        'hookname' => get_page_navigation_hook::class,
        'callback' => watchers::class . '::watch_page_navigation_hook',
        'priority' => 100,
    ],
    [
        'hookname' => bot_command_list_hook::class,
        'callback' => mywatcher::class . '::get_bot_command_list',
    ],
];
