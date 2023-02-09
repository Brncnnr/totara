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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

global $CFG;

use core\collection;
use totara_competency\models\assignment as assignment_model;
use totara_competency\data_providers\competency_progress;
use totara_competency\entity\competency_achievement;
use totara_competency\models\profile\competency_progress as cp;

require_once($CFG->dirroot . '/totara/competency/tests/totara_competency_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_data_provider_competency_progress_testcase extends totara_competency_testcase {

    /**
     * This integration test requires more or less large amount of data created, thus it's performing
     * multiple assertions within one test to avoid unnecessary extra resets
     */
    public function test_it_fetches_filters_and_orders_assignments() {
        $data = $this->create_sorting_testing_data();

        // CP - competency progress
        $cp = competency_progress::for($data['users']->first())
            ->set_order('recently-assigned')
            ->fetch()
            ->get();

        // It's not so simple to figure out how the sorting should work, so hopefully this explanation would save time.
        // First of all competency progress is a collection of "items". Each item is a bunch of assignments with some extra
        // data. These assignments are grouped by competency. Since it's possible to have multiple assignments per "item" and
        // we allow to sort by "Recently assigned" in the interface (it doesn't make much sense to grab the first assignment
        // related to this progress item, so behind the scenes the sorting algorithm looks up the latest(!) assignment and
        // uses assignment date from that one to sort progress items by "Recently assigned"

        // When we create sorting test data, for "Test Competency 1" we actually have 2 assignments created, one with the
        // oldest and another one with the most recent assignment dates. This is designed to test that the second assignment
        // will push "Test Competency 1" from the last place to the first.

        // Double pluck is used to extract competency entity from the model and the second one extracts competency name
        // It was possible to use IDs, however it's more verbose to use names, since they will be unique in the testing
        // conditions anyway

        $this->assertEquals(
            [
                'Test Competency 1',
                'Test Competency 3',
                'Something. This is a predefined key phrase for searching a competency. Another thing',
            ],
            collection::new($cp->pluck('competency'))->pluck('fullname')
        );

        // Let's try the alphabetic sort
        $cp = competency_progress::for($data['users']->first())
            ->set_order('alphabetical')
            ->fetch()
            ->get();

        // We should get different results on the same data set making sure sorting works :)
        $this->assertEquals(
            [
                'Something. This is a predefined key phrase for searching a competency. Another thing',
                'Test Competency 1',
                'Test Competency 3',
            ],
            collection::new($cp->pluck('competency'))->pluck('fullname')
        );

        // Now let's test the deal with archived assignments, they use the same principle as active assignments,
        // except that the sorting options is recently-archived, however again the above stated is valid, there
        // could be more than one archived assignment for the same competency and again we select the most recently
        // archived assignment to perform sorting.

        // Let's try the alphabetic sort
        $cp = competency_progress::for($data['users']->item(1))
            ->set_order('recently-archived')
            ->fetch()
            ->get();

        $this->assertEquals(
            [
                'Something. This is a predefined key phrase for searching a competency. Another thing',
                'Test Competency 3',
                'Test Competency 1',
            ],
            collection::new($cp->pluck('competency'))->pluck('fullname')
        );

        $this->setUser((object) $data['users']->item(1)->to_array());

        // Now let's try the alphabetic sort
        $cp = competency_progress::for($data['users']->item(1))
            ->set_order('alphabetical')
            // The following ensures null as filter value are ignored by design
            ->set_filters(['proficient' => null, 'zombie' => null])
            ->fetch()
            ->get();

        $this->assertEquals(
            [
                'Something. This is a predefined key phrase for searching a competency. Another thing',
                'Test Competency 1',
                'Test Competency 3',
            ],
            collection::new($cp->pluck('competency'))->pluck('fullname')
        );

        $at_least_one_achievement_found = false;

        // We have a big complex data structure to return, so we are going to iterate over
        // individual items and assert that they have everything loaded properly.

        // Let's assert that returned data have everything we need.
        $cp->map(function (cp $cp) use (&$at_least_one_achievement_found) {
            $this->assertInstanceOf(collection::class, $cp->get_assignments());

            // Let's check that we alias assignments to items
            $this->assertSame($cp->assignments, $cp->items);

            // Let's check all the assignments
            $cp->get_assignments()->map(function (assignment_model $assignment) use (&$at_least_one_achievement_found) {
                $this->assertTrue($assignment->get_entity()->relation_loaded('current_achievement'));

                $achievement = competency_achievement::repository()
                    ->where('user_id', $this->get_user()->id)
                    ->where('assignment_id', $assignment->get_id())
                    ->with('value') // Our achievement comes with a preloaded scale value
                    ->one();

                if ($assignment->get_entity()->current_achievement) {
                    $this->assertEquals(
                        $achievement->to_array(),
                        $assignment->get_entity()
                            ->current_achievement
                            ->to_array()
                    );

                    // Let's also check that scale value relation has been preloaded.
                    $this->assertTrue($assignment->get_entity()->current_achievement->relation_loaded('value'));

                    $at_least_one_achievement_found = true;
                } else {
                    $this->assertNull($achievement);
                }
            });
        });

        if (!$at_least_one_achievement_found) {
            $this->fail('At least one achievement expected to be found');
        }

        // Let's try filters now
        $cp = competency_progress::for($data['users']->first())
            ->set_filters([
                'proficient' => true,
            ])
            ->fetch()
            ->get();

        // We should get only one competency
        $this->assertCount(1, $cp);
        $this->assertEquals('Test Competency 1', $cp->first()->competency->fullname);

        // Let's check that there is a sanity check on sorting
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Can not order by ' . $field = 'password');

        competency_progress::for($data['users']->first())
            ->set_order($field)
            ->fetch()
            ->get();
    }
}