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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\constants;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\event\track_schedule_changed;
use mod_perform\dates\date_offset;
use totara_core\dates\date_time_setting;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/webapi_resolver_mutation_update_track_schedule.php');

/**
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_update_track_due_date_testcase
    extends mod_perform_webapi_resolver_mutation_update_track_schedule_base {

    private const MUTATION = 'mod_perform_update_track_schedule';

    use webapi_phpunit_helper;

    public function test_correct_track_is_disabled(): void {
        global $DB;

        $args = [
            'track_schedule' => [
                'track_id' => $this->track1_id,
                'schedule_is_open' => true,
                'schedule_is_fixed' => true,
                'schedule_fixed_from' => [
                    'iso' => '2019-12-04',
                    'timezone' => 'UTC',
                ],
                'due_date_is_enabled' => false,
                'repeating_is_enabled' => false,
                'subject_instance_generation' => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
            ],
        ];

        $before_tracks = $DB->get_records('perform_track', [], 'id');
        self::assertCount(4, $before_tracks);
        unset($before_tracks[$this->track1_id]->updated_at);

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $result_track = $result['track'];

        // Verify the resulting graphql data.
        self::assertEquals($this->track1_id, $result_track->id);
        self::assertFalse($result_track->due_date_is_enabled);
        self::assertNull($result_track->due_date_is_fixed);
        self::assertNull($result_track->due_date_fixed);
        self::assertNull($result_track->due_date_offset);

        // Manually make the changes that we expect to make.
        /** @var track_entity $affected_track */
        $affected_track = $before_tracks[$this->track1_id];
        $affected_track->schedule_is_open = 1;
        $affected_track->schedule_is_fixed = 1;
        $affected_track->schedule_fixed_from = $this->get_timestamp_from_date('2019-12-04', 'UTC');
        $affected_track->schedule_fixed_timezone = 'UTC';
        $affected_track->schedule_fixed_to = null;
        $affected_track->schedule_dynamic_from = null;
        $affected_track->schedule_dynamic_to = null;
        $affected_track->schedule_needs_sync = 1;
        $affected_track->due_date_is_enabled = 0;
        $affected_track->due_date_is_fixed = null;
        $affected_track->due_date_fixed = null;
        $affected_track->due_date_offset = null;
        $affected_track->repeating_is_enabled = 0;
        $affected_track->repeating_type = null;
        $affected_track->repeating_offset = null;
        $affected_track->repeating_is_limited = 0;
        $affected_track->repeating_limit = null;
        $affected_track->subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT;

        $after_tracks = $DB->get_records('perform_track', [], 'id');
        unset($after_tracks[$this->track1_id]->updated_at);

        self::assertEquals($before_tracks, $after_tracks);
    }

    public function test_correct_track_is_set_to_fixed(): void {
        global $DB;

        $args = [
            'track_schedule' => [
                'track_id' => $this->track1_id,
                'schedule_is_open' => false,
                'schedule_is_fixed' => true,
                'schedule_fixed_from' => [
                    'iso' => '2006-12-01',
                    'timezone' => 'Pacific/Auckland',
                ],
                'schedule_fixed_to' => [
                    'iso' => '2006-12-15',
                    'timezone' => 'Pacific/Auckland',
                ],
                'due_date_is_enabled' => true,
                'due_date_is_fixed' => true,
                'due_date_fixed' => [
                    'iso' => '2007-01-01',
                    'timezone' => 'Pacific/Auckland',
                ],
                'repeating_is_enabled' => false,
                'subject_instance_generation' => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
            ],
        ];

        $before_tracks = $DB->get_records('perform_track', [], 'id');
        self::assertCount(4, $before_tracks);
        unset($before_tracks[$this->track1_id]->updated_at);

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $result_track = $result['track'];

        // Verify the resulting graphql data.
        self::assertEquals($this->track1_id, $result_track->id);
        self::assertTrue($result_track->due_date_is_enabled);
        self::assertTrue($result_track->due_date_is_fixed);
        self::assertEquals(
            $this->get_timestamp_from_date('2007-01-01T23:59:59', 'Pacific/Auckland'),
            $result_track->due_date_fixed
        );
        self::assertEquals(
            'Pacific/Auckland',
            $result_track->due_date_fixed_timezone
        );
        self::assertNull($result_track->due_date_offset);

        // Manually make the changes that we expect to make.
        /** @var track_entity $affected_track */
        $affected_track = $before_tracks[$this->track1_id];
        $affected_track->schedule_is_open = 0;
        $affected_track->schedule_is_fixed = 1;
        $affected_track->schedule_fixed_from = $this->get_timestamp_from_date('2006-12-01', 'Pacific/Auckland');
        $affected_track->schedule_fixed_to = $this->get_timestamp_from_date('2006-12-15T23:59:59', 'Pacific/Auckland');
        $affected_track->schedule_fixed_timezone = 'Pacific/Auckland';
        $affected_track->schedule_dynamic_from = null;
        $affected_track->schedule_dynamic_to = null;
        $affected_track->schedule_needs_sync = 1;
        $affected_track->due_date_is_enabled = 1;
        $affected_track->due_date_is_fixed = 1;
        $affected_track->due_date_fixed = $this->get_timestamp_from_date('2007-01-01T23:59:59', 'Pacific/Auckland');
        $affected_track->due_date_fixed_timezone = 'Pacific/Auckland';
        $affected_track->due_date_offset = null;
        $affected_track->repeating_is_enabled = 0;
        $affected_track->repeating_type = null;
        $affected_track->repeating_offset = null;
        $affected_track->repeating_is_limited = 0;
        $affected_track->repeating_limit = null;
        $affected_track->subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT;

        $after_tracks = $DB->get_records('perform_track', [], 'id');
        unset($after_tracks[$this->track1_id]->updated_at);

        self::assertEquals($before_tracks, $after_tracks);
    }

    public function test_correct_track_is_set_to_relative(): void {
        global $DB;

        $args = [
            'track_schedule' => [
                'track_id' => $this->track1_id,
                'schedule_is_open' => false,
                'schedule_is_fixed' => true,
                'schedule_fixed_from' => ['iso' => '2020-12-04', 'timezone' => 'Pacific/Auckland'],
                'schedule_fixed_to' => ['iso' => '2020-12-05', 'timezone' => 'Pacific/Auckland'],
                'due_date_is_enabled' => true,
                'due_date_is_fixed' => false,
                'due_date_offset' => [
                    'count' => 2,
                    'unit' => date_offset::UNIT_WEEK
                ],
                'repeating_is_enabled' => false,
                'subject_instance_generation' => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
            ],
        ];

        $before_tracks = $DB->get_records('perform_track', [], 'id');
        self::assertCount(4, $before_tracks);
        unset($before_tracks[$this->track1_id]->updated_at);

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $result_track = $result['track'];

        // Verify the resulting graphql data.
        self::assertEquals($this->track1_id, $result_track->id);
        self::assertTrue($result_track->due_date_is_enabled);
        self::assertFalse($result_track->due_date_is_fixed);
        self::assertNull($result_track->due_date_fixed);
        self::assertEquals(
            date_offset::create_from_json(json_encode([
                'count' => 2,
                'unit' => date_offset::UNIT_WEEK
            ])),
            $result_track->due_date_offset
        );

        // Manually make the changes that we expect to make.
        $affected_track = $before_tracks[$this->track1_id];
        $affected_track->schedule_is_open = 0;
        $affected_track->schedule_is_fixed = 1;
        $affected_track->schedule_fixed_from = $this->get_timestamp_from_date(
            '2020-12-04',
            'Pacific/Auckland'
        );
        $affected_track->schedule_fixed_to =  $this->get_timestamp_from_date(
            '2020-12-05T23:59:59',
            'Pacific/Auckland'
        );
        $affected_track->schedule_fixed_timezone = 'Pacific/Auckland';
        $affected_track->schedule_dynamic_from = null;
        $affected_track->schedule_dynamic_to = null;
        $affected_track->schedule_needs_sync = 1;
        $affected_track->due_date_is_enabled = 1;
        $affected_track->due_date_is_fixed = 0;
        $affected_track->due_date_fixed = null;
        $affected_track->due_date_offset = json_encode([
            'count' => 2,
            'unit' => date_offset::UNIT_WEEK,
            'direction' => date_offset::DIRECTION_AFTER,
        ]);
        $affected_track->repeating_is_enabled = 0;
        $affected_track->repeating_type = null;
        $affected_track->repeating_offset = null;
        $affected_track->repeating_is_limited = 0;
        $affected_track->repeating_limit = null;
        $affected_track->subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT;

        $after_tracks = $DB->get_records('perform_track', [], 'id');
        unset($after_tracks[$this->track1_id]->updated_at);

        self::assertEquals($before_tracks, $after_tracks);
    }

    public function test_schedule_changed_event(): void {
        $from = [
            'iso' => '2030-12-04',
            'timezone' => 'UTC',
        ];

        $to = [
            'iso' => '2030-12-05',
            'timezone' => 'UTC',
        ];

        $args = [
            'track_schedule' => [
                'track_id' => $this->track1_id,
                'schedule_is_open' => false,
                'schedule_is_fixed' => true,
                'schedule_fixed_from' => $from,
                'schedule_fixed_to' => $to,
                'due_date_is_enabled' => true,
                'due_date_is_fixed' => false,
                'due_date_offset' => [
                    'count' => 2,
                    'unit' => date_offset::UNIT_WEEK
                ],
                'repeating_is_enabled' => false,
                'subject_instance_generation' => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
            ],
        ];

        $sink = $this->redirectEvents();
        $this->resolve_graphql_mutation(self::MUTATION, $args);

        $events = $sink->get_events();
        $this->assertCount(1, $events);

        $event = reset($events);
        $this->assertInstanceOf(track_schedule_changed::class, $event);
        $this->assertEquals($this->track1_id, $event->objectid);
        $this->assertEquals(get_admin()->id, $event->userid);

        $time = new date_time_setting(-1);
        $initial_schedule_time = $time->get_iso() . ' ' . $time->get_timezone();

        $time = date_time_setting::create_from_array($from);
        $changed_schedule_from_time = $time->get_iso() . ' ' . $time->get_timezone();

        $time = date_time_setting::create_from_array($to)->to_end_of_day();
        $changed_schedule_to_time = $time->get_iso() . ' ' . $time->get_timezone();

        $expected = [
            'is_open' => [false, false],
            'is_fixed' => [true, true],
            'fixed_from' => [$initial_schedule_time, $changed_schedule_from_time],
            'fixed_to' => [$initial_schedule_time, $changed_schedule_to_time],
            'dynamic_source' => ['', ''],
            'dynamic_from' => ['', ''],
            'dynamic_to' => ['', ''],
            'due_date' => [$initial_schedule_time, '2 weeks after each instance creation date']
        ];

        $raw = $event->other;
        foreach ($expected as $key => $values) {
            [$pre_value, $post_value] = $values;

            $this->assertEquals($raw["pre_$key"], $pre_value, "wrong pre'$key' value");
            $this->assertEquals($raw["post_$key"], $post_value, "wrong post '$key' value");
        }

        $sink->close();
    }
}
