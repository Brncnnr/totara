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
 * @category test
 */

use mod_perform\entity\activity\external_participant as external_participant_entity;
use mod_perform\models\activity\external_participant as external_participant_model;
use mod_perform\models\activity\participant;
use mod_perform\models\activity\participant_source;
use core\entity\user;

/**
 * Tests the participant model.
 *
 * @covers \mod_perform\models\activity\participant
 * @group perform
*/
class mod_perform_participant_model_testcase extends advanced_testcase {

    public function test_load_with_user() {
        $user_data = [
            'firstname' => 'Hello',
            'lastname' => 'World',
            'email' => 'me@who.com',
            'imagealt' => 'myaltimage text'
        ];
        $user = new user($this->getDataGenerator()->create_user($user_data), false, false);

        $participant = new participant($user);

        $fullname = sprintf('%s %s', $user_data['firstname'], $user_data['lastname']);

        $this->assertEquals($fullname, $participant->fullname);
        $this->assertEquals($user_data['email'], $participant->email);
        $this->assertEquals('myaltimage text', $participant->profileimagealt);
        $this->assertEquals(participant_source::SOURCE_TEXT[participant_source::INTERNAL], $participant->get_source());

        $user_data2 = [
            'firstname' => 'Firstname',
            'lastname' => 'lastname',
            'email' => 'firstname.lastname@totaralearning.com',
        ];
        $user2 = new user($this->getDataGenerator()->create_user($user_data2), false, false);

        $participant = new participant($user2);

        $fullname = sprintf('%s %s', $user_data2['firstname'], $user_data2['lastname']);

        $this->assertEquals($fullname, $participant->fullname);
        $this->assertEquals($user_data2['email'], $participant->email);
        $this->assertEquals($fullname, $participant->profileimagealt);
        $this->assertEquals(participant_source::SOURCE_TEXT[participant_source::INTERNAL], $participant->get_source());
    }

    public function test_load_with_external_participant() {
        $name = 'Aug man';
        $email = 'august@year.com';

        $external_participant_entity = new external_participant_entity();
        $external_participant_entity->name = $name;
        $external_participant_entity->email = $email;
        $external_participant_entity->save();

        $participant = new participant(
            external_participant_model::load_by_entity($external_participant_entity),
            participant_source::EXTERNAL
        );

        $this->assertEquals($name, $participant->fullname);
        $this->assertEquals($email, $participant->email);
        $this->assertEquals($name, $participant->profileimagealt);
        $this->assertEquals(participant_source::SOURCE_TEXT[participant_source::EXTERNAL], $participant->get_source());
    }

    public function test_load_with_unknown_class() {
        $this->expectException(coding_exception::class);
        $user = new stdClass();
        new participant($user);
    }
}