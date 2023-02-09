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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package ml_recommender
 */

use ml_recommender\task\export;
use ml_recommender\local\environment;

define('CLI_SCRIPT', true);

global $CFG;
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/filelib.php');

$usage = "
Export user, item and interactions data for recommendations processing.

Options:
  --help, -h    Output this help
  --force, -f   Break locks and re-export

Example:
    # sudo -u apache php ml/recommender/cli/export_data.php
        Exports collected data for further analysing and predictions
        Note: Needs to be run before the model can be trained.
";

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'force' => false,
    ],
    [
        'h' => 'help',
        'f' => 'force',
    ]
);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL.'  ', $unrecognised);
    cli_error('Unrecognised parameter: ' .  $unrecognised);
}

if ($options['help']) {
    cli_writeln($usage);
    exit();
}

if ($options['force']) {
    environment::enforce_data_path_sanity();
    export::cleanup(true);
}

$task = \core\task\manager::get_scheduled_task(\ml_recommender\task\export::class);
$task->execute();
