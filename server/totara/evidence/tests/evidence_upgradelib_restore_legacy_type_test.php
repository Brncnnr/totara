<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @package totara_evidence
 * @category test
 */

use core\orm\query\builder;
use totara_evidence\entity\evidence_item as evidence_item_entity;
use totara_evidence\entity\evidence_type as evidence_type_entity;
use totara_evidence\entity\evidence_type_field;
use totara_evidence\models\evidence_item as evidence_item_model;
use totara_evidence\models\evidence_type as evidence_type_model;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/db/upgradelib.php');


class totara_evidence_restore_legacy_import_types_testcase extends advanced_testcase {

    /** @var \totara_evidence_generator */
    private $ev_generator;

    /** @var stdClass */
    private $data;

    protected function setUp(): void {
        parent::setUp();
        $this->ev_generator = static::getDataGenerator()->get_plugin_generator('totara_evidence');
        $this->setup_data();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->ev_generator = null;
        $this->data = null;
    }

    private function setup_data() {
        $data = new stdClass();

        $data->users = [];
        for ($i = 1; $i <= 3; $i++) {
            $data->users[$i] = static::getDataGenerator()->create_user();
        }

        $data->type_names = ['LegacyType1', 'LegacyType2', 'LegacyType3', 'LegacyType4'];

        $other_fields = [
            [
                'fullname' => 'Test text field',
                'shortname' => 'testtextfield',
                'datatype' => 'text',
                'sortorder' => 2,
            ],
            [
                'fullname' => 'Test checkbox',
                'shortname' => 'testcheckbox',
                'datatype' => 'checkbox',
                'sortorder' => 3,
            ],
        ];

        $items = [
            'imported' => [
                [
                    'user' => 1,
                    'type' => 'LegacyType1',
                    'name' => 'Imported - U1 - T1 - 1',
                ],
                [
                    'user' => 2,
                    'type' => 'LegacyType1',
                    'name' => 'Imported - U2 - T1 - 1',
                ],
                [
                    'user' => 1,
                    'type' => 'LegacyType3',
                    'name' => 'Imported - U1 - T3 - 1',
                ],
                [
                    'user' => 1,
                    'type' => 'LegacyType1',
                    'name' => 'Imported - U1 - T1 - 2',
                ],
                [
                    'user' => 2,
                    'name' => 'Imported - U3 - No type',
                ],
            ],
            'manual' => [
                [
                    'user' => 1,
                    'type' => 'LegacyType2',
                    'name' => 'Manual - U1 - T2 - 1',
                ],
                [
                    'user' => 1,
                    'type' => 'LegacyType1',
                    'name' => 'Manual - U1 - T1 - 1',
                ],
                [
                    'user' => 2,
                    'type' => 'LegacyType2',
                    'name' => 'Manual - U2 - T2 - 1',
                ],
            ],
        ];

        // Legacy import type
        /** @var evidence_type_entity legacy_import_type */
        $data->legacy_import_type = $this->create_type_with_fields(
            [
                'name' => 'multilang:old_type',
                'idnumber' => 'legacycompletionimport',
                'location' => evidence_type_model::LOCATION_RECORD_OF_LEARNING,
            ],
            $other_fields
        );

        // Add the old type name field
        $old_name_field_record = [
            'typeid' => $data->legacy_import_type->id,
            'fullname' => 'multilang:old_type',
            'shortname' => 'oldtypename',
            'datatype' => 'menu',
            'param1' => implode("\n", $data->type_names),
            'sortorder' => 1,
        ];
        /** @var evidence_type_field type_name_field */
        $data->type_name_field = $this->ev_generator->create_evidence_field($old_name_field_record);

        // Migrated types
        $data->evidence_types = [$data->legacy_import_type->name => $data->legacy_import_type];
        foreach ($data->type_names as $name) {
            $data->evidence_types[$name] = $this->create_type_with_fields(['name' => $name, 'idnumber' => ''], $other_fields);
        }

        // Create evidence items
        $this->create_imported_items($data, $items['imported']);
        // The totara_evidence generator creates a field id for each custom field.
        // We need to remove the oldtypename for the imported item without a type
        builder::table('totara_evidence_type_info_data')
            ->where('fieldid', $data->type_name_field->id)
            ->where('data', '')
            ->delete();

        $this->create_manual_items($data, $items['manual']);

        $this->data = $data;

        // Verify all is set up as expected
        $legacy_type_id = $data->legacy_import_type->id;
        $expected_items = array_map(function ($item) use($legacy_type_id, $other_fields) {
            $item['imported'] = 1;
            $item['fields'] = [];
            foreach ($other_fields as $other_field) {
                $item['fields'][] = [
                    'type' => $legacy_type_id,
                    'name' => $other_field['shortname'],
                ];
            }
            if (isset($item['type'])) {
                $item['fields'][] = [
                    'type' => $legacy_type_id,
                    'name' => 'oldtypename',
                ];
            }
            return $item;
        }, $items['imported']);
        $this->verify_items($legacy_type_id, $expected_items);

        foreach($data->type_names as $type_name) {
            $expected_items = array_filter($items['manual'], function ($item) use($type_name) {
                return $item['type'] == $type_name;
            });
            $type_id = $data->evidence_types[$type_name]->id;
            $expected_items = array_map(function ($item) use($type_id, $other_fields) {
                $item['imported'] = 0;
                $item['fields'] = [];
                foreach ($other_fields as $other_field) {
                    $item['fields'][] = [
                        'type' => $type_id,
                        'name' => $other_field['shortname'],
                    ];
                }
                return $item;
            }, $expected_items);

            $this->verify_items($type_id, $expected_items);
        }
    }

    private function create_type_with_fields(array $type_record, array $fields): evidence_type_entity {
        // Prevent generator from auto creating fields
        $type_record['fields'] = 0;
        $type = $this->ev_generator->create_evidence_type_entity($type_record);

        foreach ($fields as $idx => $field_record) {
            $field_record['typeid'] = $type->id;
            $field_record['shortname'] = $field_record['shortname'] ?? preg_replace('/\s+/', '', $field_record['fullname']);
            $this->ev_generator->create_evidence_field($field_record);
        }

        return $type;
    }

    private function create_imported_items(stdClass $data, array $to_create) {
        foreach ($to_create as $idx => $record) {
            $item_record = [
                'typeid' => $data->legacy_import_type->id,
                'user_id' => $data->users[$record['user']]->id,
                'name' => $record['name'],
                'status' => evidence_item_model::STATUS_ACTIVE,
                'imported' => 1,
                'fields' => [
                    'testtextfield' => $record['name'] . ' Text field value',
                    'testcheckbox' => $idx % 2,
                    'oldtypename' => $record['type'] ?? '',
                ],
            ];

            $this->ev_generator->create_evidence_item_entity($item_record);
        }
    }

    private function create_manual_items(stdClass $data, array $to_create) {
        foreach ($to_create as $idx => $record) {
            $item_record = [
                'typeid' => $data->evidence_types[$record['type']]->id,
                'user_id' => $data->users[$record['user']]->id,
                'name' => $record['name'],
                'status' => evidence_item_model::STATUS_ACTIVE,
                'imported' => 0,
                'fields' => [
                    'testtextfield' => $record['name'] . ' Text field value',
                    'testcheckbox' => $idx % 2,
                ],
            ];
            $this->ev_generator->create_evidence_item_entity($item_record);
        }
    }

    public function test_totara_evidence_get_legacy_import_type_map(): void {
        $map = totara_evidence_get_legacy_import_type_map();
        $this->verify_type_map($map, ['LegacyType1', 'LegacyType3']);

        // Add a duplicate LegacyType1 - expecting the first type's id
        $this->ev_generator->create_evidence_type_entity(['name' => 'LegacyType1', 'idnumber' => '']);
        $map2 = totara_evidence_get_legacy_import_type_map();
        $this->verify_type_map($map2, ['LegacyType1', 'LegacyType3']);

        // Delete LegacyType3
        builder::table('totara_evidence_type')
            ->where('name', 'LegacyType3')
            ->delete();

        $map3 = totara_evidence_get_legacy_import_type_map();
        $this->verify_type_map($map3, ['LegacyType1']);
    }

    public function test_totara_evidence_move_fields() {
        $legacy_type_id = $this->data->legacy_import_type->id;
        $type1_id = $this->data->evidence_types['LegacyType1']->id;
        totara_evidence_move_fields($this->data->type_name_field->id, 'LegacyType1', $legacy_type_id, $type1_id);

        // This ONLY moves the fields. The import items still belong to the legacy import type, but the fields should now belong to the actual type
        $expected_items = [
            [
                'user' => 1,
                'name' => 'Imported - U1 - T1 - 1',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $type1_id,
                        'names' => 'testtextfield',
                    ],
                    [
                        'type' => $type1_id,
                        'names' => 'testcheckbox',
                    ],
                    [
                        'type' => $legacy_type_id,
                        'names' => 'oldtypename',
                    ],
                ],
            ],
            [
                'user' => 2,
                'type' => 'LegacyType1',
                'name' => 'Imported - U2 - T1 - 1',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $type1_id,
                        'names' => 'testtextfield',
                    ],
                    [
                        'type' => $type1_id,
                        'names' => 'testcheckbox',
                    ],
                    [
                        'type' => $legacy_type_id,
                        'names' => 'oldtypename',
                    ],
                ],
            ],
            [
                'user' => 1,
                'type' => 'LegacyType3',
                'name' => 'Imported - U1 - T3 - 1',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $legacy_type_id,
                        'names' => 'testtextfield',
                    ],
                    [
                        'type' => $legacy_type_id,
                        'names' => 'testcheckbox',
                    ],
                    [
                        'type' => $legacy_type_id,
                        'names' => 'oldtypename',
                    ],
                ],
            ],
            [
                'user' => 1,
                'type' => 'LegacyType1',
                'name' => 'Imported - U1 - T1 - 2',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $type1_id,
                        'names' => 'testtextfield',
                    ],
                    [
                        'type' => $type1_id,
                        'names' => 'testcheckbox',
                    ],
                    [
                        'type' => $legacy_type_id,
                        'names' => 'oldtypename',
                    ],
                ],
            ],
            [
                'user' => 2,
                'name' => 'Imported - U3 - No type',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $legacy_type_id,
                        'names' => 'testtextfield',
                    ],
                    [
                        'type' => $legacy_type_id,
                        'names' => 'testcheckbox',
                    ],
                ],
            ],
        ];

        $this->verify_items($legacy_type_id, $expected_items);
    }


    public function test_totara_evidence_move_items() {
        // Move imported LegacyType1 items
        $type1_id = $this->data->evidence_types['LegacyType1']->id;
        totara_evidence_move_items($this->data->type_name_field->id, 'LegacyType1', $type1_id);

        $legacy_type_id = $this->data->legacy_import_type->id;
        $expected_items = [
            [
                'user' => 1,
                'name' => 'Imported - U1 - T3 - 1',
                'imported' => 1,
            ],
            [
                'user' => 2,
                'name' => 'Imported - U3 - No type',
                'imported' => 1,
            ],
        ];
        $this->verify_items($legacy_type_id, $expected_items);

        $expected_items = [
            [
                'user' => 1,
                'name' => 'Manual - U1 - T1 - 1',
                'imported' => 0,
            ],
            [
                'user' => 1,
                'name' => 'Imported - U1 - T1 - 1',
                'imported' => 1,
            ],
            [
                'user' => 2,
                'name' => 'Imported - U2 - T1 - 1',
                'imported' => 1,
            ],
            [
                'user' => 1,
                'name' => 'Imported - U1 - T1 - 2',
                'imported' => 1,
            ],
        ];

        $this->verify_items($type1_id, $expected_items);
    }

    public function test_totara_evidence_move_items_to_unspecified_type_more_to_move() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("totara_evidence_move_items_to_unspecified_type should only after all other items have been moved");
        totara_evidence_move_items_to_unspecified_type($this->data->legacy_import_type->id, $this->data->type_name_field->id);
    }

    public function test_totara_evidence_move_items_to_unspecified_type() {
        // By deleting all manual types, imported items should be moved to the unspecified type
        // First type is the legacy type
        $types = array_splice($this->data->evidence_types, 1);
        $ids = array_map(function ($type) {
            return $type->id;
        }, $types);

        builder::table('totara_evidence_item')
            ->where_in('typeid', $ids)
            ->delete();
        builder::table('totara_evidence_type')
            ->where_in('id', $ids)
            ->delete();

        // Now the test
        totara_evidence_move_items_to_unspecified_type($this->data->legacy_import_type->id, $this->data->type_name_field->id);

        $unspecified_type = evidence_type_entity::repository()
            ->select('id')
            ->where('name', 'multilang:unspecified')
            ->one(true);
        $unspecified_type_id = $unspecified_type->id;

        $legacy_type_id = $this->data->legacy_import_type->id;
        $this->verify_items($legacy_type_id, []);

        $expected = [
            [
                'user' => 1,
                'name' => 'Imported - U1 - T1 - 1',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testtextfield',
                    ],
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testcheckbox',
                    ],
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'oldtypename',
                    ],
                ],
            ],
            [
                'user' => 2,
                'name' => 'Imported - U2 - T1 - 1',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testtextfield',
                    ],
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testcheckbox',
                    ],
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'oldtypename',
                    ],
                ],
            ],
            [
                'user' => 1,
                'name' => 'Imported - U1 - T3 - 1',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testtextfield',
                    ],
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testcheckbox',
                    ],
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'oldtypename',
                    ],
                ],
            ],
            [
                'user' => 1,
                'name' => 'Imported - U1 - T1 - 2',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testtextfield',
                    ],
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testcheckbox',
                    ],
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'oldtypename',
                    ],
                ],
            ],
            [
                'user' => 2,
                'name' => 'Imported - U3 - No type',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testtextfield',
                    ],
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testcheckbox',
                    ],
                ],
            ],
        ];

        $this->verify_items($legacy_type_id, []);
        $this->verify_items($unspecified_type_id, $expected);
    }

    public function test_totara_evidence_restore_legacy_import_types() {
        totara_evidence_restore_legacy_import_types();

        // All items as well as field data should now belong to the restored types
        // and oldtypename field and data deleted

        $legacy_type_id = $this->data->legacy_import_type->id;
        $this->verify_items($legacy_type_id, []);

        $type_id = $this->data->evidence_types['LegacyType1']->id;
        $expected_items = [
            [
                'user' => 1,
                'name' => 'Manual - U1 - T1 - 1',
                'imported' => 0,
                'fields' => [
                    [
                        'type' => $type_id,
                        'name' => 'testtextfield',
                    ],
                    [
                        'type' => $type_id,
                        'name' => 'testcheckbox',
                    ]
                ],
            ],
            [
                'user' => 1,
                'name' => 'Imported - U1 - T1 - 1',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $type_id,
                        'name' => 'testtextfield',
                    ],
                    [
                        'type' => $type_id,
                        'name' => 'testcheckbox',
                    ]
                ],
            ],
            [
                'user' => 2,
                'name' => 'Imported - U2 - T1 - 1',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $type_id,
                        'name' => 'testtextfield',
                    ],
                    [
                        'type' => $type_id,
                        'name' => 'testcheckbox',
                    ]
                ],
            ],
            [
                'user' => 1,
                'name' => 'Imported - U1 - T1 - 2',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $type_id,
                        'name' => 'testtextfield',
                    ],
                    [
                        'type' => $type_id,
                        'name' => 'testcheckbox',
                    ]
                ],
            ],
        ];
        $this->verify_items($type_id, $expected_items);

        $type_id = $this->data->evidence_types['LegacyType2']->id;
        $expected_items = [
            [
                'user' => 1,
                'name' => 'Manual - U1 - T2 - 1',
                'imported' => 0,
                'fields' => [
                    [
                        'type' => $type_id,
                        'name' => 'testtextfield',
                    ],
                    [
                        'type' => $type_id,
                        'name' => 'testcheckbox',
                    ]
                ],
            ],
            [
                'user' => 2,
                'name' => 'Manual - U2 - T2 - 1',
                'imported' => 0,
                'fields' => [
                    [
                        'type' => $type_id,
                        'name' => 'testtextfield',
                    ],
                    [
                        'type' => $type_id,
                        'name' => 'testcheckbox',
                    ]
                ],
            ],
        ];
        $this->verify_items($type_id, $expected_items);

        $type_id = $this->data->evidence_types['LegacyType3']->id;
        $expected_items = [
            [
                'user' => 1,
                'name' => 'Imported - U1 - T3 - 1',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $type_id,
                        'name' => 'testtextfield',
                    ],
                    [
                        'type' => $type_id,
                        'name' => 'testcheckbox',
                    ]
                ],
            ],
        ];

        $unspecified_type = evidence_type_entity::repository()
            ->select('id')
            ->where('name', 'multilang:unspecified')
            ->one(true);
        $unspecified_type_id = $unspecified_type->id;

        $expected_items = [
            [
                'user' => 2,
                'name' => 'Imported - U3 - No type',
                'imported' => 1,
                'fields' => [
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testtextfield',
                    ],
                    [
                        'type' => $unspecified_type_id,
                        'names' => 'testcheckbox',
                    ],
                ],
            ],
        ];

        $this->verify_items($unspecified_type_id, $expected_items);

        $type_id = $this->data->evidence_types['LegacyType4']->id;
        $this->verify_items($type_id, []);
    }

    private function verify_type_map(array $map, array $expected_type_names) {
        static::assertSame(count($map), count($expected_type_names));

        foreach ($expected_type_names as $expected_name) {
            static::assertTrue(isset($map[$expected_name]));
            static::assertTrue(isset($this->data->evidence_types[$expected_name]));

            $actual_map = $map[$expected_name];
            $evidence_type = $this->data->evidence_types[$expected_name];
            static::assertEquals($expected_name, $actual_map->name);
            static::assertEquals($evidence_type->id, $actual_map->new_type_id);
        }
    }

    private function verify_fields(evidence_item_entity $item, array $expected) {
        $actual_fields = $item->data()->get();
        static::assertSame(count($expected), $actual_fields->count());

        foreach ($expected as $idx => $expected_field) {
            foreach ($actual_fields as $actual_field) {
                if ($expected_field['type'] == $actual_field->field->typeid &&
                    $expected_field['type'] = $actual_field->field->shortname
                ) {
                    unset($expected[$idx]);
                }
            }
        }

        static::assertEmpty($expected);
    }

    private function verify_items(int $type_id, array $expected_items) {
        $actual_items = evidence_item_entity::repository()
            ->where('typeid', $type_id)
            ->get();

        static::assertSame(count($actual_items), count($expected_items));

        foreach ($expected_items as $idx => $expected_item) {
            static::assertTrue(isset($this->data->users[$expected_item['user']]));

            $to_search = [
                'typeid' => $type_id,
                'user_id' => $this->data->users[$expected_item['user']]->id,
                'name' => $expected_item['name'],
                'imported' => $expected_item['imported'],
            ];

            if (isset($expected_item['fields'])) {
                $to_search['fields'] = $expected_item['fields'];
            }

            foreach ($actual_items as $actual_item) {
                if ($actual_item->typeid == $to_search['typeid'] &&
                    $actual_item->user_id == $to_search['user_id'] &&
                    $actual_item->name == $to_search['name'] &&
                    $actual_item->imported == $to_search['imported']
                ) {
                    if (isset($to_search['fields'])) {
                        $this->verify_fields($actual_item, $to_search['fields']);
                    }
                    unset($expected_items[$idx]);
                    break;
                }
            }
        }

        static::assertEmpty($expected_items);
    }
}
