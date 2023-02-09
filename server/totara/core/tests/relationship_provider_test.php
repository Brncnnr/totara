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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

use core\collection;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship_provider;

require_once(__DIR__ . '/relationship_resolver_test.php');

/**
 * @group totara_core_relationship
 * @covers \totara_core\relationship\relationship_provider
 */
class totara_core_relationship_provider_testcase extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        relationship_entity::repository()->delete();
    }

    public function test_fetch_compatible_relationships(): void {
        // test_resolver_two & test_resolver_five only have the 'input_field_two' field in common.
        $relationship1 = relationship::create([test_resolver_two::class], 'one', 1000);
        // test_resolver_one & test_resolver_three only have the 'input_field_one' field in common.
        $relationship2 = relationship::create([test_resolver_one::class, test_resolver_three::class], 'two', 2000);
        // test_resolver_five accepts either ['input_field_one', 'input_field_three'] OR ['input_field_two']
        $relationship3 = relationship::create([test_resolver_five::class], 'three', 3000);

        $this->assert_same_relationships(
            [$relationship1, $relationship3],
            (new relationship_provider())->get_compatible_relationships(['input_field_two'])
        );
        $this->assert_same_relationships(
            [$relationship2],
            (new relationship_provider())->get_compatible_relationships(['input_field_one'])
        );
        $this->assert_same_relationships(
            [],
            (new relationship_provider())->get_compatible_relationships(['input_field_three'])
        );
        $this->assert_same_relationships(
            [$relationship2, $relationship3],
            (new relationship_provider())->get_compatible_relationships(['input_field_three', 'input_field_one'])
        );

        // There isn't any point in specifying an empty set of fields.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Must specify at least one field to filter_by_compatible()');
        (new relationship_provider())->get_compatible_relationships([]);
    }

    public function test_fetch_component_relationships(): void {
        // First relationship has no component so can be used universally
        $relationship1 = relationship::create([test_resolver_two::class], 'one', 1000);

        // Second relationship has component 'totara_core'
        $relationship2 = relationship::create([test_resolver_one::class, test_resolver_three::class], 'two', 2000, null, 'totara_core');

        // Third relationship has component 'core'
        $relationship3 = relationship::create([test_resolver_five::class], 'three', 3000, null, 'core');

        // Don't include universal relationships (i.e. relationship1)
        $this->assert_same_relationships(
            [$relationship2],
            (new relationship_provider())->filter_by_component('totara_core')->get()
        );
        $this->assert_same_relationships(
            [$relationship3],
            (new relationship_provider())->filter_by_component('core')->get()
        );

        // Include universal relationships (i.e. relationship1)
        $this->assert_same_relationships(
            [$relationship1, $relationship2],
            (new relationship_provider())->filter_by_component('totara_core', true)->get()
        );
        $this->assert_same_relationships(
            [$relationship1, $relationship3],
            (new relationship_provider())->filter_by_component('core', true)->get()
        );
    }

    public function test_fetch_universal_relationships(): void {
        // First relationship has no component so can be used universally
        $relationship1 = relationship::create([test_resolver_two::class], 'one', 1000);

        // Second relationship has component 'totara_core'
        $relationship2 = relationship::create([test_resolver_one::class, test_resolver_three::class], 'two', 2000, null, 'totara_core');

        // Third relationship has component 'core'
        $relationship3 = relationship::create([test_resolver_five::class], 'three', 3000, null, 'core');

        // No filter applied
        $this->assert_same_relationships(
            [$relationship1, $relationship2, $relationship3],
            (new relationship_provider())->get()
        );

        // Only universally usable relationships
        $this->assert_same_relationships(
            [$relationship1],
            (new relationship_provider())->filter_by_universal()->get()
        );
    }

    /**
     * Assert two relationship arrays are the same by comparing their IDs.
     * Can not compare the arrays directly due to the complex entity structure not being identical.
     *
     * @param relationship[] $expected
     * @param relationship[]|collection $actual
     */
    private function assert_same_relationships(array $expected, collection $actual): void {
        $filter = static function (relationship $relationship) {
            return $relationship->id;
        };
        $expected_ids = array_map($filter, $expected);
        $actual_ids = $actual->map($filter)->all();
        $this->assertEquals($expected_ids, $actual_ids);
    }

}
