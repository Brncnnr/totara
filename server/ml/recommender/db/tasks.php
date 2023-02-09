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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package ml_recommender
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => ml_recommender\task\export::class,
        'blocking' => 1,
        'minute' => '15',
        'hour' => '1',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
        // This task is disabled because it is better to run it through CLI script (server/ml/recommender/cli/export_data.php) using system scheduler (e.g. cron)
        // Also, separate python script must be executed after export (see: server/ml/recommender/cli/recommender_command.php)
        'disabled' => 1,
    ],
    [
        'classname' => ml_recommender\task\import::class,
        'blocking' => 0,
        'minute' => '*/15',
        'hour' => '0',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
        // This task is disabled because it is better to run it through CLI script (server/ml/recommender/cli/import_recommendations.php) using system scheduler (e.g. cron)
        // Also, separate python script must be executed before import (see: server/ml/recommender/cli/recommender_command.php)
        'disabled' => 1,
    ],
];
