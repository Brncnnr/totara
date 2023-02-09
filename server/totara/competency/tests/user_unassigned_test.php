<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 * @subpackage test
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use pathway_manual\models\roles\self_role;
use totara_competency\entity\achievement_via;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\competency_assignment_user;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\expand_task;
use totara_competency\settings;
use totara_competency\task\competency_aggregation_all;
use totara_hierarchy\entity\scale_value;
use totara_job\job_assignment;

/**
 * @group totara_competency
 */
class totara_competency_user_unassigned_testcase extends testcase {

    /**
     * @test_it_unassigns_users_correctly_with_records_deletion
     */
    public function test_it_unassigns_users_correctly_with_records_deletion() {
        $data = $this->create_data();

        // Set tracking setting
        settings::unassign_delete_records();

        $user = $data['users'][0];
        $pos = $data['positions'][0];
        $ass = $data['assignments'][0];

        // We need to remove user from a position and check that the event has been fired
        // To remove user from a position we need to remove the related job assignment record.
        $job_assignment_record = builder::table('job_assignment')
            ->where('userid', $user->id)
            ->where('positionid', $pos->id)
            ->one();

        $job_assignment = job_assignment::get_with_id($job_assignment_record->id);
        job_assignment::delete($job_assignment);

        $achievement = competency_achievement::repository()
            ->where('user_id', $user->id)
            ->where('assignment_id', $ass->id)
            ->one(true);

        $data = $this->get_unrelated_data($user, $ass);

        $unrelated_data = $this->get_unrelated_data($user, $ass);

        // And that the data is gone...
        (new expand_task(builder::get_db()))->expand_single($ass->id);

        $this->assert_data_untouched($unrelated_data, $user, $ass);

        // Achievement
        $this->assertEquals(
            0,
            competency_achievement::repository()
                ->where('user_id', $user->id)
                ->where('assignment_id', $ass->id)
                ->count()
        );

        // Let's check that cascading kicked in and we don't have records keyed with the given achievement_id

        $this->assertEquals(
            0,
            achievement_via::repository()
            ->where('comp_achievement_id', $achievement->id)
            ->count()
        );

        // Log and user
        $this->assertEquals(
            0,
            competency_assignment_user::repository()
            ->where('assignment_id', $ass->id)
            ->where('user_id', $user->id)
            ->count()
        );

        // Log and user
        $this->assertEquals(
            0,
            competency_assignment_user_log::repository()
            ->where('assignment_id', $ass->id)
            ->where('user_id', $user->id)
            ->count()
        );
    }

    /**
     * @test_it_unassigns_users_correctly_with_records_keeping
     */
    public function test_it_unassigns_users_correctly_with_records_keeping() {
        $data = $this->create_data();

        // Set tracking setting
        settings::unassign_keep_records();

        $user = $data['users'][0];
        $pos = $data['positions'][0];
        $ass = $data['assignments'][0];

        $log_count = competency_assignment_user_log::repository()
            ->where('assignment_id', $ass->id)
            ->where('user_id', $user->id)
            ->count();

        $this->assertGreaterThan(0, $log_count);

        // We need to remove user from a position and check that the event has been fired
        // To remove user from a position we need to remove the related job assignment record.
        $job_assignment_record = builder::table('job_assignment')
            ->where('userid', $user->id)
            ->where('positionid', $pos->id)
            ->one();

        $job_assignment = job_assignment::get_with_id($job_assignment_record->id);
        job_assignment::delete($job_assignment);

        $achievement = competency_achievement::repository()
            ->where('user_id', $user->id)
            ->where('assignment_id', $ass->id)
            ->one(true);

        $unrelated_data = $this->get_unrelated_data($user, $ass);

        // And that the data is gone...
        (new expand_task(builder::get_db()))->expand_single($ass->id);

        $this->assert_data_untouched($unrelated_data, $user, $ass);

        // Achievement
        $this->assertEquals(
            1,
            competency_achievement::repository()
                ->where('user_id', $user->id)
                ->where('assignment_id', $ass->id)
                ->count()
        );

        // Let's check that cascading kicked in and we don't have records keyed with the given achievement_id
        $this->assertEquals(
            1,
            achievement_via::repository()
                ->where('comp_achievement_id', $achievement->id)
                ->count()
        );

        // Expand task worked correctly and the user has actually been unassigned
        $this->assertEquals(
            0,
            competency_assignment_user::repository()
                ->where('assignment_id', $ass->id)
                ->where('user_id', $user->id)
                ->count()
        );

        // We'd have more entries created by events
        $this->assertGreaterThan(
            $log_count,
            competency_assignment_user_log::repository()
                ->where('assignment_id', $ass->id)
                ->where('user_id', $user->id)
                ->count()
        );
    }

    /**
     * @test_it_unassigns_users_correctly_with_achieved_records_keeping
     */
    public function test_it_unassigns_users_correctly_with_achieved_records_keeping() {
        $data = $this->create_data();

        // Set tracking setting
        settings::unassign_delete_empty_records();

        $user = $data['users'][0];
        $pos = $data['positions'][0];
        $ass = $data['assignments'][0];

        // We fake achievement with null scale_value
        competency_achievement::repository()
            ->where('assignment_id', $ass->id)
            ->where('user_id', $user->id)
            ->update([
                'scale_value_id' => null
            ]);

        $this->assertGreaterThan(0, competency_assignment_user_log::repository()
            ->where('assignment_id', $ass->id)
            ->where('user_id', $user->id)
            ->count());

        // We need to remove user from a position and check that the event has been fired
        // To remove user from a position we need to remove the related job assignment record.
        $job_assignment_record = builder::table('job_assignment')
            ->where('userid', $user->id)
            ->where('positionid', $pos->id)
            ->one();

        $job_assignment = job_assignment::get_with_id($job_assignment_record->id);
        job_assignment::delete($job_assignment);

        $achievement = competency_achievement::repository()
            ->where('user_id', $user->id)
            ->where('assignment_id', $ass->id)
            ->one(true);

        $unrelated_data = $this->get_unrelated_data($user, $ass);

        // And that the data is gone...
        (new expand_task(builder::get_db()))->expand_single($ass->id);

        $this->assert_data_untouched($unrelated_data, $user, $ass);

        // Achievement
        $this->assertEquals(
            0,
            competency_achievement::repository()
                ->where('user_id', $user->id)
                ->where('assignment_id', $ass->id)
                ->count()
        );

        // Let's check that cascading kicked in and we don't have records keyed with the given achievement_id
        $this->assertEquals(
            0,
            achievement_via::repository()
                ->where('comp_achievement_id', $achievement->id)
                ->count()
        );

        // Expand task worked correctly and the user has actually been unassigned
        $this->assertEquals(
            0,
            competency_assignment_user::repository()
                ->where('assignment_id', $ass->id)
                ->where('user_id', $user->id)
                ->count()
        );

        // We'd have more entries created by events
        $this->assertEquals(
            0,
            competency_assignment_user_log::repository()
                ->where('assignment_id', $ass->id)
                ->where('user_id', $user->id)
                ->count()
        );
    }

    /**
     * @test_it_deletes_assignment_correctly
     */
    public function test_it_deletes_assignment_correctly() {
        $data = $this->create_data();

        $assignment = totara_competency\models\assignment::load_by_id($data['assignments'][0]->id);

        $achievements = $assignment->get_entity()->achievements;

        $this->assertGreaterThan(
            0,
            achievement_via::repository()
                ->where('comp_achievement_id', $achievements->pluck('id'))
                ->count()
        );

        $unrelated = competency_assignment_user::repository()
            ->where('assignment_id', '!=', $assignment->get_id())
            ->count();

        $this->assertGreaterThan(0, $unrelated);

        $unrelated_achievements = competency_achievement::repository()
            ->where('assignment_id', '!=', $assignment->get_id())
            ->count();


        $this->assertGreaterThan(
            0,
            competency_achievement::repository()
                ->where('assignment_id', $assignment->get_id())
                ->count()
        );

        $this->assertGreaterThan(
            0,
            competency_assignment_user::repository()
                ->where('assignment_id', $assignment->get_id())
                ->count()
        );

        $this->assertGreaterThan(
            0,
            competency_assignment_user_log::repository()
                ->where('assignment_id', $assignment->get_id())
                ->count()
        );

        // After deleting ID will be set to null
        $assignment_id = $assignment->get_id();
        $assignment->force_delete();

        $this->assertEquals(
            0,
            competency_assignment_user::repository()
                ->where('assignment_id', $assignment_id)
                ->count()
        );

        $this->assertEquals(
            0,
            competency_assignment_user_log::repository()
                ->where('assignment_id', $assignment_id)
                ->count()
        );

        $this->assertEquals(
            $unrelated,
            competency_assignment_user::repository()
                ->where('assignment_id', '!=', $assignment_id)
                ->count()
        );

        $this->assertEquals(
            $unrelated,
            competency_assignment_user::repository()
                ->where('assignment_id', '!=', $assignment_id)
                ->count()
        );

        $this->assertEquals(
            $unrelated_achievements,
            competency_achievement::repository()
                ->where('assignment_id', '!=', $assignment_id)
                ->count()
        );

        $this->assertEquals(
            0,
            achievement_via::repository()
                ->where('comp_achievement_id', $achievements->pluck('id'))
                ->count()
        );
    }

    /**
     * Create some data to check that the things we want to remove are removed and things that shouldn't be touched
     * are not touched.
     *
     * @return array
     */
    protected function create_data() {
        $comp = $this->generator()->create_competency();
        $another_comp = $this->generator()->create_competency();

        $user = $this->getDataGenerator()->create_user();
        $another_user = $this->getDataGenerator()->create_user();

        $pos = $this->generator()->assignment_generator()
            ->create_position_and_add_members([$user, $another_user]);

        $another_pos = $this->generator()->assignment_generator()
            ->create_position_and_add_members([$user, $another_user]);

        $ass = $this->generator()
            ->assignment_generator()
            ->create_position_assignment($comp->id, $pos->id);

        $another_ass = $this->generator()
            ->assignment_generator()
            ->create_position_assignment($comp->id, $another_pos->id);

        (new expand_task(builder::get_db()))->expand_multiple([$ass->id, $another_ass->id]);

        $manual_pathway = $this->generator()->create_manual($comp);

        $value = scale_value::repository()
            ->where('name', 'Competent')
            ->one();

        // Create a manual rating and aggregate
        $this->generator()->create_manual_rating($manual_pathway, $user, $user, self_role::class, $value);
        (new competency_aggregation_all())->execute();

        return [
            'users' => [
                $user,
                $another_user,
            ],
            'competencies' => [
                $comp,
                $another_comp,
            ],
            'positions' => [
                $pos,
                $another_pos
            ],
            'organisations' => [

            ],
            'assignments' => [
                $ass,
                $another_ass
            ]
        ];
    }

    /**
     * Get number of unrelated entries from the tables we are manipulating...
     *
     * @param $user
     * @param $assignment
     * @return array
     */
    protected function get_unrelated_data($user, $assignment) {
        // We need to get some of the data we're manipulating
        $achievements = competency_achievement::repository()
            ->where('assignment_id', '!=', $assignment->id)
            ->or_where(function(builder $builder) use ($assignment, $user) {
                $builder->where('assignment_id', $assignment->id)
                    ->where('user_id', '!=', $user->id);
            })
            ->count();

        $assignment_user_log_entries = competency_assignment_user_log::repository()
            ->where('assignment_id', '!=', $assignment->id)
            ->or_where(function(builder $repository) use ($assignment, $user) {
                $repository->where('assignment_id', $assignment->id)
                    ->where('user_id', '!=', $user->id);
            })
            ->count();

        return compact('achievements', 'assignment_user_log_entries');
    }

    /**
     * Assert that data for other users is not touched
     *
     * @param array $data Data returned by get_unrelated_data
     * @param $user
     * @param $assignment
     */
    protected function assert_data_untouched(array $data, $user, $assignment) {
        $this->assertEquals(
            $data['achievements'],
            competency_achievement::repository()
                ->where('assignment_id', '!=', $assignment->id)
                ->or_where(function(builder $repository) use ($assignment, $user) {
                    $repository->where('assignment_id', $assignment->id)
                        ->where('user_id', '!=', $user->id);
                })
                ->count()
        );

        // We'll have to do greater or equal as other records might have been created
        $this->assertGreaterThanOrEqual(
            $data['assignment_user_log_entries'],
            competency_assignment_user_log::repository()
                ->where('assignment_id', '!=', $assignment->id)
                ->or_where(function(builder $repository) use ($assignment, $user) {
                    $repository->where('assignment_id', $assignment->id)
                        ->where('user_id', '!=', $user->id);
                })
                ->count()
        );
    }

    /**
     * Totara competency generator shortcut
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_competency');
    }
}
