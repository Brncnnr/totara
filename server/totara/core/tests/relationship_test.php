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

use core\orm\query\exceptions\record_not_found_exception;
use totara_core\entity\relationship as relationship_entity;
use totara_core\entity\relationship_resolver;
use totara_core\relationship\relationship;
use totara_core\relationship\resolvers\subject;

require_once(__DIR__ . '/relationship_resolver_test.php');

/**
 * @group totara_core_relationship
 * @covers \totara_core\relationship\relationship
 */
class totara_core_relationship_testcase extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        relationship_entity::repository()->delete();
    }

    public function test_load_by_idnumber(): void {
        $relationship_one = relationship::create([test_resolver_one::class], 'one', 1);
        $relationship_two = relationship::create([test_resolver_two::class], 'two', 2);

        $this->assertEquals($relationship_one->id, relationship::load_by_idnumber('one')->id);
        $this->assertEquals($relationship_two->id, relationship::load_by_idnumber('two')->id);

        // Can not load a deleted relationship via idnumber - throws exception instead.
        relationship_entity::repository()->where('idnumber', 'one')->delete();
        $this->expectException(record_not_found_exception::class);
        relationship::load_by_idnumber('one');
    }

    public function test_get_name(): void {
        $relationship_one = relationship::create([test_resolver_one::class], 'subject', 1);
        $relationship_two = relationship::create([test_resolver_two::class], 'unknown', 2);
        $this->assertEquals('Subject', $relationship_one->get_name());
        $this->assertEquals('Unknown relationship name', $relationship_two->get_name());
    }

    public function test_get_name_plural(): void {
        $relationship_one = relationship::create([test_resolver_one::class], 'subject', 1);
        $relationship_two = relationship::create([test_resolver_two::class], 'unknown', 2);
        $this->assertEquals('Subjects', $relationship_one->get_name_plural());
        $this->assertEquals('Unknown relationship name', $relationship_two->get_name_plural());
    }

    public function test_idnumber(): void {
        $relationship_one = relationship::create([test_resolver_one::class], 'one', 1);
        $relationship_two = relationship::create([test_resolver_two::class], 'two', 2);

        $this->assertEquals('one', $relationship_one->idnumber);
        $this->assertEquals('two', $relationship_two->idnumber);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('This ID number is already in use');
        relationship::create([test_resolver_one::class], 'one', 1000);
    }

    public function test_create(): void {
        $this->assertEquals(0, relationship_entity::repository()->count());
        $this->assertEquals(0, relationship_resolver::repository()->count());

        $relationship_single = relationship::create([test_resolver_one::class], 'one', 1000);

        $this->assertEquals(1, relationship_entity::repository()->count());
        $this->assertEquals(1, relationship_resolver::repository()->count());

        /** @var relationship_entity $relationship_entity */
        $relationship_entity = relationship_entity::repository()->one();

        $this->assertEquals($relationship_single->id, $relationship_entity->id);

        /** @var relationship_resolver $relationship_resolver */
        $relationship_resolver = relationship_resolver::repository()->one();

        $this->assertEquals(test_resolver_one::class, $relationship_resolver->class_name);
        $this->assertEquals($relationship_single->id, $relationship_resolver->relationship_id);

        $relationship_single->delete();

        $relationship_multiple = relationship::create([test_resolver_one::class, test_resolver_three::class], 'multi', 2000);

        $this->assertEquals(1, relationship_entity::repository()->count());
        $this->assertEquals(2, relationship_resolver::repository()->count());

        /** @var relationship_resolver[] $relationship_resolvers */
        $relationship_resolvers = relationship_resolver::repository()->get()->all();

        $this->assertEquals(test_resolver_one::class, $relationship_resolvers[0]->class_name);
        $this->assertEquals(test_resolver_three::class, $relationship_resolvers[1]->class_name);
        $this->assertEquals($relationship_multiple->id, $relationship_resolvers[0]->relationship_id);
        $this->assertEquals($relationship_multiple->id, $relationship_resolvers[0]->relationship_id);
    }

    public function test_create_without_specifying_any_resolvers(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Must specify at least one relationship resolver!');

        relationship::create([], '', 1000);
    }

    public function test_create_using_invalid_resolver_class(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            coding_exception::class . ' must be an instance of ' . totara_core\relationship\relationship_resolver::class
        );

        // coding_exception is a class, but it isn't a sub class of relationship resolver
        relationship::create([test_resolver_one::class, coding_exception::class], 'invalid', 1000);
    }

    public function test_create_using_incompatible_resolvers(): void {
        // Will work because they share the same accepted inputs
        relationship::create([test_resolver_one::class, test_resolver_three::class], 'one', 1000);
        relationship::create([test_resolver_two::class, test_resolver_five::class], 'two', 2000);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'The specified resolvers do not share at least one common input and are therefore incompatible.'
        );

        // These two resolvers do not share the same accepted inputs
        relationship::create([test_resolver_one::class, test_resolver_two::class], 'three', 3000);
    }

    public function test_create_using_duplicate_idnumber(): void {
        relationship::create([test_resolver_one::class], 'one', 1000);
        relationship::create([test_resolver_one::class], 'two', 2000);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('idnumbertaken', 'error'));

        // Has same idnumber as one
        relationship::create([test_resolver_one::class], 'one', 1000);
    }

    public function test_create_using_invalid_type(): void {
        relationship::create([test_resolver_one::class], 'one', 1000, relationship_entity::TYPE_MANUAL);
        relationship::create([test_resolver_one::class], 'two', 2000, relationship_entity::TYPE_STANDARD);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid type specified: 123456789');

        // Invalid type
        relationship::create([test_resolver_one::class], 'three', 3000, 123456789);
    }

    public function test_create_using_invalid_component(): void {
        relationship::create([test_resolver_one::class], 'one', 1000, relationship_entity::TYPE_STANDARD, 'totara_core');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Specified component/plugin nonexistent_plugin_name does not exist!');

        // Component doesn't exist
        relationship::create([test_resolver_one::class], 'two', 2000, relationship_entity::TYPE_STANDARD, 'nonexistent_plugin_name');
    }

    public function test_delete(): void {
        $this->assertEquals(0, relationship_entity::repository()->count());
        $this->assertEquals(0, relationship_resolver::repository()->count());

        $relationship1 = relationship::create([test_resolver_one::class, test_resolver_three::class], 'one', 1000);

        $this->assertEquals(1, relationship_entity::repository()->count());
        $this->assertEquals(2, relationship_resolver::repository()->count());

        $relationship2 = relationship::create([test_resolver_one::class, test_resolver_three::class], 'two', 2000);

        $relationship1->delete();

        $this->assertEquals(1, relationship_entity::repository()->count());
        $this->assertEquals(2, relationship_resolver::repository()->count());

        $this->assertFalse(relationship_entity::repository()->where('id', $relationship1->id)->exists());
        $this->assertTrue(relationship_entity::repository()->where('id', $relationship2->id)->exists());
    }

    public function test_is_acceptable_input(): void {
        // test_resolver_two & test_resolver_five only have the 'input_field_two' field in common.
        $relationship1 = relationship::create([test_resolver_two::class], 'one', 1000);
        $this->assertFalse($relationship1->is_acceptable_input(['input_field_one']));
        $this->assertTrue($relationship1->is_acceptable_input(['input_field_two']));
        $this->assertFalse($relationship1->is_acceptable_input(['input_field_three', 'input_field_one']));

        // test_resolver_one & test_resolver_three only have the 'input_field_one' field in common.
        $relationship2 = relationship::create([test_resolver_one::class, test_resolver_three::class], 'two', 2000);
        $this->assertTrue($relationship2->is_acceptable_input(['input_field_one']));
        $this->assertFalse($relationship2->is_acceptable_input(['input_field_two']));
        $this->assertTrue($relationship2->is_acceptable_input(['input_field_three', 'input_field_one']));

        // test_resolver_five accepts either ['input_field_one', 'input_field_three'] OR ['input_field_two']
        $relationship3 = relationship::create([test_resolver_five::class], 'three', 3000);
        $this->assertFalse($relationship3->is_acceptable_input(['input_field_one']));
        $this->assertTrue($relationship3->is_acceptable_input(['input_field_two']));
        $this->assertFalse($relationship3->is_acceptable_input(['input_field_three']));
        $this->assertTrue($relationship3->is_acceptable_input(['input_field_one', 'input_field_three']));
        $this->assertFalse($relationship3->is_acceptable_input(['input_field_four', 'input_field_three']));
        $this->assertTrue($relationship3->is_acceptable_input(['input_field_two', 'input_field_three']));

        // There isn't any point in specifying an empty set of fields.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Must specify at least one field to relationship::is_acceptable_input()');
        $relationship1->is_acceptable_input([]);
    }

    /**
     * Sanity check to make sure it collects unique user ids.
     */
    public function test_get_users(): void {
        $relationship = relationship::create([test_resolver_two::class, test_resolver_five::class], 'one', 1000);

        $dummy_id = 5;
        $input = [
            'input_field_two' => $dummy_id,
        ];

        $relationship_resolver_dtos = $relationship->get_users($input, context_system::instance());
        $this->assertEquals([$dummy_id], [$relationship_resolver_dtos[0]->get_user_id()]);
    }

}
