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
 * @package approvalform_simple
 */

use approvalform_simple\installer;
use mod_approval\plugininfo\approvalform;

define('CLI_SCRIPT', 1);

require __DIR__ . '/../../../server/config.php';

/** @var core_config $CFG */
require_once($CFG->dirroot . '/lib/clilib.php');

global $options;
[$options, $cli_unrecognized] = cli_get_params(
    ['help' => false,],
    ['h' => 'help',]
);

echo "\n";

if ($options['help']) {
    $self = 'dev/approval/simple/create_demo.php';
    echo "Simple approval workflow demo generator.

Use this script to create a site with a cohort, users, cohort
assignments, and a workflow based on the simple approvalform
plugin. Running the script multiple times creates multiple
workflows, but with the same cohort and user assignments.

Usage: php {$self} [options]

Options:
  --help             Show help screen

";
    exit(0);
}

echo "Creating a simple workflow, demo assignments, and a few draft and submitted applications.\n";

// Do stuff as admin user
core\session\manager::set_user(get_admin());

$installer = new installer();
try {
    /** @var \moodle_database $DB */
    $transaction = $DB->start_delegated_transaction();
    /** @var approvalform $plugin */
    $plugin = approvalform::from_plugin_name('simple');
    if (!$plugin->is_enabled()) {
        approvalform::enable_plugin('simple');
    }
    $cohort = $installer->install_demo_cohort();
    $workflow = $installer->install_demo_workflow($cohort, 'Simple');
    list($applicant, $ja) = $installer->install_demo_assignment($cohort);
    $installer->install_demo_applications($workflow, $applicant, $ja);
    $transaction->allow_commit();
} catch (Throwable $exception) {
    $transaction->rollback();
    exit("\nUnable to install workflow due to exception: {$exception->getMessage()}\n{$exception->getTraceAsString()}");
}

echo "Workflow, assignments, and applications setup complete! Any users created have password 'simple'.\n";