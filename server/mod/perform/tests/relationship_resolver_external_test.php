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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use totara_core\relationship\relationship;

require_once(__DIR__ . '/perform_relationship_resolver_testcase.php');

/**
 * @group perform
 * @covers \mod_perform\relationship\resolvers\external
 */
class relationship_resolver_external_testcase extends perform_relationship_resolver_testcase {
    public function test_get_external_participants_by_subject_instance_id() {
        [$user1, $subject_instance] = $this->create_relationship_resolver_data(constants::RELATIONSHIP_EXTERNAL);

        $external_resolver = relationship::load_by_idnumber(constants::RELATIONSHIP_EXTERNAL);
        $relationship_resolver_dtos = $external_resolver->get_users(
            ['subject_instance_id' => $subject_instance->id],
            context_user::instance($subject_instance->subject_user_id)
        );

        $this->assertEquals($user1->email, $relationship_resolver_dtos[0]->get_meta()['email']);
        $this->assertEquals($user1->username, $relationship_resolver_dtos[0]->get_meta()['name']);
    }
}
