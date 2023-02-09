<?php
/*
 *  This file is part of Totara TXP
 *
 *  Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @author Simon Coggins <simon.coggins@totaralearning.com>
 *
 */

use core_phpunit\testcase;
use totara_api\cli\metadata_helper;

/**
 * @group totara_api
 */
class metadata_helper_testcase extends testcase {
    public function test_add_file(): void {
        global $CFG;

        $metadata = new metadata_helper();

        // Shouldn't be anything there to start with.
        self::assertEmpty($metadata->get_metadata_as_array());

        // Won't add an invalid file.
        self::assertFalse($metadata->add_file('invalid_file'));

        // Nothing got added.
        self::assertEmpty($metadata->get_metadata_as_array());

        // Will add a valid file.
        self::assertTrue($metadata->add_file($CFG->dirroot . '/lib/webapi/metadata.json'));

        self::assertNotEmpty($metadata->get_metadata_as_array());
    }

    public function test_add_file_contents(): void {
        $metadata = new metadata_helper();
        $reflection = new ReflectionObject($metadata);

        // Using reflections here to test some internal implementations and allow us to add
        // the json structures we need without having to use fixtures.
        $add_file_contents = $reflection->getMethod('add_file_contents');
        $add_file_contents->setAccessible(true);

        self::assertFalse($add_file_contents->invoke($metadata, '"invalid jsona: "b"}'));
        self::assertEmpty($metadata->get_metadata_as_array());

        self::assertTrue($add_file_contents->invoke($metadata, '{"a": "b"}'));
        self::assertEquals(['a' => 'b'], $metadata->get_metadata_as_array());
        self::assertEquals("{\n    \"a\": \"b\"\n}", $metadata->get_metadata_as_json());
    }

    public function test_merge_file_contents(): void {
        $metadata = new metadata_helper();
        $reflection = new ReflectionObject($metadata);

        // Using reflections here to test some internal implementations and allow us to add
        // the json structures we need without having to use fixtures.
        $add_file_contents = $reflection->getMethod('add_file_contents');
        $add_file_contents->setAccessible(true);

        self::assertTrue($add_file_contents->invoke($metadata, '{"a": "b"}'));

        // Test adding new keys that don't clash.
        self::assertTrue($add_file_contents->invoke($metadata, '{"c": "d"}'));
        self::assertEquals(['a' => 'b', 'c' => 'd'], $metadata->get_metadata_as_array());

        // Test adding same key merges.
        self::assertTrue($add_file_contents->invoke($metadata, '{"c": "e"}'));
        self::assertEquals(['a' => 'b', 'c' => ['d', 'e']], $metadata->get_metadata_as_array());
    }

    public function test_merge_realistic_metadata(): void {
        $metadata = new metadata_helper();
        $reflection = new ReflectionObject($metadata);

        // Using reflections here to test some internal implementations and allow us to add
        // the json structures we need without having to use fixtures.
        $add_file_contents = $reflection->getMethod('add_file_contents');
        $add_file_contents->setAccessible(true);

        $input1 = '{
            "OBJECT": {
                "Mutation": {
                    "fields": {
                        "todo": {
                            "documentation": { "undocumented": true }
                        }
                    }
                },
                "Query": {
                    "fields": {
                        "core": {
                            "documentation": { "undocumented": true }
                        }
                    }
                },
                "custom_type": {
                    "documentation": { "undocumented": true }
                }
            },
            "SCALAR": {
                "param_alpha": {
                    "documentation": { "example": "Abc" }
                }
            }
        }';

        $input2 = '{
            "OBJECT": {
                "Mutation": {
                    "fields": {
                        "different_mutation": {
                            "documentation": { "undocumented": true }
                        }
                    }
                },
                "Query": {
                    "fields": {
                        "some_other_query": {
                            "documentation": { "undocumented": true }
                        }
                    }
                },
                "another_custom_type": {
                    "documentation": { "undocumented": true }
                }
            },
            "SCALAR": {
                "param_beta": {
                    "documentation": { "example": "Abc" }
                }
            }
        }';
        self::assertTrue($add_file_contents->invoke($metadata, $input1));
        self::assertTrue($add_file_contents->invoke($metadata, $input2));

        $metadata_array = $metadata->get_metadata_as_array();

        $query_keys = array_keys($metadata_array['OBJECT']['Query']['fields']);
        self::assertEqualsCanonicalizing(['core', 'some_other_query'], $query_keys);

        $mutation_keys = array_keys($metadata_array['OBJECT']['Mutation']['fields']);
        self::assertEqualsCanonicalizing(['todo', 'different_mutation'], $mutation_keys);

        $type_keys = array_keys($metadata_array['OBJECT']);
        self::assertEqualsCanonicalizing(['Query', 'Mutation', 'custom_type', 'another_custom_type'], $type_keys);

        $scalar_keys = array_keys($metadata_array['SCALAR']);
        self::assertEqualsCanonicalizing(['param_alpha', 'param_beta'], $scalar_keys);
    }
}
