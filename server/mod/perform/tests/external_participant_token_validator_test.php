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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\constants;
use mod_perform\entity\activity\external_participant;
use mod_perform\entity\activity\participant_instance;
use mod_perform\models\activity\helpers\external_participant_token_validator;
use mod_perform\models\activity\subject_instance as subject_instance_model;

defined('MOODLE_INTERNAL') || die();

/**
 * @group perform
 */
class mod_perform_external_participant_token_validator_testcase extends advanced_testcase {

    public function test_valid_token() {
        $this->setup_data();

        /** @var external_participant $external_participant */
        $external_participant = external_participant::repository()->get()->first();

        $expected_participant_instance = $external_participant->participant_instance;

        $validator = new external_participant_token_validator($external_participant->token);
        $this->assertTrue($validator->is_valid());
        $this->assertEquals($expected_participant_instance->id, $validator->get_participant_instance()->id);
        $this->assertFalse($validator->is_subject_instance_closed());

        $actual_participant_instance = $validator->get_participant_instance();
        $actual_section = $actual_participant_instance->participant_sections->first();

        /** @var participant_instance $other_participant_instance */
        $other_participant_instance = participant_instance::repository()
            ->where('participant_id', '<>', $external_participant->id)
            ->order_by('id')
            ->first();

        $other_section = $other_participant_instance->participant_sections->first();

        $this->assertTrue($validator->is_valid_for_section($actual_section->id));
        $this->assertFalse($validator->is_valid_for_section($other_section->id));

        // Use some invalid token
        $invalid_validator = new external_participant_token_validator('idontexist');
        $this->assertFalse($invalid_validator->is_valid());
        $this->assertNull($invalid_validator->get_participant_instance());
        $this->assertTrue($invalid_validator->is_subject_instance_closed());

        // Close the subject instance
        $subject_instance = subject_instance_model::load_by_entity($expected_participant_instance->subject_instance);
        $subject_instance->manually_close();

        $validator = new external_participant_token_validator($external_participant->token);
        $this->assertTrue($validator->is_valid());
        $this->assertEquals($expected_participant_instance->id, $validator->get_participant_instance()->id);
        $this->assertTrue($validator->is_subject_instance_closed());
    }

    private function setup_data() {
        self::setAdminUser();

        $generator = $this->generator();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->enable_creation_of_manual_participants()
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_EXTERNAL,
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER
                ]
            );

        $generator->create_full_activities($configuration);
    }

    /**
     * @return \mod_perform\testing\generator
     */
    protected function generator(): \mod_perform\testing\generator {
        return \mod_perform\testing\generator::instance();
    }
}