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
 */

use core\collection;
use mod_perform\entity\activity\participant_instance;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\settings\visibility_conditions\all_responses;
use mod_perform\models\activity\settings\visibility_conditions\none;
use mod_perform\models\activity\settings\visibility_conditions\own_response;
use mod_perform\models\activity\settings\visibility_conditions\visibility_manager;
use mod_perform\models\activity\settings\visibility_conditions\visibility_option;
use mod_perform\state\participant_instance\closed;
use mod_perform\state\participant_instance\open;
use mod_perform\state\participant_instance\progress_not_applicable;
use mod_perform\state\participant_section\availability_not_applicable;

/**
 * @covers \mod_perform\models\activity\settings\visibility_conditions\visibility_manager
 *
 * @group perform
*/
class mod_perform_visibility_conditions_test extends advanced_testcase {

    public function test_get_all_options(): void {
        $options = (new visibility_manager())->get_options();
        $this->assertCount(3, $options);

        foreach ($options as $option) {
            $this->assertInstanceOf(visibility_option::class, $option);
        }
    }

    public function test_has_option_with_value(): void {
        $visibility_manager = new visibility_manager();
        $this->assertTrue($visibility_manager->has_option_with_value(0));
        $this->assertTrue($visibility_manager->has_option_with_value(1));
        $this->assertTrue($visibility_manager->has_option_with_value(2));
        $this->assertFalse($visibility_manager->has_option_with_value(100));
    }

    public function test_all_responses_is_default_anonymous_option(): void {
        $options = (new visibility_manager())->get_options();

        foreach ($options as $option) {
            $expected = $option instanceof all_responses;
            $this->assertEquals($expected, $option->default_anonymous_option());
        }
    }

    /**
     * @dataProvider participant_sections_provider
     *
     * @param participant_instance_model $participant_instance
     * @param collection $other_participant_sections
     * @param array $expected_result
     */
    public function test_none_visibility_condition_shows_responses(
        participant_instance_model $participant_instance,
        collection $other_participant_sections,
        array $expected_result
    ): void {
        $none_condition = new none();
        $this->assertEquals($expected_result['none'], $none_condition->show_responses($participant_instance, $other_participant_sections));
    }

    /**
     * @dataProvider participant_sections_provider
     *
     * @param participant_instance_model $participant_instance
     * @param collection $other_participant_sections
     * @param array $expected_result
     */
    public function test_own_response_visibility_condition_shows_responses(
        participant_instance_model $participant_instance,
        collection $other_participant_sections,
        array $expected_result
    ): void {
        $own_response_condition = new own_response();

        $this->assertEquals($expected_result['own_response'], $own_response_condition->show_responses($participant_instance, $other_participant_sections));
    }

    /**
     * @dataProvider participant_sections_provider
     *
     * @param participant_instance_model $participant_instance
     * @param collection $other_participant_sections
     * @param array $expected_result
     */
    public function test_all_response_visibility_condition_shows_responses(
        participant_instance_model $participant_instance,
        collection $other_participant_sections,
        array $expected_result
    ): void {
        $all_response_condition = new all_responses();
        $this->assertEquals(
            $expected_result['all_responses'],
            $all_response_condition->show_responses($participant_instance, $other_participant_sections)
        );
    }

    /**
     * Create participant instances used to test visibility options.
     *
     * @return array
     */
    public function participant_sections_provider(): array {
        $participant_instance = [
            'open' => $this->create_mock_participant_instance_with_availability(open::class),
            'closed' => $this->create_mock_participant_instance_with_availability(closed::class),
            'not_applicable' => $this->create_mock_participant_instance_with_availability(progress_not_applicable::class),
        ];
        $other_participant_instances = $this->create_other_participant_instances();

        return [
            'participant section open and other participant sections open' => [
                $participant_instance['open'],
                $other_participant_instances['3_open'],
                [
                    'none' => true,
                    'own_response' => false,
                    'all_responses' => false,
                ],
            ],
            'participant section not applicable and other participant sections open' => [
                $participant_instance['not_applicable'],
                $other_participant_instances['3_open'],
                [
                    'none' => true,
                    'own_response' => true,
                    'all_responses' => false,
                ],
            ],
            'participant section open and other participant sections closed' => [
                $participant_instance['open'],
                $other_participant_instances['3_closed'],
                [
                    'none' => true,
                    'own_response' => false,
                    'all_responses' => false,
                ]
            ],
            'participant section not applicable and other participant sections closed' => [
                $participant_instance['not_applicable'],
                $other_participant_instances['3_closed'],
                [
                    'none' => true,
                    'own_response' => true,
                    'all_responses' => true,
                ]
            ],
            'participant section open and other participant sections open with one closed' => [
                $participant_instance['open'],
                $other_participant_instances['3_open_1_closed'],
                [
                    'none' => true,
                    'own_response' => false,
                    'all_responses' => false,
                ]
            ],
            'participant section open and other participant sections closed with one open' => [
                $participant_instance['open'],
                $other_participant_instances['3_closed_1_open'],
                [
                    'none' => true,
                    'own_response' => false,
                    'all_responses' => false,
                ]
            ],
            'participant section closed and other participant sections open' => [
                $participant_instance['closed'],
                $other_participant_instances['3_open'],
                [
                    'none' => true,
                    'own_response' => true,
                    'all_responses' => false,
                ],
            ],
            'participant section closed and other participant sections open with one closed' => [
                $participant_instance['closed'],
                $other_participant_instances['3_open_1_closed'],
                [
                    'none' => true,
                    'own_response' => true,
                    'all_responses' => false,
                ]
            ],
            'participant section closed and other participant sections closed with one open' => [
                $participant_instance['closed'],
                $other_participant_instances['3_closed_1_open'],
                [
                    'none' => true,
                    'own_response' => true,
                    'all_responses' => false,
                ]
            ],
            'participant section closed and other participant sections closed' => [
                $participant_instance['closed'],
                $other_participant_instances['3_closed'],
                [
                    'none' => true,
                    'own_response' => true,
                    'all_responses' => true,
                ]
            ],
        ];
    }

    /**
     * Creates other participant instances of different scenarios.
     *
     * @return collection[]
     * @throws coding_exception
     */
    private function create_other_participant_instances(): array {
        $other_participant_instance_entity = [
            'open' => new participant_instance([
                'availability' => open::get_code(),
            ]),
            'closed' => new participant_instance([
                'availability' => closed::get_code(),
            ]),
            'not_applicable' => new participant_instance([
                'availability' => availability_not_applicable::get_code(),
            ]),
        ];
        $other_participant_instances_3_closed_1_open = array_fill(0,3, $other_participant_instance_entity['closed']);
        $other_participant_instances_3_closed_1_open[] = $other_participant_instance_entity['open'];
        $other_participant_instances_3_closed_1_open[] = $other_participant_instance_entity['not_applicable'];

        $other_participant_instances_3_open_1_closed = array_fill(0,3, $other_participant_instance_entity['open']);
        $other_participant_instances_3_open_1_closed[] = $other_participant_instance_entity['closed'];
        $other_participant_instances_3_open_1_closed[] = $other_participant_instance_entity['not_applicable'];

        $other_participant_instances_3_open = array_fill(0,3, $other_participant_instance_entity['open']);
        $other_participant_instances_3_open[] = $other_participant_instance_entity['not_applicable'];


        $other_participant_instances_3_closed = array_fill(0,3, $other_participant_instance_entity['closed']);
        $other_participant_instances_3_closed[] = $other_participant_instance_entity['not_applicable'];

        return [
            '3_open' => new collection($other_participant_instances_3_open),
            '3_closed' => new collection($other_participant_instances_3_closed),
            '3_open_1_closed' => new collection($other_participant_instances_3_open_1_closed),
            '3_closed_1_open' => new collection($other_participant_instances_3_closed_1_open),
        ];
    }

    /**
     * @param string $availability_class
     *
     * @return participant_instance_model
     */
    private function create_mock_participant_instance_with_availability(string $availability_class): participant_instance_model {
        $participant_instance = $this->createMock(participant_instance_model::class);
        $participant_instance->method('get_availability_state')->willReturn(new $availability_class($participant_instance));

        return $participant_instance;
    }
}
