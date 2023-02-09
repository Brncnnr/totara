<?php
/*
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Marco Song <marco.song@totaralearning.com>
 * @package criteria_othercompetency
 */

use criteria_othercompetency\observer\competency as competency_observer;
use hierarchy_competency\event\competency_deleted;


defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => competency_deleted::class,
        'callback' => competency_observer::class.'::competency_deleted',
    ],
];
