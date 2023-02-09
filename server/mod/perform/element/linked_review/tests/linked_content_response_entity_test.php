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

use mod_perform\testing\generator as perform_generator;
use performelement_linked_review\entity\linked_review_content_response;
use performelement_linked_review\models\linked_review_content;
use performelement_linked_review\testing\generator as linked_review_generator;

/**
 * @group perform
 * @group perform_element
 */
class performelement_linked_review_content_response_entity_testcase extends advanced_testcase {

    public function test_it_update_or_create_response() {
        self::setAdminUser();
        [$activity, $section, $element, $section_element] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        [$user, $subject_instance, $participant_instance, $participant_section] = linked_review_generator::instance()
            ->create_participant_in_section(['activity' => $activity, 'section' => $section]);
        $child_element = perform_generator::instance()->create_child_element(['parent_element' => $element]);

        $linked_review_content_1 = linked_review_content::create(1, $section_element->id, $participant_instance->id, false);
        $linked_review_content_2 = linked_review_content::create(2, $section_element->id, $participant_instance->id, false);
        $existing_responses = linked_review_content_response::repository()->get()->all();
        $this->assertCount(0, $existing_responses);

        // Create a fresh record.
        linked_review_content_response::update_or_create_response(
            $linked_review_content_1->id,
            $child_element->id,
            $participant_instance->id,
            '"content response"'
        );

        $existing_responses = linked_review_content_response::repository()->get()->all();
        $this->assertCount(1, $existing_responses);

        // Update an existing record.
        linked_review_content_response::update_or_create_response(
            $linked_review_content_1->id,
            $child_element->id,
            $participant_instance->id,
            '"content response updated"'
        );
        $existing_responses = linked_review_content_response::repository()->get()->all();
        $this->assertCount(1, $existing_responses);

        // Create another content response record.
        linked_review_content_response::update_or_create_response(
            $linked_review_content_2->id,
            $child_element->id,
            $participant_instance->id,
            '"another record"'
        );
        $existing_responses = linked_review_content_response::repository()->get()->all();
        $this->assertCount(2, $existing_responses);
    }
}