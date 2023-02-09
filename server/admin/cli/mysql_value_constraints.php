<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_core
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir . '/clilib.php');

if ($DB->get_dbfamily() !== 'mysql') {
    cli_error('This script is used for MySQL and MariaDB databases only.');
}

if (empty($CFG->version)) {
    cli_error('Totara is not installed yet!');
}

/** @var mysqli_native_moodle_database $DB */

list($options, $unrecognized) = cli_get_params(
    array('help' => false, 'list' => false, 'fix' => false),
    array('h' => 'help', 'l' => 'list', 'f' => 'fix')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
    "Script for review and fixing of value constraints in MySQL and MariaDB

MySQL 5.7.x does not support value constraints, this script should be
used right after the server gets upgraded to supported MariaDB or MySQL 8.

Options:
-l, --list            List all column value constrains and show status
-f, --fix             Add all missing value constraints if possible
-h, --help            Print out this help

Example:
\$ sudo -u www-data /usr/bin/php admin/cli/mysql_value_constraints.php -l
";

if (empty($options['list']) and empty($options['fix'])) {
    echo $help;
    exit(0);
}

$dbmanager = $DB->get_manager();
$info = $DB->get_server_info();
$prefix = $DB->get_prefix();
$version = $DB->get_server_info()['version'];
$vendor = $DB->get_dbvendor();

$errorfound = false;
$warningfound = false;

$schema = $dbmanager->get_install_xml_schema();

cli_heading("Enum value constraint check ($vendor: $version)");

foreach ($schema->getTables() as $table) {
    foreach ($table->getFields() as $field) {
        $allowedvalues = $field->getAllowedValues();
        if ($allowedvalues === null) {
            continue;
        }
        $tablename = $table->getName();
        $fieldname = $field->getName();
        $allowedvaluesstr = '[' . implode(',', $allowedvalues) . ']';

        if ($dbmanager->field_allowed_values_constraint_exists($table, $field)) {
            cli_writeln("OK:        {$tablename}.{$fieldname} $allowedvaluesstr");
            continue;
        }

        $sql = "SELECT DISTINCT \"{$fieldname}\"
                  FROM {{$tablename}}
                 WHERE \"{$fieldname}\" IS NOT NULL";
        $values = $DB->get_fieldset_sql($sql);
        $extras = array_diff($values, $allowedvalues);
        if ($extras) {
            $errorfound = true;
            cli_writeln("ERROR:     {$tablename}.{$fieldname} $allowedvaluesstr - invalid database values detected: " . implode(',', $extras));
            continue;
        }
        if (empty($options['fix'])) {
            $warningfound = true;
            cli_writeln("WARNING:   {$tablename}.{$fieldname} $allowedvaluesstr - database constraint is missing");
            continue;
        }
        $dbmanager->change_field_allowed_values($table, $field);
        if ($dbmanager->field_allowed_values_constraint_exists($table, $field)) {
            cli_writeln("FIXED:     {$tablename}.{$fieldname} $allowedvaluesstr");
            continue;
        }
        $warningfound = true;
        cli_writeln("NOT FIXED: {$tablename}.{$fieldname} $allowedvaluesstr - database constraint is missing");
    }
}

if ($errorfound) {
    exit(1);
} else if ($warningfound) {
    exit(2);
} else {
    exit(0);
}