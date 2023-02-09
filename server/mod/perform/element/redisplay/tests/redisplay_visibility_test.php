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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_redisplay
 */

use core\orm\collection;
use mod_perform\entity\activity\element;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\response\section_element_response;
use mod_perform\entity\activity\element_response;
use mod_perform\models\activity\section_element;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;

require_once(__DIR__ . '/../../../tests/section_element_reference_test.php');

/**
 * @group perform
 * @group perform_element
 */
class performelement_redisplay_redisplay_visibility_testcase extends section_element_reference_testcase {

    public function test_manager_can_view_other_managers_previous_response(): void {
        $generator = self::getDataGenerator();
        $perform_generator = \mod_perform\testing\generator::instance();
        $this->create_test_data();

        $subject_user = $generator->create_user();
        $original_manager_user = $generator->create_user();
        $new_manager_user = $generator->create_user();

        $original_subject_instance = $perform_generator->create_subject_instance([
            'activity_id' => $this->source_activity->id,
            'subject_is_participating' => true,
            'subject_user_id' => $subject_user->id,
            'other_participant_id' => $original_manager_user->id,
            'include_questions' => true,
        ]);
        /** @var participant_instance_entity $original_manager_user_participant_instance */
        $original_manager_user_participant_instance = $original_subject_instance->participant_instances->last();

        $original_manager_user_participant_section = $perform_generator->create_participant_section(
            $this->source_activity,
            $original_manager_user_participant_instance,
            false,
            $this->source_section_element->section
        );

        $original_manager_response = $this->create_response($original_manager_user_participant_instance, $this->source_section_element);

        self::setUser(null);

        self::assertTrue(
            section_element_response::can_user_view_response($original_manager_response, $original_manager_user->id)
        );
        // New manager shouldn't be allowed to view the response: they aren't participating in the activity or a redisplay activity
        self::assertFalse(
            section_element_response::can_user_view_response($original_manager_response, $new_manager_user->id)
        );

        self::setAdminUser();
        $new_subject_instance = $perform_generator->create_subject_instance([
            'activity_id' => $this->referencing_redisplay_activity->id,
            'subject_is_participating' => true,
            'subject_user_id' => $subject_user->id,
            'other_participant_id' => $new_manager_user->id,
            'include_questions' => true,
        ]);
        /** @var participant_instance_entity $new_manager_user_participant_instance */
        $new_manager_user_participant_instance = $new_subject_instance->participant_instances->last();
        $new_manager_user_participant_instance_model = participant_instance::load_by_entity($new_manager_user_participant_instance);
        $new_manager_user_participant_section = $perform_generator->create_participant_section(
            $this->referencing_redisplay_activity,
            $new_manager_user_participant_instance,
            false,
            $this->referencing_redisplay_section_element->section
        );

        self::setUser(null);

        self::assertTrue(
            section_element_response::can_user_view_response($original_manager_response, $original_manager_user->id)
        );
        // Manager is now participating in the redisplay section, so they can view it now.
        self::assertTrue(
            section_element_response::can_user_view_response($original_manager_response, $new_manager_user->id)
        );
        self::assertTrue(section_element_response::can_participant_view_response(
            $original_manager_response, $new_manager_user_participant_instance_model
        ));

        /**
         * Convert the referencing element to an aggregation question type.
         * Aggregation is another reference element, but it should not allow direct access the the source section element,
         * it's response is based on calculations for the source section element responses.
         * @var element $aggregation_element
         * @see \performelement_aggregation\aggregation
         */
        $aggregation_element = element::repository()->find($this->referencing_redisplay_section_element->element->id);
        $aggregation_element->plugin_name = 'aggregation';
        $aggregation_element->save();

        // This condition is still true, because the user is viewing their own response.
        self::assertTrue(
            section_element_response::can_user_view_response($original_manager_response, $original_manager_user->id)
        );

        // Because the reference element is no longer a redisplay element,
        // the other users should no longer be able to view the source responses.
        self::assertFalse(
            section_element_response::can_user_view_response($original_manager_response, $new_manager_user->id)
        );
        self::assertFalse(
            section_element_response::can_participant_view_response(
                $original_manager_response, $new_manager_user_participant_instance_model
            )
        );
    }

    /**
     * @param participant_instance|participant_instance_entity $participant_instance
     * @param section_element $section_element
     * @return element_response
     */
    private function create_response(participant_instance_entity $participant_instance, section_element $section_element): element_response {
        $response_model = new section_element_response(
            participant_instance::load_by_entity($participant_instance),
            $section_element,
            null,
            new collection()
        );

        $response_model->set_response_data(json_encode('Response ' . uniqid()));
        $response_model->save();

        return new element_response($response_model->id);
    }

}
