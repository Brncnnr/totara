<?php
define('CLI_SCRIPT', 1);
require __DIR__ . '/../../server/config.php';

/** @var core_config $CFG */

require_once($CFG->dirroot . '/lib/clilib.php');

use core\entity\user;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\expand_task;
use mod_perform\task\service\subject_instance_creation;

// Adjust these values
$num_activities = 10;
$num_tracks_per_activity = 10;
$users_per_user_group = 1000;
$do_expand = true;
$do_generate_instances = false;
/*
 * will result in:
 *
 * number of users created:          $num_activities * $users_per_user_group
 * number of track_user_assignments: $num_activities * $users_per_user_group * $num_tracks_per_activity
 */

core\session\manager::set_user(get_admin());

$generator = \mod_perform\testing\generator::instance();
$config = \mod_perform\testing\activity_generator_configuration::new()
    ->set_number_of_activities($num_activities)
    ->set_number_of_tracks_per_activity($num_tracks_per_activity)
    ->disable_subject_instances()
    ->set_number_of_users_per_user_group_type($users_per_user_group);
$generator->create_full_activities($config);

if ($do_expand) {
    expand_task::create()->expand_all();
}

if ($do_generate_instances) {
    (new subject_instance_creation())->generate_instances();
}

echo "\n\ncount user: " . user::repository()->count();
echo "\ncount track_user_assignment: " . track_user_assignment::repository()->count();
echo "\ncount subject_instances: " . subject_instance::repository()->count();
echo "\n\n";
