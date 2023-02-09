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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_perform_rating
 */

use core\orm\query\builder;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section_relationship;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\section_element;
use pathway_perform_rating\models\perform_rating as perform_rating_model;
use pathway_perform_rating\testing\generator as pathway_peform_rating_generator;
use mod_perform\testing\generator as perform_generator;
use totara_core\relationship\relationship;

require_once __DIR__.'/perform_rating_base_testcase.php';

/**
 * @group pathway_perform_rating
 * @group totara_competency
 */
class pathway_perform_rating_perform_rating_model_testcase extends perform_rating_base_testcase {

    public function test_create_with_invalid_scale_value(): void {
        $data = $this->create_data();

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Valid scale value
        $rating = perform_rating_model::create(
            $data->competency->id,
            $scale_value->id,
            $data->participant_instance1->id,
            $data->section_element->id
        );
        $rating->delete();

        // null is still valid as a scale value
        $rating = perform_rating_model::create(
            $data->competency->id,
            null,
            $data->participant_instance1->id,
            $data->section_element->id
        );
        $rating->delete();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('/The specified scale valid with ID -999 is not valid for the competency with ID/');

        perform_rating_model::create(
            $data->competency->id,
            -999,
            $data->participant_instance1->id,
            $data->section_element->id
        );
    }

    public function test_create_with_existing_rating(): void {
        $data = $this->create_data();
        $competency_id = $data->competency->id;
        $subject_instance_id = $data->participant_instance1->subject_instance_id;
        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        $rating = perform_rating_model::create(
            $competency_id,
            $scale_value->id,
            $data->participant_instance1->id,
            $data->section_element->id
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "A rating has already been made for subject instance {$subject_instance_id} and competency ID {$competency_id}"
        );

        perform_rating_model::create(
            $competency_id,
            $scale_value->id,
            $data->participant_instance1->id,
            $data->section_element->id
        );
    }

    public function test_can_rate(): void {
        $data = $this->create_data();
        $participant_instance = participant_instance_model::load_by_entity($data->participant_instance1);
        $subject_relationship = relationship::load_by_idnumber('subject')->id;
        $manager_relationship = relationship::load_by_idnumber('manager')->id;
        self::setUser($data->manager_user);

        $element = new element($data->section_element->element_id);
        $element_data = [
            'content_type' => 'totara_competency',
            'content_type_settings' => [
                'enable_rating' => false,
                'rating_relationship' => null,
            ],
            'selection_relationships' => [$subject_relationship],
        ];
        $element->data = json_encode($element_data);
        $element->save();
        $section_element = section_element::load_by_id($data->section_element->id);
        // Can't rate as it is disabled on the element
        $this->assertFalse(perform_rating_model::can_rate($participant_instance, $section_element));

        $element_data['content_type_settings']['enable_rating'] = true;
        $element_data['content_type_settings']['rating_relationship'] = $subject_relationship;
        $element->data = json_encode($element_data);
        $element->save();
        $section_element = section_element::load_by_id($data->section_element->id);
        // Can't rate as user is not of the rating_relationship
        $this->assertFalse(perform_rating_model::can_rate($participant_instance, $section_element));

        $element_data['content_type_settings']['enable_rating'] = true;
        $element_data['content_type_settings']['rating_relationship'] = $manager_relationship;
        $element->data = json_encode($element_data);
        $element->save();
        $section_element = section_element::load_by_id($data->section_element->id);
        // Can rate as it is the correct relationship
        $this->assertTrue(perform_rating_model::can_rate($participant_instance, $section_element));

        // Can't rate as the relationship doesn't exist on the section.
        $section_relationship = section_relationship::repository()
            ->where('core_relationship_id', $participant_instance->core_relationship_id)
            ->where('section_id', $section_element->section_id)
            ->get()
            ->first();
        $section_relationship->delete();
        $this->assertFalse(perform_rating_model::can_rate($participant_instance, $section_element));

        // Can't rate as there is no participant section record.
        $section_relationship = new section_relationship($section_relationship->to_array());
        $section_relationship->save();
        $this->assertTrue(perform_rating_model::can_rate($participant_instance, $section_element));
        participant_section::repository()->delete();
        $this->assertFalse(perform_rating_model::can_rate($participant_instance, $section_element));

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches('/Sorry, but you do not currently have permissions to do that/');

        perform_rating_model::create(
            $data->competency->id,
            null,
            $data->participant_instance1->id,
            $data->section_element->id
        );
    }

    public function test_can_rate_with_invalid_element(): void {
        $data = $this->create_data();
        $participant_instance = participant_instance_model::load_by_entity($data->participant_instance1);

        $invalid_element = perform_generator::instance()->create_element();
        $invalid_section_element = perform_generator::instance()->create_section_element($data->section, $invalid_element);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The section element with ID {$invalid_section_element->id} is not a linked_review element");

        perform_rating_model::can_rate($participant_instance, $invalid_section_element);
    }

    public function test_get_latest_rating(): void {
        $data = $this->create_data();

        // No rating present yet
        $rating = perform_rating_model::get_latest($data->competency->id, $data->subject_user->id);
        $this->assertNull($rating);

        $rating = perform_rating_model::get_latest($data->competency->id, $data->manager_user->id);
        $this->assertNull($rating);

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        $perform_rating = pathway_peform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        $rating = perform_rating_model::get_latest($data->competency->id, $data->subject_user->id);
        $this->assertInstanceOf(perform_rating_model::class, $rating);
        $this->assertEquals($perform_rating->id, $rating->id);

        $rating = perform_rating_model::get_latest($data->competency->id, $data->manager_user->id);
        $this->assertNull($rating);

        $this->waitForSecond();

        // Now create a second rating and check that it gets returned as it is the latest
        $second_perform_rating = pathway_peform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance2),
            $data->section_element,
            $scale_value
        );

        $rating = perform_rating_model::get_latest($data->competency->id, $data->subject_user->id);
        $this->assertInstanceOf(perform_rating_model::class, $rating);
        $this->assertEquals($second_perform_rating->id, $rating->id);
    }

    public function test_aggregation_is_queued(): void {
        $data = $this->create_data();

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        $this->assertEquals(
            false,
            builder::table('totara_competency_aggregation_queue')->exists()
        );

        // Now create a rating and check that it gets returned
        pathway_peform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        // No achievement path exists so there should nothing be queued
        $this->assertEquals(
            false,
            builder::table('totara_competency_aggregation_queue')->exists()
        );

        pathway_peform_rating_generator::instance()->create_perform_rating_pathway($data->competency);

        // Now create a rating and check that it gets returned
        pathway_peform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance2),
            $data->section_element,
            $scale_value
        );

        // No achievement path exists so there should nothing be queued
        $this->assertEquals(
            true,
            builder::table('totara_competency_aggregation_queue')->exists()
        );

        $record = builder::table('totara_competency_aggregation_queue')
            ->order_by('id')
            ->first();

        $this->assertEquals($data->competency->id, $record->competency_id);
        $this->assertEquals($data->subject_user->id, $record->user_id);
    }

}