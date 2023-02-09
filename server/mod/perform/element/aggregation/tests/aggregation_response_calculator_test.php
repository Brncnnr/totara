<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\constants;
use mod_perform\entity\activity\element_response as element_response_entity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\section_element;
use mod_perform\models\response\participant_section as participant_section_model;
use mod_perform\state\activity\active;
use mod_perform\testing\generator as perform_generator;
use performelement_aggregation\aggregation;
use performelement_aggregation\calculations\average;
use performelement_custom_rating_scale\custom_rating_scale;
use performelement_numeric_rating_scale\numeric_rating_scale;
use performelement_redisplay\redisplay;

require_once __DIR__ . '/is_saved_aggregate_average_response.php';

/**
 * @group perform
 */
class performelement_aggregation_aggregation_response_calculator_testcase extends advanced_testcase {

    /**
     * @var participant_section_model
     */
    private $subject_participant_section, $subject_other_participant_section;

    /**
     * @var section_element_entity
     */
    private $aggregation_section_element;

    /**
     * @var participant_instance_entity
     */
    private $manager_participant_instance, $subject_participant_instance;

    /**
     * @var section_element
     */
    private $q1_section_element, $q2_section_element, $q3_section_element;

    public function test_aggregation_across_multiple_sections(): void {
        $this->create_elements();

        // Only the first section will be aggregated on, nothing has been answered yet, so no aggregate response should be saved.
        $this->subject_participant_section->complete();
        self::assertThat(null, new is_saved_aggregate_average_response($this->subject_participant_instance, $this->aggregation_section_element)); // () / () = null

        // Add source responses.
        $q1_response = new element_response_entity();
        $q1_response->participant_instance_id = $this->subject_participant_instance->id;
        $q1_response->section_element_id = $this->q1_section_element->id;
        $q1_response->response_data = json_encode(2, JSON_THROW_ON_ERROR); // = 2
        $q1_response->save();

        $q2_response = new element_response_entity();
        $q2_response->participant_instance_id = $this->subject_participant_instance->id;
        $q2_response->section_element_id = $this->q2_section_element->id;
        $q2_response->response_data = json_encode('option_7', JSON_THROW_ON_ERROR); // = 7
        $q2_response->save();

        $q3_response = new element_response_entity();
        $q3_response->participant_instance_id = $this->subject_participant_instance->id;
        $q3_response->section_element_id = $this->q3_section_element->id;
        $q3_response->response_data = null; // We will answer this one later.
        $q3_response->save();

        // Only the first section will be aggregated on.
        $this->subject_participant_section->complete();

        // This should result in an average response for the subject (but ony from the first section so far).
        $manager_aggregated_responses = element_response_entity::repository()
            ->where('participant_instance_id', $this->manager_participant_instance->id)
            ->where('section_element_id', $this->aggregation_section_element->id)
            ->get();
        self::assertEmpty($manager_aggregated_responses);

        self::assertThat(2, new is_saved_aggregate_average_response($this->subject_participant_instance, $this->aggregation_section_element)); // (2) / 1 = 2

        // Now the second section will be aggregated on (but not q3 because it is unanswered).
        // This should result in an average response for the subject (but ony from the first section so far).
        $this->subject_other_participant_section->complete();
        self::assertThat(4.5, new is_saved_aggregate_average_response($this->subject_participant_instance, $this->aggregation_section_element)); // (2 + 7) / 2 = 4.5

        // Now the second section will be aggregated again, including q3.
        $q3_response->participant_instance_id = $this->subject_participant_instance->id;
        $q3_response->section_element_id = $this->q3_section_element->id;
        $q3_response->response_data = json_encode(4, JSON_THROW_ON_ERROR); // = 4
        $q3_response->save();

        $this->subject_other_participant_section->complete();

        self::assertThat(4.3333, new is_saved_aggregate_average_response($this->subject_participant_instance, $this->aggregation_section_element)); // (2 + 7 + 4) / 3 = 4.3333
    }

    public function test_excluded_values(): void {
        $this->create_elements([0, -1, 5, 3.555, null]);

        // Add source responses.
        $q1_response = new element_response_entity();
        $q1_response->participant_instance_id = $this->subject_participant_instance->id;
        $q1_response->section_element_id = $this->q1_section_element->id;
        $q1_response->response_data = json_encode(1, JSON_THROW_ON_ERROR); // = 1
        $q1_response->save();

        $q2_response = new element_response_entity();
        $q2_response->participant_instance_id = $this->subject_participant_instance->id;
        $q2_response->section_element_id = $this->q2_section_element->id;
        $q2_response->response_data = json_encode('option_1', JSON_THROW_ON_ERROR); // = 1
        $q2_response->save();

        $q3_response = new element_response_entity();
        $q3_response->participant_instance_id = $this->subject_participant_instance->id;
        $q3_response->section_element_id = $this->q3_section_element->id;
        $q3_response->response_data = json_encode(1, JSON_THROW_ON_ERROR); // = 1
        $q3_response->save();

        // Complete both sections to trigger complete aggregation.
        $this->subject_participant_section->complete();
        $this->subject_other_participant_section->complete();
        self::assertThat(1, new is_saved_aggregate_average_response($this->subject_participant_instance, $this->aggregation_section_element)); // (1 + 1 + 1) / 3 = 1

        $q1_response->participant_instance_id = $this->subject_participant_instance->id;
        $q1_response->section_element_id = $this->q1_section_element->id;
        $q1_response->response_data = json_encode(-1, JSON_THROW_ON_ERROR); // = -1 - excluded
        $q1_response->save();

        $q2_response->participant_instance_id = $this->subject_participant_instance->id;
        $q2_response->section_element_id = $this->q2_section_element->id;
        $q2_response->response_data = json_encode('option_5', JSON_THROW_ON_ERROR); // = 5 - excluded
        $q2_response->save();

        $q3_response->participant_instance_id = $this->subject_participant_instance->id;
        $q3_response->section_element_id = $this->q3_section_element->id;
        $q3_response->response_data = json_encode(3.5, JSON_THROW_ON_ERROR); // = 3.5 - not quite excluded (3.555 will be)
        $q3_response->save();

        // Re-trigger calculations.
        $this->subject_participant_section->complete();
        self::assertThat(3.5, new is_saved_aggregate_average_response($this->subject_participant_instance, $this->aggregation_section_element)); // (3.5) / 1 = 3.5

        $q1_response->participant_instance_id = $this->subject_participant_instance->id;
        $q1_response->section_element_id = $this->q1_section_element->id;
        $q1_response->response_data = json_encode(1, JSON_THROW_ON_ERROR); // = 1
        $q1_response->save();

        $q2_response->participant_instance_id = $this->subject_participant_instance->id;
        $q2_response->section_element_id = $this->q2_section_element->id;
        $q2_response->response_data = json_encode('option_5', JSON_THROW_ON_ERROR); // = 5 - excluded
        $q2_response->save();

        $q3_response->participant_instance_id = $this->subject_participant_instance->id;
        $q3_response->section_element_id = $this->q3_section_element->id;
        $q3_response->response_data = json_encode(0, JSON_THROW_ON_ERROR); // = 0 - excluded
        $q3_response->save();

        // Re-trigger calculations.
        $this->subject_participant_section->complete();
        self::assertThat(1, new is_saved_aggregate_average_response($this->subject_participant_instance, $this->aggregation_section_element)); // (1) / 1 = 1

        $q1_response->participant_instance_id = $this->subject_participant_instance->id;
        $q1_response->section_element_id = $this->q1_section_element->id;
        $q1_response->response_data = json_encode(3.555, JSON_THROW_ON_ERROR); // = 3.555 - excluded
        $q1_response->save();

        // Re-trigger calculations.
        $this->subject_participant_section->complete();
        self::assertThat(null, new is_saved_aggregate_average_response($this->subject_participant_instance, $this->aggregation_section_element)); // () / () = null
    }

    public function test_redisplay_is_not_aggregate_on(): void {
        $this->create_elements();

        // Add source responses..
        $q1_response = new element_response_entity();
        $q1_response->participant_instance_id = $this->subject_participant_instance->id;
        $q1_response->section_element_id = $this->q1_section_element->id;
        $q1_response->response_data = json_encode(1, JSON_THROW_ON_ERROR); // = 1
        $q1_response->save();

        // Complete both sections to trigger complete aggregation.
        $this->subject_participant_section->complete();
        $this->subject_other_participant_section->complete();
        self::assertThat(1, new is_saved_aggregate_average_response($this->subject_participant_instance, $this->aggregation_section_element)); // (1) / 1 = 1

        // Convert the aggregation element to a redisplay element.
        $element = $this->aggregation_section_element->element;
        $element->plugin_name = redisplay::get_plugin_name();
        $element->data = json_encode([redisplay::SOURCE_SECTION_ELEMENT_ID => $q1_response->section_element_id], JSON_THROW_ON_ERROR);
        $element->save();

        // The section_element_reference still exists, so no need for post save.
        // But because the reference is from a redisplay element it should not be aggregated on, so changing the value of q1 should not update the average.
        $q1_response->participant_instance_id = $this->subject_participant_instance->id;
        $q1_response->section_element_id = $this->q1_section_element->id;
        $q1_response->response_data = json_encode(500, JSON_THROW_ON_ERROR); // = 1
        $q1_response->save();

        $this->subject_participant_section->complete();
        $this->subject_other_participant_section->complete();
        self::assertThat(1, new is_saved_aggregate_average_response($this->subject_participant_instance, $this->aggregation_section_element)); // not re-calculated.
    }

    public function tearDown(): void {
        $this->subject_participant_section = null;
        $this->subject_other_participant_section = null;
        $this->subject_participant_instance = null;
        $this->manager_participant_instance = null;
        $this->aggregation_section_element = null;
        $this->q1_section_element = null;
        $this->q2_section_element = null;
        $this->q3_section_element = null;
    }

    /**
     * @param float[] $excluded_values
     */
    private function create_elements($excluded_values = []): void {
        self::setAdminUser();

        $generator = perform_generator::instance();

        $subject_user = self::getDataGenerator()->create_user();
        $subject_user_id = $subject_user->id;
        $manager_user = self::getDataGenerator()->create_user();
        $manager_user_id = $manager_user->id;

        $subject_instance = $generator->create_subject_instance([
            'activity_name' => 'Test aggregation',
            'activity_status' => active::get_code(),
            'subject_user_id' => $subject_user_id,
            'other_participant_id' => $manager_user_id,
            'relationships_can_view' => 'subject, manager',
            'relationships_can_answer' => 'subject, manager',
            'subject_is_participating' => true,
            'include_questions' => false,
        ]);
        $participant_instances = participant_instance_entity::repository()->get();

        $this->subject_participant_instance = $participant_instances->find(function (participant_instance_entity $instance) use ($subject_user_id){
            return $instance->participant_id === $subject_user_id;
        });

        $this->manager_participant_instance = $participant_instances->find(function (participant_instance_entity $instance) use ($manager_user_id){
            return $instance->participant_id === $manager_user_id;
        });

        $activity = activity_model::load_by_entity($subject_instance->activity());
        $subject_section = $generator->create_section($activity, ['title' => 'Subject section']);
        $subject_other_section = $generator->create_section($activity, ['title' => 'Subject other section']);

        $q1_element = $generator->create_element([
            'plugin_name' => numeric_rating_scale::get_plugin_name(),
            'data' => json_encode([
                'defaultValue' => 1,
                'highValue' => 1000,
                'lowValue' => -1000,
            ], JSON_THROW_ON_ERROR),
        ]);

        $q2_element = $generator->create_element([
            'plugin_name' => custom_rating_scale::get_plugin_name(),
            'data' => json_encode([
                'options' => [
                    [
                        'name' => 'option_1',
                        'value' => ['text' => 'Option 1', 'score' => '1']
                    ],
                    [
                        'name' => 'option_3',
                        'value' => ['text' => 'Option 2', 'score' => '3']
                    ],
                    [
                        'name' => 'option_5',
                        'value' => ['text' => 'Option 3', 'score' => '5']
                    ],
                    [
                        'name' => 'option_7',
                        'value' => ['text' => 'Option 4', 'score' => '7']
                    ],
                    [
                        'name' => 'option_9',
                        'value' => ['text' => 'Option 5', 'score' => '9']
                    ],
                ]
            ], JSON_THROW_ON_ERROR),
        ]);

        $q3_element = $generator->create_element([
            'plugin_name' => numeric_rating_scale::get_plugin_name(),
            'data' => json_encode([
                'defaultValue' => 1,
                'highValue' => 1000,
                'lowValue' => -1000,
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->q1_section_element = $generator->create_section_element($subject_section, $q1_element);
        $this->q2_section_element = $generator->create_section_element($subject_other_section, $q2_element);
        $this->q3_section_element = $generator->create_section_element($subject_other_section, $q3_element);

        $generator->create_section_relationship(
            $subject_section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT],
            true,
            true
        );

        $subject_participant_section_entity = $generator->create_participant_section($activity, $this->subject_participant_instance, false, $subject_section);
        $subject_other_participant_section_entity = $generator->create_participant_section($activity, $this->subject_participant_instance, false, $subject_other_section);

        $this->subject_participant_section = participant_section_model::load_by_entity($subject_participant_section_entity);
        $this->subject_other_participant_section = participant_section_model::load_by_entity($subject_other_participant_section_entity);

        $manager_section = $generator->create_section($activity, ['title' => 'Manager section']);
        $aggregation_element = $generator->create_element([
            'plugin_name' => 'aggregation',
            'data' => json_encode([
                aggregation::SOURCE_SECTION_ELEMENT_IDS => [$this->q1_section_element->id, $this->q2_section_element->id, $this->q3_section_element->id],
                aggregation::CALCULATIONS => [average::get_name()],
                aggregation::EXCLUDED_VALUES => $excluded_values,
            ], JSON_THROW_ON_ERROR),
        ]);

        $aggregate = $generator->create_section_element($manager_section, $aggregation_element);
        $this->aggregation_section_element = section_element_entity::repository()->find($aggregate->get_id());

        $generator->create_section_relationship(
            $manager_section,
            ['relationship' => constants::RELATIONSHIP_MANAGER],
            true,
            true
        );

        $generator->create_participant_section($activity, $this->manager_participant_instance, false, $manager_section);

        // No responses yet - so there should not yet be an aggregated response
        $aggregated_responses = element_response_entity::repository()
            ->where_in('participant_instance_id', [$this->subject_participant_instance->id, $this->manager_participant_instance->id])
            ->where('section_element_id', $this->aggregation_section_element->id)
            ->get();

        self::assertEmpty($aggregated_responses);
    }

}