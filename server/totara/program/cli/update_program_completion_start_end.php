<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_program
 */

define('CLI_SCRIPT', 1);

require_once __DIR__ . '/../../../config.php';

/** @var core_config $CFG */
require_once($CFG->libdir . '/clilib.php');         // cli only functions
require_once($CFG->dirroot . '/totara/program/program.class.php');

$help = "Update program start and completion times.
Start and completion times of certifications can not be updated through this script.

By default only times set in the future will be updated.
This can be overridden through the --include-past option.

Please note: This script can be used to update multiple program completion records in a single run. Please use with care.

Usage:

    php server/totara/program/cli/update_program_completion_start_end.php [options]

Options:
--started=DATETIME              Update the program start time to this value. (Format: Any valid English date format with or without the time, e.g. YYYY-MM-DD or YYYYMMDD HH:MM)
--completed=DATETIME            Update the program completion time to this value. (Format: Any valid English date format with or without the time, e.g. YYYY-MM-dd or YYYYMMDD HH:MM)

--category=CATEGORY_ID_NUMBER   Optional. Update program start / completion times of programs in the category with the specified id number.
--program=PROGRAM_ID_NUMBER     Optional. Update program start / completion times of the program with the specified id number.
--user=USERNAME                 Optional. Update program start / completion times of the specific user.

--include-past                  Optional. If specified, the start and completion times will be updated even if they are not currently set to a future time.

--verbose                       Optional. Provide more verbose output.
-h, --help                      Print out this help
";

/**
 * Parse the datetime string to a datetime
 *
 * @param string|null $value
 * @param string $item_name
 * @param int $now
 * @return int|false Parsed datetime
 */
function parse_datetime(?string $value = null, string $item_name = 'datetime', int $now = null) {
    if ($now === null) {
        $now = time();
    }

    if ($value !== null) {
        $new_started = strtotime($value);
        if ($new_started === false) {
            cli_error("Invalid {$item_name} format: {$value}.");
        }

        if ($new_started > $now) {
            cli_error("New {$item_name} should be in the past.");
        }

        return $new_started;
    }
    return false;
}

/**
 * Update program completion times
 *
 * @param string $where
 * @param array $params
 * @param array $to_update
 * @param bool $include_past
 * @param int $now
 * @param false $verbose
 * @throws dml_exception
 * @throws dml_transaction_exception
 */
function update_completion_times(string $where, array $params, array $to_update, bool $include_past, int $now, $verbose = false): void {
    global $DB;

    $admin_user = get_admin();

    $sql =
        "SELECT pc.*, p.fullname, u.username
           FROM {prog_completion} pc
           JOIN {prog} p
             ON pc.programid = p.id
           JOIN {user} u
             ON pc.userid = u.id
           $where
       ORDER BY pc.programid, pc.userid";

    $rows = $DB->get_recordset_sql($sql, $params);

    cli_writeln('Updating program completion records');
    $num_processed = 0;
    $num_updated = 0;

    $trans = $DB->start_delegated_transaction();

    if ($verbose) {
        cli_writeln('  Processing:');
    }

    foreach ($rows as $row) {
        $num_processed += 1;
        if ($verbose) {
            cli_write("    [program: {$row->fullname}; user: {$row->username}] - ");
        }

        unset ($row->fullname);
        unset ($row->username);

        $do_update = false;
        foreach ($to_update as $column => $value) {
            if ($value && $row->$column
                && ($include_past || $row->$column > $now)) {
                $do_update = true;
                $row->$column = $value;
            }
        }

        if ($do_update) {
            try {
                $DB->update_record('prog_completion', $row);
                prog_write_completion_log(
                    $row->programid,
                    $row->userid,
                    "Updated program completion record via update_program_completion_start_end",
                    $admin_user->id
                );

                $num_updated += 1;
            } catch(dml_exception $ex) {
                $trans->rollback($ex);
                cli_error($ex->getMessage());
                exit;
            }
        }

        if ($verbose) {
            cli_writeln($do_update ? 'Updated' : 'Skipped');
        }
    }
    $trans->allow_commit();

    cli_writeln('Completed.');
    cli_writeln("  Number records processed: {$num_processed}");
    cli_writeln("  Number records updated: {$num_updated}");
}

list($options, $unrecognized) = cli_get_params(
    [
        'started' => null,
        'completed' => null,
        'category' => null,
        'program' => null,
        'user' => null,
        'include-past' => false,
        'verbose' => false,
        'help'   => false
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    cli_writeln($help);
    die;
}

// Handle specified empty values
foreach (['started', 'completed', 'category', 'program', 'user'] as $option) {
    if (is_bool($options[$option]) || empty($options[$option]) || $options[$option] == "''") {
        $options[$option] = null;
    }
}

if ($options['started'] === null && $options['completed'] === null) {
    cli_writeln($help);
    cli_error('At least one of the start or completed datetimes must be specified.');
}

if ($options['category'] === null && $options['program'] === null && $options['user'] === null) {
    cli_writeln($help);
    cli_error('Updating all future completion records in a single run is not allowed. Please specify --category, --program and/or --user.');
}

$starttime = microtime();
$now = time();

// Parsing options

$new_started = parse_datetime($options['started'], 'start time', $now);
$new_completed = parse_datetime($options['completed'], 'completion time', $now);
$include_past = (bool) $options['include-past'];
$verbose = (bool) $options['verbose'];

$params = [];
$where = '';
$program_error = '';

if ($options['category'] !== null) {
    if (empty($options['category'])) {
        cli_error('Category idnumber expected when using --category argument.');
    }

    $category = $DB->get_record('course_categories', ['idnumber' => $options['category']]);
    if ($category === false) {
        cli_error('Invalid category idnumber: "' . $options['category'] . '".');
    }

    $program_error = ' in category "' . $options['category'] . '"';

    // No concatenation to where - first part
    $where = ' AND p.category = :category';
    $params['category'] = $category->id;
}

if ($options['program'] !== null) {
    if (empty($options['program'])) {
        cli_error('Program idnumber expected when using --program argument.');
    }

    $params['idnumber'] = $options['program'];
    $program = $DB->get_record('prog', $params);
    if ($program === false) {
        cli_error('A program with idnumber "' . $options['program'] . '" doesn\'t exist' . $program_error . '.');
    }
    if (!empty($program->certifid)) {
        cli_error('The specified idnumber ("' . $options['program'] . '") is the idnumber of a certification. This script can only be used to update program start and completion times.');
    }

    // No need to query on category and idnumber any more
    unset ($params['idnumber']);
    if (isset($params['category'])) {
        unset($params['category']);
    }

    // No concatenation here - override
    $where = ' AND pc.programid = :program_id';
    $params['program_id'] = $program->id;
}

if ($options['user'] !== null) {
    if (empty($options['user'])) {
        cli_error('User\'s username expected when using --user argument.');
    }

    $user = $DB->get_record('user', ['username' => $options['user']]);
    if ($user === false) {
        cli_error('Unknown user: "' . $options['user'] . '".');
    }

    // Concatenate to potential category / program where clause
    $where .= ' AND pc.userid = :userid';
    $params['userid'] = $user->id;
}

if ($new_started || $new_completed) {
    $completion_where =
        "WHERE pc.coursesetid = 0
            AND p.certifid IS NULL";

    $future_where = '';
    if (!$include_past) {
        $future_where =
            " AND (pc.timestarted > :timestarted
               OR pc.timecompleted > :timecompleted)";

        $params['timestarted'] = $now;
        $params['timecompleted'] = $now;
    }

    update_completion_times(
        $completion_where . $where . $future_where,
        $params,
        ['timestarted' => $new_started, 'timecompleted' => $new_completed],
        $include_past,
        $now,
        $verbose
    );
}

$difftime = microtime_diff($starttime, microtime());
cli_writeln("  Update took $difftime seconds");

exit(0);