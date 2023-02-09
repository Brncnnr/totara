<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use mod_approval\model\assignment\helper\assignment_approver_inheritance_builder;

define('CLI_SCRIPT', 1);

require __DIR__ . '/../../server/config.php';

/** @var core_config $CFG */
require_once($CFG->dirroot . '/lib/clilib.php');

global $options;
[$options, $cli_unrecognized] = cli_get_params(
    [
        'help' => false,
        'workflow_id' => null,
        'assignment_id' => null,
    ],
    [
        'h' => 'help',
        'w' => 'workflow_id',
        'a' => 'assignment_id',
    ]
);

if ($options['help']) {
    $self = 'dev/approval/sf182/rebuild_approvers_tree.php';
    echo "Rebuild an approval workflow approvers tree.

The approvers tree is an index of who the approvers are at each level, for each assignment.

Usage: php {$self} [options]

Options:
  -h, --help            Show help screen
  -w, --workflow_id     Workflow ID to rebuild approvers tree for, required
  -a, --assignment_id   Assignment ID of the root of the tree, defaults to workflow's default assignment

Example:
    php {$self} -w=<workflow_id> -a=<assignment_id>
";
    exit(0);
}

if (!isset($options['workflow_id'])) {
    cli_error("No workflow_id was set", 1);
}
$workflow = \mod_approval\model\workflow\workflow::load_by_id($options['workflow_id']);
cli_writeln("Loaded workflow {$workflow->get_workflow_type()->name} {$workflow->name}.");

if (!$workflow->is_any_active()) {
    cli_writeln("There are no active versions of this workflow, cannot continue.");
}
$workflow_version = $workflow->get_active_version();

if (!isset($options['assignment_id'])) {
    $assignment = $workflow->default_assignment;
    cli_writeln("Will rebuild approvers tree from default assignment {$assignment->name}.");
} else {
    $assignment = \mod_approval\model\assignment\assignment::load_by_id($options['assignment_id']);
    cli_writeln("Will rebuild approvers tree from assignment {$assignment->name}.");
}

core\session\manager::set_user(get_admin());

$inheritance_helper = new assignment_approver_inheritance_builder();
assignment_approver_inheritance_builder::$cli_mode = true;
$inheritance_helper->rebuild_tree_for_assignment($assignment, $workflow_version);

// EOF