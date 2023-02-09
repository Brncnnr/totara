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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\collection;

use core\orm\query\builder;
use mod_perform\constants;
use mod_perform\entity\activity\element_response as element_response_entity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\entity\activity\section_relationship;
use mod_perform\hook\post_element_response_submission;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\section_element;
use mod_perform\models\response\section_element_response;
use mod_perform\models\response\element_validation_error;
use mod_perform\testing\generator as perform_generator;
use performelement_short_text\answer_length_exceeded_error;
use totara_core\relationship\relationship;

/**
 * @group perform
 */
class mod_perform_response_model_testcase extends advanced_testcase {

    /**
     * @dataProvider constructor_only_allows_responses_entities_related_to_others_provider
     * @param participant_instance_entity $participant_instance_entity
     * @param section_element_entity $section_element_entity
     * @param string $expected_message
     * @throws coding_exception
     */
    public function test_constructor_does_not_allow_responses_entities_not_related_to_others(
        participant_instance_entity $participant_instance_entity,
        section_element_entity $section_element_entity,
        string $expected_message
    ): void {

        $element_response_entity = new element_response_entity();
        $element_response_entity->participant_instance_id = 1;
        $element_response_entity->section_element_id = 1;

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage($expected_message);

        new section_element_response(
            participant_instance::load_by_entity($participant_instance_entity),
            section_element::load_by_entity($section_element_entity),
            $element_response_entity,
            new collection()
        );
    }

    public function constructor_only_allows_responses_entities_related_to_others_provider(): array {
        $matching_participant_instance = new participant_instance_entity(['id' => 1]);
        $not_matching_participant_instance = new participant_instance_entity(['id' => 2]);

        $matching_section_element = new section_element_entity(['id' => 1]);
        $not_matching_section_element = new section_element_entity(['id' => 2]);

        return [
            'Participant instance does not match element response' => [
                $not_matching_participant_instance,
                $matching_section_element,
                'participant_instance_id'
            ],
            'Section element does not match element response' => [
                $matching_participant_instance,
                $not_matching_section_element,
                'section_element_id'
            ],
        ];
    }

    public function test_post_element_response_submission_hook_is_called_on_saving() {
        self::setAdminUser();
        $subject = self::getDataGenerator()->create_user();
        /** @var perform_generator $generator */
        $generator = perform_generator::instance();
        $activity = $generator->create_activity_in_container();

        $subject_instance = $generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'include_questions' => true,
        ]);
        $participant_instance = $subject_instance->participant_instances->first();

        /** @var section_entity $section*/
        $section = $subject_instance->activity()->sections->first();
        $section_element = $section->section_elements->first();

        $section_element_response = new section_element_response(
            participant_instance::load_by_entity($participant_instance),
            section_element::load_by_entity($section_element),
            null,
            new collection()
        );
        $sink = $this->redirectHooks();
        $section_element_response->set_response_data('"random response"')->save();
        $hooks_executed = $sink->get_hooks();
        $this->assertGreaterThan(0, count($hooks_executed));
        $hook = reset($hooks_executed);
        $this->assertTrue($hook instanceof post_element_response_submission);
    }

    /**
     * @throws coding_exception
     */
    public function test_saving_supports_elements_that_have_not_been_responded_to(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();
        $participant = self::getDataGenerator()->create_user();

        /** @var perform_generator $generator */
        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => $participant->id,
            'include_questions' => true,
        ]);

        $participant_instance = $subject_instance->participant_instances->first();

        $track = $subject_instance->track;
        $activity = $track->activity;
        $sections = $activity->sections;
        /** @var section_entity $section */
        foreach ($sections as $section) {
            /** @var section_element_entity $section_element */
            $section_elements = $section->section_elements;
            foreach ($section_elements as $section_element) {
                $element_type = $section_element->element;
                if ($element_type) {
                    break 2;
                }
            }
        }

        $element_response = new section_element_response(
            participant_instance::load_by_entity($participant_instance),
            section_element::load_by_entity($section_element),
            null,
            new collection()
        );

        $element_response->save();

        $element_response_entity = new element_response_entity($element_response->get_id());

        // Saving when a response record has not yet been created will create the record with the foreign keys
        // pulled from the participant_instance and section_element.
        self::assertEquals($participant_instance->id, $element_response_entity->participant_instance_id);
        self::assertEquals($section_element->id, $element_response_entity->section_element_id);
    }

    /**
     * @throws coding_exception
     */
    public function test_validation_success(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();
        $participant = self::getDataGenerator()->create_user();

        /** @var perform_generator $generator */
        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => $participant->id,
            'include_questions' => true,
        ]);

        $participant_instance = $subject_instance->participant_instances->first();

        $track = $subject_instance->track;
        $activity = $track->activity;
        $sections = $activity->sections;
        /** @var section_entity $section */
        foreach ($sections as $section) {
            /** @var section_element_entity $section_element */
            $section_elements = $section->section_elements;
            foreach ($section_elements as $section_element) {
                $element_type = $section_element->element;
                if ($element_type) {
                    break 2;
                }
            }
        }

        $element_response = new section_element_response(
            participant_instance::load_by_entity($participant_instance),
            section_element::load_by_entity($section_element),
            null,
            new collection()
        );

        $element_response->set_response_data(json_encode('Hello there.'));

        self::assertTrue($element_response->validate_response());
    }

    /**
     * @throws coding_exception
     */
    public function test_validation_with_errors(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();
        $participant = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => $participant->id,
            'include_questions' => true,
        ]);

        $participant_instance = $subject_instance->participant_instances->first();

        $track = $subject_instance->track;
        $activity = $track->activity;
        $sections = $activity->sections;
        /** @var section_entity $section */
        foreach ($sections as $section) {
            /** @var section_element_entity $section_element */
            $section_elements = $section->section_elements;
            foreach ($section_elements as $section_element) {
                $element_type = $section_element->element;
                if ($element_type) {
                    break 2;
                }
            }
        }

        $element_response = new section_element_response(
            participant_instance::load_by_entity($participant_instance),
            section_element::load_by_entity($section_element),
            null,
            new collection()
        );

        // Structurally valid response, but will fail validation for being too long.
        $response_data = str_repeat('x', 1025);

        $element_response->set_response_data(json_encode($response_data, JSON_THROW_ON_ERROR));

        self::assertFalse($element_response->validate_response());

        /** @var element_validation_error[] $validation_errors */
        $validation_errors = $element_response->get_validation_errors()->all();

        self::assertCount(1, $validation_errors);

        self::assertEquals('Question text exceeds the maximum length', $validation_errors[0]->error_message);
        self::assertEquals(answer_length_exceeded_error::LENGTH_EXCEEDED, $validation_errors[0]->error_code);
    }

    public function test_user_can_view_response(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();

        $perform_generator = perform_generator::instance();
        $activity = $perform_generator->create_activity_in_container();

        $subject_user = $generator->create_user();
        $manager_user = $generator->create_user();
        $other_user = $generator->create_user();
        $admin_user = $generator->create_user();

        $subject_instance = $perform_generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_is_participating' => true,
            'subject_user_id' => $subject_user->id,
            'other_participant_id' => $manager_user->id,
            'include_questions' => true,
        ]);
        $subject_section = section_entity::repository()->get()->first();
        $subject_section_element = section_element_entity::repository()->get()->first();
        /** @var section_relationship $subject_section_relationship */
        $subject_section_relationship = section_relationship::repository()->get()->first();
        // Make it so the subject relationship isn't allowed to view other's responses
        $subject_section_relationship->can_view = false;
        $subject_section_relationship->save();

        /** @var participant_instance_entity $subject_user_participant_instance */
        $subject_user_participant_instance = $subject_instance->participant_instances->first();
        $subject_user_response_model = new section_element_response(
            participant_instance::load_by_entity($subject_user_participant_instance),
            section_element::load_by_entity($subject_section_element),
            null,
            new collection()
        );
        $subject_user_response_model->set_response_data(json_encode('Subject response'));
        $subject_user_response_model->save();
        $subject_user_response = new element_response_entity($subject_user_response_model->id);

        /** @var participant_instance_entity $manager_user_participant_instance */
        $manager_user_participant_instance = $subject_instance->participant_instances->last();
        $manager_user_response_model = new section_element_response(
            participant_instance::load_by_entity($manager_user_participant_instance),
            section_element::load_by_entity($subject_section_element),
            null,
            new collection()
        );
        $manager_user_response_model->set_response_data(json_encode('Manager response'));
        $manager_user_response_model->save();
        $manager_user_response = new element_response_entity($manager_user_response_model->id);

        $other_subject_instance = $perform_generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_is_participating' => true,
            'subject_user_id' => $other_user->id,
            'include_questions' => true,
        ]);
        $other_section_element = section_element_entity::repository()->get()->last();
        /** @var participant_instance_entity $other_user_participant_instance */
        $other_user_participant_instance = $other_subject_instance->participant_instances->first();
        $other_user_response_model = new section_element_response(
            participant_instance::load_by_entity($other_user_participant_instance),
            section_element::load_by_entity($other_section_element),
            null,
            new collection()
        );
        $other_user_response_model->set_response_data(json_encode('Other response'));
        $other_user_response_model->save();
        $other_user_response = new element_response_entity($other_user_response_model->id);

        \mod_perform\models\activity\section_relationship::create(
            $subject_section->id,
            relationship::load_by_idnumber(constants::RELATIONSHIP_EXTERNAL)->id,
            true,
            true
        );
        [$external_participant_instance] = $perform_generator->generate_external_participant_instances(
            $subject_instance->id,
            [
                'fullname' => 'A name',
                'email' => 'A email',
            ]
        );
        $external_participant_instance = participant_instance::load_by_entity($external_participant_instance);
        $external_user_response_model = new section_element_response(
            $external_participant_instance,
            section_element::load_by_entity($subject_section_element),
            null,
            new collection()
        );
        $external_user_response_model->set_response_data(json_encode('External response'));
        $external_user_response_model->save();
        $external_user_response = new element_response_entity($external_user_response_model->id);

        self::setUser(null);

        // Test as subject user
        // User can always view their own response
        $this->assertTrue(section_element_response::can_user_view_response($subject_user_response, $subject_user->id));
        // Can't view their manager's response because the section_relationship can_view field is false
        $this->assertFalse(section_element_response::can_user_view_response($manager_user_response, $subject_user->id));
        // Can't view the external response because the section_relationship can_view field is false
        $this->assertFalse(section_element_response::can_user_view_response($external_user_response, $subject_user->id));
        // Can't view the other user's response because they aren't participating in the same subject instance
        $this->assertFalse(section_element_response::can_user_view_response($other_user_response, $subject_user->id));

        // Test as manager user
        // User can always view their own response
        $this->assertTrue(section_element_response::can_user_view_response($manager_user_response, $manager_user->id));
        // Can view their manager's response because the section_relationship can_view field is true
        $this->assertTrue(section_element_response::can_user_view_response($subject_user_response, $manager_user->id));
        // Can view the external response because the section_relationship can_view field is true
        $this->assertTrue(section_element_response::can_user_view_response($external_user_response, $manager_user->id));
        // Can't view the other user's response because they aren't participating in the same subject instance
        $this->assertFalse(section_element_response::can_user_view_response($other_user_response, $manager_user->id));

        // Test as other user
        // User can always view their own response
        $this->assertTrue(section_element_response::can_user_view_response($other_user_response, $other_user->id));
        // Can't view the other subject's response because they aren't participating in the same subject instance
        $this->assertFalse(section_element_response::can_user_view_response($subject_user_response, $other_user->id));
        // Can't view the other subject's response because they aren't participating in the same subject instance
        $this->assertFalse(section_element_response::can_user_view_response($external_user_response, $other_user->id));
        // Can't view the other manager's response because they aren't participating in the same subject instance
        $this->assertFalse(section_element_response::can_user_view_response($manager_user_response, $other_user->id));

        // Test as external user
        // User can always view their own response
        $this->assertTrue(
            section_element_response::can_participant_view_response($external_user_response, $external_participant_instance)
        );
        // Can view the subject response
        $this->assertTrue(
            section_element_response::can_participant_view_response($subject_user_response, $external_participant_instance)
        );
        // Can't view the other user's response because they aren't participating in the same subject instance
        $this->assertFalse(
            section_element_response::can_participant_view_response($other_user_response, $external_participant_instance)
        );

        // Test as admin
        // Doesn't have the proper reporting capabilities yet
        $this->assertFalse(section_element_response::can_user_view_response($subject_user_response, $admin_user->id));
        $this->assertFalse(section_element_response::can_user_view_response($manager_user_response, $admin_user->id));
        $this->assertFalse(section_element_response::can_user_view_response($other_user_response, $admin_user->id));

        self::setUser($admin_user);
        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        assign_capability(
            'mod/perform:report_on_subject_responses',
            CAP_ALLOW,
            $role_id,
            context_user::instance($subject_user->id)
        );

        // Now has the reporting capability for the subject instance, but not the other user
        $this->assertTrue(section_element_response::can_user_view_response($subject_user_response, $admin_user->id));
        $this->assertTrue(section_element_response::can_user_view_response($manager_user_response, $admin_user->id));
        $this->assertFalse(section_element_response::can_user_view_response($other_user_response, $admin_user->id));

        assign_capability(
            'mod/perform:report_on_all_subjects_responses',
            CAP_ALLOW,
            $role_id,
            context_system::instance()
        );

        // Now has the reporting capability for everyone
        $this->assertTrue(section_element_response::can_user_view_response($subject_user_response, $admin_user->id));
        $this->assertTrue(section_element_response::can_user_view_response($manager_user_response, $admin_user->id));
        $this->assertTrue(section_element_response::can_user_view_response($other_user_response, $admin_user->id));
    }

}
