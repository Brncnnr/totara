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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package pathway_criteria_group
 */

use pathway_criteria_group\watcher\criteria as criteria_watcher;
use totara_criteria\hook\criteria_achievement_changed;
use totara_criteria\hook\criteria_validity_changed;

defined('MOODLE_INTERNAL') || die();

$watchers = [
    [
        'hookname' => criteria_achievement_changed::class,
        'callback' => criteria_watcher::class.'::achievement_changed',
    ],
    [
        'hookname' => criteria_validity_changed::class,
        'callback' => criteria_watcher::class.'::validity_changed',
    ],
];
