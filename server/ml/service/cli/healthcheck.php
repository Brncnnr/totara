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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_service
 */

use ml_service\healthcheck;

define('CLI_SCRIPT', true);

global $CFG;
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$usage = "
Run a check to see if both Totara & the Machine Learning service can communicate with each other.

Options:
  --help, -h    Output this help

Example:
\$sudo -u www-data /usr/bin/php ml/service/cli/healthcheck.php
";

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error('Unrecognised parameter: ' . $unrecognised);
}

if ($options['help']) {
    cli_writeln($usage);
    exit();
}

$healthcheck = healthcheck::make();

cli_heading(get_string('healthcheck_title', 'ml_service'));
cli_writeln("");
$healthcheck->check_health();

cli_heading(get_string('healthcheck_subtitle_totara', 'ml_service'));
$totara_info = $healthcheck->get_totara_info();
if (!empty($totara_info)) {
    foreach ($totara_info as $line) {
        cli_writeln($line);
    }
    cli_writeln("");
}

$other_info = $healthcheck->get_service_info();
cli_heading(get_string('healthcheck_subtitle_service', 'ml_service'));
if (!empty($other_info)) {
    foreach ($other_info as $key => $line) {
        if (!is_numeric($key)) {
            cli_write("$key: ");
        }
        cli_writeln($line);
    }
    cli_writeln("");
}

$troubleshooting = $healthcheck->get_troubleshooting();
if (!empty($troubleshooting)) {
    cli_heading(get_string('healthcheck_subtitle_troubleshooting', 'ml_service'));
    foreach ($troubleshooting as $error) {
        cli_writeln('• ' . $error);
    }
    cli_error('');
}
