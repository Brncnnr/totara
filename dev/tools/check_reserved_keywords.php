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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../server/config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'check' => false,
        'help'  => false
    ),
    array(
        'h' => 'help'
    )
);

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if (!$options['check']) {
    $help =
        "Check list of reserved SQL keywords

Options:
-h, --help            Print out this help
--check               List SQL keywords that are not specified in DDL generator
";

    echo $help;
    exit(0);
}

$dbfamily = $DB->get_dbfamily();
$dbvendor = $DB->get_dbvendor();
$dbversion = $DB->get_server_info()['version'];
$keywords = [];

cli_heading("Database reserved keywords check for '$dbvendor $dbversion'");

if ($dbfamily === 'mysql' && $dbvendor === 'mysql' && version_compare($dbversion, '8.0', '>')) {
    // See https://dev.mysql.com/doc/refman/8.0/en/keywords.html
    $keywords = $DB->get_fieldset_sql("SELECT WORD FROM INFORMATION_SCHEMA.KEYWORDS WHERE RESERVED = 1");

} else {
    cli_error("Current database is not supported in this script'");
}

$keywords = array_map('strtolower', $keywords);

$generator = $DB->get_manager()->generator;
$existing = $generator->getReservedWords();

$missing = array_diff($keywords, $existing);

if ($missing) {
    $missing = array_map('strtoupper', $missing);
    cli_writeln('Missing keywords: ' . implode(', ', $missing));
    exit(1);
} else {
    cli_writeln('No missing SQL keywords found');
    exit(0);
}


