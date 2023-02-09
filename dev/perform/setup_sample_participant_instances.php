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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_perform
 */

define('CLI_SCRIPT', 1);

require __DIR__ . '/../../server/config.php';

/** @var core_config $CFG */

require_once($CFG->dirroot . '/lib/clilib.php');

echo "This script is for functionality testing and demo.\n";

//modify these values to change number of users created.
$no_of_users = 5;
$users_per_relationship = 5;

use mod_perform\constants;
use mod_perform\entity\activity\track_assignment;
use mod_perform\expand_task;
use mod_perform\models\activity\track;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\user_groups\grouping;
use totara_job\job_assignment;

// Do stuff as admin user
core\session\manager::set_user(get_admin());

/**
 * Class setup_data
 */
class setup_data {

    /**
     * Activity tree generated.
     *
     * @var stdClass
     */
    public $activity_tree;

    /**
     * Users per relationship.
     *
     * @var int
     */
    private $users_per_relationship;

    /**
     * Number of users to create participant instances for.
     *
     * @var int
     */
    private $no_of_users;

    /**
     * setup_data constructor.
     *
     * @param int $users_per_relationship
     * @param int $no_of_users
     */
    public function __construct(int $users_per_relationship, int $no_of_users) {
        $this->no_of_users = $no_of_users;
        $this->users_per_relationship = $users_per_relationship;
    }

    /**
     * Setup test pre-conditions.
     */
    public function setUp(): void {
        $activity_tree = $this->setup_activity();

        $this->setup_job_assignments();
        $this->activity_tree = $activity_tree;
    }

    /**
     * Get data generator.
     */
    private function getDataGenerator(): \core\testing\generator {
        return \core\testing\generator::instance();
    }

    /**
     * Setup activity details.
     *
     * @return stdClass
     */
    private function setup_activity(): stdClass {
        $generator = \mod_perform\testing\generator::instance();

        $activity_tree = new stdClass();
        $activity_tree->activity = $generator->create_activity_in_container();

        //create sections and add relationships to activity:
        $activity_tree->section = $generator->create_section($activity_tree->activity, ['title' => 'Test section 1']);
        $activity_tree->section_relationships = [];
        $activity_tree->section_relationships[] = $generator->create_section_relationship(
            $activity_tree->section,
            ['relationship' => constants::RELATIONSHIP_APPRAISER]
        );
        $activity_tree->section_relationships[] = $generator->create_section_relationship(
            $activity_tree->section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );

        /** @var track $track */
        $tracks = $generator->create_activity_tracks($activity_tree->activity);
        $activity_tree->track = $tracks->first();
        $activity_tree->track = $generator->create_track_assignments($activity_tree->track, 0, 0, 0, $this->no_of_users);

        return $activity_tree;
    }

    /**
     * Sets up job assignments and returns stats of job assignments created.
     *
     * @return void
     */
    private function setup_job_assignments(): void {
        $job_assignment_data = [];

        for ($i = 0; $i < $this->users_per_relationship; $i++) {
            $manager = $this->getDataGenerator()->create_user();
            $appraiser = $this->getDataGenerator()->create_user();

            $job_assignment_data[] = [
                'manager_ja_id' => job_assignment::create_default($manager->id)->id,
                'appraiser_id' => $appraiser->id,
            ];
        }

        $track_assignments = track_assignment::repository()
            ->where('user_group_type', grouping::USER)
            ->get();

        foreach ($track_assignments as $assignment) {
            foreach ($job_assignment_data as $key => $job_assignment_datum) {
                job_assignment::create(
                    [
                        'userid' => $assignment->user_group_id,
                        'idnumber' => $assignment->id . $key . microtime(),
                        'managerjaid' => $job_assignment_datum['manager_ja_id'],
                        'appraiserid' => $job_assignment_datum['appraiser_id'],
                    ]
                );
            }
        }
        expand_task::create()->expand_all();
    }
}

(new setup_data($users_per_relationship, $no_of_users))->setUp();

$expand_task = new subject_instance_creation();
$expand_task->generate_instances();
echo "\nSetup Script run complete!\n";
