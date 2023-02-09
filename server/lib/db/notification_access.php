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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

$accesses = [
    [
        'capability' => 'moodle/course:managecoursenotifications',
        'context_levels' => [
            CONTEXT_MODULE,
            CONTEXT_COURSE,
            CONTEXT_COURSECAT,
            CONTEXT_TENANT,
            CONTEXT_SYSTEM,
        ]
    ],
    [
        'capability' => 'moodle/course:auditcoursenotifications',
        'context_levels' => [
            CONTEXT_MODULE,
            CONTEXT_COURSE,
            CONTEXT_COURSECAT,
            CONTEXT_TENANT,
            CONTEXT_SYSTEM,
        ]
    ],
];
