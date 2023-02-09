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
 * @package totara_cloudfiledir
 */

use totara_cloudfiledir\local\store;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    ['help' => false, 'list' => false, 'idnumber' => '', 'fetch' => false, 'push' => false, 'resetlocalproblems' => false, 'diag' => false, 'log' => ''],
    ['h' => 'help']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

// NOTE: there is no point in localising this script, admins must at least understand English.
$showhelp = false;
if ($options['help'] or (!$options['list'] && !$options['fetch'] && !$options['push'] && !$options['resetlocalproblems'] && !$options['diag'])) {
    $showhelp = true;
} else if (strlen($options['idnumber'] === 0) && ($options['fetch'] || $options['push'] || $options['resetlocalproblems'])) {
    $showhelp = true;
}
if ($showhelp) {
    $help =
        "Check file contents stored in local file content storage (aka filedir).

Options:
-h, --help            Print out this help
--list                List configured cloud stores
--idnumber=IDNUMBER   Specifies which store will be used
--fetch               Fetch list of content files available in the cloud store; requires --idnumber parameter
--push                Push all used local content files to the cloud store; requires --idnumber parameter
--resetlocalproblems  Resets flags indicating local files are missing or invalid; requires --idnumber parameter
--diag                Check if cloud store contains all file contents; requires --idnumber parameter
--log=/file/path      Log list of files not present in cloud to file

Example:
\$sudo -u www-data /usr/bin/php totara/cloudfiledir/cli/store.php --push --idnumber=shared
";

    echo $help;
    if ($options['help']) {
        exit(0);
    }
    exit(1);
}

define('DOTS_PER_LINE' , 80);
define('FILES_PER_DOT' , 100);

$stores = store::get_stores();

if ($options['list']) {
    cli_heading('List of cloud stores');
    foreach ($stores as $store) {
        cli_write($store->get_idnumber());
        if ($store->is_active()) {
            cli_write(' - active');
        }
        cli_writeln('');
    }
    if ($options['fetch'] || $options['push'] || $options['resetlocalproblems']) {
        cli_writeln('');
        cli_error('Cannot execute other actions together with --list.');
    }
    exit(0);
}

if (!isset($stores[$options['idnumber']])) {
    cli_error('Invalid store idnumber');
}
$store = $stores[$options['idnumber']];
if (!$store->is_active()) {
    cli_error('Store is not active');
}

if ($options['fetch']) {
    cli_heading('Fetching list of content files from store: ' . $store->get_idnumber());
    cli_writeln('Time started: ' . userdate(time()));
    if (!$stores[$options['idnumber']]->fetch_list()) {
        cli_error('Cannot fetch list of cloud content files');
    }
    cli_writeln('...done: ' . userdate(time()));
    cli_writeln('');
}

if ($options['resetlocalproblems']) {
    cli_heading('Resetting flags for missing local content files in store: ' . $store->get_idnumber());
    $store->reset_localproblem_flag();
    cli_writeln('...done.');
    cli_writeln('');
}

if ($options['push']) {
    cli_heading('Pushing local content files to store: ' . $store->get_idnumber());
    cli_writeln('Time started: ' . userdate(time()));
    $i = 0;
    $logger = function (string $contenthash) use (&$i) {
        $i++;
        if ($i % FILES_PER_DOT === 0) {
            cli_write('.');
        }
        if ($i % (DOTS_PER_LINE * FILES_PER_DOT) === 0) {
            cli_writeln('');
        }
    };
    if (!$stores[$options['idnumber']]->push_changes($logger)) {
        cli_error('Cannot push content files to cloud store');
    }
    cli_writeln('');
    cli_writeln('...done: ' . userdate(time()));
    cli_writeln('');
}

if ($options['diag']) {
    cli_heading('Checking cloud store contents: ' . $store->get_idnumber());

    $sql = "FROM {files} f
       LEFT JOIN {totara_cloudfiledir_sync} s ON s.contenthash = f.contenthash AND s.idnumber = :idnumber
           WHERE s.timeuploaded IS NULL";
    $params = ['idnumber' => $store->get_idnumber()];
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT f.contenthash) $sql", $params);
    if (!$count) {
        cli_writeln('All content files are present in cloud store.');
        exit(0);
    }
    cli_writeln('Number of file contents not present in cloud store: ' . $count);
    if ($options['log']) {
        $loghandle = fopen($options['log'], 'w');
    } else {
        $loghandle = null;
    }
    $records = $DB->get_recordset_sql("SELECT DISTINCT f.contenthash $sql ORDER BY f.contenthash", $params);
    foreach ($records as $record) {
        if ($loghandle) {
            fwrite($loghandle, $record->contenthash . "\n");
        } else {
            echo $record->contenthash . "\n";
        }
    }
    if ($loghandle) {
        fclose($loghandle);
    }
    exit(1);
}

exit(0);
