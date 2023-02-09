<?php
/**
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 */
define('CLI_SCRIPT', 1);
use degeneration\App;
use degeneration\performance_testing;
use degeneration\performance_testing_config;

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "In order to execute this script you need to execute: 'composer install' from this directory!" . PHP_EOL;
    return 1;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once(__DIR__ . '/../../server/config.php');
require_once(App::config()->dirroot . "/lib/clilib.php");

core_php_time_limit::raise(0);
raise_memory_limit(MEMORY_UNLIMITED);

[$options, $params] = cli_get_params(
    [
        'help' => false,
        'size' => 's',
        'enrolments' => false,
        'perform' => false,
        'engage' => false,
        'transaction' => 0,
        'override' => false
    ],
    [
        'h' => 'help',
        's' => 'size',
        'c' => 'enrolments',
        'p' => 'perform',
        'e' => 'engage',
        't' => 'transaction'
    ]
);

if ($options['help']) {
    echo "
This script is designed to generate a vast amount of testing data to test performance.
The script meant to be executed on a clean (freshly installed) site. Some options have
been added to control the size and scope of the data generated, but for more granular
control please see the generate function in dev/data_generation/classes/performance_testing.php

Usage:
    php dev/data_generation/generate_site.php -s=\"S\" -t=0

Options:
    -h, --help          Print out this help
    -s, --size          T - A tiny site, created fast and suitable for non-performance testing
                        XS - A very small amount of data
                        S - Small amount of data (default)
                        M - Medium amount of data
                        L - Large amount of data
                        XL - Extra large amount of data
                        XXL - Extra-extra large amount of data
                        GOLIATH - Unnecessary large amount of data
    -c, --enrolments    Enrol users on generated courses, and add completion data
    -p, --perform       Include perform data generation
    -e, --engage        Include engage data generation
    -t, --transaction   Specify 1 if you want to run data generator in transaction. (default = 0)
    
Note: Enabling optional features (enrolments, perform, etc) may take quite a while. Do not 
exceed --size='L' unless you have several days for this to run.
    ";

    return 0;
}

if (!isset($options['size']) || !in_array(strtolower($options['size']), ['t', 'xs', 's', 'm', 'l', 'xl', 'xxl', 'goliath'])) {
    echo 'You must specify size of data to create to run the script';
    return 1;
}

// Ok we need to do something.
// Need to instantiate the main app and create things

$time = time();
$size = strtolower($options['size']);
$USER = get_admin();

$config = new performance_testing_config();
$config->include_enrolments = $options['enrolments'] ?? false;
$config->include_perform = $options['perform'] ?? false;
$config->include_engage = $options['engage'] ?? false;

$override = $options['override'] ?? false;
if (($config->include_enrolments || $config->include_perform || $config->include_engage) && in_array($size, ['xl', 'xxl', 'goliath']) && !$override) {
    echo "
WARNING: You are about to run an extra large site generation including enrolments, perform, and/or engage. 
This will take several days to run, and need several hundred gigabytes of drive space. It is recommended 
to re-run with a smaller size however if you don't mind the time/space sink then rerun the command with 
--override to continue... you've been warned :)
    ";
    return 2;

}

$generate = function () use ($size, $config) {
    $pt = new performance_testing();

    $pt->set_size($size)
       ->generate_from_config($config);
};

if ($options['transaction']) {
    echo 'Running data generation inside a transaction it might take a long time...' . PHP_EOL;
    App::transaction($generate);
} else {
    echo 'Running data generation without a transaction it might still take a long time...' . PHP_EOL;
    $generate();
}

echo PHP_EOL . PHP_EOL . "Time elapsed: " . (time() - $time) . PHP_EOL;
