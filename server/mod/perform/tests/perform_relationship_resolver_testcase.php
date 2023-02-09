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
use mod_perform\models\activity\subject_instance_manual_participant;
use totara_core\relationship\relationship;

abstract class perform_relationship_resolver_testcase extends advanced_testcase {
    /**
     * create manual relationship resolver data
     *
     * @param string $idnumber
     * @return array
     * @throws coding_exception
     */
    protected function create_relationship_resolver_data(string $idnumber) {
        $data_generator = $this->getDataGenerator();
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');
        // user
        $user1 = $data_generator->create_user();
        $user2 = $data_generator->create_user();

        $this->setAdminUser();

        // activity
        $activity = $perform_generator->create_activity_in_container(['activity_name' => 'User1 One Activity']);

        //subject instance
        $subject_instance = $perform_generator->create_subject_instance(
            [
                'activity_id'     => $activity->id,
                'subject_user_id' => $user1->id,
            ]
        );

        //subject_instance_manual_participants
        $relationship_id = relationship::load_by_idnumber($idnumber)->id;
        if ($idnumber == constants::RELATIONSHIP_EXTERNAL) {
            $subject_instance_manual_participant = subject_instance_manual_participant::create_for_external(
                $subject_instance->id, $user2->id, $relationship_id, $user1->email, $user1->username
            );
        } else {
            $subject_instance_manual_participant = subject_instance_manual_participant::create_for_internal(
                $subject_instance->id, $user2->id, $relationship_id, $user1->id
            );
        }

        return [$user1, $subject_instance, $subject_instance_manual_participant];
    }
}