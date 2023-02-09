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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\dates\date_offset;
use mod_perform\dates\resolvers\dynamic\activity_assigned_date;
use mod_perform\entity\activity\track as track_enttity;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\expand_task;
use mod_perform\models\activity\track;

/**
 * Class mod_perform_date_resolver_dynamic_source_activity_assigned_testcase
 *
 * @group perform
 */
class mod_perform_date_resolver_dynamic_source_activity_assigned_testcase extends advanced_testcase {

    public function test_get_option(): void {
        $activity_date_resolver = new activity_assigned_date();
        $result = $activity_date_resolver->get_options();
        $this->assertCount(1, $result);
    }

    public function test_option_is_available(): void {
        $activity_date_resolver = new activity_assigned_date();
        $this->assertTrue(
            $activity_date_resolver->option_is_available(activity_assigned_date::DEFAULT_KEY)
        );
    }

    /**
     * @dataProvider resolve_provider
     * @param int $user_assignment_start_date
     * @param date_offset $date_offset
     * @param int $expected_resolver_start_date
     */
    public function test_resolve(
        int $user_assignment_start_date,
        date_offset $date_offset,
        int $expected_resolver_start_date
    ): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container(['create_track' => true]);

        /** @var track $track */
        $track = $activity->get_tracks()->first();

        $track = $perform_generator->create_track_assignments(
            $track,
            0,
            0,
            0,
            3
        );

        expand_task::create()->expand_single($track->assignments->first()->id);

        /** @var track_user_assignment $track_user_assignment */
        $track_user_assignment = track_user_assignment::repository()->get()->first();
        $track_user_assignment->subject_user_id;
        $track_user_assignment->created_at = $user_assignment_start_date;
        $track_user_assignment->update();

        $activity_date_resolver = new activity_assigned_date();
        $activity_date_resolver->set_custom_data(json_encode([activity_assigned_date::THIS_ACTIVITY_ID => $activity->id]));
        $activity_date_resolver->set_parameters(
            $date_offset,
            null,
            activity_assigned_date::DEFAULT_KEY,
            [$track_user_assignment->subject_user_id]
        );

        $actual_resolver_start_date = $activity_date_resolver->get_start($track_user_assignment->subject_user_id);

        $this->assertEquals($expected_resolver_start_date, $actual_resolver_start_date);
    }

    /**
     * @dataProvider resolve_provider
     * @param int $user_assignment_start_date
     * @param date_offset $date_offset
     * @param int $expected_resolver_start_date
     */
    public function test_resolve_with_multiple_tracks_in_activity(
        int $user_assignment_start_date,
        date_offset $date_offset,
        int $expected_resolver_start_date
    ): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container(['create_track' => true]);

        /** @var track $track */
        $track = $activity->get_tracks()->first();

        $track = $perform_generator->create_track_assignments(
            $track,
            0,
            0,
            0,
            3
        );

        expand_task::create()->expand_single($track->assignments->first()->id);

        $first_track_entity = new track_enttity($track->id);
        $copy = $first_track_entity->to_array();
        $copy['id'] = null;

        $track2 = new track_enttity($copy);
        $track2->save();

        /** @var track_user_assignment $track_user_assignment */
        $track_user_assignment = track_user_assignment::repository()->get()->first();
        $track_user_assignment->subject_user_id;

        $track_user_assignment_copy = $track_user_assignment->to_array();
        $track_user_assignment_copy['id'] = null;
        $track_user_assignment_copy['track_id'] = $track2->id;

        $track_user_assignment2 = new track_user_assignment($track_user_assignment_copy);
        $track_user_assignment2->save();

        $for_user = track_user_assignment::repository()
            ->where('subject_user_id', $track_user_assignment->subject_user_id)
            ->get();

        self::assertCount(2, $for_user, 'The target subject user should have two user assignments');

        /** @var track_user_assignment $track_user_assignment1 */
        $track_user_assignment1 = $for_user->first();

        /** @var track_user_assignment $track_user_assignment2 */
        $track_user_assignment2 = $for_user->last();

        self::assertNotEquals(
            $track_user_assignment1->id,
            $track_user_assignment2->id,
            'Track user assignments should be separate instances'
        );

        self::assertEquals(
            $track_user_assignment1->track->activity->id,
            $track_user_assignment2->track->activity->id,
            'User assignments should be linked to the same activity'
        );

        // Set "2" slightly after "1" (we should get the first created_at).
        $track_user_assignment1->created_at = $user_assignment_start_date;
        $track_user_assignment1->update();

        $track_user_assignment2->created_at = $user_assignment_start_date + 100;
        $track_user_assignment2->update();

        $activity_date_resolver = new activity_assigned_date();
        $activity_date_resolver->set_custom_data(json_encode([activity_assigned_date::THIS_ACTIVITY_ID => $activity->id]));
        $activity_date_resolver->set_parameters(
            $date_offset,
            null,
            activity_assigned_date::DEFAULT_KEY,
            [$track_user_assignment->subject_user_id]
        );

        $actual_resolver_start_date = $activity_date_resolver->get_start($track_user_assignment->subject_user_id);

        $this->assertEquals($expected_resolver_start_date, $actual_resolver_start_date);

        // Now if we flip the "1" and "2" we user assignments we should get "1" from the resolver.
        $track_user_assignment1->created_at = $user_assignment_start_date - 100;
        $track_user_assignment1->update();

        $track_user_assignment2->created_at = $user_assignment_start_date;
        $track_user_assignment2->update();

        $activity_date_resolver = new activity_assigned_date();
        $activity_date_resolver->set_custom_data(json_encode([activity_assigned_date::THIS_ACTIVITY_ID => $activity->id]));
        $activity_date_resolver->set_parameters(
            $date_offset,
            null,
            activity_assigned_date::DEFAULT_KEY,
            [$track_user_assignment->subject_user_id]
        );

        $actual_resolver_start_date = $activity_date_resolver->get_start($track_user_assignment->subject_user_id);

        $this->assertEquals($expected_resolver_start_date - 100, $actual_resolver_start_date);

        // Lastly removing "2" should take us back to "1" being resolved.
        $track_user_assignment2->delete();

        $activity_date_resolver = new activity_assigned_date();
        $activity_date_resolver->set_custom_data(json_encode([activity_assigned_date::THIS_ACTIVITY_ID => $activity->id]));
        $activity_date_resolver->set_parameters(
            $date_offset,
            null,
            activity_assigned_date::DEFAULT_KEY,
            [$track_user_assignment->subject_user_id]
        );

        $actual_resolver_start_date = $activity_date_resolver->get_start($track_user_assignment->subject_user_id);

        $this->assertEquals($expected_resolver_start_date - 100, $actual_resolver_start_date);
    }

    /**
     * @dataProvider resolve_provider
     * @param int $now_time
     * @param date_offset $date_offset
     * @param int $expected_resolver_start_date
     */
    public function test_resolve_no_track_user_assignment(
        int $now_time,
        date_offset $date_offset,
        int $expected_resolver_start_date
    ): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container(['create_track' => true]);

        /** @var track $track */
        $track = $activity->get_tracks()->first();

        self::assertEmpty($track->assignments, 'There should be no track assignments');


        $activity_date_resolver = new activity_assigned_date();
        $activity_date_resolver->set_time($now_time);
        $activity_date_resolver->set_custom_data(json_encode([activity_assigned_date::THIS_ACTIVITY_ID => $activity->id]));
        $activity_date_resolver->set_parameters(
            $date_offset,
            null,
            activity_assigned_date::DEFAULT_KEY,
            [500]
        );

        $actual_resolver_start_date = $activity_date_resolver->get_start(500);

        $this->assertEquals($expected_resolver_start_date, $actual_resolver_start_date);
    }

    public function resolve_provider(): array {
        $base_start = strtotime('2020-12-04');

        return [
            'With no offset' => [
                $base_start, new date_offset(0, date_offset::UNIT_DAY), $base_start,
            ],
            'With forward offset' => [
                $base_start, new date_offset(3, date_offset::UNIT_DAY), $base_start + (DAYSECS * 3),
            ],
            'With backward offset' => [
                $base_start, new date_offset(3, date_offset::UNIT_DAY, date_offset::DIRECTION_BEFORE), $base_start - (DAYSECS * 3),
            ],
        ];
    }

}
