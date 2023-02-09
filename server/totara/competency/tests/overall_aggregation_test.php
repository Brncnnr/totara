<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\overall_aggregation;

/**
 * @group totara_competency
 */
class totara_competency_overall_aggregation_testcase extends advanced_testcase {

    public function test_set_pathways_can_be_set_once_with_valid_values() {
        $pathway1 = $this->getMockForAbstractClass(\totara_competency\pathway::class);
        $pathway2 = $this->getMockForAbstractClass(\totara_competency\pathway::class);

        /**
         * @var \PHPUnit\Framework\MockObject\MockObject|\totara_competency\overall_aggregation $aggregation1
         */
        $aggregation1 = $this->getMockForAbstractClass(overall_aggregation::class);
        $aggregation1->set_pathways([$pathway1, $pathway2]);

        /**
         * @var \PHPUnit\Framework\MockObject\MockObject|\totara_competency\overall_aggregation $aggregation2
         */
        $aggregation2 = $this->getMockForAbstractClass(overall_aggregation::class);
        $aggregation2->set_pathways([]);

        /**
         * @var \PHPUnit\Framework\MockObject\MockObject|overall_aggregation $aggregation3
         */
        $aggregation3 = $this->getMockForAbstractClass(overall_aggregation::class);
        $aggregation3->set_pathways([$pathway1]);
    }

    public function test_aggregate_initialises_to_empty_values_for_users() {
        $user1_id = 101;
        $user2_id = 102;

        /**
         * @var \PHPUnit\Framework\MockObject\MockObject|overall_aggregation $aggregation1
         */
        $aggregation1 = $this->getMockForAbstractClass(overall_aggregation::class);
        $aggregation1->set_pathways([]);

        // Internally, this will call do_aggregate. The mock should have stubbed this method with one that returns null.
        foreach ([$user1_id, $user2_id] as $user_id) {
            $aggregation1->aggregate_for_user($user_id);
            $this->assertNull($aggregation1->get_achieved_value_id($user_id));
            $this->assertEquals([], $aggregation1->get_achieved_via($user_id));
        }
    }
}
