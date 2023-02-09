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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\helpers\manual_participant_helper;
use mod_perform\models\due_date;
use mod_perform\models\activity\subject_instance;

use mod_perform\testing\generator;
use totara_core\relationship\relationship;

/**
 * @group perform
 */
class mod_perform_manual_participant_helper_testcase extends testcase {

    public function test_has_pending_selections(): void {
        $user = self::getDataGenerator()->create_user();
        $helper = manual_participant_helper::for_user($user->id);

        self::assertFalse($helper->has_pending_selections());

        $pending_subject_instance = $this->create_pending_subject_instance_for_user($user);

        self::assertTrue($helper->has_pending_selections());

        $pending_subject_instance->manually_close(true);

        self::assertFalse($helper->has_pending_selections());
    }

    public function test_get_pending_subject_instances_count(): void {
        $user = self::getDataGenerator()->create_user();
        $helper = manual_participant_helper::for_user($user->id);

        self::assertEquals(0, $helper->get_pending_subject_instances_count());

        $pending_subject_instance1 = $this->create_pending_subject_instance_for_user($user);

        self::assertEquals(1, $helper->get_pending_subject_instances_count());

        $pending_subject_instance2 = $this->create_pending_subject_instance_for_user($user);

        self::assertEquals(2, $helper->get_pending_subject_instances_count());

        $pending_subject_instance1->manually_close(true);
        $pending_subject_instance2->manually_close(true);

        self::assertEquals(0, $helper->get_pending_subject_instances_count());
    }

    /**
     * @return subject_instance
     */
    private function create_pending_subject_instance_for_user(stdClass $user): subject_instance {
        self::setAdminUser();
        $perform_generator = generator::instance();

        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $peer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_PEER);
        $mentor_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MENTOR);

        $activity = $perform_generator->create_activity_in_container();
        $perform_generator->create_manual_relationships_for_activity($activity, [
            ['selector' => $subject_relationship->id, 'manual' => $peer_relationship->id],
            ['selector' => $subject_relationship->id, 'manual' => $mentor_relationship->id],
        ]);

        $subject_instance_entity = $perform_generator->create_subject_instance_with_pending_selections(
            $activity, $user, [$peer_relationship, $mentor_relationship]
        );
        $subject_instance = subject_instance::load_by_entity($subject_instance_entity);
        self::assertTrue($subject_instance->is_pending());
        self::assertTrue($subject_instance->is_open());

        return $subject_instance;
    }
}
