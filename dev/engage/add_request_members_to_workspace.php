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

use totara_core\advanced_feature;
use core_container\factory;
use container_workspace\workspace;
use container_workspace\member\member_request;

global $CFG, $DB;
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
        'h' => 'h'
    ]
);

if ($options['help']) {
    echo "
A script to add number of member requests into the workspace. It will create new users
within the system and create a request for those users to join the given workspace.

Usage:
    php dev/engage/add_request_members_to_workspace.php --workspace_id=42

Options:
    -h, --help              Print out this help
    -w, --workspace_id      The workspace which we want to add the members into it.
    -n, --number            The number of member requests that we want to add.
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
} else if ($workspace->is_public()) {
    echo "Cannot create requests for public workspace\n";
    return 1;
} else if ($workspace->is_hidden()) {
    echo "Cannot create requests for hidden workspace - as it just consumes time\n";
    return 1;
}

$number = $options['number'] ?? 100;

$generator = \core\testing\generator::instance();

for ($i = 0; $i < $number; $i++) {
    $user = $generator->create_user();
    echo "Adding request for user '{$user->id}'\n";
    member_request::create($workspace->get_id(), $user->id);
}

return 0;