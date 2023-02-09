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

use totara_core\relationship\relationship_resolver;
use totara_core\relationship\relationship_resolver_dto;

/**
 * @group totara_core_relationship
 * @covers \totara_core\relationship\relationship_resolver
 */
class totara_core_relationship_resolver_testcase extends \advanced_testcase {

    public function test_accepted_fields(): void {
        $resolvers = $this->get_all_resolvers();

        // A resolver must have at least one accepted field
        foreach ($resolvers as $resolver) {
            $this->assertGreaterThan(0, count($resolver::get_accepted_fields()),
                $resolver . '::get_accepted_fields() must have at least one set of fields defined'
            );

            foreach ($resolver::get_accepted_fields() as $fields) {
                $this->assertGreaterThan(0, count($fields),
                    'At least one field must be defined per field set in ' . $resolver . '::get_accepted_fields()'
                );
            }
        }
    }

    public function test_is_acceptable_input(): void {
        // test_resolver_one has the field input_field_one
        $this->assertTrue(
            test_resolver_one::is_acceptable_input(['input_field_one'])
        );

        // test_resolver_two does not have the field input_field_one
        $this->assertFalse(
            test_resolver_two::is_acceptable_input(['input_field_one'])
        );

        // test_resolver_three has the fields input_field_one and input_field_two
        $this->assertTrue(
            test_resolver_three::is_acceptable_input(['input_field_one'])
        );

        // By default, only one of the accepted fields can be passed in
        $this->assertFalse(
            test_resolver_two::is_acceptable_input(['input_field_one'])
        );

        // test_resolver_four's is_acceptable_input() method is overridden to always be true
        $this->assertTrue(
            test_resolver_four::is_acceptable_input(['any', 'field', 'is', 'accepted'])
        );

        $this->assertTrue(
            test_resolver_five::is_acceptable_input(['input_field_two'])
        );
        $this->assertFalse(
            test_resolver_five::is_acceptable_input(['input_field_one'])
        );
        $this->assertTrue(
            test_resolver_five::is_acceptable_input(['input_field_one', 'input_field_three'])
        );
    }

    /**
     * @return relationship_resolver[]
     */
    private function get_all_resolvers(): array {
        $core_relationship_resolvers = core_component::get_namespace_classes(
            'relationship\\resolvers',
            totara_core\relationship\relationship_resolver::class,
            'totara_core'
        );
        $plugin_relationship_resolvers = core_component::get_namespace_classes(
            'totara_core\\relationship\\resolvers',
            totara_core\relationship\relationship_resolver::class
        );
        return array_merge($core_relationship_resolvers, $plugin_relationship_resolvers);
    }

}

class test_resolver_one extends relationship_resolver {
    public static function get_name(): string {
        return 'resolver_one';
    }
    public static function get_name_plural(): string {
        return 'resolver_ones';
    }
    protected function get_data(array $data, context $context): array {
        return [new relationship_resolver_dto($data['input_field_one'])];
    }
    public static function get_accepted_fields(): array {
        return [['input_field_one']];
    }
}
class test_resolver_two extends relationship_resolver {
    public static function get_name(): string {
        return 'resolver_two';
    }
    public static function get_name_plural(): string {
        return 'resolver_twos';
    }
    protected function get_data(array $data, context $context): array {
        return [new relationship_resolver_dto($data['input_field_two'])];
    }
    public static function get_accepted_fields(): array {
        return [['input_field_two']];
    }
}
class test_resolver_three extends test_resolver_one {
    public static function get_name(): string {
        return "resolver_three<script>alert('Bad!')</script>";
    }
    public static function get_name_plural(): string {
        return "resolver_three<script>alert('Bads!')</script>";
    }
}
class test_resolver_four extends relationship_resolver {
    public static function get_name(): string {
        return 'resolver_four';
    }
    public static function get_name_plural(): string {
        return 'resolver_fours';
    }
    protected function get_data(array $data, context $context): array {
        return [new relationship_resolver_dto($data)];
    }
    public static function get_accepted_fields(): array {
        return [[]];
    }
    public static function is_acceptable_input(array $fields): bool {
        return true;
    }
}
class test_resolver_five extends test_resolver_one {
    public static function get_name(): string {
        return 'resolver_five';
    }
    public static function get_name_plural(): string {
        return 'resolver_fives';
    }
    protected function get_data(array $data, context $context): array {
        $relationship_resolver_dto = null;
        if (isset($data['input_field_one'])) {
            $relationship_resolver_dto = new relationship_resolver_dto($data['input_field_one']);
        } else {
            $relationship_resolver_dto = new relationship_resolver_dto($data['input_field_two']);
        }
        return [$relationship_resolver_dto];
    }
    public static function get_accepted_fields(): array {
        return [['input_field_one', 'input_field_three'], ['input_field_two']];
    }
}
