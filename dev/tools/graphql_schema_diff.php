<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package dev_tools
 */

use GraphQL\Utils\BuildSchema;
use totara_webapi\tool\schema_diff;

define('CLI_SCRIPT', true);
define('NO_OUTPUT_BUFFERING', true);

define('DISPLAY_SEPARATOR', '----------------');

$config = __DIR__ . '/../../server/config.php';
require($config);
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    ['help' => false, 'old' => false, 'new' => false, 'type' => 'all'],
    ['h' => 'help', 'o' => 'old', 'n' => 'new', 't' => 'type']
);
if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

$allowed_types = ['all', 'nonbreaking', 'breaking'];

if (!empty($options['help'])) {
    $basename = basename(__FILE__);
    cli_writeln("Find the breaking changes and/or additions between two GraphQL schema files (*.graphqls).

Usage:
    php {$basename} {-o|--old} {-n|--new} [-t|--type]

Required parameters:
    -o, --old         Path to old .graphqls schema file
    -n, --new         Path to new .graphqls schema file

Options:
    -t, --type        Difference type: breaking, nonbreaking, or (default) all
    -h, --help        Print out this help

Example:
  $ php {$basename} -o=/path/to/old/schema.graphqls -n=/path/to/new/schema.graphqls
  $ php {$basename} -o=/path/to/old/schema.graphqls -n=/path/to/new/schema.graphqls -t=nonbreaking
  $ php {$basename} -o=/path/to/old/schema.graphqls -n=/path/to/new/schema.graphqls -t=breaking
");
    exit(2);
}

// Check file paths.
if (empty($options['old']) || empty($options['new'])) {
    cli_error('Required path parameter(s) missing, use -h for help.');
}
if (!is_readable($options['old'])) {
    cli_error('Old schema file does not exist or is not readable.');
}
if (!is_readable($options['new'])) {
    cli_error('New schema file does not exist or is not readable.');
}

// Check type.
if (!in_array($options['type'], $allowed_types)) {
    cli_error("Unimplemented diff type: {$options['type']}");
}

// Load old schema.
$old = file_get_contents($options['old']);
try {
    $old_schema = BuildSchema::build($old);
} catch (Exception $e) {
    cli_error('Unable to parse old schema, is it a valid .graphqls file?');
}

// Load new schema.
$new = file_get_contents($options['new']);
try {
    $new_schema = BuildSchema::build($new);
} catch (Exception $e) {
    cli_error('Unable to parse new schema, is it a valid .graphqls file?');
}

// Instantiate diff tool.
$differ = new schema_diff($old_schema, $new_schema);
$totals = [];

if (in_array($options['type'], ['all', 'nonbreaking'])) {
    $non_breaking = $differ->find_non_breaking_changes();
    $totals['non_breaking'] = count($non_breaking) . ' non-breaking changes';
    cli_writeln(DISPLAY_SEPARATOR);
    cli_writeln('NON-BREAKING CHANGES:');
    cli_writeln(DISPLAY_SEPARATOR);
    cli_writeln(print_r($non_breaking,1));
}

if (in_array($options['type'], ['all', 'breaking'])) {
    $breaking = $differ->find_breaking_changes();
    $totals['breaking'] = count($breaking) . ' breaking changes';
    cli_writeln(DISPLAY_SEPARATOR);
    cli_writeln('BREAKING CHANGES:');
    cli_writeln(DISPLAY_SEPARATOR);
    cli_writeln(print_r($breaking,1));
}

cli_writeln(DISPLAY_SEPARATOR);
cli_writeln('Summary');
cli_writeln(DISPLAY_SEPARATOR);
cli_writeln('Old schema: ' . $options['old']);
cli_writeln('New schema: ' . $options['new']);
foreach($totals as $msg) {
    cli_writeln($msg);
}
