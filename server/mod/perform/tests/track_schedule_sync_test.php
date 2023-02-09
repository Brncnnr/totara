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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\entity\user;
use mod_perform\dates\date_offset;
use mod_perform\dates\resolvers\dynamic\user_creation_date;
use mod_perform\entity\activity\activity;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\expand_task;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\track as track_model;
use mod_perform\state\activity\draft;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\task\service\track_schedule_sync;
use mod_perform\testing\activity_generator_configuration;
use totara_core\dates\date_time_setting;

defined('MOODLE_INTERNAL') || die();

/**
 * @coversDefaultClass \mod_perform\expand_task
 *
 * @group perform
 */
class mod_perform_track_schedule_sync_testcase extends advanced_testcase {

    /**
     * @return \mod_perform\testing\generator
     */
    protected function generator() {
        return \mod_perform\testing\generator::instance();
    }

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_sync_updates_user_assignment_dates(): void {
        $generator = $this->generator();
        $config = activity_generator_configuration::new()
            ->disable_user_assignments()
            ->set_number_of_users_per_user_group_type(1);
        /** @var activity_model $activity */
        $activity = $generator->create_full_activities($config)->first();
        /** @var track_model $track */
        $track = $activity->get_tracks()->first();
        $tomorrow = new date_time_setting(time() + 86400);
        $yesterday = new date_time_setting(time() - 86400);
        $track->set_schedule_closed_fixed($yesterday, $tomorrow);
        $track->update();

        // Expand creates the track_user_assignments with schedule restriction.
        expand_task::create()->expand_all();
        /** @var track_user_assignment $user_assignment */
        $user_assignment = track_user_assignment::repository()->one();
        $this->assertEquals($yesterday->get_timestamp(), $user_assignment->period_start_date);
        $this->assertEquals($tomorrow->get_timestamp(), $user_assignment->period_end_date);

        $now = time();
        $track->set_schedule_open_fixed(new date_time_setting($now));
        $track->update();

        (new track_schedule_sync())->sync_all_flagged();
        $user_assignment->refresh();
        $this->assertEquals($now, $user_assignment->period_start_date);
        $this->assertEquals(null, $user_assignment->period_end_date);
    }

    public function test_sync_updates_user_assignment_dates_using_anniversary_date_resolution(): void {
        $generator = $this->generator();
        $config = activity_generator_configuration::new()
            ->disable_user_assignments()
            ->set_number_of_users_per_user_group_type(3);

        /** @var activity_model $activity */
        $activity = $generator->create_full_activities($config)->first();

        /** @var track_model $track */
        $track = $activity->get_tracks()->first();

        $dynamic_source = (new user_creation_date())->get_options()->first();

        $track->set_schedule_closed_dynamic(
            new date_offset(0, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
            new date_offset(1, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
            $dynamic_source,
            true
        )->update();

        $create_date = (new DateTime('2000-02-03T00:00:00', new DateTimeZone('UTC')))->getTimestamp();

        /** @var user $user */
        user::repository()->update(['timecreated' => $create_date]);

        // Expand creates the track_user_assignments with schedule restriction.
        expand_task::create()->expand_all();
        /** @var track_user_assignment $user_assignment */
        $user_assignments = track_user_assignment::repository()->get();

        foreach ($user_assignments as $user_assignment) {
            $this->assert_anniversary_date($user_assignment->period_start_date, 3, 2);

            // End dates are adjusted to "end of day".
            $this->assert_anniversary_date($user_assignment->period_end_date, 5, 2);

            $track->set_schedule_open_dynamic(
                new date_offset(0, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                $dynamic_source,
                true
            )->update();

            (new track_schedule_sync())->sync_all_flagged();
            $user_assignment->refresh();
            $this->assert_anniversary_date($user_assignment->period_start_date, 3, 2);
            $this->assertEquals(null, $user_assignment->period_end_date);
        }
    }

    public function test_draft_activity_is_not_synced(): void {
        $generator = $this->generator();
        $config = activity_generator_configuration::new()
            ->disable_user_assignments()
            ->set_number_of_users_per_user_group_type(1);
        /** @var activity_model $activity */
        $activity = $generator->create_full_activities($config)->first();
        /** @var track_model $track */
        $track = $activity->get_tracks()->first();
        $tomorrow = new date_time_setting(time() + 86400);
        $yesterday = new date_time_setting(time() - 86400);
        $track->set_schedule_closed_fixed($yesterday, $tomorrow);
        $track->update();

        // Expand creates the track_user_assignments with schedule restriction.
        expand_task::create()->expand_all();
        /** @var track_user_assignment $user_assignment */
        $user_assignment = track_user_assignment::repository()->one();
        $this->assertEquals($yesterday->get_timestamp(), $user_assignment->period_start_date);
        $this->assertEquals($tomorrow->get_timestamp(), $user_assignment->period_end_date);

        $now = time();
        $track->set_schedule_open_fixed(new date_time_setting($now));
        $track->update();

        // Change activity status to draft
        activity::repository()->update_record([
            'id' => $activity->id,
            'status' => draft::get_code()
        ]);

        (new track_schedule_sync())->sync_all_flagged();

        // No change expected
        $user_assignment->refresh();
        $this->assertEquals($yesterday->get_timestamp(), $user_assignment->period_start_date);
        $this->assertEquals($tomorrow->get_timestamp(), $user_assignment->period_end_date);
    }

    public function test_paused_track_is_not_synced(): void {
        $generator = $this->generator();
        $config = activity_generator_configuration::new()
            ->disable_user_assignments()
            ->set_number_of_users_per_user_group_type(1);
        /** @var activity_model $activity */
        $activity = $generator->create_full_activities($config)->first();
        /** @var track_model $track */
        $track = $activity->get_tracks()->first();
        $tomorrow = new date_time_setting(time() + 86400);
        $yesterday = new date_time_setting(time() - 86400);
        $track->set_schedule_closed_fixed($yesterday, $tomorrow);
        $track->update();

        // Expand creates the track_user_assignments with schedule restriction.
        expand_task::create()->expand_all();
        /** @var track_user_assignment $user_assignment */
        $user_assignment = track_user_assignment::repository()->one();
        $this->assertEquals($yesterday->get_timestamp(), $user_assignment->period_start_date);
        $this->assertEquals($tomorrow->get_timestamp(), $user_assignment->period_end_date);

        $now = time();
        $track->set_schedule_open_fixed(new date_time_setting($now));
        $track->update();

        // Pause track
        $track->pause();

        // No change expected
        (new track_schedule_sync())->sync_all_flagged();
        $user_assignment->refresh();
        $this->assertEquals($yesterday->get_timestamp(), $user_assignment->period_start_date);
        $this->assertEquals($tomorrow->get_timestamp(), $user_assignment->period_end_date);

        // Re-activated track should be synced
        $track->activate();

        (new track_schedule_sync())->sync_all_flagged();
        $user_assignment->refresh();
        $this->assertEquals($now, $user_assignment->period_start_date);
        $this->assertEquals(null, $user_assignment->period_end_date);
    }

    public function test_expand_picks_up_synced_dates(): void {
        $generator = $this->generator();
        $config = activity_generator_configuration::new()
            ->disable_user_assignments();
        /** @var activity_model $activity */
        $activity = $generator->create_full_activities($config)->first();
        /** @var track_model $track */
        $track = $activity->get_tracks()->first();
        $tomorrow = new date_time_setting(time() + 86400);
        $yesterday = new date_time_setting(time() - 86400);
        $track->set_schedule_open_fixed($tomorrow);
        $track->update();
        $this->assertEquals(1, track::repository()->find($track->id)->schedule_needs_sync);

        // Sync flag is set, but calling sync doesn't do anything without any track_user_assignments.
        // It only resets the flag.
        (new track_schedule_sync())->sync_all_flagged();
        $this->assertEquals(0, track::repository()->find($track->id)->schedule_needs_sync);
        $this->assertCount(0, track_user_assignment::repository()->get());
        $this->assertCount(0, subject_instance::repository()->get());

        // Expand creates the track_user_assignments with schedule restriction.
        expand_task::create()->expand_all();
        $this->assertCount(5, track_user_assignment::repository()->get());

        // No subject instances should be created before $tomorrow.
        (new subject_instance_creation())->generate_instances();
        $this->assertCount(0, subject_instance::repository()->get());

        $track->set_schedule_open_fixed($yesterday);
        $track->update();

        (new track_schedule_sync())->sync_all_flagged();
        (new subject_instance_creation())->generate_instances();

        $this->assertCount(5, track_user_assignment::repository()->get());
        $this->assertCount(5, subject_instance::repository()->get());
    }

    /**
     * Assert that a date is this year or next and that the day and month are particular values.
     *
     * @param int $date
     * @param int $expected_day
     * @param int $expected_month
     */
    private function assert_anniversary_date(
        int $date,
        int $expected_day,
        int $expected_month
    ): void {
        [$year, $month, $day] = explode(
            '-',
            (new DateTime("@{$date}"))->format('Y-m-d')
        );

        $this_year = (new DateTime())->format('Y');
        $next_year = (new DateTime())->modify('+1 year')->format('Y');

        $this->assertEquals($expected_day, (int) $day);
        $this->assertEquals($expected_month, (int) $month);
        $this->assertTrue(
            $year === $this_year || $year === $next_year,
            'Year was not this year or next'
        );
    }

    public function sync_flag_and_unflagged_data_provider() {
        return [
            ['sync_all_flagged', false, false],
            ['sync_all_flagged', true, true],
            ['sync_all', true, true],
            ['sync_all', false, true],
        ];
    }

    /**
     * Make sure sync_all_flagged() only picks up flagged tracks and sync_all() picks
     * up tracks no matter if flagged or not.
     *
     * @dataProvider sync_flag_and_unflagged_data_provider
     * @param string $method_name
     * @param bool $flagged
     * @param bool $is_sync_expected
     */
    public function test_sync_flagged_and_unflagged(string $method_name, bool $flagged, bool $is_sync_expected) {
        $generator = $this->generator();
        $config = activity_generator_configuration::new()
            ->disable_user_assignments()
            ->set_number_of_users_per_user_group_type(1);
        /** @var activity_model $activity */
        $activity = $generator->create_full_activities($config)->first();
        /** @var track_model $track */
        $track = $activity->get_tracks()->first();
        $tomorrow = new date_time_setting(time() + 86400);
        $yesterday = new date_time_setting(time() - 86400);
        $track->set_schedule_closed_fixed($yesterday, $tomorrow);
        $track->update();

        // Let expand task create the track_user_assignment with current schedule restrictions.
        expand_task::create()->expand_all();
        /** @var track_user_assignment $user_assignment */
        $user_assignment = track_user_assignment::repository()->where('track_id', $track->id)->one();

        $now = time();
        $track->set_schedule_open_fixed(new date_time_setting($now));
        $track->update();

        // Set or unset the flag.
        track::repository()->where('id', $track->id)->update(['schedule_needs_sync' => $flagged]);

        (new track_schedule_sync())->$method_name();
        $user_assignment->refresh();
        if ($is_sync_expected) {
            $this->assertEquals($now, $user_assignment->period_start_date);
            $this->assertEquals(null, $user_assignment->period_end_date);
        } else {
            $this->assertEquals($yesterday->get_timestamp(), $user_assignment->period_start_date);
            $this->assertEquals($tomorrow->get_timestamp(), $user_assignment->period_end_date);
        }
    }
}
