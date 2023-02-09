<?php
/*
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\dates\date_offset;
use mod_perform\dates\resolvers\dynamic\another_activity_date;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\expand_task;
use mod_perform\models\activity\track;
use mod_perform\task\service\subject_instance_creation;

/**
 * Class mod_perform_date_resolver_dynamic_source_another_activity_testcase
 *
 * @group perform
 */
class mod_perform_date_resolver_dynamic_source_another_activity_testcase extends advanced_testcase {

    public function test_get_option() {
        $activity_date_resolver = new another_activity_date();
        $result = $activity_date_resolver->get_options();
        $this->assertCount(4, $result);
    }

    public function test_option_is_available() {
        $activity_date_resolver = new another_activity_date();
        $this->assertTrue(
            $activity_date_resolver->option_is_available(another_activity_date::ACTIVITY_COMPLETED_DAY)
        );
        $this->assertTrue(
            $activity_date_resolver->option_is_available(another_activity_date::ACTIVITY_INSTANCE_CREATION_DAY)
        );
        $this->assertTrue(
            $activity_date_resolver->option_is_available(another_activity_date::ACTIVITY_CLOSED_DAY)
        );
        $this->assertTrue(
            $activity_date_resolver->option_is_available(another_activity_date::ACTIVITY_CLOSED_OR_COMPLETED_DAY)
        );
    }

    public function resolve_option_key_data_provider() {
        return [
            [another_activity_date::ACTIVITY_COMPLETED_DAY],
            [another_activity_date::ACTIVITY_INSTANCE_CREATION_DAY],
            [another_activity_date::ACTIVITY_CLOSED_DAY],
            [another_activity_date::ACTIVITY_CLOSED_OR_COMPLETED_DAY],
        ];
    }

    /**
     * @dataProvider resolve_option_key_data_provider
     * @param string $option_key
     */
    public function test_resolve_dates(string $option_key) {
        $data = $this->generate_test_data();

        (new subject_instance_creation())->generate_instances();

        // Adjust times in DB for activity1.
        $this->adjust_instance_date($option_key, $data->user1, $data->activity1_track1, '2019-01-09T12:00:00');
        $this->adjust_instance_date($option_key, $data->user1, $data->activity1_track2, '2019-07-09T12:00:00');
        $this->adjust_instance_date($option_key, $data->user2, $data->activity1_track1, '2019-12-15T12:12:12');
        $this->adjust_instance_date($option_key, $data->user2, $data->activity1_track2, '2019-12-15T11:11:11');
        // Adjust times in DB for activity2.
        $this->adjust_instance_date($option_key, $data->user1, $data->activity2_track1, '2018-01-09T12:00:00');
        $this->adjust_instance_date($option_key, $data->user1, $data->activity2_track2, '2018-07-09T12:00:00');
        $this->adjust_instance_date($option_key, $data->user2, $data->activity2_track1, '2018-12-15T12:12:12');
        $this->adjust_instance_date($option_key, $data->user2, $data->activity2_track2, '2018-12-15T11:11:11');

        // Check resolve results for activity 1.
        $activity_date_resolver = (new another_activity_date());
        $activity_date_resolver
            ->set_parameters(
                new date_offset(1, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                new date_offset(2, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                $option_key,
                [$data->user1->id, $data->user2->id]
            )
            ->set_custom_data(json_encode(['activity' => $data->activity1->id]));

        $this->assert_time_result('2019-07-02 12:00:00', $activity_date_resolver->get_start($data->user1->id));
        $this->assert_time_result('2019-07-12 12:00:00', $activity_date_resolver->get_end($data->user1->id));
        $this->assert_time_result('2019-12-08 12:12:12', $activity_date_resolver->get_start($data->user2->id));
        $this->assert_time_result('2019-12-18 12:12:12', $activity_date_resolver->get_end($data->user2->id));

        // Now check for activity 2.
        $activity_date_resolver = new another_activity_date();
        $activity_date_resolver
            ->set_parameters(
                new date_offset(1, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                new date_offset(2, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                $option_key,
                [$data->user1->id, $data->user2->id]
            )
            ->set_custom_data(json_encode(['activity' => $data->activity2->id]));

        $this->assert_time_result('2018-07-02 12:00:00', $activity_date_resolver->get_start($data->user1->id));
        $this->assert_time_result('2018-07-12 12:00:00', $activity_date_resolver->get_end($data->user1->id));
        $this->assert_time_result('2018-12-08 12:12:12', $activity_date_resolver->get_start($data->user2->id));
        $this->assert_time_result('2018-12-18 12:12:12', $activity_date_resolver->get_end($data->user2->id));
    }

    /**
     * @dataProvider resolve_option_key_data_provider
     * @param string $option_key
     */
    public function test_resolve_without_subject_instances(string $option_key) {
        $data = $this->generate_test_data();

        // Check null results when no subject_instances exist.
        $activity_date_resolver = (new another_activity_date());
        $activity_date_resolver
            ->set_parameters(
                new date_offset(1, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                new date_offset(2, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                $option_key,
                [$data->user1->id, $data->user2->id]
            )
            ->set_custom_data(json_encode(['activity' => $data->activity1->id]));

        $this->assertNull($activity_date_resolver->get_start($data->user1->id));
        $this->assertNull($activity_date_resolver->get_start($data->user2->id));
        $this->assertNull($activity_date_resolver->get_end($data->user1->id));
        $this->assertNull($activity_date_resolver->get_end($data->user2->id));
    }

    /**
     * @dataProvider resolve_option_key_data_provider
     * @param string $option_key
     */
    public function test_resolve_for_users_not_included(string $option_key) {
        $data = $this->generate_test_data();

        (new subject_instance_creation())->generate_instances();

        $this->adjust_instance_date($option_key, $data->user1, $data->activity2_track1, '2018-01-09T12:00:00');
        $this->adjust_instance_date($option_key, $data->user1, $data->activity2_track2, '2018-07-09T12:00:00');
        $this->adjust_instance_date($option_key, $data->user2, $data->activity2_track1, '2018-12-15T12:12:12');
        $this->adjust_instance_date($option_key, $data->user2, $data->activity2_track2, '2018-12-15T11:11:11');

        $activity_date_resolver = new another_activity_date();
        $activity_date_resolver
            ->set_parameters(
                new date_offset(1, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                new date_offset(2, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                $option_key,
                [$data->user1->id]
            )
            ->set_custom_data(json_encode(['activity' => $data->activity2->id]));

        $this->assert_time_result('2018-07-02 12:00:00', $activity_date_resolver->get_start($data->user1->id));
        $this->assert_time_result('2018-07-12 12:00:00', $activity_date_resolver->get_end($data->user1->id));
        $this->assertNull($activity_date_resolver->get_start($data->user2->id));
        $this->assertNull($activity_date_resolver->get_end($data->user2->id));
    }

    /**
     * @dataProvider resolve_option_key_data_provider
     * @param string $option_key
     */
    public function test_resolve_only_from_date(string $option_key) {
        $data = $this->generate_test_data();

        (new subject_instance_creation())->generate_instances();

        // Adjust times in DB for activity1.
        $this->adjust_instance_date($option_key, $data->user1, $data->activity1_track1, '2019-01-09T12:00:00');
        $this->adjust_instance_date($option_key, $data->user1, $data->activity1_track2, '2019-07-09T12:00:00');
        $this->adjust_instance_date($option_key, $data->user2, $data->activity1_track1, '2019-12-15T12:12:12');
        $this->adjust_instance_date($option_key, $data->user2, $data->activity1_track2, '2019-12-15T11:11:11');

        $activity_date_resolver = (new another_activity_date());
        $activity_date_resolver
            ->set_parameters(
                new date_offset(1, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                null,
                $option_key,
                [$data->user1->id, $data->user2->id]
            )
            ->set_custom_data(json_encode(['activity' => $data->activity1->id]));

        $this->assert_time_result('2019-07-02 12:00:00', $activity_date_resolver->get_start($data->user1->id));
        $this->assertNull($activity_date_resolver->get_end($data->user1->id));
        $this->assert_time_result('2019-12-08 12:12:12', $activity_date_resolver->get_start($data->user2->id));
        $this->assertNull($activity_date_resolver->get_end($data->user2->id));
    }

    /**
     * @dataProvider resolve_option_key_data_provider
     * @param string $option_key
     */
    public function test_resolve_multiple_instances_one_track(string $option_key) {
        // for $option_key = ACTIVITY_CLOSED_OR_COMPLETED_DAY we do test separate
        // see test_resolve_closed_or_completed_dates
        if ($option_key == another_activity_date::ACTIVITY_CLOSED_OR_COMPLETED_DAY) {
            return;
        }

        $data = $this->generate_test_data();

        (new subject_instance_creation())->generate_instances();

        // Adjust times in DB for activity1.
        $subject_instance1 = $this->adjust_instance_date($option_key, $data->user1, $data->activity1_track1, '2019-08-09T14:14:14');
        $this->adjust_instance_date($option_key, $data->user1, $data->activity1_track2, '2019-07-09T12:00:00');
        $this->adjust_instance_date($option_key, $data->user2, $data->activity1_track1, '2019-12-15T12:12:12');
        $this->adjust_instance_date($option_key, $data->user2, $data->activity1_track2, '2019-12-15T11:11:11');

        // Add a second instance for a track
        $subject_instance2 = new subject_instance();
        $subject_instance2->track_user_assignment_id = $subject_instance1->track_user_assignment_id;
        $subject_instance2->subject_user_id = $data->user1->id;
        $subject_instance2->save();

        // Adjust its date field
        switch($option_key) {
            case another_activity_date::ACTIVITY_CLOSED_DAY:
                $timestamp_field_name = 'closed_at';
                break;
            case another_activity_date::ACTIVITY_COMPLETED_DAY:
                $timestamp_field_name = 'completed_at';
                break;
            default:
                $timestamp_field_name = 'created_at';
                break;
        }
        $timestamp = (new DateTime('2019-09-20T15:15:15', new DateTimeZone('UTC')))->getTimestamp();
        // for $option_key = ACTIVITY_CLOSED_OR_COMPLETED_DAY we do test separate
        // see test_resolve_closed_or_completed_dates
        subject_instance::repository()
            ->where('id', $subject_instance2->id)
            ->update([$timestamp_field_name => $timestamp]);

        $activity_date_resolver = (new another_activity_date());
        $activity_date_resolver
            ->set_parameters(
                new date_offset(1, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                new date_offset(2, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                $option_key,
                [$data->user1->id, $data->user2->id]
            )
            ->set_custom_data(json_encode(['activity' => $data->activity1->id]));

        $this->assert_time_result('2019-09-13 15:15:15', $activity_date_resolver->get_start($data->user1->id));
        $this->assert_time_result('2019-09-23 15:15:15', $activity_date_resolver->get_end($data->user1->id));
        $this->assert_time_result('2019-12-08 12:12:12', $activity_date_resolver->get_start($data->user2->id));
        $this->assert_time_result('2019-12-18 12:12:12', $activity_date_resolver->get_end($data->user2->id));

        // Re-adjust the date to make sure order in DB doesn't matter.
        $timestamp = (new DateTime('2019-06-09T15:15:15', new DateTimeZone('UTC')))->getTimestamp();
        subject_instance::repository()
            ->where('id', $subject_instance2->id)
            ->update([$timestamp_field_name => $timestamp]);

        $activity_date_resolver = (new another_activity_date());
        $activity_date_resolver
            ->set_parameters(
                new date_offset(1, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                new date_offset(2, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                $option_key,
                [$data->user1->id, $data->user2->id]
            )
            ->set_custom_data(json_encode(['activity' => $data->activity1->id]));

        $this->assert_time_result('2019-08-02 14:14:14', $activity_date_resolver->get_start($data->user1->id));
        $this->assert_time_result('2019-08-12 14:14:14', $activity_date_resolver->get_end($data->user1->id));
        $this->assert_time_result('2019-12-08 12:12:12', $activity_date_resolver->get_start($data->user2->id));
        $this->assert_time_result('2019-12-18 12:12:12', $activity_date_resolver->get_end($data->user2->id));
    }

    public function test_resolve_with_null_date() {
        // Only completed_at is nullable. created_at is not nullable.
        $option_key = another_activity_date::ACTIVITY_COMPLETED_DAY;

        $data = $this->generate_test_data();

        (new subject_instance_creation())->generate_instances();

        $this->adjust_instance_date($option_key, $data->user1, $data->activity2_track1, null);
        $this->adjust_instance_date($option_key, $data->user1, $data->activity2_track2, null);
        $this->adjust_instance_date($option_key, $data->user2, $data->activity2_track1, null);
        $this->adjust_instance_date($option_key, $data->user2, $data->activity2_track2, '2018-12-15T11:11:11');

        $activity_date_resolver = new another_activity_date();
        $activity_date_resolver
            ->set_parameters(
                new date_offset(1, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                new date_offset(2, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                $option_key,
                [$data->user1->id, $data->user2->id]
            )
            ->set_custom_data(json_encode(['activity' => $data->activity2->id]));
        $this->assertNull($activity_date_resolver->get_start($data->user1->id));
        $this->assertNull($activity_date_resolver->get_end($data->user1->id));
        $this->assert_time_result('2018-12-08 11:11:11', $activity_date_resolver->get_start($data->user2->id));
        $this->assert_time_result('2018-12-18 11:11:11', $activity_date_resolver->get_end($data->user2->id));
    }

    public function test_resolve_closed_or_completed_dates() {
        $data = $this->generate_test_data();

        (new subject_instance_creation())->generate_instances();

        // Adjust times in DB for activity1.
        $this->adjust_instance_date(another_activity_date::ACTIVITY_CLOSED_DAY, $data->user1, $data->activity1_track1, '2019-01-09T12:00:00');
        $this->adjust_instance_date(another_activity_date::ACTIVITY_CLOSED_DAY, $data->user1, $data->activity1_track2, '2019-07-09T12:00:00');
        $this->adjust_instance_date(another_activity_date::ACTIVITY_COMPLETED_DAY, $data->user1, $data->activity1_track2, '2019-07-09T13:00:00');
        $this->adjust_instance_date(another_activity_date::ACTIVITY_COMPLETED_DAY, $data->user2, $data->activity1_track1, '2019-12-15T12:12:12');
        $this->adjust_instance_date(another_activity_date::ACTIVITY_COMPLETED_DAY, $data->user2, $data->activity1_track2, '2019-12-15T11:11:11');
        // Adjust times in DB for activity2.
        $this->adjust_instance_date(another_activity_date::ACTIVITY_CLOSED_DAY, $data->user1, $data->activity2_track1, '2018-01-09T12:00:00');
        $this->adjust_instance_date(another_activity_date::ACTIVITY_COMPLETED_DAY, $data->user1, $data->activity2_track2, '2018-07-09T12:00:00');
        $this->adjust_instance_date(another_activity_date::ACTIVITY_CLOSED_DAY, $data->user1, $data->activity2_track2, '2018-07-09T12:00:00');
        $this->adjust_instance_date(another_activity_date::ACTIVITY_COMPLETED_DAY, $data->user2, $data->activity2_track1, '2018-12-15T12:12:12');
        $this->adjust_instance_date(another_activity_date::ACTIVITY_COMPLETED_DAY, $data->user2, $data->activity2_track2, '2018-12-15T11:11:11');

        // Check resolve results for activity 1.
        $activity_date_resolver = (new another_activity_date());
        $activity_date_resolver
            ->set_parameters(
                new date_offset(1, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                new date_offset(2, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                another_activity_date::ACTIVITY_CLOSED_OR_COMPLETED_DAY,
                [$data->user1->id, $data->user2->id]
            )
            ->set_custom_data(json_encode(['activity' => $data->activity1->id]));

        $this->assert_time_result('2019-07-02 12:00:00', $activity_date_resolver->get_start($data->user1->id));
        $this->assert_time_result('2019-07-12 12:00:00', $activity_date_resolver->get_end($data->user1->id));
        $this->assert_time_result('2019-12-08 12:12:12', $activity_date_resolver->get_start($data->user2->id));
        $this->assert_time_result('2019-12-18 12:12:12', $activity_date_resolver->get_end($data->user2->id));

        // Now check for activity 2.
        $activity_date_resolver = new another_activity_date();
        $activity_date_resolver
            ->set_parameters(
                new date_offset(1, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                new date_offset(2, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                another_activity_date::ACTIVITY_CLOSED_OR_COMPLETED_DAY,
                [$data->user1->id, $data->user2->id]
            )
            ->set_custom_data(json_encode(['activity' => $data->activity2->id]));

        $this->assert_time_result('2018-07-02 12:00:00', $activity_date_resolver->get_start($data->user1->id));
        $this->assert_time_result('2018-07-12 12:00:00', $activity_date_resolver->get_end($data->user1->id));
        $this->assert_time_result('2018-12-08 12:12:12', $activity_date_resolver->get_start($data->user2->id));
        $this->assert_time_result('2018-12-18 12:12:12', $activity_date_resolver->get_end($data->user2->id));
    }

    private function generate_test_data(): stdClass {
        self::setAdminUser();
        $data_generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        // Create 2 activities with 2 tracks each.
        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(2)
            ->set_number_of_tracks_per_activity(2)
            ->disable_user_assignments()
            ->disable_subject_instances()
            ->set_number_of_users_per_user_group_type(0);
        [$activity1, $activity2] = $perform_generator->create_full_activities($config)->all();

        // Assign same audience of 2 users to all tracks.
        $user1 = $data_generator->create_user();
        $user2 = $data_generator->create_user();
        $cohort = $data_generator->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);
        [$activity1_track1, $activity1_track2] = track::load_by_activity($activity1)->all();
        [$activity2_track1, $activity2_track2] = track::load_by_activity($activity2)->all();
        foreach ([$activity1_track1, $activity1_track2, $activity2_track1, $activity2_track2] as $track) {
            $perform_generator->create_track_assignments_with_existing_groups($track, [$cohort->id]);
        }
        expand_task::create()->expand_all();

        return (object)[
            'user1' => $user1,
            'user2' => $user2,
            'activity1' => $activity1,
            'activity2' => $activity2,
            'activity1_track1' => $activity1_track1,
            'activity1_track2' => $activity1_track2,
            'activity2_track1' => $activity2_track1,
            'activity2_track2' => $activity2_track2,
        ];
    }

    /**
     * @param string $expected_time_string
     * @param int $unix_timestamp
     */
    private function assert_time_result(string $expected_time_string, int $unix_timestamp) {
        $start_date = (new DateTime())->setTimestamp($unix_timestamp)->setTimezone(new DateTimeZone('UTC'));
        $this->assertSame($expected_time_string, $start_date->format('Y-m-d H:i:s'));
    }

    /**
     * @param string $option_key
     * @param stdClass $user
     * @param track $track
     * @param string|null $time_string
     * @return \core\orm\entity\entity|subject_instance
     */
    private function adjust_instance_date(string $option_key, stdClass $user, track $track, ?string $time_string): subject_instance {
        global $DB;
        $track_user_assignment = track_user_assignment::repository()
            ->where('subject_user_id', $user->id)
            ->where('track_id', $track->id)
            ->one(true);

        $timestamp = is_null($time_string) ? null : (new DateTime($time_string, new DateTimeZone('UTC')))->getTimestamp();

        switch ($option_key) {
            case another_activity_date::ACTIVITY_CLOSED_OR_COMPLETED_DAY:
                $si_table = subject_instance::TABLE;
                $params = ['subject_user_id' => $user->id, 'track_user_assignment_id' => $track_user_assignment->id];
                $query = "SELECT
                            CASE
                                WHEN closed_at IS NULL AND completed_at IS NULL THEN NULL
                                WHEN closed_at IS NULL AND completed_at IS NOT NULL THEN 'completed_at'
                                WHEN closed_at IS NOT NULL AND completed_at IS NULL THEN 'closed_at'
                                WHEN COALESCE(closed_at, 0) > COALESCE(completed_at, 0) THEN 'completed_at'
                                ELSE 'closed_at'
                          END AS timestamp_field_name
                          FROM {{$si_table}}
                          WHERE subject_user_id = :subject_user_id
                          AND track_user_assignment_id = :track_user_assignment_id";
                $timestamp_field_name = $DB->get_field_sql($query, $params);
                if (!$timestamp_field_name) {
                    // Nothing yet happened.
                    $timestamp_field_name = 'closed_at';
                }
                break;
            case another_activity_date::ACTIVITY_CLOSED_DAY:
                $timestamp_field_name = 'closed_at';
                break;
            case another_activity_date::ACTIVITY_COMPLETED_DAY:
                $timestamp_field_name = 'completed_at';
                break;
            default:
                $timestamp_field_name = 'created_at';
                break;
        }
        subject_instance::repository()
            ->where('subject_user_id', $user->id)
            ->where('track_user_assignment_id', $track_user_assignment->id)
            ->update([$timestamp_field_name => $timestamp]);

        return subject_instance::repository()
            ->where('subject_user_id', $user->id)
            ->where('track_user_assignment_id', $track_user_assignment->id)
            ->one(true);
    }
}
