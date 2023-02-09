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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

use core\orm\collection;
use core\orm\query\table;
use core_phpunit\testcase;
use criteria_childcompetency\childcompetency;
use criteria_childcompetency\watcher\achievement;
use totara_competency\hook\competency_achievement_updated_bulk;
use totara_competency\hook\competency_configuration_changed;
use totara_criteria\criterion;
use totara_criteria\entity\criteria_item as item_entity;
use totara_criteria\entity\criteria_item_record as item_record_entity;
use totara_criteria\entity\criteria_metadata as metadata_entity;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\hook\criteria_achievement_changed;
use criteria_childcompetency\watcher\competency as competency_wathcer;
use totara_criteria\hook\criteria_validity_changed;
use totara_hierarchy\entity\competency as competency_entity;
use totara_hierarchy\entity\scale;

/**
 * Test hooks in this plugin
 *
 * @group totara_competency
 */
class criteria_childcompetency_watchers_testcase extends testcase {

    /**
     * Test watcher when a user's competency achievement changes
     */
    public function test_competency_achievement_updated_bulk() {
        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $framework = $competency_generator->create_framework();

        // Create 2 competencies, each with 1 child. First one has childcompetency criteria
        $competencies = [];
        foreach (['Comp A', 'Comp B'] as $name) {
            $competencies[$name] = $competency_generator->create_competency($name, $framework);

            $childname = $name . '-1';
            $competencies[$childname] = $competency_generator->create_competency(
                $childname,
                $framework,
                ['parentid' => $competencies[$name]->id]
            );
        }
        $criterion = $criteria_generator->create_childcompetency(['competency' => $competencies['Comp A']->id]);

        // Verify generated data
        $this->assertSame(4, competency_entity::repository()->count());
        $this->assertSame(1, criterion_entity::repository()->count());

        // The competency_created observer should have created the necessary items
        $this->verify_items($competencies['Comp A']->id, [$competencies['Comp A-1']->id]);
        $this->assertSame(0, item_record_entity::repository()->count());

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Now for the test :- We only check that the correct hooks are executed
        $sink = $this->redirectHooks();

        // Parent with criteria
        $test_hook = new competency_achievement_updated_bulk($competencies['Comp A']);
        $test_hook->add_user_id($user1->id, ['is_proficient' => 1]);
        $test_hook->add_user_id($user2->id, ['is_proficient' => 0]);
        achievement::updated_bulk($test_hook);
        $this->assertSame(0, $sink->count());

        // Parent without criteria
        $test_hook = new competency_achievement_updated_bulk($competencies['Comp B']);
        $test_hook->add_user_id($user1->id, ['is_proficient' => 1]);
        $test_hook->add_user_id($user2->id, ['is_proficient' => 0]);
        achievement::updated_bulk($test_hook);
        $this->assertSame(0, $sink->count());

        // Child competency of parent with criteria
        $test_hook = new competency_achievement_updated_bulk($competencies['Comp A-1']);
        $test_hook->add_user_id($user1->id, ['is_proficient' => 1]);
        $test_hook->add_user_id($user2->id, ['is_proficient' => 0]);
        achievement::updated_bulk($test_hook);
        $this->verify_hook($sink, [$user1->id => [$criterion->get_id()], $user2->id => [$criterion->get_id()]]);
        $sink->clear();

        // Child competency of parent without criteria
        $test_hook = new competency_achievement_updated_bulk($competencies['Comp B-1']);
        $test_hook->add_user_id($user1->id, ['is_proficient' => 1]);
        $test_hook->add_user_id($user2->id, ['is_proficient' => 0]);
        achievement::updated_bulk($test_hook);
        $this->assertSame(0, $sink->count());

        $sink->close();
    }

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

        $parent = $competency_generator->create_competency('Comp A');
        $child = $competency_generator->create_competency('Comp A-1', null, ['parentid' => $parent->id]);

        /** @var scale $scale */
        $scale = $parent->scale;
        $non_proficient_scalevalue = $scale->default_value;
        $proficient_scalevalue = $scale->min_proficient_value;

        $parent_criterion = $criteria_generator->create_childcompetency(['competency' => $parent->id]);
        $parent_pw = $competency_generator->create_criteria_group($parent, [$parent_criterion], $non_proficient_scalevalue);
        $this->assertFalse($parent_pw->is_valid());


        // First add a non-proficient pw to child
        $child_criterion = $criteria_generator->create_coursecompletion(['courseids' => [$course1->id]]);
        $child_pw = $competency_generator->create_criteria_group($child, [$child_criterion], $non_proficient_scalevalue);

        $hook_sink->clear();

        // competency_configuration_changed is triggered from the webapi
        // Manually triggering it here to simulate changes through the api
        /** @var competency_configuration_changed $hook */
        $hook = new competency_configuration_changed($child->id);

        // Child competency - not proficient - should not result in validity change
        competency_wathcer::configuration_changed($hook);
        $this->assertSame(0, $hook_sink->count());

        // Now add a pathway to the child that will allow the user to become proficient
        $child_criterion2 = $criteria_generator->create_coursecompletion(['courseids' => [$course2->id]]);
        $child_pw2 = $competency_generator->create_criteria_group($child, [$child_criterion2], $proficient_scalevalue);

        $hook_sink->clear();
        // Child competency now proficient - expect parent validity change
        $hook = new competency_configuration_changed($child->id);
        competency_wathcer::configuration_changed($hook);

        $hooks = $hook_sink->get_hooks();
        $this->assertSame(1, count($hooks));
        $hook = reset($hooks);
        $this->assertTrue($hook instanceof criteria_validity_changed);
        $this->assertEqualsCanonicalizing([$parent_criterion->get_id()], $hook->get_criteria_ids());

        $hook_sink->close();
        $event_sink->close();
    }


    /**
     * Get the items linked to childcompetency criteria on the specified competency
     * @param int $competency_id
     * @return collection
     */
    private function get_items(int $competency_id): collection {
        $item_type = (new childcompetency())->get_items_type();

        return item_entity::repository()
            ->join((new table(metadata_entity::TABLE))->as('metadata'), 'criterion_id', '=', 'criterion_id')
            ->where('item_type', $item_type)
            ->where('metadata.metakey', criterion::METADATA_COMPETENCY_KEY)
            ->where('metadata.metavalue', $competency_id)
            ->get();
    }

    /**
     * Verify the criteria_items are as expected.
     *
     * @param int $competency_id - Competency
     * @param array $expected_item_ids - Exepected item ids
     */
    private function verify_items(int $competency_id, array $expected_item_ids) {
        $current_items = $this->get_items($competency_id);

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
