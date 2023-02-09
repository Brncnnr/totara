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
 * @author Riana Rossow <riana.rossow@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package criteria_coursecompletion
 */

use core\event\course_completed;
use core\event\course_deleted;
use core\event\course_restored;
use core\event\course_updated;
use criteria_coursecompletion\observer\course as course_observer;
use totara_completioneditor\event\course_completion_edited;
use totara_completionimport\event\bulk_course_completionimport;
use totara_core\event\course_completion_reset;

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => course_completed::class,
        'callback' => course_observer::class.'::course_completion_changed',
    ],
    [
        'eventname' => course_completion_edited::class,
        'callback' => course_observer::class.'::course_completion_changed',
    ],
    [
        'eventname' => course_completion_reset::class,
        'callback' => course_observer::class.'::course_completion_reset',
    ],
    [
        'eventname' => bulk_course_completionimport::class,
        'callback' => course_observer::class.'::bulk_course_completions_imported',
    ],
    [
        'eventname' => course_deleted::class,
        'callback' => course_observer::class.'::course_deleted',
    ],
    [
        'eventname' => course_restored::class,
        'callback' => course_observer::class.'::course_restored',
    ],

    // Validity currently just checks whether completion tracking is enabled on the course,
    // not whether there are any completion criteria set - thus just checking for course update
    [
        'eventname' => course_updated::class,
        'callback' => course_observer::class.'::course_updated',
    ],

];
