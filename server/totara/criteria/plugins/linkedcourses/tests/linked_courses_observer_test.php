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
use criteria_linkedcourses\linkedcourses;
use totara_competency\linked_courses;
use totara_criteria\criterion;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\entity\criteria_item as item_entity;
use totara_criteria\entity\criteria_item_record as item_record_entity;
use totara_criteria\entity\criteria_metadata as metadata_entity;
use totara_hierarchy\entity\competency as competency_entity;

/**
 * Class criteria_linkedcourses_observer_testcase tests the observers
 * The tests are very similar to the ones defined in items_processor_test.php, but here we don't sink the events.
 * We basically test that by relying on the observers we get the same results as we got when calling the item_processor
 * manually
 *
 * @group totara_competency
 */
class criteria_linkedcourses_linked_courses_observer_testcase extends testcase {

    private function setup_data(bool $add_criterion = false) {
        $data = new class() {
            /** @var competency_entity $competency */
            public $competency;
            /** @var criterion $criterion */
            public $criterion;
            public $courses = [];
        };

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $data->competency = $competency_generator->create_competency('Comp A');

        for ($i = 1; $i <= 3; $i++) {
            $data->courses[$i] = $this->getDataGenerator()->create_course();
        }

        if ($add_criterion) {
            /** @var \totara_criteria\testing\generator $criteria_generator */
            $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

            $data->criterion = $criteria_generator->create_linkedcourses(['competency' => $data->competency->id]);
        }

        return $data;
    }

    /**
     * Test observer when courses linked to a comptetency with linkedcourse criteria changes
     */
    public function test_linked_courses_updated_with_criteria() {

        $data = $this->setup_data(true);

        // Verify generated data
        $this->assertSame(1, competency_entity::repository()->count());
        $this->assertSame(1, criterion_entity::repository()->count());
        $this->assertSame(0, item_entity::repository()->count());
        $this->assertSame(0, item_record_entity::repository()->count());

        // Now link a course to the competency and verify that an item is created
        linked_courses::set_linked_courses($data->competency->id, [
            ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
        ]);

        $this->verify_items($data->competency->id, [$data->courses[1]->id]);
        $this->assertSame(0, item_record_entity::repository()->count());
        $item = item_entity::repository()->one();
        $item_id_map = [$data->courses[1]->id => $item->id];

        // Link a second course and verify again. Make sure that the item for course[1] were not deleted and re-created
        linked_courses::set_linked_courses($data->competency->id, [
            ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ['id' => $data->courses[2]->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
        ]);

        $this->verify_items($data->competency->id, [$data->courses[1]->id, $data->courses[2]->id], $item_id_map);
        $this->assertSame(0, item_record_entity::repository()->count());

        // Now add a criteria_item_record for the items, remove one of the linked courses and verify that the item as
        // well as the item_record is deleted
        $user = $this->getDataGenerator()->create_user();
        for ($i = 1; $i <= 2; $i++) {
            $this->create_item_record($user->id, $data->courses[$i]->id);
            $this->verify_item_records($data->courses[$i]->id, [$user->id]);
        }

        linked_courses::set_linked_courses($data->competency->id, [
            ['id' => $data->courses[2]->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
        ]);

        $this->verify_items($data->competency->id, [$data->courses[2]->id]);
        $this->verify_item_records($data->courses[1]->id, []);
        $this->verify_item_records($data->courses[2]->id, [$user->id]);
    }

    /**
     * Test observer when courses linked to a comptetency with linkedcourse criteria changes
     */
    public function test_linked_courses_updated_without_criteria() {

        $data = $this->setup_data(false);

        // Verify generated data
        $this->assertSame(1, competency_entity::repository()->count());
        $this->assertSame(0, criterion_entity::repository()->count());
        $this->assertSame(0, item_entity::repository()->count());
        $this->assertSame(0, item_record_entity::repository()->count());

        // Now link a course to the competency and verify that an item is created
        linked_courses::set_linked_courses($data->competency->id, [
            ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
        ]);

        // No criterion - no items expected
        $this->assertSame(0, criterion_entity::repository()->count());
        $this->assertSame(0, item_entity::repository()->count());
        $this->assertSame(0, item_record_entity::repository()->count());
    }

    /**
     * Get the items linked to linkedcourses criteria on the specified competency
     * @param int $competency_id
     * @return collection
     */
    private function get_items(int $competency_id): collection {
        $item_type = (new linkedcourses())->get_items_type();

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
     * @param int $course_id - Course id
     * @param array $expected_user_ids
     */
    private function verify_item_records(int $course_id, array $expected_user_ids) {
        $item_type = (new linkedcourses())->get_items_type();

        $item_records = item_record_entity::repository()
            ->join((new table(item_entity::TABLE))->as('item'), 'criterion_item_id', '=', 'id')
            ->where('item.item_type', $item_type)
            ->where('item.item_id', $course_id)
            ->get();

        $this->assertSame(count($expected_user_ids), $item_records->count());
        foreach ($item_records as $record) {
            $this->assertTrue(in_array($record->user_id, $expected_user_ids));
        }
    }

    /**
     * Create an item record for the specified course and user
     *
     * @param int $user_id
     * @param int $course_id
     */
    private function create_item_record(int $user_id, int $course_id) {
        $item_type = (new linkedcourses())->get_items_type();
        $item = item_entity::repository()
            ->select('id')
            ->where('item_type', $item_type)
            ->where('item_id', $course_id)
            ->one();

        $item_record = new item_record_entity();
        $item_record->user_id = $user_id;
        $item_record->criterion_item_id = $item->id;
        $item_record->criterion_met = 0;
        $item_record->timeevaluated = time();
        $item_record->save();
    }

}
