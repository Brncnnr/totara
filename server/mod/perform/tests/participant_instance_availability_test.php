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
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\event\participant_instance_availability_closed;
use mod_perform\event\participant_instance_progress_updated;
use mod_perform\event\participant_section_progress_updated;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\response\participant_section;
use mod_perform\observers\participant_instance_availability;
use mod_perform\state\participant_instance\closed;
use mod_perform\state\participant_instance\complete;
use mod_perform\state\participant_instance\availability_not_applicable;
use mod_perform\state\participant_instance\in_progress;
use mod_perform\state\participant_instance\not_started;
use mod_perform\state\participant_instance\open;
use mod_perform\state\participant_instance\participant_instance_availability as participant_instance_availability_state;
use mod_perform\state\state;

require_once(__DIR__ . '/state_testcase.php');

/**
 * @group perform
 */
class mod_perform_participant_instance_availability_testcase extends state_testcase {

    protected static function get_object_type(): string {
        return participant_instance_availability_state::get_type();
    }

    public function state_transitions_data_provider(): array {
        return [
            'Open to Open' => [open::class, open::class, false],
            'Open to Closed' => [open::class, closed::class, true],
            'Open to Not applicable' => [open::class, availability_not_applicable::class, false],

            'Closed to Closed' => [closed::class, closed::class, false],
            'Closed to Open' => [closed::class, open::class, true],
            'Closed to Not applicable' => [closed::class, availability_not_applicable::class, false],

            'Not applicable to Not applicable' => [availability_not_applicable::class, availability_not_applicable::class, false],
            'Not applicable to Closed' => [availability_not_applicable::class, closed::class, false],
            'Not applicable to Open' => [availability_not_applicable::class, open::class, false],
        ];
    }

    /**
     * @dataProvider state_transitions_data_provider
     * @param string $initial_state_class
     * @param string $final_state_class
     * @param bool $can_switch
     */
    public function test_state_switching(string $initial_state_class, string $final_state_class, bool $can_switch): void {
        [$participant_instance] = $this->create_data();

        /** @var state $initial_state */
        $initial_state = new $initial_state_class($participant_instance);

        $this->assertEquals($can_switch, $initial_state->can_switch($final_state_class));
    }

    public function test_participant_instance_closed_upon_instance_completion(): void {
        /**
         * @var participant_instance $participant1
         * @var participant_instance_entity $participant1_entity
         * @var participant_instance_entity $participant2_entity
         */
        [$participant1, $participant1_entity, $participant2_entity] = $this->create_data();

        /** @var participant_section $participant1_section */
        $participant1_section = $participant1->get_participant_sections()->first();

        $participant1_section->complete(); // Auto-aggregates completion of participant instance.

        $participant1_entity->refresh();
        $participant2_entity->refresh();

        $participant1_model = participant_instance::load_by_entity($participant1_entity);
        $participant2_model = participant_instance::load_by_entity($participant2_entity);

        $this->assertEquals(complete::get_code(), $participant1_model->progress_state::get_code());
        $this->assertEquals(not_started::get_code(), $participant2_model->progress_state::get_code());

        $this->assertEquals(closed::get_code(), $participant1_model->availability_state::get_code());
        $this->assertEquals(open::get_code(), $participant2_model->availability_state::get_code());
    }

    public function test_participant_instance_not_closed_when_instance_not_complete(): void {
        /**
         * @var participant_instance $participant1
         * @var participant_instance_entity $participant1_entity
         * @var participant_instance_entity $participant2_entity
         */
        [$participant1, $participant1_entity, $participant2_entity] = $this->create_data();

        /** @var participant_section $participant1_section */
        $participant1_section = $participant1->get_participant_sections()->first();

        $participant1_section->draft(); // Auto-aggregates in-progress of participant instance.

        $participant1_entity->refresh();
        $participant2_entity->refresh();

        $participant1_model = participant_instance::load_by_entity($participant1_entity);
        $participant2_model = participant_instance::load_by_entity($participant2_entity);

        $this->assertEquals(in_progress::get_code(), $participant1_model->progress_state::get_code());
        $this->assertEquals(not_started::get_code(), $participant2_model->progress_state::get_code());

        $this->assertEquals(open::get_code(), $participant1_model->availability_state::get_code());
        $this->assertEquals(open::get_code(), $participant2_model->availability_state::get_code());
    }

    public function test_participant_instance_closed_event(): void {
        /**
         * @var participant_instance $participant1
         * @var participant_instance_entity $participant1_entity
         * @var participant_instance_entity $participant2_entity
         * @var activity $activity
         */
        [$participant1, $participant1_entity, $participant2_entity, $activity] = $this->create_data();

        $participant1_entity->progress = complete::get_code();
        $participant1_entity->save();
        $event_sink = $this->redirectEvents();
        participant_instance_availability::close_completed_participant_instance(
            participant_instance_progress_updated::create_from_participant_instance($participant1)
        );
        $event_sink->close();
        $events = $event_sink->get_events();
        $participant1_entity->refresh();
        $participant2_entity->refresh();

        $this->assertCount(1, $events);

        $event = reset($events);
        $this->assertInstanceOf(participant_instance_availability_closed::class, $event);
        $this->assertEquals($participant1_entity->id, $event->objectid);
        $this->assertEquals($activity->get_context()->id, $event->contextid);
        $this->assertEquals(get_admin()->id, $event->userid);

        $participant1_model = participant_instance::load_by_entity($participant1_entity);
        $participant2_model = participant_instance::load_by_entity($participant2_entity);

        $this->assertEquals(complete::get_code(), $participant1_model->progress_state::get_code());
        $this->assertEquals(not_started::get_code(), $participant2_model->progress_state::get_code());

        $this->assertEquals(closed::get_code(), $participant1_model->availability_state::get_code());
        $this->assertEquals(open::get_code(), $participant2_model->availability_state::get_code());
    }

    public function test_instance_is_not_closed_if_activity_close_on_completion_is_not_set(): void {
        /**
         * @var participant_instance $participant1
         * @var participant_instance_entity $participant1_entity
         * @var participant_instance_entity $participant2_entity
         * @var activity $activity
         */
        [$participant1, $participant1_entity, $participant2_entity, $activity] = $this->create_data();
        $previous_progress_status = $participant1->progress_status;

        $activity->settings->update([activity_setting::CLOSE_ON_COMPLETION => false]);

        /** @var participant_section $participant1_section */
        $participant1_section = $participant1->get_participant_sections()->first();

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
        $participant1->update_progress_status();
        $event_sink->close();
        $events = $event_sink->get_events();
        $this->assertCount(1, $events);


        $event = reset($events);
        $this->assertInstanceOf(participant_instance_progress_updated::class,$event);
        $this->assertEquals($participant1->id, $event->objectid);
        $this->assertEquals($participant1->participant_id, $event->relateduserid);
        $this->assertEquals(get_admin()->id, $event->userid);
        $this->assertEquals($participant1->get_context(), $event->get_context());

        $anonymous = $participant1
            ->subject_instance
            ->activity
            ->anonymous_responses;

        $this->assertEquals($previous_progress_status, $event->other['previous_progress']);
        $this->assertEquals($participant1->progress_status, $event->other['progress']);
        $this->assertEquals($anonymous, $event->other['anonymous']);
        $this->assertEquals($participant1->participant_source, $event->other['participant_source']);

        $participant1_entity->refresh();
        $participant2_entity->refresh();

        $participant1_model = participant_instance::load_by_entity($participant1_entity);
        $participant2_model = participant_instance::load_by_entity($participant2_entity);

        $this->assertEquals(complete::get_code(), $participant1_model->progress_state::get_code());
        $this->assertEquals(not_started::get_code(), $participant2_model->progress_state::get_code());

        $this->assertEquals(open::get_code(), $participant1_model->availability_state::get_code());
        $this->assertEquals(open::get_code(), $participant2_model->availability_state::get_code());
    }

    /**
     * Create activity and participant instances required for testing.
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

        $subject_instance = $generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $user1->id,
            'include_questions' => false,
        ]);

        $subject_relationship_id = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;
        $manager_relationship_id = $generator->get_core_relationship(constants::RELATIONSHIP_MANAGER)->id;

        $participant1_section_entity = $generator->create_participant_instance_and_section(
            $activity,
            $user1,
            $subject_instance->id,
            $section,
            $subject_relationship_id
        );
        $participant2_section_entity = $generator->create_participant_instance_and_section(
            $activity,
            $user2,
            $subject_instance->id,
            $section,
            $manager_relationship_id
        );

        $participant1_entity = $participant1_section_entity->participant_instance;
        $participant2_entity = $participant2_section_entity->participant_instance;

        $participant1_model = participant_instance::load_by_entity($participant1_entity);
        $participant2_model = participant_instance::load_by_entity($participant2_entity);

        $this->assertEquals(open::get_code(), $participant1_model->availability_state::get_code());
        $this->assertEquals(open::get_code(), $participant2_model->availability_state::get_code());

        return [$participant1_model, $participant1_entity, $participant2_entity, $activity];
    }

}
