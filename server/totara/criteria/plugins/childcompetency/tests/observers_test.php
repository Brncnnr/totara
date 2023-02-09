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
use totara_criteria\criterion;
use totara_criteria\entity\criteria_item as item_entity;
use totara_criteria\entity\criteria_item_record as item_record_entity;
use totara_criteria\entity\criteria_metadata as metadata_entity;
use totara_criteria\entity\criterion as criterion_entity;
use totara_hierarchy\entity\competency as competency_entity;

/**
 * Class criteria_childcompetency_observer_testcase tests the observers
 * The tests are very similar to the ones defined in items_process_test.php, but here we don't sink the events.
 * We basically test that by relying on the observers we get the same results as we got when calling the item_processor
 * manually
 *
 * We also include tests for all observers here to avoid duplicating of validation functions
 *
 * @group totara_competency
 */
class criteria_childcompetency_observers_testcase extends testcase {

    /**
     * Test observer when a child competency is created for a comptetency with childcompetency criteria
     */
    public function test_competency_created_with_criteria() {
        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $framework = $competency_generator->create_framework();
        $competency = $competency_generator->create_competency('Comp A', $framework);
        $criteria_generator->create_childcompetency(['competency' => $competency->id]);

        // Verify generated data
        $this->assertSame(1, competency_entity::repository()->count());
        $this->assertSame(1, criterion_entity::repository()->count());
        $this->assertSame(0, item_entity::repository()->count());
        $this->assertSame(0, item_record_entity::repository()->count());

        // Create a child competency
        $child_competency = $competency_generator->create_competency('Comp A-1', $framework, ['parentid' => $competency->id]);

        // This should have resulted in the competency_created observer being called and an criteria_item created
        $this->verify_items($competency->id, [$child_competency->id]);
        $this->assertSame(0, item_record_entity::repository()->count());

        // Create a second child competency and verify again
        $child_competency2 = $competency_generator->create_competency('Comp A-2', $framework, ['parentid' => $competency->id]);

        // Should have resulted in the competency_created observer being called and a criteria_item created for the second child
        $this->verify_items($competency->id, [$child_competency->id, $child_competency2->id]);
        $this->assertSame(0, item_record_entity::repository()->count());

        // Add a criteria_item_record for one of the items, create another child and ensure that the existing item_records are not changed
        $user = $this->getDataGenerator()->create_user();
        $this->create_item_record($user->id, $child_competency->id);
        $this->verify_item_records($child_competency->id, [$user->id]);

        $child_competency3 = $competency_generator->create_competency('Comp A-2', $framework, ['parentid' => $competency->id]);
        $this->verify_items($competency->id, [$child_competency->id, $child_competency2->id, $child_competency3->id]);
        $this->verify_item_records($child_competency->id, [$user->id]);
        $this->verify_item_records($child_competency2->id, []);
        $this->verify_item_records($child_competency2->id, []);
    }

    /**
     * Test observer when a child competency is created for a comptetency without
     */
    public function test_competency_created_without_criteria() {
        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $framework = $competency_generator->create_framework();
        $competency = $competency_generator->create_competency('Comp A', $framework);

        // Verify generated data
        $this->assertSame(1, competency_entity::repository()->count());
        $this->assertSame(0, criterion_entity::repository()->count());
        $this->assertSame(0, item_entity::repository()->count());
        $this->assertSame(0, item_record_entity::repository()->count());

        // Create a child competency
        $competency_generator->create_competency('Comp A-1', $framework, ['parentid' => $competency->id]);
        $this->assertSame(0, item_entity::repository()->count());
        $this->assertSame(0, item_record_entity::repository()->count());

        // Create a second child competency and verify again
        $competency_generator->create_competency('Comp A-2', $framework, ['parentid' => $competency->id]);
        $this->assertSame(0, item_entity::repository()->count());
        $this->assertSame(0, item_record_entity::repository()->count());
    }

    /**
     * Test observer when a child competency is moved to another competency. Both with criteria
     */
    public function test_competency_moved_to_competency_with_criteria() {
        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $framework = $competency_generator->create_framework();

        // Create 2 competencies, each with 1 child
        $competencies = [];
        foreach (['Comp A', 'Comp B'] as $name) {
            $competencies[$name] = $competency_generator->create_competency($name, $framework);
            $criteria_generator->create_childcompetency(['competency' => $competencies[$name]->id]);

            $childname = $name . '-1';
            $competencies[$childname] = $competency_generator->create_competency(
                $childname,
                $framework,
                ['parentid' => $competencies[$name]->id]
            );
            $childname = $name . '-2';
            $competencies[$childname] = $competency_generator->create_competency(
                $childname,
                $framework,
                ['parentid' => $competencies[$name]->id]
            );
        }

        // Verify generated data
        $this->assertSame(6, competency_entity::repository()->count());
        $this->assertSame(2, criterion_entity::repository()->count());

        // The competency_created observer should have created the necessary items
        foreach (['Comp A', 'Comp B'] as $name) {
            $this->verify_items($competencies[$name]->id, [$competencies["{$name}-1"]->id, $competencies["{$name}-2"]->id]);
        }
        $this->assertSame(0, item_record_entity::repository()->count());

        // Now move Comp A-1 to Comp B
        $this->move_competency($competencies['Comp A-1'], $competencies['Comp B']);

        // This should have resulted in the competency_moved observer which should have updated both parent competencies' items
        $this->verify_items($competencies['Comp A']->id, [$competencies['Comp A-2']->id]);
        $this->verify_items(
            $competencies['Comp B']->id,
            [$competencies['Comp A-1']->id, $competencies['Comp B-1']->id, $competencies['Comp B-2']->id]
        );
        $this->assertSame(0, item_record_entity::repository()->count());

        // Now add a criteria_item_record for the items. Move an item and ensure that it's item_record is also removed
        $user = $this->getDataGenerator()->create_user();
        foreach (['Comp A', 'Comp B'] as $name) {
            $this->create_item_record($user->id, $competencies["{$name}-1"]->id);
            $this->create_item_record($user->id, $competencies["{$name}-2"]->id);
        }

        // Move Comp B-2 to Comp A
        $this->move_competency($competencies['Comp B-2'], $competencies['Comp A']);
        $this->verify_items($competencies['Comp A']->id, [$competencies['Comp A-2']->id, $competencies['Comp B-2']->id]);
        $this->verify_items($competencies['Comp B']->id, [$competencies['Comp A-1']->id, $competencies['Comp B-1']->id]);
        $this->verify_item_records($competencies['Comp A-1']->id, [$user->id]);
        $this->verify_item_records($competencies['Comp A-2']->id, [$user->id]);
        $this->verify_item_records($competencies['Comp B-1']->id, [$user->id]);
        $this->verify_item_records($competencies['Comp B-2']->id, []);


        // Move Comp B-1 to top (no parent)
        $this->move_competency($competencies['Comp B-1'], null);
        $this->verify_items($competencies['Comp A']->id, [$competencies['Comp A-2']->id, $competencies['Comp B-2']->id]);
        $this->verify_items($competencies['Comp B']->id, [$competencies['Comp A-1']->id]);
        $this->verify_item_records($competencies['Comp A-1']->id, [$user->id]);
        $this->verify_item_records($competencies['Comp A-2']->id, [$user->id]);
        $this->verify_item_records($competencies['Comp B-1']->id, []);
        $this->verify_item_records($competencies['Comp B-2']->id, []);

        // And move Comp B-1 back from top to Comp B
        $this->move_competency($competencies['Comp B-1'], $competencies['Comp B']);
        $this->verify_items($competencies['Comp A']->id, [$competencies['Comp A-2']->id, $competencies['Comp B-2']->id]);
        $this->verify_items($competencies['Comp B']->id, [$competencies['Comp A-1']->id, $competencies['Comp B-1']->id]);
        $this->verify_item_records($competencies['Comp A-1']->id, [$user->id]);
        $this->verify_item_records($competencies['Comp A-2']->id, [$user->id]);
        $this->verify_item_records($competencies['Comp B-1']->id, []);
        $this->verify_item_records($competencies['Comp B-1']->id, []);
    }

    /**
     * Test observer when a child competency is moved to another competency. Both with criteria
     */
    public function test_competency_moved_to_competency_without_criteria() {
        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $framework = $competency_generator->create_framework();

        // Create 2 competencies, each with 1 child
        $competencies = [];
        foreach (['Comp A', 'Comp B'] as $name) {
            $competencies[$name] = $competency_generator->create_competency($name, $framework);

            $childname = $name . '-1';
            $competencies[$childname] = $competency_generator->create_competency(
                $childname,
                $framework,
                ['parentid' => $competencies[$name]->id]
            );
            $childname = $name . '-2';
            $competencies[$childname] = $competency_generator->create_competency(
                $childname,
                $framework,
                ['parentid' => $competencies[$name]->id]
            );
        }

        // Verify generated data
        $this->assertSame(6, competency_entity::repository()->count());
        $this->assertSame(0, criterion_entity::repository()->count());

        // As these competencies have no criteria, no items should exist
        $this->assertSame(0, item_entity::repository()->count());
        $this->assertSame(0, item_record_entity::repository()->count());

        // Move Comp B-2 to Comp A and verify there are still no items
        $this->move_competency($competencies['Comp B-2'], $competencies['Comp A']);
        $this->assertSame(0, item_entity::repository()->count());
        $this->assertSame(0, item_record_entity::repository()->count());

        // Move Comp B-1 to top (no parent)
        $this->move_competency($competencies['Comp B-1'], null);
        $this->assertSame(0, item_entity::repository()->count());
        $this->assertSame(0, item_record_entity::repository()->count());

        // And move Comp B-1 back from top to Comp B
        $this->move_competency($competencies['Comp B-1'], $competencies['Comp B']);
        $this->assertSame(0, item_entity::repository()->count());
        $this->assertSame(0, item_record_entity::repository()->count());
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
     * Verify the criteria_item_records are as expected.
     *
     * @param int $child_competency_id - Child competency id
     * @param array $expected_user_ids
     */
    private function verify_item_records(int $child_competency_id, array $expected_user_ids) {
        $item_type = (new childcompetency())->get_items_type();

        $item_records = item_record_entity::repository()
            ->join((new table(item_entity::TABLE))->as('item'), 'criterion_item_id', '=', 'id')
            ->where('item.item_type', $item_type)
            ->where('item.item_id', $child_competency_id)
            ->get();

        $this->assertSame(count($expected_user_ids), $item_records->count());
        foreach ($item_records as $record) {
            $this->assertTrue(in_array($record->user_id, $expected_user_ids));
        }
    }

    /**
     * Create an item record for the specified child competency
     * @param int $user_id
     * @param int $child_competency_id
     */
    private function create_item_record(int $user_id, int $child_competency_id) {
        $item_type = (new childcompetency())->get_items_type();
        $item = item_entity::repository()
            ->select('id')
            ->where('item_type', $item_type)
            ->where('item_id', $child_competency_id)
            ->one();

        $item_record = new item_record_entity();
        $item_record->user_id = $user_id;
        $item_record->criterion_item_id = $item->id;
        $item_record->criterion_met = 0;
        $item_record->timeevaluated = time();
        $item_record->save();
    }

    /**
     * Move the competency to the specied parent and trigger the event
     *
     * @param competency_entity $competency Competency to move
     * @param competency_entity|null $new_parent
     */
    private function move_competency(competency_entity $competency, ?competency_entity $new_parent = null) {
        global $DB;

        // The event is generated in the old hierarchy classes.
        // We simulate it here by updating the parent and manually generating it here

        $competency->parentid = is_null($new_parent) ? 0 : $new_parent->id;
        $competency->save();

        $updateditem = $DB->get_record('comp', ['id' => $competency->id]);
        hierarchy_competency\event\competency_moved::create_from_instance($updateditem)->trigger();
    }

}
