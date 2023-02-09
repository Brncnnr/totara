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

use mod_perform\dates\date_offset;
use mod_perform\dates\resolvers\dynamic\dynamic_source;
use mod_perform\dates\resolvers\dynamic\user_creation_date;
use mod_perform\entity\activity\track_assignment;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\track;

/**
 * @group perform
 */
abstract class mod_perform_webapi_resolver_mutation_update_track_schedule_base extends advanced_testcase {

    protected $track1_id;

    public function setUp(): void {
        global $DB, $PAGE;

        self::setAdminUser();

        set_config('totara_job_allowmultiplejobs', 0);

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

        // Set all records to some known values so that we can see which records and fields are being modified.
        $control_offset = json_encode(new date_offset(
            -1,
            date_offset::UNIT_WEEK,
            date_offset::DIRECTION_BEFORE
        ));
        $DB->set_field('perform_track', 'subject_instance_generation', -1);
        $DB->set_field('perform_track', 'schedule_is_open', -1);
        $DB->set_field('perform_track', 'schedule_is_fixed', -1);
        $DB->set_field('perform_track', 'schedule_fixed_from', -1);
        $DB->set_field('perform_track', 'schedule_fixed_to', -1);
        $DB->set_field('perform_track', 'schedule_dynamic_from', $control_offset);
        $DB->set_field('perform_track', 'schedule_dynamic_to', $control_offset);
        $DB->set_field('perform_track', 'due_date_is_enabled', -1);
        $DB->set_field('perform_track', 'due_date_is_fixed', -1);
        $DB->set_field('perform_track', 'due_date_fixed', -1);
        $DB->set_field('perform_track', 'due_date_offset', $control_offset);
        $DB->set_field('perform_track', 'repeating_is_enabled', -1);
        $DB->set_field('perform_track', 'repeating_type', -1);
        $DB->set_field('perform_track', 'repeating_offset', $control_offset);
        $DB->set_field('perform_track', 'repeating_is_limited', -1);
        $DB->set_field('perform_track', 'repeating_limit', -1);

        /** @var activity $activity1 */
        $activity1 = $activities->first();
        /** @var track $track1 */
        $track1 = $activity1->get_tracks()->first();

        // All track assignments are expanded
        $this->assertFalse(track_assignment::repository()->where('expand', 1)->exists());

        $this->track1_id = $track1->id;
    }

    protected function get_user_creation_date_dynamic_source(): array {
        $date_dynamic_source = (new user_creation_date())->get_options()->first();

        /* @var $date_dynamic_source dynamic_source */
        $dynamic_source_input = [
            'resolver_class_name' => user_creation_date::class,
            'option_key' => $date_dynamic_source->get_option_key(),
        ];

        return [$date_dynamic_source, $dynamic_source_input];
    }

    protected function get_timestamp_from_date(string $date, string $timezone): string {
        return (new DateTime($date, new DateTimeZone($timezone)))->getTimestamp();
    }

    public function tearDown(): void {
        $this->track1_id = null;

        parent::tearDown();
    }
}
