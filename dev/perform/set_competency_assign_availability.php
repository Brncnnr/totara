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
 * @author Riana Rossouw <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

use totara_hierarchy\entity\competency_framework;

define('CLI_SCRIPT', 1);

require_once __DIR__ . '/../../server/config.php';

/** @var core_config $CFG */
require_once($CFG->libdir . '/clilib.php');         // cli only functions
require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot .  '/totara/hierarchy/lib.php');

$help = "Set assignment availability for competencies.
When set, competency assignment will be available for direct assignment to a user via their competency profile
by either a user with permission to assign to themselves (i.e. --availability=self), or another user who has permission
to assign to them (i.e. --availability=other).

Please note: This sets the availability on all competencies (in the given framework) replacing the existing setting. Use with care.

Usage:

    php dev/perform/set_competency_assign_availability --availability=<availabitity> [options]

Options:
--availabiliy=AVAILABILITY        Set the availability for competencies. Availability can be one of
                                      'none',
                                      'self',
                                      'other' or
                                      'any' (Any = self AND other)
--framework=FRAMEWORK_ID_NUMBER   Set the assignment availability for all competencies in the framework with the specified id number

-h, --help  Print out this help
";

list($options, $unrecognized) = cli_get_params(
    [
        'availability' => null,
        'framework' => null,
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

if ($options['availability'] === null) {
    cli_writeln($help);
    cli_error('Availabiltiy must to specified');
}
$availabilities = totara_hierarchy_parse_competency_assignment_availability($options['availability']);
if ($availabilities === null) {
    cli_error('Invalid assignment availability. Please specify "none", "self", "other" or "any"');
}

$msg = 'Updating assignment availability to "' . $options['availability'] . '". ';

$starttime = microtime();

if ($options['framework'] !== null) {
    if (is_bool($options['framework'])) {
        cli_error('Framework idnumber expected when using --framework argument');
    }

    $frameworks = competency_framework::repository()
        ->select('id')
        ->where('idnumber', $options['framework'])
        ->get();
    if ($frameworks->count() === 0) {
        cli_error('Invalid framework idnumber: "' . $options['framework'] . '"');
    }

    if ($options['framework'] === '') {
        $msg .= 'Updating competencies in all frameworks without an idnumber.';
    } else {
        $msg .= 'Updating competencies in framework "' . $options['framework'] . '".';
    }


    cli_writeln($msg);
    foreach ($frameworks as $fw) {
        $fw_id = $fw->id;
        competency::update_assignment_availabilities($availabilities, $fw_id);
    }
} else {
    $msg .= 'Updating all competencies.';
    cli_writeln($msg);
    competency::update_assignment_availabilities($availabilities);
}

$difftime = microtime_diff($starttime, microtime());
cli_writeln("Finished. Update took $difftime seconds");
