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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

use hierarchy_goal\event\goal_created;
use hierarchy_goal\event\goal_updated;
use hierarchy_goal\event\personal_created;
use hierarchy_goal\event\personal_updated;
use hierarchy_goal\observers\company_goal;
use hierarchy_goal\observers\personal_goal;

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => goal_created::class,
        'callback' => company_goal::class . '::add_target_date_history_goal_created',
    ],
    [
        'eventname' => goal_updated::class,
        'callback' => company_goal::class . '::add_target_date_history_goal_updated',
    ],
    [
        'eventname' => personal_created::class,
        'callback' => personal_goal::class . '::add_target_date_history_personal_created',
    ],
    [
        'eventname' => personal_updated::class,
        'callback' => personal_goal::class . '::add_target_date_history_personal_updated',
    ],
];
