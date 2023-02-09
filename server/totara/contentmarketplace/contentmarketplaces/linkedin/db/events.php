<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
defined('MOODLE_INTERNAL') || die();

use contentmarketplace_linkedin\event\import_course_full_failure;
use contentmarketplace_linkedin\event\import_course_partial_failure;
use contentmarketplace_linkedin\observer;
use totara_notification\observer\notifiable_event_observer;
use totara_xapi\event\xapi_statement_created;

$observers = [
    [
        'eventname' => import_course_full_failure::class,
        'callback' => [notifiable_event_observer::class, 'watch_notifiable_event']
    ],
    [
        'eventname' => import_course_partial_failure::class,
        'callback' => [notifiable_event_observer::class, 'watch_notifiable_event']
    ],
    [
        'eventname' => xapi_statement_created::class,
        'callback' => [observer::class, 'watch_xapi_statement_created']
    ]
];