<?php
/**
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_evidence
 * @category test
 */

use totara_evidence\customfield_area\evidence;
use totara_evidence\entity\evidence_field_data;
use totara_evidence\entity\evidence_item;
use totara_evidence\entity\evidence_type;
use totara_evidence\event\evidence_item_deleted;
use totara_evidence\userdata\evidence_items_other;
use totara_evidence\userdata\evidence_items_self;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

/**
 * @group totara_evidence
 */
class totara_evidence_userdata_evidence_items_testcase extends totara_evidence_testcase {

    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
    }

    public function test_context_levels(): void {
        $context_levels = evidence_items_self::get_compatible_context_levels();
        $this->assertCount(1, $context_levels);
        $this->assertContains(CONTEXT_SYSTEM, $context_levels);

        $context_levels = evidence_items_other::get_compatible_context_levels();
        $this->assertCount(1, $context_levels);
        $this->assertContains(CONTEXT_SYSTEM, $context_levels);
    }

    /**
     * Confirm that no errors are thrown and that correct data is returned
     * when no items for the user exist within the system.
     */
    public function test_with_no_data(): void {
        $user = self::getDataGenerator()->create_user();

        $export = $this->export_other($user);
        $this->assertEmpty($export->files);
        $this->assertEmpty($export->data['items']);
        $this->assertEmpty($export->files);

        $export = $this->export_self($user);
        $this->assertEmpty($export->files);
        $this->assertEmpty($export->data['items']);
        $this->assertEmpty($export->files);

        $this->assertEquals(0, $this->count_other($user));
        $this->assertEquals(0, $this->count_self($user));

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $this->purge_other($user));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $this->purge_self($user));
    }

    public function test_count(): void {
        $data = $this->create_data();

        $this->assertEquals(0, $this->count_self($data->user1));
        $this->assertEquals(2, $this->count_self($data->user2));
        $this->assertEquals(0, $this->count_self($data->user3));
        $this->assertEquals(1, $this->count_other($data->user1));
        $this->assertEquals(1, $this->count_other($data->user2));
        $this->assertEquals(0, $this->count_other($data->user3));
    }

    public function test_purge(): void {
        $data = $this->create_data(true);

        $files = $this->get_files();
        // two files per item
        $this->assertEquals(10, count($files));

        $sink = $this->redirectEvents();

        // User did not have items created by self, only others
        $this->purge_self($data->user1);
        // So it's still there
        $this->assert_has_item_count(1, $data->user1);

        $this->assertEquals(0, $sink->count());

        // Now it's all gone
        $this->purge_other($data->user1);
        $this->assert_has_item_count(0, $data->user1);

        // Check that event was fired
        $this->assertEquals(1, $sink->count());
        $this->assertContainsOnlyInstancesOf(evidence_item_deleted::class, $sink->get_events());
        $sink->clear();

        // Other users are untouched
        $this->assert_has_item_count(3, $data->user2);
        $this->assert_has_item_count(1, $data->user4);

        // Delete user 2
        $this->purge_self($data->user2);
        $this->assert_has_item_count(0, $data->user2, $data->user2);

        // Check that event was fired
        $this->assertEquals(2, $sink->count());
        $events = $sink->get_events();
        $this->assertContainsOnlyInstancesOf(evidence_item_deleted::class, $events);
        $objectids = array_map(function (\core\event\base $event): int {
            return $event->objectid;
        }, $events);
        foreach ($objectids as $objectid) {
            $this->assertGreaterThan(0, $objectid);
        }
        // check that we have different objectids
        $this->assertCount(2, array_unique($objectids));

        // Still some left
        $this->assert_has_item_count(1, $data->user2);

        // Now it's all gone
        $this->purge_other($data->user2);
        $this->assert_has_item_count(0, $data->user2);

        // item created by this user fo other is still there
        $this->assert_has_item_count(1, null, $data->user2);

        $files = $this->get_files();
        // only one item left
        $this->assertEquals(2, count($files));

        foreach ($files as $file) {
            $item_data = evidence_field_data::repository()->find($file->get_itemid());
            $this->assertNotEmpty($item_data);
            $this->assertEquals($data->item5->id, $item_data->evidenceid);
        }
    }

    public function test_export(): void {
        $data = $this->create_data(true);

        // This one does not have any items he has created himself
        $export = $this->export_self($data->user1);
        $this->assertInstanceOf(export::class, $export);
        $this->assertArrayHasKey('items', $export->data);
        $this->assertEmpty($export->data['items']);
        $this->assertEmpty($export->files);

        // This user has only one item
        $export = $this->export_other($data->user1);
        $this->assertArrayHasKey('items', $export->data);
        $this->assertCount(1, $export->data['items']);
        $this->assertCount(2, $export->files);
        $this->assert_export_has_item($export, $data->item1);

        $export = $this->export_self($data->user2);
        $this->assertArrayHasKey('items', $export->data);
        $this->assertCount(2, $export->data['items']);
        $this->assertCount(4, $export->files);
        $this->assert_export_has_item($export, $data->item3);
        $this->assert_export_has_item($export, $data->item4);

        // This user has only one item
        $export = $this->export_other($data->user2);
        $this->assertArrayHasKey('items', $export->data);
        $this->assertCount(1, $export->data['items']);
        $this->assertCount(2, $export->files);
        $this->assert_export_has_item($export, $data->item2);
    }

    protected function count_self(object $user): int {
        return evidence_items_self::execute_count(new target_user($user), context_system::instance());
    }

    protected function count_other(object $user): int {
        return evidence_items_other::execute_count(new target_user($user), context_system::instance());
    }

    protected function purge_self(object $user): int {
        return evidence_items_self::execute_purge(new target_user($user), context_system::instance());
    }

    protected function purge_other(object $user): int {
        return evidence_items_other::execute_purge(new target_user($user), context_system::instance());
    }

    protected function export_self(object $user) {
        return evidence_items_self::execute_export(new target_user($user), context_system::instance());
    }

    protected function export_other(object $user) {
        return evidence_items_other::execute_export(new target_user($user), context_system::instance());
    }

    /**
     * Create the data needed for the tests
     *
     * @param bool $create_files true to create files
     * @return object
     */
    protected function create_data($create_files = false): object {
        $data = new class {
            public $user1;
            public $user2;
            public $user3;
            public $user4;

            public $item1;
            public $item2;
            public $item3;
            public $item4;
            public $item5;

            /**
             * @var evidence_type
             */
            public $type;
        };

        $generator = $this->generator();
        $generator->set_create_files($create_files);

        $data->user1 = (object) self::getDataGenerator()->create_user();
        $data->user2 = (object) self::getDataGenerator()->create_user();
        $data->user3 = (object) self::getDataGenerator()->create_user();
        $data->user4 = (object) self::getDataGenerator()->create_user();

        $data->type = $generator->create_evidence_type_entity([
            'name' => 'Type',
            'field_types' => [
                'text',
                'textarea',
                'file'
            ]
        ]);

        $data->item1 = $generator->create_evidence_item_entity([
            'name'       => 'One',
            'type'       => $data->type,
            'user_id'    => $data->user1->id,
            'created_by' => $data->user2->id
        ]);
        $data->item2 = $generator->create_evidence_item_entity([
            'name'       => 'Two',
            'type'       => $data->type,
            'user_id'    => $data->user2->id,
            'created_by' => $data->user1->id
        ]);
        $data->item3 = $generator->create_evidence_item_entity([
            'name'       => 'Three',
            'type'       => $data->type,
            'user_id'    => $data->user2->id,
            'created_by' => $data->user2->id
        ]);
        $data->item4 = $generator->create_evidence_item_entity([
            'name'       => 'Four',
            'type'       => $data->type,
            'user_id'    => $data->user2->id,
            'created_by' => $data->user2->id
        ]);
        $data->item5 = $generator->create_evidence_item_entity([
            'name'       => 'Five',
            'type'       => $data->type,
            'user_id'    => $data->user4->id,
            'created_by' => $data->user2->id
        ]);

        return $data;
    }

    /**
     * Assert that the given user and/or creator has the expected amount of items
     *
     * @param int $expected_count
     * @param object|null $user
     * @param object|null $creator
     */
    protected function assert_has_item_count(int $expected_count, ?object $user = null, ?object $creator = null): void {
        $repo = evidence_item::repository();
        if ($user) {
            $repo->where('user_id', $user->id);
        }
        if ($creator) {
            $repo->where('created_by', $creator->id);
        }
        $count = $repo->count();

        $message = sprintf('Item does not have expected count %d. Actually found %d', $expected_count, $count);
        $this->assertEquals($expected_count, $count, $message);
    }

    /**
     * Asserts that the item is present in the exported data
     *
     * @param export $export
     * @param evidence_item $item
     */
    protected function assert_export_has_item(export $export, evidence_item $item): void {
        $type = new evidence_type($item->typeid);

        $expected_data = [
            'id' => $item->id,
            'typeid' => $type->id,
            'type' => format_string($type->name),
            'name' => format_string($item->name),
            'status' => (int)$item->status,
            'created_by' => (int)$item->created_by,
            'created_at' => (int)$item->created_at,
            'modified_by' => (int)$item->modified_by,
            'modified_at' => (int)$item->modified_at
        ];

        foreach ($export->data['items'] as $exported_item) {
            if ($exported_item['id'] == $item->id) {
                $this->assertValidKeys($expected_data, $exported_item);
                foreach ($expected_data as $key => $value) {
                    $this->assertEquals($value, $exported_item[$key]);
                }
                $this->assertArrayHasKey('fields', $exported_item);
                $this->assertCount(3, $exported_item['fields']);
                foreach ($exported_item['fields'] as $field) {
                    $this->assertArrayHasKey('type', $field);
                    $this->assertArrayHasKey('label', $field);
                    $this->assertArrayHasKey('value', $field);
                    // Check that there's the file information present for the relevant field types.
                    // The details are not worth checking as it runs through a standard function of the export
                    if (in_array($field['type'], ['file', 'textarea'])) {
                        $this->assertArrayHasKey('files', $field);
                        $this->assertCount(1, $field['files']);
                    } else {
                        $this->assertArrayNotHasKey('files', $field);
                    }
                }
                return;
            }
        }
        $this->fail('Expected item not found');
    }

    /**
     * get all files for evidence
     *
     * @return stored_file[]
     */
    protected function get_files(): array {
        return get_file_storage()->get_area_files(
            context_system::instance()->id,
            'totara_customfield',
            evidence::get_fileareas(),
            false,
            "itemid",
            false
        );
    }

}