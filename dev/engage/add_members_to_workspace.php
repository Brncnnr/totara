<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
define('CLI_SCRIPT', true);
require_once(__DIR__ . "/../../server/config.php");

use core_container\factory;
use container_workspace\workspace;
use container_workspace\member\member;
use totara_core\advanced_feature;

global $CFG, $DB, $USER;
require_once("{$CFG->dirroot}/lib/clilib.php");

advanced_feature::require('container_workspace');

if ('development' !== $CFG->sitetype) {
    echo "This script is for development only\n";
    return 1;
}

[$options, $params] = cli_get_params(
    [
        'workspace_id' => null,
        'number' => 100,
        'help' => false
    ],
    [
        'w' => 'workspace_id',
        'n' => 'number',
        'h' => 'help'
    ]
);

if ($options['help']) {
    echo "
A script to add a number of members into the workspace. It will check for the total of the current users
within the system against the number provided from parameter to determine whether to add new users or not.

Usage:
    php dev/engage/add_members_to_workspace.php --workspace_id=15
    
Options:
    -h, --help              Print out this help
    -w, --workspace_id      The workspace which we want to add the members into it.
    -n, --number            The number of members that we want to add.
    ";

    return 0;
}

if (!isset($options['workspace_id'])) {
    echo "No workspace_id was set\n";
    return 1;
}

/** @var workspace $workspace */
$workspace = factory::from_id($options['workspace_id']);

if (!$workspace->is_typeof(workspace::get_type())) {
    echo "Cannot find the workspace from id '{$options['workspace_id']}'\n";
    return 1;
}

$number_of_user = 100;
if (isset($options['number'])) {
    $number_of_user = (int) $options['number'];
}

$parameters = [
    'guest_id' => $CFG->siteguest,
    'owner_id' => $workspace->get_user_id()
];

$total = $DB->count_records_sql(
    'SELECT COUNT(id) FROM "ttr_user" u WHERE u.id <> :guest_id AND u.id <> :owner_id',
    $parameters
);
$USER = get_admin();

if ($total < $number_of_user) {
    // There are not enough users
    $different = ($number_of_user - $total);

    $generator = \core\testing\generator::instance();

    for ($i = 0; $i < $different; $i++) {
        $generator->create_user();
    }
}

$user_ids = $DB->get_fieldset_sql(
    'SELECT id FROM "ttr_user" u WHERE u.id <> :guest_id AND u.id <> :owner_id',
    $parameters
);

foreach ($user_ids as $user_id) {
    echo "Adding member '{$user_id}' to workspace\n";
    member::added_to_workspace($workspace, $user_id, false, $USER->id);
}

return 0;