<?php
/**
 * This file is part of Totara TXP
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 */

define('CLI_SCRIPT', true);
define('NO_OUTPUT_BUFFERING', true);

$config = __DIR__ . '/../../server/config.php';
require($config);
require_once($CFG->libdir . '/clilib.php');

$agreement = 'The previously-released schema is only meant to be replaced by a release manager as
part of the Totara release process, or on the rare occasion when a breaking change 
is necessary in a stable build.';

[$options, $unrecognized] = cli_get_params(
    ['help' => false, 'agree' => false],
    ['h' => 'help', 'a' => 'agree']
);
if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if (!empty($options['help'])) {
    $basename = basename(__FILE__);
    cli_writeln("Run API-related backend build tasks at release time. Builds documentation schema, and installs
current GraphQL schema as the 'previously released' schema to use for checking breaking changes.

To use this tool, you must agree: 

{$agreement} 

Usage:
    php {$basename} {-a|--agree}

Required arguments:
    -a, --agree     Indicates that you understand the purpose of this tool, as set out in 'To use this tool' above.

Options:
    -h, --help      Print out this help
");
    exit(2);
}

// Confirm agreement with terms.
if (empty($options['agree'])) {
    cli_writeln("To use this tool you must agree to the following:

{$agreement}

Run this script with --agree to indicate that you agree.
");
    exit(2);
}

// Make sure we are at Totara source root.
chdir($CFG->srcroot);

// Find current version.
include($CFG->dirroot . '/version.php');
$version = 'Totara-' . $TOTARA->release;

cli_writeln("Installing {$version} dev schema as previous_release.graphqls...");
$released_schema_file = 'server/totara/webapi/tests/fixtures/schema_diff/previous_release.graphqls';
`php server/totara/api/cli/generate_graphql_schema.php -t=dev -f={$released_schema_file}`;

// Append version to top of schema.
$lines = file($released_schema_file);
array_unshift($lines, '# ' . $version . PHP_EOL);
file_put_contents($released_schema_file, $lines);

// Also build API docs assets.
$schema_dir = `php server/totara/api/cli/prep_api_docs.php -q`;
cli_writeln("Schema and metadata assets generated, saved to {$schema_dir}");
cli_writeln('--------');
cli_writeln('If you have Totara Enterprise, you could run the following to build the External API documentation:');
cli_writeln("npm run build-docs {$schema_dir}");

// End