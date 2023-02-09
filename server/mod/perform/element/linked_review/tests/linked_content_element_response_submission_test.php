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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package performelement_linked_review
 */

use core\collection;
use core\testing\generator;
use mod_perform\models\activity\element;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\response\section_element_response;
use mod_perform\testing\generator as perform_generator;
use performelement_linked_review\entity\linked_review_content_response;
use performelement_linked_review\models\linked_review_content;
use performelement_linked_review\testing\generator as linked_review_generator;

/**
 * @group perform
 * @group perform_element
 */
class performelement_linked_review_content_element_response_submission_testcase extends advanced_testcase {

    public function test_saving_and_building_content_child_element_responses() {
        self::setAdminUser();
        $subject = generator::instance()->create_user();

        /** @var element $element */
        [$activity, $section1, $element, $section_element] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        $child_element = perform_generator::instance()->create_child_element(['parent_element' => $element]);

        $subject_instance = perform_generator::instance()->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'include_questions' => false,
        ]);
        $participant_instance = $subject_instance->participant_instances->first();
        $content_id_1 = linked_review_content::create(1, $section_element->id, $participant_instance->id, false)->id;
        $content_id_2 = linked_review_content::create(2, $section_element->id, $participant_instance->id, false)->id;

        $child_element_config = $element->element_plugin->get_child_element_config();
        $repeating_item_identifier = $child_element_config->repeating_item_identifier;
        $child_element_responses_identifier = $child_element_config->child_element_responses_identifier;

        $section_element_response = new section_element_response(
            participant_instance::load_by_entity($participant_instance),
            $section_element,
            null,
            new collection()
        );

        // Assert responses are empty.
        $this->assertCount(0, linked_review_content_response::repository()->get()->all());

        // Linked review response for content.
        $linked_review_response = [
            $repeating_item_identifier => [
                $content_id_1 => [
                    $child_element_responses_identifier => [
                        $child_element->id => [
                            "response_data" => '"content 1 response"',
                            "child_element_id" => $child_element->id,
                        ]
                    ],
                    "content_id" => $content_id_1,
                ],
                $content_id_2 => [
                    $child_element_responses_identifier => [
                        $child_element->id => [
                            "response_data" => '"content 2 response"',
                            "child_element_id" => $child_element->id,
                        ]
                    ],
                    "content_id" => $content_id_2,
                ],
            ],
        ];
        $section_element_response->set_response_data(json_encode(['response' => $linked_review_response]))->save();

        $expected_responses = [
            $content_id_1 => '"content 1 response"',
            $content_id_2 => '"content 2 response"',
        ];
        $saved_responses = linked_review_content_response::repository()->get()->all();
        $this->assertCount(2, $saved_responses);

        foreach ($saved_responses as $saved_response) {
            $expected_result = $expected_responses[$saved_response->linked_review_content_id];
            $this->assertEquals($expected_result, $saved_response->response_data);
        }
        // Loading the response.
        $built_response = json_decode($section_element_response->response_data, true);
        $this->assertEqualsCanonicalizing($linked_review_response, $built_response);

        // Test updating responses.
        $updated_linked_review_response = [
            $repeating_item_identifier => [
                $content_id_1 => [
                    $child_element_responses_identifier => [
                        $child_element->id => [
                            "response_data" => '"content 1 response updated"',
                            "child_element_id" => $child_element->id,
                        ]
                    ],
                    "content_id" => $content_id_1,
                ],
                $content_id_2 => [
                    $child_element_responses_identifier => [
                        $child_element->id => [
                            "response_data" => '"content 2 response updated"',
                            "child_element_id" => $child_element->id,
                        ]
                    ],
                    "content_id" => $content_id_2,
                ],
            ],
        ];
        $section_element_response->set_response_data(json_encode(['response' => $updated_linked_review_response]))->save();

        $expected_updated_responses = [
            $content_id_1 => '"content 1 response updated"',
            $content_id_2 => '"content 2 response updated"',
        ];
        $saved_responses = linked_review_content_response::repository()->get()->all();
        $this->assertCount(2, $saved_responses);

        foreach ($saved_responses as $saved_response) {
            $expected_result = $expected_updated_responses[$saved_response->linked_review_content_id];
            $this->assertEquals($expected_result, $saved_response->response_data);
        }

        // Loading the updated response.
        $built_response = json_decode($section_element_response->response_data, true);
        $this->assertEqualsCanonicalizing($updated_linked_review_response, $built_response);
    }

    public function test_saving_response_to_content_participant_instance_does_not_have_access_to() {
        self::setAdminUser();
        $subject = generator::instance()->create_user();
        $another_subject = generator::instance()->create_user();

        /** @var element $element */
        [$activity, $section1, $element, $section_element] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        $child_element = perform_generator::instance()->create_child_element(['parent_element' => $element]);

        $subject_instance_1 = perform_generator::instance()->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'include_questions' => false,
        ]);
        $subject_instance_2 = perform_generator::instance()->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_is_participating' => true,
            'subject_user_id' => $another_subject->id,
            'include_questions' => false,
        ]);
        $participant_instance_1 = $subject_instance_1->participant_instances->first();
        $content_id_2 = linked_review_content::create(2, $section_element->id, $participant_instance_1->id, false)->id;

        $participant_instance_2 = $subject_instance_2->participant_instances->first();
        $other_content_id_1 = linked_review_content::create(4, $section_element->id, $participant_instance_2->id, false)->id;

        $child_element_config = $element->element_plugin->get_child_element_config();
        $repeating_item_identifier = $child_element_config->repeating_item_identifier;
        $child_element_responses_identifier = $child_element_config->child_element_responses_identifier;
        $child_element_id = $element->children->first()->id;

        $section_element_response = new section_element_response(
            participant_instance::load_by_entity($participant_instance_1),
            $section_element,
            null,
            new collection()
        );

        // Linked review response for content.
        $linked_review_response = [
            $repeating_item_identifier => [
                $other_content_id_1 => [
                    $child_element_responses_identifier => [
                        $child_element->id => [
                            "response_data" => '"content 1 response"',
                            "child_element_id" => $child_element->id,
                        ]
                    ],
                    "content_id" => $other_content_id_1,
                ],
                $content_id_2 => [
                    $child_element_responses_identifier => [
                        $child_element->id => [
                            "response_data" => '"content 2 response"',
                            "child_element_id" => $child_element->id,
                        ]
                    ],
                    "content_id" => $content_id_2,
                ],
            ],
        ];
        $section_element_response->set_response_data(json_encode(['response' => $linked_review_response]))->save();

        $debug_messages = $this->getDebuggingMessages();
        $this->assertCount(1, $debug_messages);
        $error_message = reset($debug_messages);
        $this->assertStringContainsString('You do not have permissions to respond to this content.', $error_message->message);
        $this->assertDebuggingCalledCount(1);

        $saved_responses = linked_review_content_response::repository()->get()->all();
        $this->assertCount(0, $saved_responses);
    }

    public function test_saving_content_response_for_non_existing_child_element() {
        self::setAdminUser();
        $subject = generator::instance()->create_user();

        /** @var element $element */
        [$activity, $section1, $element, $section_element] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();

        $subject_instance_1 = perform_generator::instance()->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'include_questions' => false,
        ]);
        $participant_instance_1 = $subject_instance_1->participant_instances->first();
        $content_id_1 = linked_review_content::create(1, $section_element->id, $participant_instance_1->id, false)->id;
        $content_id_2 = linked_review_content::create(2, $section_element->id, $participant_instance_1->id, false)->id;

        $child_element_config = $element->element_plugin->get_child_element_config();
        $repeating_item_identifier = $child_element_config->repeating_item_identifier;
        $child_element_responses_identifier = $child_element_config->child_element_responses_identifier;

        $section_element_response = new section_element_response(
            participant_instance::load_by_entity($participant_instance_1),
            $section_element,
            null,
            new collection()
        );
        $non_existing_child_element_1 = 10;
        $non_existing_child_element_2 = 20;

        // Linked review response for content.
        $linked_review_response = [
            $repeating_item_identifier => [
                $content_id_1 => [
                    $child_element_responses_identifier => [
                        $non_existing_child_element_1 => [
                            "response_data" => '"content 1 response"',
                            "child_element_id" => $non_existing_child_element_1,
                        ]
                    ],
                    "content_id" => $content_id_1,
                ],
                $content_id_2 => [
                    $child_element_responses_identifier => [
                        $non_existing_child_element_2 => [
                            "response_data" => '"content 2 response"',
                            "child_element_id" => $non_existing_child_element_2,
                        ]
                    ],
                    "content_id" => $content_id_2,
                ],
            ],
        ];
        $section_element_response->set_response_data(json_encode(['response' => $linked_review_response]))->save();

        $debug_messages = $this->getDebuggingMessages();
        $this->assertCount(1, $debug_messages);
        $error_message = reset($debug_messages);
        $this->assertStringContainsString('Child element does not exist.', $error_message->message);
        $this->assertDebuggingCalledCount(1);

        $saved_responses = linked_review_content_response::repository()->get()->all();
        $this->assertCount(0, $saved_responses);
    }
}