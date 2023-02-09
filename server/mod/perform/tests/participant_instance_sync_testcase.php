<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use core\entity\user;
use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance;
use mod_perform\expand_task;
use mod_perform\models\activity\track;
use mod_perform\state\activity\draft;
use mod_perform\state\participant_instance\availability_not_applicable;
use mod_perform\state\participant_instance\closed;
use mod_perform\state\participant_instance\complete;
use mod_perform\state\participant_instance\open;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\testing\generator as perform_generator;
use totara_core\relationship\relationship;
use totara_job\entity\job_assignment as job_assignment_entity;
use totara_job\job_assignment;

/**
 * Class participant_instance_creation_service_test
 *
 * @group perform
 */
abstract class mod_perform_participant_instance_sync_testcase extends testcase {

    public static $all_relationships = [
        constants::RELATIONSHIP_SUBJECT => true,
        constants::RELATIONSHIP_MANAGER => true,
        constants::RELATIONSHIP_MANAGERS_MANAGER => true,
        constants::RELATIONSHIP_DIRECT_REPORT => true,
        constants::RELATIONSHIP_APPRAISER => true,
    ];

    /**
     * @param int $user_id
     * @return array
     */
    protected function add_manager_for_user(int $user_id): array {
        /** @var user $user */
        $user = user::repository()->find($user_id);
        $manager = self::getDataGenerator()->create_user();
        /** @var job_assignment $manager_ja */
        $manager_ja = job_assignment::create_default($manager->id);
        job_assignment::create_default($user->id,
            [
                'managerjaid' => $manager_ja->id
            ]
        );
        return [$manager, $manager_ja];
    }

    /**
     * @param job_assignment $job_assignment
     * @return array
     */
    protected function add_manager_for_job_assignment(job_assignment $job_assignment): array {
        $manager = self::getDataGenerator()->create_user();
        /** @var job_assignment $manager_ja */
        $manager_ja = job_assignment::create_default($manager->id);
        $job_assignment->update([
            'managerjaid' => $manager_ja->id
        ]);
        return [$manager, $manager_ja];
    }

    /**
     * @param int $user_id
     * @return array
     */
    protected function add_direct_report_for_user(int $user_id): array {
        $direct_report = self::getDataGenerator()->create_user();
        /** @var job_assignment $manager_ja */
        $manager_ja = job_assignment::create_default($user_id);
        $direct_report_ja = job_assignment::create_default($direct_report->id, [
            'managerjaid' => $manager_ja->id
        ]);
        return [$direct_report, $direct_report_ja];
    }

    /**
     * @param int $user_id
     * @return array
     */
    protected function add_appraiser_for_user(int $user_id): array {
        $appraiser = self::getDataGenerator()->create_user();
        /** @var job_assignment $user_ja */
        $user_ja = job_assignment::create_default($user_id, [
            'appraiserid' => $appraiser->id
        ]);
        return [$appraiser, $user_ja];
    }

    /**
     * @param int $user_id
     * @param int|null $new_manager_id
     * @return void
     */
    protected function replace_manager(int $user_id, ?int $new_manager_id): void {
        $user_ja = job_assignment_entity::repository()
            ->where('userid', $user_id)
            ->one(true);

        $new_manager_ja_id = $new_manager_id ? job_assignment::create_default($new_manager_id)->id : null;

        $job_assignment = job_assignment::from_entity($user_ja);
        $job_assignment->update([
            'managerjaid' => $new_manager_ja_id
        ]);
    }
    /**
     * @param int $user_id
     * @param int|null $new_appraiser_id
     * @return void
     */
    protected function replace_appraiser(int $user_id, ?int $new_appraiser_id): void {
        $user_ja = job_assignment_entity::repository()
            ->where('userid', $user_id)
            ->one(true);

        $job_assignment = job_assignment::from_entity($user_ja);
        $job_assignment->update([
            'appraiserid' => $new_appraiser_id
        ]);
    }

    /**
     * @param int $user_id
     * @return void
     */
    protected function remove_manager(int $user_id): void {
        $this->replace_manager($user_id, null);
    }
    /**
     * @param int $user_id
     * @return void
     */
    protected function remove_appraiser(int $user_id): void {
        $this->replace_appraiser($user_id, null);
    }

    /**
     * Create an activity with given relationships. Optionally fill relationships with created users.
     * Also activates the activity and runs the tasks to create all the subject instances.
     *
     * @param array|null $relationships  array of [relationship-key => (bool)create a participant for that relationship]
     * @param array $view_only_relationships
     * @param bool $assign_all_created_users_as_subjects
     * @return array
     */
    protected function create_activity_with_relationships(
        ?array $relationships = null,
        array $view_only_relationships = [],
        bool $assign_all_created_users_as_subjects = false
    ): array {
        // Create all relationships by default and fill them with users.
        if (is_null($relationships)) {
            $relationships = self::$all_relationships;
        }
        self::setAdminUser();
        $generator = perform_generator::instance();

        [
            $subject,
            $other_subject,
            $manager,
            $managers_manager,
            $direct_report,
            $appraiser,
        ] = $this->create_users(6);

        /** @var job_assignment $managers_manager_ja */
        $managers_manager_ja = job_assignment::create_default($managers_manager->id);

        $managers_manager_ja_id = isset($relationships[constants::RELATIONSHIP_MANAGERS_MANAGER])
            && $relationships[constants::RELATIONSHIP_MANAGERS_MANAGER] === true
            ? $managers_manager_ja->id
            : null;
        /** @var job_assignment $manager_ja */
        $manager_ja = job_assignment::create_default($manager->id,
            [
                'managerjaid' => $managers_manager_ja_id,
            ]
        );

        $appraiser_id = isset($relationships[constants::RELATIONSHIP_APPRAISER])
            && $relationships[constants::RELATIONSHIP_APPRAISER] === true
            ? $appraiser->id
            : null;
        $manager_ja_id = isset($relationships[constants::RELATIONSHIP_MANAGER])
            && $relationships[constants::RELATIONSHIP_MANAGER] === true
            ? $manager_ja->id
            : null;

        /** @var job_assignment $subject_ja */
        $subject_ja = job_assignment::create_default($subject->id,
            [
                'managerjaid' => $manager_ja_id,
                'appraiserid' => $appraiser_id,
            ]
        );
        if (isset($relationships[constants::RELATIONSHIP_DIRECT_REPORT])
            && $relationships[constants::RELATIONSHIP_DIRECT_REPORT] === true) {
            job_assignment::create_default($direct_report->id,
                [
                    'managerjaid' => $subject_ja->id
                ]
            );
        }

        $activity = $generator->create_activity_in_container([
            'create_track' => false,
            'create_section' => false,
            'activity_status' => draft::get_code()
        ]);

        $track = track::create($activity, "test track");
        $subjects = $assign_all_created_users_as_subjects
            ? [$subject->id, $other_subject->id, $manager->id, $managers_manager->id, $appraiser->id, $direct_report->id]
            : [$subject->id, $other_subject->id];
        $generator->create_track_assignments_with_existing_groups($track, [], [], [], $subjects);
        $element1 = $generator->create_element(['title' => 'Question one', 'plugin_name' => 'short_text']);
        $section1 = $generator->create_section($activity, ['title' => 'Section 1']);
        $generator->create_section_element($section1, $element1);
        foreach ($relationships as $relationship => $should_be_filled) {
            $can_answer = !in_array($relationship, $view_only_relationships);
            $generator->create_section_relationship($section1, ['relationship' => $relationship], true, $can_answer);
        }
        $activity->activate();

        expand_task::create()->expand_all();
        (new subject_instance_creation())->generate_instances();

        $created_users = [
            $subject->id => constants::RELATIONSHIP_SUBJECT,
            $manager->id => constants::RELATIONSHIP_MANAGER,
            $managers_manager->id => constants::RELATIONSHIP_MANAGERS_MANAGER,
            $direct_report->id => constants::RELATIONSHIP_DIRECT_REPORT,
            $appraiser->id => constants::RELATIONSHIP_APPRAISER,
        ];
        $participants_created = array_filter(
            $created_users,
            static function (string $relationship) use ($relationships) {
                return array_key_exists($relationship, $relationships)
                    && $relationships[$relationship] === true;
            }
        );

        $participants_view_only = array_filter(
            $created_users,
            static function ($relationship) use ($view_only_relationships) {
                return in_array($relationship, $view_only_relationships);
            }
        );

        $participants_answering = array_diff($participants_created, $participants_view_only);

        $this->assertEquals($assign_all_created_users_as_subjects ? 6 : 2, subject_instance::repository()->count());
        $this->assert_participant_instances_count($subject->id, count($participants_created));
        $this->assert_open_participant_instances($subject->id, $participants_answering);
        $this->assert_view_only_participant_instances($subject->id, $participants_view_only);

        return [
            'participants' => $participants_created,
            'other_subject_id' => $other_subject->id,
            'activity' => $activity,
        ];
    }

    protected function assert_participant_instances_count(int $subject_user_id, int $expected_count): void {
        $this->assertEquals(
            $expected_count,
            participant_instance::repository()
                ->join([subject_instance::TABLE, 'si'], 'subject_instance_id', 'si.id')
                ->where('si.subject_user_id', $subject_user_id)
                ->count()
        );
    }

    protected function assert_view_only_participant_instances(int $subject_user_id, array $expected_users): void {
        $this->assert_participant_instances($subject_user_id, $expected_users, availability_not_applicable::get_code());
    }

    protected function assert_open_participant_instances(int $subject_user_id, array $expected_users): void {
        $this->assert_participant_instances($subject_user_id, $expected_users, open::get_code());
    }

    protected function assert_closed_participant_instances(int $subject_user_id, array $expected_users): void {
        $this->assert_participant_instances($subject_user_id, $expected_users, closed::get_code());
    }

    /**
     * Check that expected participant instances exist in the given state for a list of user_id/relationship combinations.
     *
     * @param int $subject_user_id
     * @param array $expected_users array of $user_id => (string|string[])$relationship_idnumbers
     * @param $availability
     * @return void
     */
    protected function assert_participant_instances(int $subject_user_id, array $expected_users, $availability): void {
        $num_expected_participant_instances = 0;
        foreach ($expected_users as $user_id => $relationship_idnumbers) {
            if (!is_array($relationship_idnumbers)) {
                // Only one relationship for that user id expected.
                $relationship_idnumbers = [$relationship_idnumbers];
            }
            foreach ($relationship_idnumbers as $relationship_idnumber) {
                $num_expected_participant_instances ++;
                $relationship_id = relationship::load_by_idnumber($relationship_idnumber)->id;
                $this->assertTrue(
                    participant_instance::repository()
                        ->join([subject_instance::TABLE, 'si'], 'subject_instance_id', 'si.id')
                        ->where('si.subject_user_id', $subject_user_id)
                        ->where('participant_id', $user_id)
                        ->where('core_relationship_id', $relationship_id)
                        ->where('availability', $availability)
                        ->exists(),
                    "Could not find expected participant instance for relationship '{$relationship_idnumber}'"
                    . " and availability {$availability}"
                );
            }
        }
        $this->assertEquals(
            $num_expected_participant_instances,
            participant_instance::repository()
                ->join([subject_instance::TABLE, 'si'], 'subject_instance_id', 'si.id')
                ->where('si.subject_user_id', $subject_user_id)
                ->where('availability', $availability)
                ->count()
        );
    }

    /**
     * @param int $num_users
     * @return array
     */
    private function create_users(int $num_users): array {
        return array_map(static function () {
            return self::getDataGenerator()->create_user();
        }, range(1, $num_users));
    }

    /**
     * Set a participant_instance to complete by actually completing it (as opposed to just setting the
     * status value in the DB).
     *
     * @param int $participant_id
     * @throws coding_exception
     */
    protected function complete_participant_instance(int $participant_id): void {
        // The user must have only one participant instance when calling this.
        /** @var participant_instance $participant_instance_entity */
        $participant_instance_entity = participant_instance::repository()
            ->where('participant_id', $participant_id)
            ->one(true);

        perform_generator::instance()->complete_participant_instance($participant_instance_entity);

        // Make sure it worked.
        $participant_instance_entity->refresh();
        $this->assertEquals(complete::get_code(), $participant_instance_entity->progress);
    }

    /**
     * @param int $subject_user_id
     * @return subject_instance
     */
    protected function get_subject_instance(int $subject_user_id): subject_instance {
        // The user must have only one subject instance when calling this.
        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()
            ->where('subject_user_id', $subject_user_id)
            ->one(true);
        return $subject_instance;
    }

    /**
     * @return array
     */
    public function override_config_data_provider(): array {
        return [

            'no overrides, all globals set' => [
                [
                    'global_creation' => 1,
                    'global_closure' => 1,
                ],
                true,
                true,
            ],

            'no overrides, only global creation' => [
                [
                    'global_creation' => 1,
                    'global_closure' => 0,
                ],
                true,
                false,
            ],

            'no overrides, only global closure' => [
                [
                    'global_creation' => 0,
                    'global_closure' => 1,
                ],
                false,
                true,
            ],

            'no overrides, no globals set' => [
                [
                    'global_creation' => 0,
                    'global_closure' => 0,
                ],
                false,
                false,
            ],

            'only override flag - no change' => [
                [
                    'global_creation' => 1,
                    'global_closure' => 1,
                    'activity_override' => 1,
                ],
                true,
                true,
            ],

            'override creation (turn off)' => [
                [
                    'global_creation' => 1,
                    'global_closure' => 1,
                    'activity_override' => 1,
                    'activity_creation' => 0,
                    'activity_closure' => 1,
                ],
                false,
                true,
            ],

            'override closure (turn off)' => [
                [
                    'global_creation' => 1,
                    'global_closure' => 1,
                    'activity_override' => 1,
                    'activity_creation' => 1,
                    'activity_closure' => 0,
                ],
                true,
                false,
            ],

            'override creation (turn on)' => [
                [
                    'global_creation' => 0,
                    'global_closure' => 0,
                    'activity_override' => 1,
                    'activity_creation' => 1,
                    'activity_closure' => 0,
                ],
                true,
                false,
            ],

            'override closure (turn on)' => [
                [
                    'global_creation' => 0,
                    'global_closure' => 0,
                    'activity_override' => 1,
                    'activity_creation' => 0,
                    'activity_closure' => 1,
                ],
                false,
                true,
            ],

            'override flag turned off' => [
                [
                    'global_creation' => 0,
                    'global_closure' => 0,
                    'activity_override' => 0,
                    'activity_creation' => 1,
                    'activity_closure' => 1,
                ],
                false,
                false,
            ],
        ];
    }

}
