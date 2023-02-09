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
use mod_perform\dates\date_offset;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\trigger\repeating\after_creation_and_completion;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/webapi_resolver_mutation_update_track_schedule.php');

/**
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_update_track_repeating_testcase
    extends mod_perform_webapi_resolver_mutation_update_track_schedule_base {

    use webapi_phpunit_helper;

    public function test_correct_track_is_disabled(): void {
        global $DB;

        $args = [
            'track_schedule' => [
                'track_id' => $this->track1_id,
                'schedule_is_open' => true,
                'schedule_is_fixed' => true,
                'schedule_fixed_from' => ['iso' => '2020-01-01T00:00:00'],
                'due_date_is_enabled' => false,
                'repeating_is_enabled' => false,
                'subject_instance_generation' => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
            ],
        ];

        $before_tracks = $DB->get_records('perform_track', [], 'id');
        self::assertCount(4, $before_tracks);
        unset($before_tracks[$this->track1_id]->updated_at);

        $result = $this->resolve_graphql_mutation(
            'mod_perform_update_track_schedule',
            $args
        );
        $result_track = $result['track'];

        // Verify the resulting graphql data.
        self::assertEquals($this->track1_id, $result_track->id);
        self::assertFalse($result_track->repeating_is_enabled);

        // Manually make the changes that we expect to make.
        /** @var track_entity $affected_track */
        $affected_track = $before_tracks[$this->track1_id];
        $affected_track->schedule_is_open = 1;
        $affected_track->schedule_is_fixed = 1;
        $affected_track->schedule_fixed_from = (new DateTime('2020-01-01T00:00:00'))->getTimestamp();
        $affected_track->schedule_fixed_timezone = core_date::get_user_timezone();
        $affected_track->schedule_fixed_to = null;
        $affected_track->schedule_dynamic_from = null;
        $affected_track->schedule_dynamic_to = null;
        $affected_track->schedule_needs_sync = 1;
        $affected_track->due_date_is_enabled = 0;
        $affected_track->due_date_is_fixed = null;
        $affected_track->due_date_fixed = null;
        $affected_track->due_date_offset = null;
        $affected_track->repeating_is_enabled = 0;
        $affected_track->repeating_trigger = null;
        $affected_track->repeating_type = null;
        $affected_track->repeating_offset = null;
        $affected_track->repeating_is_limited = 0;
        $affected_track->repeating_limit = null;
        $affected_track->subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT;

        $after_tracks = $DB->get_records('perform_track', [], 'id');
        unset($after_tracks[$this->track1_id]->updated_at);

        self::assertEquals($before_tracks, $after_tracks);
    }

    public function test_correct_track_is_enabled(): void {
        global $DB, $PAGE;

        self::setAdminUser();

        $configuration = \mod_perform\testing\activity_generator_configuration::new();
        $configuration->set_number_of_activities(2);
        $configuration->set_number_of_tracks_per_activity(2);

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activities = $perform_generator->create_full_activities($configuration);

        // Because notifications got emailed to the notification recipients
        // and theme and output got initialised as a result of that we don't
        // want the process to fail moodle_page::ensure_theme_not_set.
        $PAGE->reset_theme_and_output();

        /** @var activity $activity1 */
        $activity1 = $activities->first();
        /** @var track $track1 */
        $track1 = $activity1->get_tracks()->first();

        $repeating_trigger = new after_creation_and_completion();

        $args = [
            'track_schedule' => [
                'track_id' => $track1->id,
                'schedule_is_open' => true,
                'schedule_is_fixed' => true,
                'schedule_fixed_from' => ['iso' => '2020-01-01T00:00:00'],
                'due_date_is_enabled' => false,
                'repeating_is_enabled' => true,
                'repeating_trigger_interval' => $repeating_trigger->get_interval(),
                'repeating_trigger_type' => $repeating_trigger->get_name(),
                'repeating_offset' => [
                    'count' => 4,
                    'unit' => date_offset::UNIT_WEEK,
                ],
                'repeating_is_limited' => true,
                'repeating_limit' => 5,
                'subject_instance_generation' => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
            ],
        ];

        $before_tracks = $DB->get_records('perform_track', [], 'id');
        self::assertCount(8, $before_tracks);
        unset($before_tracks[$track1->id]->updated_at);

        $result = $this->resolve_graphql_mutation(
            'mod_perform_update_track_schedule',
            $args
        );
        $result_track = $result['track'];

        // Verify the resulting graphql data.
        self::assertEquals($track1->id, $result_track->id);
        self::assertTrue($result_track->repeating_is_enabled);

        // Manually make the changes that we expect to make. Note the stringification
        // of certain fields because the $DB->get_records() call won't convert strings
        // to proper integers where necessary!
        /** @var track_entity $affected_track */
        $affected_track = $before_tracks[$track1->id];
        $affected_track->subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT;
        $affected_track->schedule_is_open = '1';
        $affected_track->schedule_is_fixed = '1';
        $affected_track->schedule_fixed_from = (string)(new DateTime('2020-01-01T00:00:00'))->getTimestamp();
        $affected_track->schedule_fixed_to = null;
        $affected_track->schedule_fixed_timezone = core_date::get_user_timezone();
        $affected_track->schedule_dynamic_from = null;
        $affected_track->schedule_dynamic_to = null;
        $affected_track->schedule_needs_sync = '1';
        $affected_track->due_date_is_enabled = '0';
        $affected_track->repeating_is_enabled = '1';
        $affected_track->repeating_trigger = get_class($repeating_trigger);
        $affected_track->repeating_type = (string)track::SCHEDULE_REPEATING_TYPE_AFTER_CREATION_WHEN_COMPLETE;
        $affected_track->repeating_offset = json_encode([
            'count' => 4,
            'unit' => date_offset::UNIT_WEEK,
            'direction' => date_offset::DIRECTION_AFTER
        ]);
        $affected_track->repeating_is_limited = 1;
        $affected_track->repeating_limit = 5;
        $affected_track->subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT;

        $after_tracks = $DB->get_records('perform_track', [], 'id');
        unset($after_tracks[$track1->id]->updated_at);

        self::assertEquals($after_tracks, $before_tracks);
    }
}
