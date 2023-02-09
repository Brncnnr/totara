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
 * @package core
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    ['help' => false, 'diag' => false, 'validate' => false, 'recover' => false, 'deleteinvalid' => false,
        'prune' => false, 'log' => false, 'resetstatus' => false],
    ['h' => 'help']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

// NOTE: there is no point in localising this script, admins must at least understand English.

if ($options['help'] or (!$options['diag'] && !$options['prune'] && !$options['resetstatus'])) {
    $help =
        "Check file contents stored in local file content storage (aka filedir).

Options:
-h, --help            Print out this help
--diag                Diagnose content storage of all stored files
--validate            Validate hash of all content files when diagnosing stored files
--recover             Attempt to restore missing content files when diagnosing stored files
--deleteinvalid       Delete files with invalid content that were found during validation
--prune               Delete all unused local content files that are not referenced by any stored file
--resetstatus         Reset all stored files status back to 0
--log=/file/path      Log problems to file instead of printing list at the end

Problem types:
 -   missing content file
 R   recovered content file
 !   invalid content file is present
 X   invalid content file was deleted
 +   unreferenced file was deleted
 
WARNING: Please make sure the file permissions and current user are valid before executing this script!
 
Example:
\$sudo -u www-data /usr/bin/php admin/cli/check_filedir.php --diag
";

    echo $help;
    if ($options['help']) {
        exit(0);
    }
    exit(1);
}

define('CHECK_FILEDIR_MISSING', '-');
define('CHECK_FILEDIR_RECOVERED', 'R');
define('CHECK_FILEDIR_INVALID', '!');
define('CHECK_FILEDIR_DELETED_INVALID', 'X');
define('CHECK_FILEDIR_DELETED_UNREFERENCED', '+');

define('DOTS_PER_LINE' , 75);
define('FILES_PER_DOT' , 100);

raise_memory_limit(MEMORY_HUGE);

if ($options['log']) {
    $logfile = $options['log'];
} else {
    $logfile = make_request_directory() . '/filedir.log';
}

$problemsfound = 0;
$problemsfixed = 0;
$loghandle = null;
$logger = function (string $contenthash, string $prefix) use ($logfile, &$loghandle) {
    if ($logfile === null) {
        return;
    }
    if ($loghandle === null) {
        $loghandle = fopen($logfile, 'w');
    }
    fwrite($loghandle, $prefix . ' ' . $contenthash . "\n");
};

$fs = get_file_storage();

// The number of files may change in time, we cannot fetch them all into memory.
$estimate = $DB->count_records_sql("SELECT COUNT(DISTINCT contenthash) FROM {files}");

$timestarted = time();
cli_heading('Content files check');
cli_writeln('Time started: ' . userdate($timestarted));
cli_writeln('Number of stored files: ' . $estimate);
if ($options['diag']) {
    cli_writeln('');
    cli_writeln("Diagnosing content files:");

    $i = 0;
    $hashprefix = $DB->sql_substr('contenthash', 1, 4); // Yes, start is 1-based value in SQL.
    $chunks = $DB->get_fieldset_sql("SELECT DISTINCT $hashprefix AS hashprefix FROM {files}");
    foreach ($chunks as $chunk) {
        $contenthashses = $DB->get_fieldset_sql("SELECT DISTINCT contenthash FROM {files} WHERE contenthash LIKE ? ORDER BY contenthash", [$chunk . '%']);
        foreach ($contenthashses as $contenthash) {
            $i++;
            if ($i % FILES_PER_DOT === 0) {
                cli_write('.');
            }
            if ($i % (DOTS_PER_LINE * FILES_PER_DOT) === 0) {
                if ($i >= $estimate) {
                    $percentage = 99;
                } else {
                    $percentage = intval(floor(($i / (float)$estimate) * 100));
                }
                $percentage = str_pad($percentage, 2, ' ');
                cli_writeln(" $percentage %");
            }

            $invaliddeleted = false;
            if ($fs->content_exists($contenthash, true)) {
                if (!$options['validate']) {
                    continue;
                }
                if ($fs->validate_content($contenthash, $options['deleteinvalid'])) {
                    // File is valid.
                    continue;
                }
                if (!$options['deleteinvalid'] || $fs->content_exists($contenthash)) {
                    $problemsfound++;
                    call_user_func($logger, $contenthash, CHECK_FILEDIR_INVALID);
                    continue;
                }
                $invaliddeleted = true;
            }

            if (!$options['recover']) {
                $problemsfound++;
                if ($invaliddeleted) {
                    call_user_func($logger, $contenthash, CHECK_FILEDIR_DELETED_INVALID);
                } else {
                    call_user_func($logger, $contenthash, CHECK_FILEDIR_MISSING);
                }
                continue;
            }
            $fs->try_content_recovery($contenthash);
            if ($fs->content_exists($contenthash)) {
                $problemsfixed++;
                call_user_func($logger, $contenthash, CHECK_FILEDIR_RECOVERED);
                continue;
            } else {
                $problemsfound++;
                if ($invaliddeleted) {
                    call_user_func($logger, $contenthash, CHECK_FILEDIR_DELETED_INVALID);
                } else {
                    call_user_func($logger, $contenthash, CHECK_FILEDIR_MISSING);
                }
                continue;
            }
        }
    }
    unset($chunks);
    unset($chunk);
    unset($contenthashses);
    cli_writeln(' 100 %');
}

if ($options['prune']) {
    cli_writeln('');
    cli_writeln('Pruning unreferenced content files from local filedir:');

    $i = 0;

    $progress = function(string $contenthash, bool $deleted) use (&$logger, &$i, &$problemsfixed) {
        $i++;
        if ($deleted) {
            call_user_func($logger, $contenthash, CHECK_FILEDIR_DELETED_UNREFERENCED);
            $problemsfixed++;
        }
        if ($i % FILES_PER_DOT === 0) {
            cli_write('.');
        }
        if ($i % (DOTS_PER_LINE * FILES_PER_DOT) === 0) {
            cli_writeln('');
        }
    };
    $fs->prune_unreferenced_files($progress);
    cli_writeln('');
}

if ($options['resetstatus']) {
    cli_writeln('');
    cli_writeln('Resetting stored files status to 0');
    $DB->set_field_select('files', 'status', 0, "status <> 0");
    cli_writeln('');
}

cli_writeln('Time finished: ' . userdate(time()));

if ($problemsfound || $problemsfixed) {
    if ($problemsfound) {
        cli_writeln('Problems found: ' . $problemsfound);
    }
    if ($problemsfixed) {
        cli_writeln('Problems fixed: ' . $problemsfixed);
    }
    if ($loghandle !== null) {
        fclose($loghandle);
        if (!$options['log']) {
            cli_writeln('');
            echo file_get_contents($logfile);
            cli_writeln('');
        }
    }
} else {
    cli_writeln('No problems found.');
}

$exitcode = $problemsfound ? 1 : 0;
exit($exitcode);
