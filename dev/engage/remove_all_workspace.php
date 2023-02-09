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

use container_workspace\loader\workspace\loader;
use container_workspace\query\workspace\query;
use container_workspace\workspace;
use container_workspace\local\workspace_helper;
use container_workspace\member\member;
use container_workspace\tracker\tracker;
use totara_core\advanced_feature;

global $CFG, $DB;
require_once("{$CFG->dirroot}/lib/clilib.php");

advanced_feature::require('container_workspace');

[$options, $params] = cli_get_params(
    [
        'user_id' => null,
        'help' => false
    ],
    [
        'u' => 'user_id',
        'h' => 'help'
    ]
);

if (!empty($options['help'])) {
    echo "
This is a script to delete all the related workspace to a specified user. And this script should be used in development
site only.

Usage:
    php dev/engage/remove_all_workspace.php -u=\"2\"
    
Options:
    -h, --help          Print out this help
    -u, --user_id       The target user that you want to delete it from
    ";

    return 0;
}

if (!isset($CFG->sitetype) || 'development' !== $CFG->sitetype) {
    echo "This script should only be used in 'development' mode\n";
    return 1;
}

if (!isset($options['user_id'])) {
    echo "User's id is not specified";
    return 1;
}

// This might take quite a bit of time.
core_php_time_limit::raise();
raise_memory_limit(MEMORY_HUGE);

$user_id = $options['user_id'];
$transaction = $DB->start_delegated_transaction();

$query = query::create_for_user($user_id);

while (true) {
    $paginator = loader::get_workspaces($query);
    $workspaces = $paginator->get_items()->all();

    /** @var workspace $workspace */
    foreach ($workspaces as $workspace) {
        $owner_id = $workspace->get_user_id();
        if ($owner_id == $user_id) {
            // This user is an owner. Therefore deleting the workspace
            // Note, if the workspace has not been marked for deletion, you should call first: $workspace->mark_as_deleted(true).
            try {
                workspace_helper::delete_workspace($workspace);
            } catch (Exception $exc) {
                echo "Unable to delete workspace, please check the workspace has been marked for deletion. " . $exc->getMessage();
            }
        } else {
            // A member of a workspace
            $workspace_id = $workspace->get_id();

            $member = member::from_user($user_id, $workspace_id);
            $member->delete($user_id);
        }
    }

    $next_cursor = $paginator->get_next_cursor();
    if (null === $next_cursor) {
        break;
    }

    $query->set_cursor($next_cursor);
}

// We need to reset tracker as well.
tracker::clear_all();

$transaction->allow_commit();

echo "Done \n";
return 0;