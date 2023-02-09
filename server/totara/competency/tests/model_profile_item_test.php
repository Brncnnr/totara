<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschata@totaralearning.com>
 * @package totara_competency
 */

use core\collection;
use totara_competency\entity\assignment;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\profile\item;
use totara_competency\models\profile\traits\assignment_key;

global $CFG;

require_once($CFG->dirroot . '/totara/competency/tests/totara_competency_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_model_profile_item_testcase extends totara_competency_testcase {

    use assignment_key;

    public function test_build_items_from_assignments() {
        $fw = $this->generator()->create_framework();
        $comp1 = $this->generator()->create_competency(null, $fw);
        $comp2 = $this->generator()->create_competency(null, $fw);
        $comp3 = $this->generator()->create_competency(null, $fw);

        // Create users
        $user1 = $this->create_user();
        $user2 = $this->create_user();

        $ass_gen = $this->generator()->assignment_generator();

        $pos1 = $ass_gen->create_position_and_add_members([$user1->id, $user2->id]);
        $pos2 = $ass_gen->create_position_and_add_members([$user1->id, $user2->id]);

        $pos_ass1 = new assignment($this->generator()->assignment_generator()->create_position_assignment($comp1->id, $pos1->id));
        $pos_ass2 = new assignment($this->generator()->assignment_generator()->create_position_assignment($comp2->id, $pos1->id));
        $pos_ass3 = new assignment($this->generator()->assignment_generator()->create_position_assignment($comp1->id, $pos2->id));
        $pos_ass4 = new assignment($this->generator()->assignment_generator()->create_position_assignment($comp2->id, $pos2->id));

        $user_ass1 = new assignment($ass_gen->create_user_assignment($comp2->id, $user1->id));
        $user_ass2 = new assignment($ass_gen->create_user_assignment($comp1->id, $user1->id));
        $user_ass3 = new assignment($ass_gen->create_user_assignment($comp2->id, $user1->id, ['type' => assignment::TYPE_OTHER]));
        $user_ass4 = new assignment($ass_gen->create_user_assignment($comp1->id, $user2->id, ['type' => assignment::TYPE_OTHER]));

        $self_ass1 = new assignment($ass_gen->create_self_assignment($comp2->id, $user1->id));
        $self_ass2 = new assignment($ass_gen->create_self_assignment($comp1->id, $user1->id));
        $self_ass3 = new assignment($ass_gen->create_self_assignment($comp1->id, $user2->id));

        $assignments = new \core\orm\collection([
            $pos_ass1,
            $pos_ass2,
            $pos_ass3,
            $pos_ass4,
            $user_ass1,
            $user_ass2,
            $user_ass3,
            $user_ass4,
            $self_ass1,
            $self_ass2,
            $self_ass3,
        ]);

        $collection = item::build_from_assignments($assignments);

        // Expecting 2 items as there are two competencies
        $this->assertCount(6, $collection);

        $expected_assignemts[self::build_key($pos_ass1)] = collection::new([
            $pos_ass1,
            $pos_ass2,
        ]);

        $expected_assignemts[self::build_key($pos_ass3)] = collection::new([
            $pos_ass3,
            $pos_ass4,
        ]);

        $expected_assignemts[self::build_key($user_ass1)] = collection::new([
            $user_ass1,
            $user_ass2,
            // $user_ass3  --> skipped as there's already an item in the collection for the same user group type / id
        ]);

        $expected_assignemts[self::build_key($user_ass4)] = collection::new([
            $user_ass4,
        ]);

        $expected_assignemts[self::build_key($self_ass2)] = collection::new([
            $self_ass1,
            $self_ass2,
        ]);

        $expected_assignemts[self::build_key($self_ass3)] = collection::new([
            $self_ass3,
        ]);

        /** @var item $item */
        foreach ($collection as $key => $item) {
            $this->assertEqualsCanonicalizing(
                $expected_assignemts[$key]->pluck('id'),
                $item->get_assignments()->map(function (assignment_model $assignment_model) {
                    return $assignment_model->get_entity();
                })->pluck('id')
            );
        }
    }

}
