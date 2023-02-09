<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package performelement_aggregation
 */

use mod_perform\event\participant_section_progress_updated;
use performelement_aggregation\observers\participant_section_aggregation;

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => participant_section_progress_updated::class,
        'callback' => [participant_section_aggregation::class, 'update_aggregation_responses'],
    ],
];