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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\entity\activity\section_relationship as section_relationship_entity;
use mod_perform\event\activity_activated;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\section_relationship;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use mod_perform\state\activity\activity_state;
use mod_perform\state\state_helper;
use performelement_aggregation\aggregation;
use performelement_aggregation\calculations\average;
use performelement_numeric_rating_scale\numeric_rating_scale;
use mod_perform\testing\generator as perform_generator;

/**
 * @group perform
 */
class mod_perform_activity_state_testcase extends advanced_testcase {

    public function test_activate(): void {
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);

        $activity = $this->create_valid_activity();

        $this->assertEquals(draft::get_code(), $activity->status);
        $this->assertTrue($activity->is_draft());
        $this->assertFalse($activity->is_active());

        $activity->activate();

        $activity->refresh();

        $this->assertEquals(active::get_code(), $activity->status);
        $this->assertTrue($activity->is_active());
        $this->assertFalse($activity->is_draft());

        // Activating an already active activity should not change anything
        $activity->activate();

        $activity->refresh();

        $this->assertEquals(active::get_code(), $activity->status);
        $this->assertTrue($activity->is_active());
        $this->assertFalse($activity->is_draft());
    }

    public function test_activate_event_is_triggered(): void {
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);

        $activity = $this->create_valid_activity();

        $sink = $this->redirectEvents();

        $activity->activate();

        $events = $sink->get_events();

        $this->assertCount(1, $events);
        $event = array_shift($events);
        $this->assertInstanceOf(activity_activated::class, $event);
        $this->assertEquals($activity->get_id(), $event->objectid);
        $this->assertEquals($activity->get_context()->id, $event->contextid);
        $this->assertEquals($user->id, $event->userid);

        $sink->clear();

        // Activating an already active activity should not trigger an event again
        $activity->refresh()
            ->activate()
            ->refresh();

        $this->assertEmpty($sink->get_events());
    }

    public function test_can_activate(): void {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        // A draft activity which fulfills all conditions can be activated
        $draft_activity = $this->create_valid_activity();

        $this->assertTrue($draft_activity->can_potentially_activate());
        $this->assertTrue($draft_activity->can_activate());

        $this->setUser($user2);

        // The user can't activate it because he does not have the capability
        $this->assertFalse($draft_activity->can_potentially_activate());
        $this->assertFalse($draft_activity->can_activate());

        $this->setUser($user1);

        // An activate activity cannot be activated anymore
        $active_activity = $this->create_valid_activity(active::get_code());

        $this->assertFalse($active_activity->can_potentially_activate());
        $this->assertFalse($active_activity->can_activate());

        $this->setUser($user3);

        // An activity with section only including static element, it can not be activated.
        $draft_activity = $this->create_valid_activity(0, 'static_content');
        $this->assertTrue($draft_activity->can_potentially_activate());
        $this->assertFalse($draft_activity->can_activate());
    }

    public function test_get_all_translated(): void {
        $this->assertEqualsCanonicalizing(
            [
                1 => 'Active',
                0 => 'Draft',
            ],
            state_helper::get_all_display_names(
                'activity',
                activity_state::get_type()
            )
        );
    }

    public function test_cant_activate_with_unsatisfied_conditions(): void {
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);

        // Now lets create an activity which does not satisfy the conditions
        // (at least one section with at least on question and one relationship)
        $invalid_draft_activity = $this->create_activity();

        // The user has the capability and the activity is in draft, so potentially can be activated
        $this->assertTrue($invalid_draft_activity->can_potentially_activate());
        // But not really as the conditions are not satisfied
        $this->assertFalse($invalid_draft_activity->can_activate());

        // Having a section won't change anything
        $perform_generator = $this->generator();
        $section = $perform_generator->create_section($invalid_draft_activity, ['title' => 'Test section 1']);

        $invalid_draft_activity->refresh(true);

        $this->assertTrue($invalid_draft_activity->can_potentially_activate());
        $this->assertFalse($invalid_draft_activity->can_activate());

        // Same with a section relationship
        $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );

        $invalid_draft_activity->refresh(true);

        $this->assertTrue($invalid_draft_activity->can_potentially_activate());
        $this->assertFalse($invalid_draft_activity->can_activate());

        // Same with a section element
        $element = $perform_generator->create_element(['title' => 'Question one']);
        $perform_generator->create_section_element($section, $element);

        $invalid_draft_activity->refresh(true);

        $this->assertTrue($invalid_draft_activity->can_potentially_activate());
        $this->assertFalse($invalid_draft_activity->can_activate());

        // Finally, with a track and assignment we have everything in place
        $perform_generator->create_single_activity_track_and_assignment($invalid_draft_activity);

        $invalid_draft_activity->refresh(true);

        $this->assertTrue($invalid_draft_activity->can_potentially_activate());
        $this->assertTrue($invalid_draft_activity->can_activate());
    }

    public function test_cant_activate_with_view_only_relationships(): void {
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);

        $activity = $this->create_activity_with_view_only_relationship();

        $this->assertEquals(draft::get_code(), $activity->status);
        $this->assertTrue($activity->is_draft());
        $this->assertFalse($activity->is_active());

        $activity->activate();

        $activity->refresh();

        $this->assertEquals(draft::get_code(), $activity->status);
        $this->assertFalse($activity->is_active());
        $this->assertTrue($activity->is_draft());

        /** @var section_relationship_entity $section_relationship_entity */
        $section_relationship_entity = section_relationship_entity::repository()->order_by('id')->first();
        $section_relationship = section_relationship::load_by_entity($section_relationship_entity);
        $section_relationship->update_attribution_settings(false, true);

        $activity->refresh(true);
        $activity->activate();

        $this->assertEquals(active::get_code(), $activity->status);
        $this->assertTrue($activity->is_active());
        $this->assertFalse($activity->is_draft());
    }

    public function test_cant_activate_without_any_relationships(): void {
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);

        // Now lets create an activity which does not satisfy the conditions
        // (at least one section with at least on question and one relationship)
        $invalid_draft_activity = $this->create_activity();

        // The user has the capability and the activity is in draft, so potentially can be activated
        $this->assertTrue($invalid_draft_activity->can_potentially_activate());
        // But not really as the conditions are not satisfied
        $this->assertFalse($invalid_draft_activity->can_activate());

        // Having a section won't change anything
        $perform_generator = $this->generator();
        $section = $perform_generator->create_section($invalid_draft_activity, ['title' => 'Test section 1']);

        $invalid_draft_activity->refresh(true);

        $this->assertTrue($invalid_draft_activity->can_potentially_activate());
        $this->assertFalse($invalid_draft_activity->can_activate());

        $invalid_draft_activity->refresh(true);

        $this->assertTrue($invalid_draft_activity->can_potentially_activate());
        $this->assertFalse($invalid_draft_activity->can_activate());

        // Same with a section element
        $element = $perform_generator->create_element(['title' => 'Question one']);
        $perform_generator->create_section_element($section, $element);

        $invalid_draft_activity->refresh(true);

        $this->assertTrue($invalid_draft_activity->can_potentially_activate());
        $this->assertFalse($invalid_draft_activity->can_activate());

        // Finally, with a track and assignment we have everything in place EXCEPT relationships
        $perform_generator->create_single_activity_track_and_assignment($invalid_draft_activity);

        $invalid_draft_activity->refresh(true);

        $this->assertTrue($invalid_draft_activity->can_potentially_activate());
        $this->assertFalse($invalid_draft_activity->can_activate());
    }

    public function test_cant_activate_with_only_static_elements(): void {
        $user1 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        // A draft activity which fulfills all conditions can be activated, EXCEPT the element is static.
        $draft_activity = $this->create_valid_activity(draft::get_code(), 'static_content');

        $this->assertTrue($draft_activity->can_potentially_activate());
        $this->assertFalse($draft_activity->can_activate());
    }

    public function test_cant_activate_with_only_aggregate_elements_in_sections(): void {
        $user1 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        $generator = perform_generator::instance();
        $activity = $this->create_valid_activity();

        $section1 = $activity->get_sections()->first();

        $element = $generator->create_element(['plugin_name' => numeric_rating_scale::get_plugin_name()]);
        $source_section_element = $generator->create_section_element($section1, $element);
        $generator->create_element(['plugin_name' => numeric_rating_scale::get_plugin_name()]);

        $aggregation_data = [
            aggregation::SOURCE_SECTION_ELEMENT_IDS => [$source_section_element->id],
            aggregation::EXCLUDED_VALUES => [],
            aggregation::CALCULATIONS => [average::get_name()],
        ];
        $aggregation_element = $generator->create_element([
            'plugin_name' => aggregation::get_plugin_name(),
            'data' => json_encode($aggregation_data, JSON_THROW_ON_ERROR),
        ]);

        $section2 = $generator->create_section($activity);
        $generator->create_section_element($section2, $aggregation_element);

        // A draft activity which fulfills all conditions can be activated, EXCEPT the element is has derived/calculated response elements.
        $this->assertTrue($activity->can_potentially_activate());
        $this->assertFalse($activity->can_activate());
    }

    /**
     * Create a basic activity without any sections or questions in it
     *
     * @param int $status defaults to draft
     * @param bool $create_section default to false
     * @param bool $create_track default to false
     * @return activity
     */
    protected function create_activity(
        int $status = 0,
        bool $create_section = false,
        bool $create_track = false
    ): activity {
        $perform_generator = $this->generator();

        return $perform_generator->create_activity_in_container([
            'activity_name' => 'User1 One',
            'activity_status' => $status,
            'create_section' => $create_section,
            'create_track' => $create_track
        ]);
    }

    /**
     * Creates an activity with one section, one question and one relationship
     *
     * @param int $status defaults to draft
     * @param string $element_plugin_name
     * @param null $element_plugin_data
     * @return activity
     */
    protected function create_valid_activity(
        int $status = 0,
        $element_plugin_name = 'short_text',
        $element_plugin_data = null
    ): activity {
        $perform_generator = $this->generator();
        $activity = $this->create_activity($status);
        $section = $perform_generator->create_section($activity, ['title' => 'Test section 1']);

        $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );

        $element = $perform_generator->create_element([
            'title' => 'Question one',
            'plugin_name' => $element_plugin_name,
            'data' => $element_plugin_data,
        ]);

        $perform_generator->create_section_element($section, $element);
        $perform_generator->create_single_activity_track_and_assignment($activity);

        return $activity;
    }

    /**
     * Creates an activity with one section, one question and one relationship
     *
     * @param int $status defaults to draft
     * @param string $element_plugin_name
     * @return activity
     */
    protected function create_activity_with_view_only_relationship(
        int $status = 0,
        $element_plugin_name = 'short_text'
    ): activity {
        $perform_generator = $this->generator();

        $activity = $this->create_activity($status);

        $section = $perform_generator->create_section($activity, ['title' => 'Test section 1']);

        $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER],
            true,
            false
        );

        $element = $perform_generator->create_element(['title' => 'Question one', 'plugin_name' => $element_plugin_name]);
        $perform_generator->create_section_element($section, $element);

        $perform_generator->create_single_activity_track_and_assignment($activity);

        return $activity;
    }

    protected function generator(): \mod_perform\testing\generator {
        $data_generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        return $data_generator->get_plugin_generator('mod_perform');
    }

}