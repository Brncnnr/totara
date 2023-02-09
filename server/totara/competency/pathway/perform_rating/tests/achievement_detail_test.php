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
 * @package pathway_perform_rating
 */

use mod_perform\constants;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use pathway_perform_rating\achievement_detail;
use pathway_perform_rating\entity\perform_rating as perform_rating_entity;
use pathway_perform_rating\testing\generator as pathway_peform_rating_generator;
use totara_core\relationship\relationship;

require_once __DIR__.'/perform_rating_base_testcase.php';

/**
 * @group pathway_perform_rating
 * @group totara_competency
 */
class pathway_perform_rating_achievement_detail_testcase extends perform_rating_base_testcase {

    public function test_achievement_detail_string() {
        $data = $this->create_data();

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        $perform_rating = pathway_peform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        $achievement_detail = new achievement_detail();
        $achievement_detail->add_rating($perform_rating);

        $string = $achievement_detail->get_achieved_via_strings();
        $this->assertEquals(
            [
                sprintf(
                    'Performance Activity rating for \'%s\' by %s (%s)',
                    $data->participant_instance1->subject_instance->activity()->name,
                    fullname($data->manager_user),
                    relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER)->get_name()
                )
            ],
            $string
        );
    }

    public function test_achievement_detail_string_with_deleted_activity() {
        $data = $this->create_data();

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        $perform_rating = pathway_peform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        $activity = activity_model::load_by_entity($data->participant_instance1->subject_instance->activity());
        $activity->delete();

        $achievement_detail = new achievement_detail();
        $achievement_detail->add_rating($perform_rating);

        $string = $achievement_detail->get_achieved_via_strings();
        $this->assertEquals(
            [
                sprintf(
                    'This activity no longer exists. Performance Activity rating by %s (%s)',
                    fullname($data->manager_user),
                    relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER)->get_name()
                )
            ],
            $string
        );
    }

    public function test_achievement_detail_string_with_deleted_user() {
        $data = $this->create_data();

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        $perform_rating = pathway_peform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        $this->delete_user($data->manager_user);

        $achievement_detail = new achievement_detail();
        $achievement_detail->add_rating($perform_rating);

        $string = $achievement_detail->get_achieved_via_strings();
        $this->assertEquals(
            [
                sprintf(
                    'Performance Activity rating for \'%s\' by ? ? (%s)',
                    $data->participant_instance1->subject_instance->activity()->name,
                    relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER)->get_name()
                )
            ],
            $string
        );
    }

    public function test_achievement_detail_string_with_deleted_user_and_deleted_activity() {
        $data = $this->create_data();

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        $perform_rating = pathway_peform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        $activity = activity_model::load_by_entity($data->participant_instance1->subject_instance->activity());
        $activity->delete();

        $this->delete_user($data->manager_user);

        $achievement_detail = new achievement_detail();
        $achievement_detail->add_rating($perform_rating);

        $string = $achievement_detail->get_achieved_via_strings();
        $this->assertEquals(
            [
                sprintf(
                    'This activity no longer exists. Performance Activity rating by ? ? (%s)',
                    relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER)->get_name()
                )
            ],
            $string
        );
    }

    public function test_achievement_detail_string_with_purged_user_and_deleted_activity() {
        $data = $this->create_data();

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        $perform_rating = pathway_peform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        /** @var perform_rating_entity $rating */
        $rating = perform_rating_entity::repository()->find($perform_rating->id);
        $rating->rater_user_id = null;
        $rating->save();

        $activity = activity_model::load_by_entity($data->participant_instance1->subject_instance->activity());
        $activity->delete();

        $achievement_detail = new achievement_detail();
        $achievement_detail->add_rating($perform_rating);

        $string = $achievement_detail->get_achieved_via_strings();
        $this->assertEquals(
            [
                sprintf(
                    'This activity no longer exists. Performance Activity rating by (rater details removed) (%s)',
                    relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER)->get_name()
                )
            ],
            $string
        );
    }

}
