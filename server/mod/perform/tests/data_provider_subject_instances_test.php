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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use core\orm\query\builder;
use core\pagination\offset_cursor;
use mod_perform\constants;
use mod_perform\data_providers\activity\subject_instance_for_participant;
use mod_perform\entity\activity\activity_type as activity_type_entity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\state\participant_instance\complete as participant_instance_complete;
use mod_perform\state\participant_instance\in_progress as participant_instance_in_progress;
use mod_perform\state\participant_instance\not_started as participant_instance_not_started;
use mod_perform\state\subject_instance\complete as subject_instance_complete;
use mod_perform\state\subject_instance\in_progress as subject_instance_in_progress;
use mod_perform\state\subject_instance\not_started as subject_instance_not_started;
use mod_perform\testing\generator;
use totara_job\job_assignment;

require_once(__DIR__ . '/subject_instance_testcase.php');

/**
 * @group perform
 */
class mod_perform_data_provider_subject_instances_testcase extends mod_perform_subject_instance_testcase {

    /**
     * Even unfiltered must only return activities the user is participating in.
     */
    public function test_get_unfiltered(): void {
        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->fetch()
            ->get();

        self::assertCount(2, $returned_subject_instances);

        self::assert_same_subject_instance(
            self::$about_someone_else_and_participating, $returned_subject_instances->first()
        ); // 538003

        self::assert_same_subject_instance(
            self::$about_user_and_participating, $returned_subject_instances->last()
        ); // 538001
    }

    /**
     * Hidden activities should be filtered out
     */
    public function test_get_excludes_hidden_courses(): void {
        // Hide one of the activities
        builder::table('course')
            ->where('id', self::$about_user_and_participating->get_activity()->course)
            ->update([
                'visible' => 0,
                'visibleold' => 0
            ]);

        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->fetch()
            ->get();

        self::assertCount(1, $returned_subject_instances);

        self::assert_same_subject_instance(
            self::$about_someone_else_and_participating, $returned_subject_instances->first()
        ); // 538003
    }

    /**
     * @dataProvider subject_instance_provider
     * @param callable $get_query_activity
     * @param bool $expected_to_be_return
     */
    public function test_get_by_subject_instance_id(callable $get_query_activity, bool $expected_to_be_return): void {
        /** @var subject_instance_model $query_activity */
        $query_activity = $get_query_activity();

        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->add_filters(['subject_instance_id' => $query_activity->get_id()])
            ->fetch()
            ->get();

        if ($expected_to_be_return) {
            self::assertCount(1, $returned_subject_instances);
            self::assert_same_subject_instance($query_activity, $returned_subject_instances->first());
        } else {
            self::assertCount(0, $returned_subject_instances);
        }
    }

    public function test_get_subject_instances_by_roles(): void {
        $generator = generator::instance();
        $data_provider = new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL);

        $subject_role = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $returned_subject_instances = $data_provider
            ->add_filters(['about_role' => $subject_role->id])
            ->fetch()
            ->get();

        self::assertCount(1, $returned_subject_instances);
        self::assert_same_subject_instance(self::$about_user_and_participating, $returned_subject_instances->first());

        $manager_role = $generator->get_core_relationship(constants::RELATIONSHIP_MANAGER);
        $returned_subject_instances = $data_provider
            ->add_filters(['about_role' => $manager_role->id])
            ->fetch()
            ->get();

        self::assertCount(1, $returned_subject_instances);
        self::assert_same_subject_instance(self::$about_someone_else_and_participating, $returned_subject_instances->first());

        $peer_role = $generator->get_core_relationship(constants::RELATIONSHIP_PEER);
        $returned_subject_instances = $data_provider
            ->add_filters(['about_role' => $peer_role->id])
            ->fetch()
            ->get();

        self::assertCount(0, $returned_subject_instances);
    }

    /**
     * Check that the result includes all participant instances not just the one for $user->id.
    */
    public function test_attaches_all_participant_instance(): void {
        $subject_role = generator::instance()->get_core_relationship(constants::RELATIONSHIP_SUBJECT);

        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->add_filters(['about_role' => $subject_role->id])
            ->fetch()
            ->get();

        $this->assertCount(1, $returned_subject_instances);

        /** @var subject_instance_model $returned_subject_instance */
        $returned_subject_instance = $returned_subject_instances->first();

        // Verify that there are two participant_instances for this subject_instance.
        $participant_instances = participant_instance_entity::repository()
            ->where('subject_instance_id', $returned_subject_instance->get_id())
            ->get();
        $this->assertCount(2, $participant_instances);

        // Verify that the participant_instance for the subject user is in the result.
        $subject_participant_instances = $participant_instances->filter('participant_id', self::$user->id);
        $returned_participant_instances = $returned_subject_instance->get_participant_instances();
        $this->assertCount(1, $subject_participant_instances);
        $this->assertCount(2, $returned_participant_instances);
        $this->assertContains($subject_participant_instances->first()->id, $returned_participant_instances->pluck('id'));
    }

    /**
     * Check that the 'perform_hide_incomplete_responses_closed_instances' setting is observed in the data provider.
     */
    public function test_hide_incomplete_responses(): void {
        $generator = generator::instance();

        // Close the subject instance which also closes the participant instances.
        self::$about_user_and_participating->manually_close();

        $subject_role = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);

        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->add_filters(['about_role' => $subject_role->id])
            ->fetch()
            ->get();
        $this->assertCount(1, $returned_subject_instances);

        /** @var subject_instance_model $returned_subject_instance */
        $returned_subject_instance = $returned_subject_instances->first();

        /** @var participant_instance_entity $participant_instance_subject */
        $participant_instance_subject = participant_instance_entity::repository()
            ->where('subject_instance_id', $returned_subject_instance->get_id())
            ->where('participant_id', self::$user->id)
            ->one(true);
        $participant_section_ids_subject = $participant_instance_subject->participant_sections->pluck('id');
        $this->assertCount(1, $participant_section_ids_subject);
        $participant_section_id_subject = $participant_section_ids_subject[0];

        /** @var participant_instance_entity $participant_instance_other_user */
        $participant_instance_other_user = participant_instance_entity::repository()
            ->where('subject_instance_id', $returned_subject_instance->get_id())
            ->where('participant_id', '<>', self::$user->id)
            ->one(true);
        $participant_section_ids_other_user = $participant_instance_other_user->participant_sections->pluck('id');
        $this->assertCount(1, $participant_section_ids_other_user);
        $participant_section_id_other_user = $participant_section_ids_other_user[0];

        $this->assert_participant_sections_returned([$participant_section_id_subject, $participant_section_id_other_user]);

        // Activate the setting. Only the subject's section should be returned now.
        set_config('perform_hide_incomplete_responses_closed_instances', 1);

        $this->assert_participant_sections_returned([$participant_section_id_subject]);
    }

    /**
     * @param array $expected_participant_section_ids
     * @return void
     */
    private function assert_participant_sections_returned(array $expected_participant_section_ids): void {
        $generator = generator::instance();
        $subject_role = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);

        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->add_filters(['about_role' => $subject_role->id])
            ->fetch()
            ->get();
        $this->assertCount(1, $returned_subject_instances);

        /** @var subject_instance_model $returned_subject_instance */
        $returned_subject_instance = $returned_subject_instances->first();
        $returned_participant_instances = $returned_subject_instance->get_participant_instances();
        $participant_section_ids = [];
        foreach ($returned_participant_instances as $participant_instance) {
            $participant_section_ids[] = $participant_instance->participant_sections->pluck('id');
        }
        $participant_section_ids = array_merge(...$participant_section_ids);
        $this->assertEqualsCanonicalizing(
            $expected_participant_section_ids,
            $participant_section_ids
        );
    }

    /**
     * @dataProvider cursor_size_provider
     * @param int $page_size
     * @param array $item_counts
     */
    public function test_with_pagination(int $page_size, array $item_counts): void {
        // Create activities
        $all_subject_instances = [self::$about_user_and_participating->id];

        // Remember we already have 1 - thus <
        for ($i = 1; $i < 4; $i++) {
            $si = self::perform_generator()->create_subject_instance([
                'activity_name' => "activity{$i}",
                'subject_user_id' => self::$user->id,
                'subject_is_participating' => true,
            ]);
            $all_subject_instances[] = $si->id;
        }

        // We order by created_at desc, id
        $expected_subject_instances = array_chunk(
            array_reverse($all_subject_instances),
            $page_size
        );
        // Just verifying test parameters here ...
        $this->assertSame(count($expected_subject_instances), count($item_counts));

        $cursor = offset_cursor::create()
            ->set_page(1)
            ->set_limit($page_size);

        $subject_role = generator::instance()->get_core_relationship(constants::RELATIONSHIP_SUBJECT);

        for ($i = 0, $item_count = count($item_counts); $i < $item_count; $i++) {
            $paginator = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
                ->add_filters(['about_role' => $subject_role->id])
                ->get_offset($cursor);

            $items = $paginator->get_items();
            $this->assertCount($item_counts[$i], $items);
            $actual_ids = $items->pluck('id');
            // Order should be the same
            $this->assertSame($expected_subject_instances[$i], $actual_ids);

            $cursor = $paginator->get_next_cursor();
        }

        $this->assertNull($cursor);
    }

    /**
     * Data provider for cursor sizes
     */
    public function cursor_size_provider() {
        return [
            ['page_size' => 1, 'item_counts' => [1, 1, 1, 1]],
            ['page_size' => 2, 'item_counts' => [2, 2]],
            ['page_size' => 3, 'item_counts' => [3, 1]],
            ['page_size' => 4, 'item_counts' => [4]],
            ['page_size' => 5, 'item_counts' => [4]],
        ];
    }

    public function test_get_by_activity_type(): void {
        $activity_types = activity_type_entity::repository()
            ->order_by('name')
            ->get();
        $activity_types = array_combine($activity_types->pluck('name'), $activity_types->pluck('id'));

        // Create a set for each activity type
        $instances = self::create_activities_for_all_types($activity_types);

        // Now filter on each type
        foreach ($activity_types as $type => $id) {
            $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
                ->add_filters(['activity_type' => $id])
                ->fetch()
                ->get();

            self::assertCount(2, $returned_subject_instances);
            self::assert_same_subject_instance(
                $instances[$type]['about_someone_else_and_participating'], $returned_subject_instances->first()
            );
            self::assert_same_subject_instance(
                $instances[$type]['about_user_and_participating'], $returned_subject_instances->last()
            );
        }
    }

    public function test_get_by_own_progress(): void {
        // Create a set for each activity type
        $activity_types = activity_type_entity::repository()
            ->order_by('name')
            ->get();
        $activity_types = array_combine($activity_types->pluck('name'), $activity_types->pluck('id'));
        $instances = self::create_activities_for_all_types($activity_types);

        // Progress some instances
        self::set_participant_instance_progress($instances['appraisal']['about_user_and_participating'], participant_instance_not_started::get_code());
        self::set_participant_instance_progress($instances['appraisal']['about_someone_else_and_participating'], participant_instance_in_progress::get_code());
        self::set_participant_instance_progress($instances['check-in']['about_user_and_participating'], participant_instance_in_progress::get_code());
        self::set_participant_instance_progress($instances['check-in']['about_someone_else_and_participating'], participant_instance_complete::get_code());
        self::set_participant_instance_progress($instances['feedback']['about_user_and_participating'], participant_instance_complete::get_code());
        self::set_participant_instance_progress($instances['feedback']['about_someone_else_and_participating'], participant_instance_complete::get_code());

        // Now test filter
        // Ordered descending ...
        $to_test = [
            participant_instance_not_started::get_name() => [
                $instances['appraisal']['about_user_and_participating'],
            ],
            participant_instance_in_progress::get_name() => [
                $instances['check-in']['about_user_and_participating'],
                $instances['appraisal']['about_someone_else_and_participating'],
            ],
            participant_instance_complete::get_name() => [
                $instances['feedback']['about_someone_else_and_participating'],
                $instances['feedback']['about_user_and_participating'],
                $instances['check-in']['about_someone_else_and_participating'],
            ],
        ];

        foreach ($to_test as $progress_value => $expected_results) {
            $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
                ->add_filters(['participant_progress' => $progress_value])
                ->fetch()
                ->get();

            self::assertCount(count($expected_results), $returned_subject_instances);
            foreach ($expected_results as $expected_si) {
                self::assert_same_subject_instance($expected_si, $returned_subject_instances->shift());
            }
        }
    }

    public function test_get_by_exclude_complete(): void {
        // Create a set for each activity type
        $activity_types = activity_type_entity::repository()
            ->order_by('name')
            ->get();
        $activity_types = array_combine($activity_types->pluck('name'), $activity_types->pluck('id'));
        $instances = self::create_activities_for_all_types($activity_types);

        // Progress some instances
        self::set_participant_instance_progress($instances['appraisal']['about_user_and_participating'], participant_instance_not_started::get_code());
        self::set_participant_instance_progress($instances['appraisal']['about_someone_else_and_participating'], participant_instance_in_progress::get_code());
        self::set_participant_instance_progress($instances['check-in']['about_user_and_participating'], participant_instance_in_progress::get_code());
        self::set_participant_instance_progress($instances['check-in']['about_someone_else_and_participating'], participant_instance_complete::get_code());
        self::set_participant_instance_progress($instances['feedback']['about_user_and_participating'], participant_instance_complete::get_code());
        self::set_participant_instance_progress($instances['feedback']['about_someone_else_and_participating'], participant_instance_complete::get_code());

        $to_test = [
            false => [
                $instances['feedback']['about_someone_else_and_participating'],
                $instances['feedback']['about_user_and_participating'],
                $instances['check-in']['about_someone_else_and_participating'],
                $instances['check-in']['about_user_and_participating'],
                $instances['appraisal']['about_someone_else_and_participating'],
                $instances['appraisal']['about_user_and_participating'],
            ],
            true => [
                $instances['check-in']['about_user_and_participating'],
                $instances['appraisal']['about_someone_else_and_participating'],
                $instances['appraisal']['about_user_and_participating'],
            ],
        ];

        foreach ($to_test as $exclude_complete => $expected_results) {
            $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
                ->add_filters(['exclude_complete' => $exclude_complete])
                ->fetch()
                ->get();

            self::assertCount(count($expected_results), $returned_subject_instances);
            foreach ($expected_results as $expected_si) {
                self::assert_same_subject_instance($expected_si, $returned_subject_instances->shift());
            }
        }

        // Check combination with other progress filter.
        $expected_results = [
            $instances['check-in']['about_user_and_participating'],
            $instances['appraisal']['about_someone_else_and_participating'],
        ];
        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->add_filters([
                'exclude_complete' => true,
                'participant_progress' => participant_instance_in_progress::get_name(),
            ])
            ->fetch()
            ->get();
        self::assertCount(2, $returned_subject_instances);
        foreach ($expected_results as $expected_si) {
            self::assert_same_subject_instance($expected_si, $returned_subject_instances->shift());
        }

        // progress = complete and exclude progress should have no results.
        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->add_filters([
                'exclude_complete' => true,
                'participant_progress' => participant_instance_complete::get_name(),
            ])
            ->fetch()
            ->get();
        self::assertCount(0, $returned_subject_instances);
    }

    public function test_get_by_overdue(): void {
        $activity_types = activity_type_entity::repository()
            ->order_by('name')
            ->get();
        $activity_types = array_combine($activity_types->pluck('name'), $activity_types->pluck('id'));

        // Create a set for each activity type
        $instances = self::create_activities_for_all_types($activity_types);

        // Set overdue and progress some instances
        self::set_subject_instance_progress($instances['appraisal']['about_user_and_participating'], subject_instance_not_started::get_code());
        self::set_participant_instance_progress($instances['appraisal']['about_user_and_participating'], participant_instance_not_started::get_code());

        self::set_subject_instance_progress($instances['appraisal']['about_someone_else_and_participating'], subject_instance_in_progress::get_code());
        self::set_participant_instance_progress($instances['appraisal']['about_someone_else_and_participating'], participant_instance_complete::get_code());

        self::set_subject_instance_progress($instances['check-in']['about_user_and_participating'], subject_instance_in_progress::get_code());
        self::set_subject_instance_due_date($instances['check-in']['about_user_and_participating'], strtotime("-1 day"));
        self::set_participant_instance_progress($instances['check-in']['about_user_and_participating'], participant_instance_in_progress::get_code());

        self::set_subject_instance_progress($instances['check-in']['about_someone_else_and_participating'], subject_instance_complete::get_code());
        self::set_subject_instance_due_date($instances['check-in']['about_someone_else_and_participating'], strtotime("-1 day"));
        self::set_participant_instance_progress($instances['check-in']['about_someone_else_and_participating'], participant_instance_complete::get_code());

        self::set_subject_instance_progress($instances['feedback']['about_user_and_participating'], subject_instance_not_started::get_code());
        self::set_subject_instance_due_date($instances['feedback']['about_user_and_participating'], strtotime("+1 day"));
        self::set_participant_instance_progress($instances['feedback']['about_user_and_participating'], participant_instance_not_started::get_code());

        self::set_subject_instance_progress($instances['feedback']['about_someone_else_and_participating'], subject_instance_in_progress::get_code());
        self::set_subject_instance_due_date($instances['feedback']['about_someone_else_and_participating'], strtotime("+1 day"));
        self::set_participant_instance_progress($instances['feedback']['about_someone_else_and_participating'], participant_instance_not_started::get_code());

        // Now test filters
        // Ordered descending ...
        $to_test = [
            [
                'filters' => [
                    'activity_type' => $activity_types['check-in'],
                    'participant_progress' => participant_instance_complete::get_name(),
                ],
                'expected' => [
                    $instances['check-in']['about_someone_else_and_participating'],
                ],
            ],
            [
                'filters' => [
                    'activity_type' => $activity_types['check-in'],
                    'participant_progress' => participant_instance_complete::get_name(),
                    'overdue' => 1,
                ],
                'expected' => [],
            ],
            [
                'filters' => [
                    'activity_type' => $activity_types['check-in'],
                    'participant_progress' => participant_instance_complete::get_name(),
                    'overdue' => 0,
                ],
                'expected' => [
                    $instances['check-in']['about_someone_else_and_participating'],
                ],
            ],
            [
                'filters' => [
                    'participant_progress' => participant_instance_not_started::get_name(),
                    'overdue' => 0,
                ],
                'expected' => [
                    $instances['feedback']['about_someone_else_and_participating'],
                    $instances['feedback']['about_user_and_participating'],
                    $instances['appraisal']['about_user_and_participating'],
                ],
            ],
        ];

        foreach ($to_test as $data) {
            $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
                ->add_filters($data['filters'])
                ->fetch()
                ->get();
            self::assertCount(count($data['expected']), $returned_subject_instances);
            foreach ($data['expected'] as $expected_si) {
                self::assert_same_subject_instance($expected_si, $returned_subject_instances->shift());
            }
        }
    }

    public function test_combined_filters(): void {
        $activity_types = activity_type_entity::repository()
            ->order_by('name')
            ->get();
        $activity_types = array_combine($activity_types->pluck('name'), $activity_types->pluck('id'));

        // Create a set for each activity type
        $instances = self::create_activities_for_all_types($activity_types);

        // Set overdue and progress some instances
        self::set_subject_instance_progress($instances['appraisal']['about_user_and_participating'], subject_instance_not_started::get_code());
        self::set_subject_instance_progress($instances['appraisal']['about_someone_else_and_participating'], subject_instance_in_progress::get_code());

        self::set_subject_instance_progress($instances['check-in']['about_user_and_participating'], subject_instance_in_progress::get_code());
        self::set_subject_instance_due_date($instances['check-in']['about_user_and_participating'], strtotime("-1 day"));
        self::set_subject_instance_progress($instances['check-in']['about_someone_else_and_participating'], subject_instance_complete::get_code());
        self::set_subject_instance_due_date($instances['check-in']['about_someone_else_and_participating'], strtotime("-1 day"));

        self::set_subject_instance_progress($instances['feedback']['about_user_and_participating'], subject_instance_not_started::get_code());
        self::set_subject_instance_due_date($instances['feedback']['about_user_and_participating'], strtotime("+1 day"));
        self::set_subject_instance_progress($instances['feedback']['about_someone_else_and_participating'], subject_instance_in_progress::get_code());
        self::set_subject_instance_due_date($instances['feedback']['about_someone_else_and_participating'], strtotime("+1 day"));

        // Now test filter
        // Ordered descending ...
        $to_test = [
            1 => [
                $instances['check-in']['about_user_and_participating'],
            ],
            0 => [
                $instances['feedback']['about_someone_else_and_participating'],
                $instances['feedback']['about_user_and_participating'],
                $instances['check-in']['about_someone_else_and_participating'],
                $instances['check-in']['about_user_and_participating'],
                $instances['appraisal']['about_someone_else_and_participating'],
                $instances['appraisal']['about_user_and_participating'],
            ],
        ];

        foreach ($to_test as $is_overdue => $expected_results) {
            $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
                ->add_filters(['overdue' => $is_overdue])
                ->fetch()
                ->get();
            self::assertCount(count($expected_results), $returned_subject_instances);
            foreach ($expected_results as $expected_si) {
                self::assert_same_subject_instance($expected_si, $returned_subject_instances->shift());
            }
        }
    }

    public function search_data_provider(): array {
        // Activity names are:
        // "appraisal activity about someone else and participating"
        // "appraisal activity about target user"
        // Target user first/middle/last name is: "Actinguser Manfred Ziller"
        // Other user first/middle/last name is: "Otheruser Testfred Nontarget"
        return [
            // Empty search.
            ['', ['other', 'self']],
            // Matches beginning of both activity names.
            ['appraisal activity about', ['other', 'self']],
            // Matches substring of both activity names.
            ['activity about', ['other', 'self']],
            // Matches substring of one activity.
            ['about s', ['other']],
            // Matches substring of other activity.
            ['about t', ['self']],
            // Not supporting multiple substrings.
            ['appraisal about', []],
            // Matches just the target user name.
            ['Ziller', ['self']],
            // Matches just the other user name.
            ['Nontarget', ['other']],
            // Matches nothing.
            ['notamatchatall', []],
            // Matches one user name and one activity name
            ['target', ['other', 'self']],
            // Search is case-insensitive.
            ['tArGeT', ['other', 'self']],
            // Matches both middle names, but middle names are not configured for fullname by default
            ['fred', []],
            // Matches both middle names when config has middlename
            ['fred', ['other', 'self'], 'firstname middlename lastname'],
            // Matches complete fullname including middlename
            ['Actinguser Manfred Ziller', ['self'], 'firstname middlename lastname'],
        ];
    }

    /**
     * @dataProvider search_data_provider
     * @param string $search_term
     * @param array $expected_results
     * @param string|null $fullname_config
     */
    public function test_get_by_search_term(string $search_term, array $expected_results, ?string $fullname_config = null): void {
        global $CFG;

        $original_fullnamedisplay = $CFG->fullnamedisplay;
        if ($fullname_config) {
            $CFG->fullnamedisplay = $fullname_config;
        }

        $expected_subject_instances = array_map(static function ($expected_result) {
            return 'other' === $expected_result
                ? self::$about_someone_else_and_participating
                : self::$about_user_and_participating;
        }, $expected_results);

        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->add_filters([
                'search_term' => $search_term
            ])
            ->fetch()
            ->get();
        self::assertCount(count($expected_subject_instances), $returned_subject_instances);
        foreach ($expected_subject_instances as $expected_si) {
            self::assert_same_subject_instance($expected_si, $returned_subject_instances->shift());
        }

        $CFG->fullnamedisplay = $original_fullnamedisplay;
    }

    public function test_sort_by(): void {
        global $CFG;

        $activity_types = activity_type_entity::repository()
            ->order_by('name')
            ->get();
        $activity_types = array_combine($activity_types->pluck('name'), $activity_types->pluck('id'));
        $instances = self::create_activities_for_all_types($activity_types);

        // Sort by activity name.
        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->sort_by('activity_name')
            ->fetch()
            ->get();
        $this->assert_order([
            'appraisal activity about someone else and participating',
            'appraisal activity about target user',
            'check-in activity about someone else and participating',
            'check-in activity about target user',
            'feedback activity about someone else and participating',
            'feedback activity about target user',
        ], $returned_subject_instances);

        // Sort by creation date.
        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->sort_by('created_at')
            ->fetch()
            ->get();
        // This is the reverse order in which they were inserted.
        $sorted_by_creation_date = [
            'feedback activity about someone else and participating',
            'feedback activity about target user',
            'check-in activity about someone else and participating',
            'check-in activity about target user',
            'appraisal activity about someone else and participating',
            'appraisal activity about target user',
        ];
        $this->assert_order($sorted_by_creation_date, $returned_subject_instances);
        // Default is also sort by creation date, so must be the same.
        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->fetch()
            ->get();
        $this->assert_order($sorted_by_creation_date, $returned_subject_instances);

        // Sort by subject name.
        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->sort_by('subject_name')
            ->fetch()
            ->get();
        $this->assert_order([
            'feedback activity about target user', // Actinguser Manfred Ziller
            'check-in activity about target user', // Actinguser Manfred Ziller
            'appraisal activity about target user', // Actinguser Manfred Ziller
            'feedback activity about someone else and participating', // Otheruser Testfred Nontarget
            'check-in activity about someone else and participating', // Otheruser Testfred Nontarget
            'appraisal activity about someone else and participating', // Otheruser Testfred Nontarget
        ], $returned_subject_instances);

        // Sorting is sensitive to fullname config.
        $original_fullnamedisplay = $CFG->fullnamedisplay;
        $CFG->fullnamedisplay = 'lastname, firstname';
        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->sort_by('subject_name')
            ->fetch()
            ->get();
        $this->assert_order([
            'feedback activity about someone else and participating', // Otheruser Testfred Nontarget
            'check-in activity about someone else and participating', // Otheruser Testfred Nontarget
            'appraisal activity about someone else and participating', // Otheruser Testfred Nontarget
            'feedback activity about target user', // Actinguser Manfred Ziller
            'check-in activity about target user', // Actinguser Manfred Ziller
            'appraisal activity about target user', // Actinguser Manfred Ziller
        ], $returned_subject_instances);
        $CFG->fullnamedisplay = $original_fullnamedisplay;

        // Sort by due date.
        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->sort_by('due_date')
            ->fetch()
            ->get();
        // Due date is not set for any instance, so it will be sorted by creation time, oldest first.
        $this->assert_order(array_reverse($sorted_by_creation_date), $returned_subject_instances);
        // Adjust some due dates to have a different expected order.
        self::set_subject_instance_due_date($instances['check-in']['about_user_and_participating'], strtotime("-1 day"));
        self::set_subject_instance_due_date($instances['appraisal']['about_someone_else_and_participating'], strtotime("-1 week"));
        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->sort_by('due_date')
            ->fetch()
            ->get();
        $this->assert_order([
            'appraisal activity about someone else and participating',
            'check-in activity about target user',
            'appraisal activity about target user',
            'check-in activity about someone else and participating',
            'feedback activity about target user',
            'feedback activity about someone else and participating',
        ], $returned_subject_instances);

        // Sort by job assignment
        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->sort_by('job_assignment')
            ->fetch()
            ->get();
        // Job assignment is not set for any instance, so it will be sorted by creation time.
        $this->assert_order($sorted_by_creation_date, $returned_subject_instances);

        // Create job assignments and assign them to subject instances.
        /** @var job_assignment $a_ja */
        $a_ja = job_assignment::create_default(self::$user->id, ['fullname' => 'AAA job assignment']);
        /** @var job_assignment $z_ja */
        $z_ja = job_assignment::create_default(self::$user->id, ['fullname' => 'ZZZ job assignment']);

        $si = new subject_instance_entity($instances['check-in']['about_user_and_participating']->get_id());
        $si->job_assignment_id = $a_ja->id;
        $si->save();
        $si = new subject_instance_entity($instances['feedback']['about_user_and_participating']->get_id());
        $si->job_assignment_id = $z_ja->id;
        $si->save();

        $returned_subject_instances = (new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL))
            ->sort_by('job_assignment')
            ->fetch()
            ->get();
        $this->assert_order([
            'check-in activity about target user',
            'feedback activity about target user',
            'feedback activity about someone else and participating',
            'check-in activity about someone else and participating',
            'appraisal activity about someone else and participating',
            'appraisal activity about target user',
        ], $returned_subject_instances);
    }

    public function test_sort_options(): void {
        $sort_options = subject_instance_for_participant::$sort_options;
        self::assertEqualsCanonicalizing([
            'created_at',
            'activity_name',
            'subject_name',
            'job_assignment',
            'due_date',
        ], $sort_options);

        /** @var core_string_manager $string_manager */
        $string_manager = get_string_manager();
        foreach (subject_instance_for_participant::$sort_options as $sort_option) {
            self::assertTrue($string_manager->string_exists('user_activities_sort_option_' . $sort_option, 'mod_perform'));
            self::assertTrue(method_exists(subject_instance_for_participant::class, 'sort_query_by_' . $sort_option));
        }
    }

    private function assert_order(array $expected_activity_order, collection $actual_subject_instances) {
        $activity_names = array_map(static function (subject_instance_model $subject_instance) {
            return $subject_instance->activity->name;
        }, $actual_subject_instances->all());
        self::assertSame($expected_activity_order, $activity_names);
    }

    public function test_overdue_count(): void {
        $activity_types = activity_type_entity::repository()
            ->order_by('name')
            ->get();
        $activity_types = array_combine($activity_types->pluck('name'), $activity_types->pluck('id'));

        // Create a set for each activity type
        $instances = self::create_activities_for_all_types($activity_types);

        $provider = new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL);
        $actual_count = $provider->get_overdue_count();
        $this->assertSame(0, $actual_count);

        // Set overdue
        self::set_subject_instance_due_date($instances['check-in']['about_user_and_participating'], strtotime("-1 day"));
        self::set_subject_instance_due_date($instances['appraisal']['about_user_and_participating'], strtotime("-1 day"));

        $actual_count = $provider->get_overdue_count();
        $this->assertSame(2, $actual_count);

        $generator = generator::instance();
        $subject_role = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $provider = new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL);
        $provider->add_filters(['about_role' => $subject_role->id]);
        $actual_count = $provider->get_overdue_count();
        $this->assertSame(2, $actual_count);

        $manager_role = $generator->get_core_relationship(constants::RELATIONSHIP_MANAGER);
        $provider = new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL);
        $provider->add_filters(['about_role' => $manager_role->id]);
        $actual_count = $provider->get_overdue_count();
        $this->assertSame(0, $actual_count);
    }

    public function test_completed_count(): void {
        $activity_types = activity_type_entity::repository()
            ->order_by('name')
            ->get();
        $activity_types = array_combine($activity_types->pluck('name'), $activity_types->pluck('id'));

        // Create a set for each activity type
        $instances = self::create_activities_for_all_types($activity_types);

        $provider = new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL);
        $actual_count = $provider->get_completed_count();
        $this->assertSame(0, $actual_count);

        // Set overdue and progress some instances
        self::set_participant_instance_progress($instances['appraisal']['about_someone_else_and_participating'], participant_instance_complete::get_code());
        self::set_participant_instance_progress($instances['check-in']['about_someone_else_and_participating'], participant_instance_complete::get_code());

        $actual_count = $provider->get_completed_count();
        $this->assertSame(2, $actual_count);

        $generator = generator::instance();
        $subject_role = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $provider = new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL);
        $provider->add_filters(['about_role' => $subject_role->id]);
        $actual_count = $provider->get_completed_count();
        $this->assertSame(0, $actual_count);

        $manager_role = $generator->get_core_relationship(constants::RELATIONSHIP_MANAGER);
        $provider = new subject_instance_for_participant(self::$user->id, participant_source::INTERNAL);
        $provider->add_filters(['about_role' => $manager_role->id]);
        $actual_count = $provider->get_completed_count();
        $this->assertSame(2, $actual_count);
    }

    /**
     * @param array $activity_types
     * @return array
     * @throws coding_exception
     */
    protected static function create_activities_for_all_types(array $activity_types): array {
        // Create a set for each activity type
        // Initial instances are all 'appraisals'
        $instances = ['appraisal' =>
            [
                'about_user_and_participating' => self::$about_user_and_participating,
                'about_someone_else_and_participating' => self::$about_someone_else_and_participating,
                'about_user_but_not_participating' => self::$about_user_but_not_participating,
                'non_existing' => self::$non_existing,
            ]
        ];

        foreach ($activity_types as $type => $id) {
            if ($type === 'appraisal') {
                continue;
            }

            self::create_user_activities(self::$user, $type);
            $instances[$type] = [
                'about_user_and_participating' => self::$about_user_and_participating,
                'about_someone_else_and_participating' => self::$about_someone_else_and_participating,
                'about_user_but_not_participating' => self::$about_user_but_not_participating,
                'non_existing' => self::$non_existing,
            ];
        }
        return $instances;
    }

    /**
     * @param subject_instance_model $si
     * @param int $progress
     */
    protected static function set_participant_instance_progress(subject_instance_model $si, int $progress): void {
        $pi = participant_instance_entity::repository()
            ->where('subject_instance_id', $si->get_id())
            ->where('participant_id', self::$user->id)
            ->order_by('id')
            ->first();
        $pi->progress = $progress;
        $pi->save();
    }

    /**
     * @param subject_instance_model $si
     * @param int $progress
     */
    protected static function set_subject_instance_progress(subject_instance_model $si, int $progress): void {
        $si = new subject_instance_entity($si->get_id());
        $si->progress = $progress;
        $si->save();
    }

    /**
     * @param subject_instance_model $si
     * @param int $due_date
     */
    protected static function set_subject_instance_due_date(subject_instance_model $si, int $due_date): void {
        $si = new subject_instance_entity($si->get_id());
        $si->due_date = $due_date;
        $si->save();
    }
}