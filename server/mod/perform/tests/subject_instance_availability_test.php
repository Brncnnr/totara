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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\constants;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\event\participant_instance_progress_updated;
use mod_perform\event\participant_section_progress_updated;
use mod_perform\event\subject_instance_availability_closed;
use mod_perform\event\subject_instance_progress_updated;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\response\participant_section;
use mod_perform\models\activity\subject_instance;
use mod_perform\observers\subject_instance_availability;
use mod_perform\state\state;
use mod_perform\state\subject_instance\closed;
use mod_perform\state\subject_instance\complete;
use mod_perform\state\subject_instance\not_started;
use mod_perform\state\subject_instance\open;
use mod_perform\state\subject_instance\subject_instance_availability as subject_instance_availability_state;

require_once(__DIR__ . '/state_testcase.php');

/**
 * @group perform
 */
class mod_perform_subject_instance_availability_testcase extends state_testcase {

    protected static function get_object_type(): string {
        return subject_instance_availability_state::get_type();
    }

    public function state_transitions_data_provider(): array {
        return [
            'Open to Closed' => [open::class, closed::class, true],
            'Closed to Open' => [closed::class, open::class, true],
            'Open to Open' => [open::class, open::class, false],
            'Closed to Closed' => [closed::class, closed::class, false],
        ];
    }

    /**
     * @dataProvider state_transitions_data_provider
     * @param string $initial_state_class
     * @param string $final_state_class
     * @param bool $can_switch
     */
    public function test_state_switching(string $initial_state_class, string $final_state_class, bool $can_switch): void {
        [$subject_instance] = $this->create_data();

        /** @var state $initial_state */
        $initial_state = new $initial_state_class($subject_instance);

        $this->assertEquals($can_switch, $initial_state->can_switch($final_state_class));
    }

    public function test_subject_instance_closed_upon_instance_completion(): void {
        /**
         * @var subject_instance $subject1
         * @var subject_instance_entity $subject1_entity
         * @var subject_instance_entity $subject2_entity
         */
        [$subject1, $subject1_entity, $subject2_entity] = $this->create_data();

        /** @var participant_instance $participant1_instance */
        $participant1_instance = $subject1->get_participant_instances()->first();
        /** @var participant_section $participant1_section */
        $participant1_section = $participant1_instance->get_participant_sections()->first();

        $participant1_section->complete(); // Auto-aggregates completion of subject instance.

        $subject1_entity->refresh();
        $subject2_entity->refresh();

        $subject1_model = subject_instance::load_by_entity($subject1_entity);
        $subject2_model = subject_instance::load_by_entity($subject2_entity);

        $this->assertEquals(complete::get_code(), $subject1_model->progress_state::get_code());
        $this->assertEquals(not_started::get_code(), $subject2_model->progress_state::get_code());

        $this->assertEquals(closed::get_code(), $subject1_model->availability_state::get_code());
        $this->assertEquals(open::get_code(), $subject2_model->availability_state::get_code());
    }

    public function test_subject_instance_not_closed_when_instance_not_complete(): void {
        /**
         * @var subject_instance $subject1
         * @var subject_instance_entity $subject1_entity
         * @var subject_instance_entity $subject2_entity
         */
        [$subject1, $subject1_entity, $subject2_entity] = $this->create_data();

        $subject1_entity->progress = not_started::get_code();
        $subject1_entity->save();

        /** @var participant_instance $participant1_instance */
        $participant1_instance = $subject1->get_participant_instances()->first();
        /** @var participant_section $participant1_section */
        $participant1_section = $participant1_instance->get_participant_sections()->first();

        $participant1_section->draft(); // Auto-aggregates in-progress of subject instance.

        $subject1_entity->refresh();
        $subject2_entity->refresh();

        $subject1_model = subject_instance::load_by_entity($subject1_entity);
        $subject2_model = subject_instance::load_by_entity($subject2_entity);

        $this->assertEquals(open::get_code(), $subject1_model->availability_state::get_code());
        $this->assertEquals(open::get_code(), $subject2_model->availability_state::get_code());
    }

    public function test_subject_instance_closed_event(): void {
        /**
         * @var subject_instance $subject1
         * @var subject_instance_entity $subject1_entity
         * @var subject_instance_entity $subject2_entity
         * @var activity $activity
         */
        [$subject1, $subject1_entity, $subject2_entity, $activity] = $this->create_data();

        $subject1_entity->progress = complete::get_code();
        $subject1_entity->save();
        $event_sink = $this->redirectEvents();
        subject_instance_availability::close_completed_subject_instance(
            subject_instance_progress_updated::create_from_subject_instance($subject1)
        );
        $event_sink->close();
        $events = $event_sink->get_events();

        $this->assertCount(1, $events);

        $event = reset($events);
        $this->assertInstanceOf(subject_instance_availability_closed::class, $event);
        $this->assertEquals($subject1_entity->id, $event->objectid);
        $this->assertEquals($activity->get_context()->id, $event->contextid);
        $this->assertEquals(get_admin()->id, $event->userid);

        $subject1_entity->refresh();
        $subject2_entity->refresh();

        $subject1_model = subject_instance::load_by_entity($subject1_entity);
        $subject2_model = subject_instance::load_by_entity($subject2_entity);

        $this->assertEquals(closed::get_code(), $subject1_model->availability_state::get_code());
        $this->assertEquals(open::get_code(), $subject2_model->availability_state::get_code());
    }

    public function test_instance_is_not_closed_if_activity_close_on_completion_is_not_set(): void {
        /**
         * @var subject_instance $subject1
         * @var subject_instance_entity $subject1_entity
         * @var subject_instance_entity $subject2_entity
         * @var activity $activity
         */
        [$subject1, $subject1_entity, $subject2_entity, $activity] = $this->create_data();
        $previous_subject_progress = $subject1->progress_status;

        $activity->settings->update([activity_setting::CLOSE_ON_COMPLETION => false]);

        /** @var participant_instance $participant1_instance */
        $participant1_instance = $subject1->get_participant_instances()->first();
        /** @var participant_section $participant1_section */
        $participant1_section = $participant1_instance->get_participant_sections()->first();

        // Capture the participant section progress event.
        $event_sink = $this->redirectEvents();
        $participant1_section->complete();
        $event_sink->close();
        $events = $event_sink->get_events();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(participant_section_progress_updated::class, $event);

        // Manually fire the update that should occur due to the first event, and capture the participant instance progress event.
        $event_sink = $this->redirectEvents();
        $participant1_instance->update_progress_status();
        $event_sink->close();
        $events = $event_sink->get_events();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(participant_instance_progress_updated::class, $event);

        // Manually fire the update that should occur due to the second event, and capture the subject instance progress event.
        $event_sink = $this->redirectEvents();
        $subject1->update_progress_status();
        $event_sink->close();
        $events = $event_sink->get_events();
        $this->assertCount(1, $events);

        $event = reset($events);
        $this->assertInstanceOf(subject_instance_progress_updated::class, $event);
        $this->assertEquals($subject1->id, $event->objectid);
        $this->assertEquals($activity->get_context()->id, $event->contextid);
        $this->assertEquals(get_admin()->id, $event->userid);
        $this->assertEquals($subject1->subject_user_id, $event->relateduserid);
        $this->assertEquals($subject1->progress_status, $event->other['progress']);
        $this->assertEquals($previous_subject_progress, $event->other['previous_progress']);

        $subject1_entity->refresh();
        $subject2_entity->refresh();

        $subject1_model = subject_instance::load_by_entity($subject1_entity);
        $subject2_model = subject_instance::load_by_entity($subject2_entity);

        $this->assertEquals(open::get_code(), $subject1_model->availability_state::get_code());
        $this->assertEquals(open::get_code(), $subject2_model->availability_state::get_code());
    }

    public function test_closed_at_date(): void {
        /** @var subject_instance $subject_instance */
        [$subject_instance] = $this->create_data();
        $subject_instance_entity = $subject_instance->get_entity_copy();
        $this->assertNull($subject_instance_entity->closed_at);

        $subject_instance->manually_close();
        $subject_instance_entity->refresh();
        $this->assertLessThanOrEqual(time(), $subject_instance_entity->closed_at);

        $subject_instance->manually_open();
        $subject_instance_entity->refresh();
        $this->assertNull($subject_instance_entity->closed_at);
    }

    /**
     * Create activity and subject instances required for testing.
     *
     * @return array
     */
    private function create_data(): array {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $activity = $generator->create_activity_in_container();
        $activity->settings->update([activity_setting::CLOSE_ON_COMPLETION => true]);
        $section = $activity->get_sections()->first();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $subject1_entity = $generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $user1->id,
            'include_questions' => false,
        ]);

        $subject2_entity = $generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $user2->id,
            'include_questions' => false,
        ]);

        $subject_relationship_id = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;

        $generator->create_participant_instance_and_section(
            $activity,
            $user1,
            $subject1_entity->id,
            $section,
            $subject_relationship_id
        );
        $generator->create_participant_instance_and_section(
            $activity,
            $user2,
            $subject2_entity->id,
            $section,
            $subject_relationship_id
        );

        $subject1_model = subject_instance::load_by_entity($subject1_entity);
        $subject2_model = subject_instance::load_by_entity($subject2_entity);

        $this->assertEquals(open::get_code(), $subject1_model->availability_state::get_code());
        $this->assertEquals(open::get_code(), $subject2_model->availability_state::get_code());

        return [$subject1_model, $subject1_entity, $subject2_entity, $activity];
    }

}
