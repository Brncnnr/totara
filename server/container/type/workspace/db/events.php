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
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\event\discussion_created;
use container_workspace\event\discussion_updated;
use container_workspace\observer\comment_observer;
use container_workspace\observer\discussion_observer;
use container_workspace\observer\notifiable_event_observer;
use container_workspace\observer\share_observer;
use container_workspace\observer\tenant_observer;
use container_workspace\observer\user_observer;
use core\event\tenant_created;
use core\event\user_deleted;
use core\event\user_enrolment_created;
use totara_comment\event\comment_created;
use totara_comment\event\comment_soft_deleted;
use totara_comment\event\comment_updated;
use totara_comment\event\reply_created;
use totara_comment\event\reply_soft_deleted;
use totara_core\event\bulk_enrolments_ended;
use totara_engage\event\share_created;

$observers = [
    [
        'eventname' => comment_created::class,
        'callback' => [comment_observer::class, 'on_comment_created']
    ],
    [
        'eventname' => reply_created::class,
        'callback' => [comment_observer::class, 'on_reply_created']
    ],
    [
        'eventname' => comment_updated::class,
        'callback' => [comment_observer::class, 'on_comment_updated']
    ],
    [
        'eventname' => reply_soft_deleted::class,
        'callback' => [comment_observer::class, 'on_reply_soft_deleted'],
    ],
    [
        'eventname' => comment_soft_deleted::class,
        'callback' => [comment_observer::class, 'on_comment_soft_deleted']
    ],
    [
        'eventname' => share_created::class,
        'callback' => [share_observer::class, 'content_added']
    ],
    [
        'eventname' => discussion_created::class,
        'callback' => [discussion_observer::class, 'on_created']
    ],
    [
        'eventname' => discussion_updated::class,
        'callback' => [discussion_observer::class, 'on_updated']
    ],
    [
        'eventname' => user_deleted::class,
        'callback' => [user_observer::class, 'on_user_deleted']
    ],
    [
        'eventname' => tenant_created::class,
        'callback' => [tenant_observer::class, 'tenant_created'],
    ],
    [
        'eventname' => user_enrolment_created::class,
        'callback' => [notifiable_event_observer::class, 'user_added_to_workspace'],
    ],
    [
        'eventname' => bulk_enrolments_ended::class,
        'callback' => [user_observer::class, 'on_users_removed']
    ],
];