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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package ml_recommender
 */

use ml_recommender\local\environment;

define('CLI_SCRIPT', true);

require_once(realpath(__DIR__ . "/../../../config.php"));
require_once($CFG->libdir . '/clilib.php');

// Retrieve cli call arguments.
$py3path = environment::get_py3path();
$pyscript_path = realpath($CFG->dirroot . '/../extensions/ml_recommender/python/ml_recommender.py');
$query = environment::get_query();

$data_path = environment::get_data_path();

$user_result_count = environment::get_user_result_count();
$item_result_count = environment::get_item_result_count();
$threads = environment::get_threads();

// Set up cli call with arguments.
$args = [
    '--query' => $query,
    '--result_count_user' => $user_result_count,
    '--result_count_item' => $item_result_count,
    '--threads' => $threads,
    '--data_path' => $data_path
];

$cmd = escapeshellcmd(trim($py3path)) . ' ' . escapeshellarg(trim($pyscript_path)) . ' ';
foreach ($args as $key => $value) {
    $cmd .= ' ' . $key . ' ' . escapeshellarg(trim($value));
}

// Output the command line call.
echo($cmd);