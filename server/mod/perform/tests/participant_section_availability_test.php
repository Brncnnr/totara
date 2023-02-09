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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\collection;
use core\entity\user;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_setting;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\models\response\section_element_response;
use mod_perform\models\response\participant_section;
use mod_perform\state\invalid_state_switch_exception;
use mod_perform\state\participant_section\availability_not_applicable;
use mod_perform\state\participant_section\closed;
use mod_perform\state\participant_section\open;
use mod_perform\state\participant_section\participant_section_availability;
use mod_perform\state\state_helper;

require_once(__DIR__ . '/relationship_testcase.php');
require_once(__DIR__ . '/state_testcase.php');

/**
 * @group perform
 */
class mod_perform_participant_section_availability_testcase extends state_testcase {

    protected static function get_object_type(): string {
        return 'participant_section';
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
     * Check switching section states.
     *
     * @dataProvider state_transitions_data_provider
     * @param string $initial_state_class
     * @param string $target_state_class
     * @param bool $transition_possible
     */
    public function test_switch_state(
        string $initial_state_class,
        string $target_state_class,
        bool $transition_possible = true
    ): void {
        $this->setAdminUser();
        $subject_user = self::getDataGenerator()->create_user();
        $other_participant = self::getDataGenerator()->create_user();

        // Set and verify initial state.
        $entity = $this->create_participant_section($subject_user, $other_participant);
        $entity->availability = $initial_state_class::get_code();
        $entity->update();
        $participant_section = participant_section::load_by_entity($entity);
        $this->assertInstanceOf(
            $initial_state_class,
            $participant_section->get_availability_state()
        );

        $this->setUser($subject_user);
        $sink = $this->redirectEvents();

        if (!$transition_possible) {
            $this->expectException(invalid_state_switch_exception::class);
            $this->expectExceptionMessage('Cannot switch');
        }

        $participant_section->switch_state($target_state_class);

        $db_availability = participant_section_entity::repository()->find($participant_section->get_id())->availability;
        $this->assertEquals($target_state_class::get_code(), $db_availability);
        $this->assertInstanceOf($target_state_class, $participant_section->get_availability_state());

        // Check that event has been triggered.
        if ($target_state_class === closed::class) {
            $this->assert_section_availability_closed_event($sink, $participant_section, $subject_user->id);
        }
    }

    /**
     * @param \core_phpunit\event_sink $sink
     * @param participant_section $participant_section
     * @param int $user_id
     */
    private function assert_section_availability_closed_event(
        \core_phpunit\event_sink $sink,
        participant_section $participant_section,
        int $user_id
    ): void {
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('mod_perform\event\participant_section_availability_closed', $event);
        $this->assertEquals($participant_section->get_id(), $event->objectid);

        $this->assertEquals($participant_section->get_context(), $event->get_context());
        $this->assertEquals($user_id, $event->userid);

        $only_activity = activity::load_by_entity(activity_entity::repository()->one());

        $this->assertEquals($only_activity->get_context(), $event->get_context());

        $sink->close();
    }

    public function test_get_all_translated() {
        $this->assertEqualsCanonicalizing([
            70 => 'Not applicable',
            10 => 'Closed',
            0 => 'Open',
        ], state_helper::get_all_display_names('participant_section', participant_section_availability::get_type()));
    }

    public function close_on_completion_data_provider(): array {
        return [
            'Close on completion disabled' => [false],
            'Close on completion enabled' => [true],
        ];
    }

    /**
     * Tests the availability of participant changes based on workflow setting in activity.
     *
     * @dataProvider close_on_completion_data_provider
     * @param bool $close_on_completion
     * @return void
     */
    public function test_availability_change_on_activity_settings(bool $close_on_completion): void {
        /** @var participant_section $participant_section */
        $participant_section = participant_section::load_by_entity($this->create_participant_section());

        self::assertEquals(
            open::get_code(),
            $participant_section->get_availability_state()::get_code(),
            'Participant section should start with the open availability status'
        );

        activity::load_by_entity(activity_entity::repository()->one())
            ->settings
            ->update([activity_setting::CLOSE_ON_COMPLETION => $close_on_completion]);

        $this->mark_answers_complete($participant_section);
        $completion_success = $participant_section->complete();

        self::assertTrue($completion_success);
        $expected_availability = $close_on_completion
            ? closed::get_code()
            : open::get_code();

        /** @var participant_section_entity $participant_section_entity */
        $participant_section_entity = participant_section_entity::repository()->find($participant_section->get_id());
        self::assertEquals(
            $expected_availability,
            $participant_section_entity->availability,
            'Participant section has the wrong availability status'
        );
    }

    private function mark_answers_complete(participant_section $participant_section): void {
        $section_elements = $participant_section->get_section()->get_section_elements();

        $responses = new collection();
        foreach ($section_elements as $section_element) {
            $responses->append($this->create_valid_element_response());
        }

        $participant_section->set_section_element_responses($responses);
    }

    /**
     * @param stdClass|null $subject_user
     * @param stdClass|null $other_participant
     * @return participant_section_entity
     */
    private function create_participant_section(
        stdClass $subject_user = null,
        stdClass $other_participant = null
    ): participant_section_entity {
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        self::setAdminUser();

        $subject_user_id = $subject_user ? $subject_user->id : user::logged_in()->id;
        $other_participant_id = $other_participant ? $other_participant->id : null;

        $perform_generator->create_subject_instance([
            'subject_user_id' => $subject_user_id,
            'other_participant_id' => $other_participant_id,
            'subject_is_participating' => true,
        ]);

        return participant_section_entity::repository()
            ->join([participant_instance_entity::TABLE, 'pi'], 'participant_instance_id', '=', 'id')
            ->where('pi.participant_id', $subject_user_id)
            ->one();
    }

    private function create_valid_element_response(): section_element_response {
        return new class extends section_element_response {
            public $was_saved = false;

            public function __construct() {
            }

            public function save(): section_element_response {
                $this->was_saved = true;
                return $this;
            }

            public function validate_response($is_draft_validation = false): bool {
                $this->validation_errors = new collection();
                return true;
            }
        };
    }

}
