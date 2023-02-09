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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

use core\entity\cohort;
use hierarchy_organisation\entity\organisation;
use hierarchy_position\entity\position;
use core\entity\user;
use totara_competency\user_groups;
use totara_competency\entity\assignment;
use totara_hierarchy\entity\competency;

define('CLI_SCRIPT', 1);

require __DIR__ . '/../../server/config.php';

/** @var core_config $CFG */

require_once($CFG->libdir . '/clilib.php');         // cli only functions

list($options, $unrecognized) = cli_get_params(
    [
        'status' => 1,
        'number' => 1,
        'user-group-type' => user_groups::POSITION,
        'help'   => false
    ],
    [
        'h' => 'help',
        's' => 'status',
        'n' => 'number',
        'u' => 'user-group-type',
    ]
);


if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
        "Create random assignments.

Usage:

    php create_random_assignments.php [--status=NUMBER] [--number=NUMBER]

Options:
-s, --status=NUMBER                     The status the random entries should get, defaults to 1
-n, --number=NUMBER                     The number of entries to create, if not specified one entry is created
-u, --user-group-type=user|coh|aud|(pos)|org     Create entries with user groups of specified type, defaults to position
-h, --help         Print out this help
";
    echo $help;
    die;
}

$user_group_types = [
    'user' => user_groups::USER,
    'coh'  => user_groups::COHORT,
    'aud'  => user_groups::COHORT,
    'pos'  => user_groups::POSITION,
    'org'  => user_groups::ORGANISATION
];

$user_group_type = $options['user-group-type'];
$number = $options['number'];
$status = $options['status'];

cli_writeln("Creating {$options['number']} random assignments for user group type '{$user_group_types[$user_group_type]}'");

global $DB;

$USER = get_admin();

for ($i = 0; $i < $number; $i++) {
    switch ($user_group_type) {
        case 'user':
            $user_group = user::repository()
                ->order_by_raw('random()')
                ->first();
            break;
        case 'pos':
            $user_group = position::repository()
                ->order_by_raw('random()')
                ->first();
            break;
        case 'org':
            $user_group = organisation::repository()
                ->order_by_raw('random()')
                ->first();
            break;
        case 'coh':
        case 'aud':
            $user_group = cohort::repository()
                ->order_by_raw('random()')
                ->first();
            break;
        default:
            cli_error('Invalid user group type given, only \'user\', \'pos\', \'org\', \'coh\'');
            break;
    }

    $competency = competency::repository()
        ->order_by_raw('random()')
        ->first();

    if (empty($user_group)) {
        cli_error("No user group of specified type '{$user_group_type}' found'");
    }

    if (empty($user_group)) {
        cli_error("No competency found in the system");
    }

    $assignment = new assignment();
    $assignment->competency_id = $competency->id;
    $assignment->user_group_type = $user_group_types[$user_group_type];
    $assignment->user_group_id = $user_group->id;
    $assignment->type = assignment::TYPE_ADMIN;
    $assignment->status = $status;
    $assignment->created_at = time();
    $assignment->updated_at = time();
    $assignment->created_by = $USER->id;
    $assignment->save();
}