<?php
/*
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 */

use GraphQL\Utils\SchemaPrinter;
use totara_webapi\endpoint_type\factory;
use totara_webapi\graphql;

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'help'    => false,
        'file'    => false,
        'type'    => false
    ),
    array(
        'h' => 'help',
        'f' => 'file',
        't' => 'type'
    )
);

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help'] || !$options['file']) {
    $help =
        "Produces a complete GraphQL schema for the specified type by concatenating individual schema files. The specified file will be overwritten if it already exists.

php ./server/totara/api/cli/generate_external_schema.php -t=ajax -f=totara.graphqls

Options:
-h, --help            Print out this help
-t, --type            Endpoint type e.g. 'ajax', 'dev', 'mobile' or 'external' (default)
-f, --file            Writes the schema to the given file. Use '-' for stdout

";

    echo $help;
    exit(0);
}

$type_name = $options['type'] ?? 'external';
$type = factory::get_instance($type_name);
$schema = graphql::get_schema($type);
$text_schema = SchemaPrinter::doPrint($schema);

if ($options['file'] !== '-') {
    file_put_contents($options['file'], $text_schema);
} else {
    echo $text_schema;
}
