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
 * @package totara_criteria
 */

use core_phpunit\testcase;
use criteria_othercompetency\watcher\achievement;
use totara_competency\hook\competency_achievement_updated_bulk;
use totara_competency\hook\competency_configuration_changed;
use criteria_othercompetency\watcher\competency as competency_watcher;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\entity\criterion_item as item_entity;
use totara_criteria\hook\criteria_achievement_changed;
use totara_criteria\hook\criteria_validity_changed;
use totara_hierarchy\entity\competency as competency_entity;
use totara_hierarchy\models\scale;

/**
 * Test hooks in this plugin
 *
 * @group totara_competency
 */
class criteria_othercompetency_watchers_testcase extends testcase {

    /**
     * Test watcher when the a competency's configuration change
     */
    public function test_competency_configuration_changed() {
        $event_sink = $this->redirectEvents();
        $hook_sink = $this->redirectHooks();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $course2 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);

        $other_competency = $competency_generator->create_competency('Comp A');
        $competency_item = $competency_generator->create_competency('Comp A-1');

        /** @var scale $scale */
        $scale = $other_competency->scale;
        $non_proficient_scalevalue = $scale->default_value;
        $proficient_scalevalue = $scale->min_proficient_value;

        $other_competency_criterion = $criteria_generator->create_othercompetency(['competencyids' => [$competency_item->id]]);
        $other_competency_pw = $competency_generator->create_criteria_group($other_competency,
            [$other_competency_criterion],
            $non_proficient_scalevalue
        );
        $this->assertFalse($other_competency_pw->is_valid());

        // First add a non-proficient pw to competency_item
        $competency_item_criterion = $criteria_generator->create_coursecompletion(['courseids' => [$course1->id]]);
        $competency_item_pw = $competency_generator->create_criteria_group($competency_item, [$competency_item_criterion], $non_proficient_scalevalue);

        $hook_sink->clear();

        // competency_configuration_changed is triggered from the webapi
        // Manually triggering it here to simulate changes through the api
        /** @var competency_configuration_changed $hook */
        $hook = new competency_configuration_changed($competency_item->id);

        // Other competency - not proficient - should not result in validity change
        competency_watcher::configuration_changed($hook);
        $this->assertSame(0, $hook_sink->count());

        // Now add a pathway to the child that will allow the user to become proficient
        $competency_item_criterion2 = $criteria_generator->create_coursecompletion(['courseids' => [$course2->id]]);
        $competency_item_pw2 = $competency_generator->create_criteria_group($competency_item, [$competency_item_criterion2], $proficient_scalevalue);

        $hook_sink->clear();
        // Other competency now proficient - expect parent validity change
        $hook = new competency_configuration_changed($competency_item->id);
        competency_watcher::configuration_changed($hook);

        $hooks = $hook_sink->get_hooks();
        $this->assertSame(1, count($hooks));
        $hook = reset($hooks);
        $this->assertTrue($hook instanceof criteria_validity_changed);
        $this->assertEqualsCanonicalizing([$other_competency_criterion->get_id()], $hook->get_criteria_ids());

        $hook_sink->close();
        $event_sink->close();
    }

    /**
     * Test watcher when a user's competency achievement changes
     */
    public function test_competency_achievement_updated_bulk() {
        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $framework = $competency_generator->create_framework();

        // Create 3 competencies. Some are used as items in othercompetency criteria
        $competencies = [];
        foreach (['Comp A', 'Other B', 'Other C'] as $name) {
            $competencies[$name] = $competency_generator->create_competency($name, $framework);
        }

        $criterion1 = $criteria_generator->create_othercompetency([
            'competencyids' => [$competencies['Other B']->id, $competencies['Other C']->id],
        ]);
        $criterion2 = $criteria_generator->create_othercompetency([
            'competencyids' => [$competencies['Other C']->id],
        ]);

        // Verify generated data
        $this->assertSame(3, competency_entity::repository()->count());
        $this->assertSame(2, criterion_entity::repository()->count());
        $this->verify_items($criterion1->get_id(), [$competencies['Other B']->id, $competencies['Other C']->id]);
        $this->verify_items($criterion2->get_id(), [$competencies['Other C']->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Now for the test :- We only check that the correct hooks are executed
        $sink = $this->redirectHooks();

        // Competency not used as an othercompetency item
        $test_hook = new competency_achievement_updated_bulk($competencies['Comp A']);
        $test_hook->add_user_id($user1->id, ['is_proficient' => 1]);
        $test_hook->add_user_id($user2->id, ['is_proficient' => 0]);
        achievement::updated_bulk($test_hook);
        $this->assertSame(0, $sink->count());

        // Competency used in 1 othercompetency criterion
        $test_hook = new competency_achievement_updated_bulk($competencies['Other B']);
        $test_hook->add_user_id($user1->id, ['is_proficient' => 1]);
        $test_hook->add_user_id($user2->id, ['is_proficient' => 0]);
        achievement::updated_bulk($test_hook);
        $this->verify_hook($sink, [$user1->id => [$criterion1->get_id()], $user2->id => [$criterion1->get_id()]]);
        $sink->clear();

        // Competency used in 2 othercompetency criteria
        $test_hook = new competency_achievement_updated_bulk($competencies['Other C']);
        $test_hook->add_user_id($user1->id, ['is_proficient' => 1]);
        $test_hook->add_user_id($user2->id, ['is_proficient' => 0]);
        achievement::updated_bulk($test_hook);
        $this->verify_hook($sink, [
            $user1->id => [$criterion1->get_id(), $criterion2->get_id()],
            $user2->id => [$criterion1->get_id(), $criterion2->get_id()]
        ]);
        $sink->clear();

        // Competency used in 2 othercompetency criteria but with a single user only
        $test_hook = new competency_achievement_updated_bulk($competencies['Other C']);
        $test_hook->add_user_id($user1->id, ['is_proficient' => 1]);
        achievement::updated_bulk($test_hook);
        $this->verify_hook($sink, [
            $user1->id => [$criterion1->get_id(), $criterion2->get_id()],
        ]);

        $sink->close();
    }

    /**
     * Verify the criteria_items are as expected.
     *
     * @param int $criterion_id
     * @param array $expected_item_ids - Expected item ids
     */
    private function verify_items(int $criterion_id, array $expected_item_ids) {
        $current_items = item_entity::repository()
            ->where('criterion_id', $criterion_id)
            ->get();

        $this->assertSame(count($expected_item_ids), $current_items->count());
        foreach ($current_items as $item) {
            $this->assertTrue(in_array($item->item_id, $expected_item_ids));
        }
    }

    /**
     * Verify the executed hook
     *
     * @param \core_phpunit\hook_sink $sink
     * @param array $expected_criteria_ids
     */
    private function verify_hook(\core_phpunit\hook_sink $sink, array $expected_criteria_ids) {
        $this->assertSame(1, $sink->count());
        $hooks = $sink->get_hooks();
        /** @var criteria_achievement_changed $hook */
        $hook = reset($hooks);
        $this->assertInstanceOf(criteria_achievement_changed::class, $hook);
        $this->assertEqualsCanonicalizing($expected_criteria_ids, $hook->get_user_criteria_ids());
    }

}
