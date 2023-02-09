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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

use core\orm\collection;
use core\orm\query\table;
use core_phpunit\testcase;
use criteria_childcompetency\childcompetency;
use criteria_childcompetency\items_processor;
use totara_criteria\criterion;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\entity\criteria_item as item_entity;
use totara_criteria\entity\criteria_item_record as item_record_entity;
use totara_criteria\entity\criteria_metadata as metadata_entity;
use totara_hierarchy\entity\competency as competency_entity;

/**
 * @group totara_competency
 */
class criteria_childcompetency_items_processor_testcase extends testcase {

    private function setup_data() {
        $data = new class() {
            /** @var [\stdClass] competencies */
            public $competencies = [];
            /** var [childcompetency] $criteria */
            public $criteria = [];
        };

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        // Preventing events here to prevent the observers from making the calls to update_items
        $sink = $this->redirectEvents();

        $to_create = [
            'Comp A' => ['with_criteria' => true],
            'Comp A-1' => ['parent' => 'Comp A'],
            'Comp A-1-1' => ['parent' => 'Comp A-1'],
            'Comp B' => ['with_criteria' => false],
            'Comp B-1' => ['parent' => 'Comp B'],
            'Comp B-1-1' => ['parent' => 'Comp B-1'],
            'Comp C' => ['with_criteria' => true],
            'Comp C-1' => ['parent' => 'Comp C'],
            'Comp C-2' => ['parent' => 'Comp C'],
            'Comp D' => ['with_criteria' => false],
            'Comp D-1' => ['parent' => 'Comp D'],
            'Comp D-2' => ['parent' => 'Comp D'],
            'Comp E' => ['with_criteria' => true],
            'Comp F' => ['with_criteria' => false],
        ];

        // Create competencies with 2 levels of children
        foreach ($to_create as $compname => $compdata) {
            $comp_record = isset($compdata['parent']) ? ['parentid' => $data->competencies[$compdata['parent']]->id] : [];
            $data->competencies[$compname] = $competency_generator->create_competency($compname, null, $comp_record);

            if (isset($compdata['with_criteria']) && $compdata['with_criteria']) {
                $data->criteria[$compname] = $criteria_generator->create_childcompetency(
                    ['competency' => $data->competencies[$compname]->id]
                );
            }
        }

        // Verify generated data
        $this->assertSame(count($to_create), competency_entity::repository()->count());

        foreach ($to_create as $compname => $compdata) {
            if (isset($compdata['parent'])) {
                $child_exists = competency_entity::repository()
                    ->where('id', $data->competencies[$compname]->id)
                    ->where('parentid', $data->competencies[$compdata['parent']]->id)
                    ->exists();
                $this->assertTrue($child_exists);
            }

            if (isset($compdata['with_criteria']) && $compdata['with_criteria']) {
                $this->verify_criterion($data->competencies[$compname]->id, true);
            }
        }

        $sink->clear();

        return $data;
    }


    public function test_update_items() {
        $data = $this->setup_data();
        $sink = $this->redirectEvents();

        // Update items for non-existent competency
        $this->verify_criterion(1, false);
        $this->verify_items(1, []);
        items_processor::update_items(1);
        $this->verify_criterion(1, false);
        $this->verify_items(1, []);

        // Ensure that the no event was triggered
        $this->assertSame(0, $sink->count());

        // Update items for competency without any children
        items_processor::update_items($data->competencies['Comp E']->id);
        $this->verify_items($data->competencies['Comp E']->id, []);
        $this->assertSame(0, $sink->count());

        items_processor::update_items($data->competencies['Comp F']->id);
        $this->verify_items($data->competencies['Comp F']->id, []);
        $this->assertSame(0, $sink->count());

        // Update items for competencies with children but without criteria
        foreach (['Comp B', 'Comp D'] as $compname) {
            items_processor::update_items($data->competencies[$compname]->id);
            $this->verify_items($data->competencies[$compname]->id, []);
            $this->assertSame(0, $sink->count());
        }

        // Update items for competency with criteria and multi level children
        items_processor::update_items($data->competencies['Comp A']->id);
        $this->verify_items($data->competencies['Comp A']->id, [$data->competencies['Comp A-1']->id]);

        // Update items for competency with criteria and multiple direct children
        items_processor::update_items($data->competencies['Comp C']->id);
        $this->verify_items($data->competencies['Comp C']->id,
            [$data->competencies['Comp C-1']->id, $data->competencies['Comp C-2']->id]
        );

        $sink->close();
    }

    public function test_update_items_with_changed_children() {
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $data = $this->setup_data();
        $sink = $this->redirectEvents();

        // Comp E starts with criterion, but no children
        items_processor::update_items($data->competencies['Comp E']->id);
        $this->verify_items($data->competencies['Comp E']->id, []);
        $this->assertSame(0, $sink->count());

        // Create 2 child competencies of Comp E
        $new_child_1 = $competency_generator->create_competency('New Child 1',
            null,
            ['parentid' => $data->competencies['Comp E']->id]
        );
        $new_child_2 = $competency_generator->create_competency('New Child 2',
            null,
            ['parentid' => $data->competencies['Comp E']->id]
        );
        $new_child_3 = $competency_generator->create_competency('New Child 3',
            null,
            ['parentid' => $data->competencies['Comp E']->id]
        );

        // Ignore the hierarchy events that were triggered (will be picked up by observer - tested elsewhere)
        $sink->clear();

        // Now we should create 3 items for Comp E and 1 event generated
        items_processor::update_items($data->competencies['Comp E']->id);
        $this->verify_items($data->competencies['Comp E']->id,
            [$new_child_1->id, $new_child_2->id, $new_child_3->id]
        );

        // Retrieve the current item record ids to be used later
        $initial_items = $this->get_items($data->competencies['Comp E']->id);
        $initial_item_id_map = array_combine($initial_items->pluck('item_id'), $initial_items->pluck('id'));

        // Change NewChild1 to point to another parent
        // Add Another Child to Comp E
        $another_child = $competency_generator->create_competency('Another Child',
            null,
            ['parentid' => $data->competencies['Comp E']->id]
        );

        $entity1 = new competency_entity($new_child_1->id);
        $entity1->parentid = 0;
        $entity1->path = '/' . $new_child_1->id;
        $entity1->save();

        $sink->clear();

        items_processor::update_items($data->competencies['Comp E']->id);
        $this->verify_items($data->competencies['Comp E']->id,
            [$new_child_2->id, $new_child_3->id, $another_child->id],
            $initial_item_id_map
        );

        $sink->close();
    }

    public function test_update_items_with_records() {
        $data = $this->setup_data();
        $sink = $this->redirectEvents();

        // Run update_items to generate the initial items
        // C initially has 2 children C-1 and C-2 (Tested in previous test)
        items_processor::update_items($data->competencies['Comp C']->id);

        // Now manually create some criteria_item_records for both items
        $user = $this->getDataGenerator()->create_user();

        $current_items = $this->get_items($data->competencies['Comp C']->id);
        foreach ($current_items as $item) {
            $item->item_records()->save(
                new item_record_entity(
                    [
                        'user_id' => $user->id,
                        'criterion_met' => 0,
                        'timeevaluated' => time(),
                    ]
                )
            );
        }

        $sink->clear();

        // Now for the test
        // Move Comp C-1 to Comp A who has a childcompetency criterion
        $data->competencies['Comp C-1']->parentid = $data->competencies['Comp A']->id;
        $data->competencies['Comp C-1']->save();

        // Run update items on both
        items_processor::update_items($data->competencies['Comp C']->id);

        items_processor::update_items($data->competencies['Comp A']->id);

        // And verify both the items and item_records
        $this->verify_items($data->competencies['Comp C']->id, [$data->competencies['Comp C-2']->id]);
        $this->verify_items($data->competencies['Comp A']->id,
            [$data->competencies['Comp A-1']->id, $data->competencies['Comp C-1']->id]
        );

        // Comp C-1 should no longer have an item_record
        $this->verify_item_records($data->competencies['Comp C-1']->id, []);

        $sink->close();
    }


    private function verify_criterion(int $competency_id, bool $expect_to_exist = true) {
        $criterion_count = criterion_entity::repository()
            ->set_filter('competency', $competency_id)
            ->where('plugin_type', 'childcompetency')
            ->count();

        $this->assertSame($expect_to_exist ? 1 : 0, $criterion_count);
    }

    /**
     * Get the items linked to childcompetency criteria on the specified competency
     *
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
     * @param array|null $previous_item_id_map - If specified, this is used to ensure that the item row were not replaced
     *                                           (totara_criteria_item.id is still the same)
     */
    private function verify_items(int $competency_id, array $expected_item_ids, ?array $previous_item_id_map = null) {
        $current_items = $this->get_items($competency_id);

        $this->assertSame(count($expected_item_ids), $current_items->count());
        foreach ($current_items as $item) {
            $this->assertTrue(in_array($item->item_id, $expected_item_ids));

            if (!is_null($previous_item_id_map) && isset($previous_item_id_map[$item->item_id])) {
                $this->assertEquals($previous_item_id_map[$item->item_id], $item->id);
            }
        }
    }

    /**
     * Verify the criteria_item_records are as expected.
     *
     * @param int $child_competency_id - Competency
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

}
