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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplaceactivity_linkedin
 */

defined('MOODLE_INTERNAL') || die();

use contentmarketplace_linkedin\event\user_progress_updated;
use contentmarketplaceactivity_linkedin\observer\user_enrolment_observer;
use contentmarketplaceactivity_linkedin\observer\user_progress_observer;
use core\event\user_enrolment_created;

$observers = [
    [
        'eventname' => user_progress_updated::class,
        'callback' => [user_progress_observer::class, 'user_progress_updated']
    ],
    [
        'eventname' => user_enrolment_created::class,
        'callback' => [user_enrolment_observer::class, 'user_enrolment_created']
    ]
];
