<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package message_totara_alert
 */
defined('MOODLE_INTERNAL') || die();

use core_message\hook\purge_check_notification_hook;
use message_totara_alert\watcher\purge_notification_watcher;

$watchers = [
    [
        /** @see purge_notification_watcher::check_notification_for_purge() */
        'hookname' => purge_check_notification_hook::class,
        'callback' => [purge_notification_watcher::class, 'check_notification_for_purge']
    ]
];