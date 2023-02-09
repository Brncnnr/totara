<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_cloudfiledir
 */

$watchers = [
    [
        'hookname' => 'totara_core\hook\filedir_content_file_added',
        'callback' => ['totara_cloudfiledir\local\filedir_hook_watcher', 'content_file_added'],
        'priority' => 100,
    ],
    [
        'hookname' => 'totara_core\hook\filedir_content_file_deleted',
        'callback' => ['totara_cloudfiledir\local\filedir_hook_watcher', 'content_file_deleted'],
        'priority' => 100,
    ],
    [
        'hookname' => 'totara_core\hook\filedir_content_file_restore',
        'callback' => ['totara_cloudfiledir\local\filedir_hook_watcher', 'content_file_restore'],
        'priority' => 100,
    ],
    [
        'hookname' => 'totara_core\hook\filedir_xsendfile',
        'callback' => ['totara_cloudfiledir\local\filedir_hook_watcher', 'xsendfile'],
        'priority' => 100,
    ],
];
