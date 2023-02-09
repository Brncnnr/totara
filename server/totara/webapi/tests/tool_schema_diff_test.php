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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_webapi
 */

use core_phpunit\testcase;
use GraphQL\Utils\BuildSchema;
use totara_webapi\tool\schema_diff;

/**
 * Test the schema_diff class to ensure it correctly compares GraphQL schemas
 */
class totara_webapi_tool_schema_diff_testcase extends testcase {

    private $old_schema;
    private $new_schema;

    protected function setUp(): void {
        global $CFG;
        $old_path = "{$CFG->dirroot}/totara/webapi/tests/fixtures/schema_diff/schema_test_old.graphqls";
        $new_path = "{$CFG->dirroot}/totara/webapi/tests/fixtures/schema_diff/schema_test_new.graphqls";
        $this->old_schema = BuildSchema::build(file_get_contents($old_path));
        $this->new_schema = BuildSchema::build(file_get_contents($new_path));
    }

    protected function tearDown(): void {
        $this->old_schema = null;
        $this->new_schema = null;
    }

    private function get_expected_new_fields(): array {
        return [
            [
                'type' => 'ADD_FIELD_TO_TYPE',
                'description' => "New field 'new_query_added' is added to Type 'Query'."
            ],
            [
                'type' => 'ADD_FIELD_TO_TYPE',
                'description' => "New field 'coordinates' is added to Type 'model_one'."
            ],
            [
                'type' => 'ADD_FIELD_TO_TYPE',
                'description' => "New field 'new_mutation_added' is added to Type 'Mutation'."
            ],
            [
                'type' => 'ADD_FIELD_TO_TYPE',
                'description' => "New field 'field_has_been_added' is added to Type 'my_mutation_result'."
            ],
        ];
    }

    public function test_find_add_new_field(): void {
        $differ = new schema_diff($this->old_schema, $this->new_schema);

        $expected = $this->get_expected_new_fields();

        $new_fields = $differ->find_add_new_field();

        // TODO: remove debugging hack
        //exit(print_r($new_fields,1));

        $this->assertEqualsCanonicalizing($expected, $new_fields);
    }

    private function get_expected_new_arguments(): array {
        return [
            [
                'type' => 'ADD_ARG_TO_FIELD',
                'description' => "New arg 'optional_arg_has_been_added' added to field 'my_query' in Type 'Query'."
            ],
            [
                'type' => 'ADD_ARG_TO_FIELD',
                'description' => "New arg 'required_arg_has_been_added' added to field 'my_mutation' in Type 'Mutation'."
            ],
            [
                'type' => 'ADD_ARG_TO_FIELD',
                'description' => "New arg 'visibility' added to field 'required_arg_will_added' in Type 'my_mutation_result'."
            ],
            [
                'type' => 'ADD_ARG_TO_FIELD',
                'description' => "New arg 'priority' added to field 'optional_arg_will_be_added' in Type 'my_mutation_result'."
            ],
        ];
    }

    public function test_find_add_new_argument(): void {
        $differ = new schema_diff($this->old_schema, $this->new_schema);

        $expected = $this->get_expected_new_arguments();

        $new_arguments = $differ->find_add_new_argument();

        // TODO: remove debugging hack
        //exit(print_r($new_arguments,1));

        $this->assertEqualsCanonicalizing($expected, $new_arguments);
    }

    private function get_expected_new_types(): array {
        return [
            [
                'type' => 'ADD_INTERFACE_TYPE',
                'description' => "New interface type 'my_new_interface' is added."
            ],
            [
                'type' => 'ADD_OBJECT_TYPE',
                'description' => "New object type 'new_type_added' is added."
            ],
            [
                'type' => 'ADD_INPUT_OBJECT_TYPE',
                'description' => "New input object type 'new_input_added' is added."
            ],
            [
                'type' => 'ADD_ENUM_TYPE',
                'description' => "New enum type 'new_enum_added' is added."
            ],
            [
                'type' => 'ADD_UNION_TYPE',
                'description' => "New union type 'new_union_added' is added."
            ],
            [
                'type' => 'ADD_SCALAR_TYPE',
                'description' => "New scalar type 'new_scalar_field' is added."
            ],
        ];
    }

    public function test_find_add_new_type(): void {
        $differ = new schema_diff($this->old_schema, $this->new_schema);

        $expected = $this->get_expected_new_types();

        $new_types = $differ->find_add_new_type();

        // TODO: remove debugging hack
        //exit(print_r($new_types,1));

        $this->assertEqualsCanonicalizing($expected, $new_types);
    }

    private function get_other_expected_non_breaking_changes(): array {
        return [
            [
                'type' => 'VALUE_ADDED_TO_ENUM',
                'description' => "NEW_ENUM_ADDED was added to enum type my_enum."
            ],
            [
                'type' => 'IMPLEMENTED_INTERFACE_ADDED',
                'description' => "my_new_interface added to interfaces implemented by model_one."
            ],
            [
                'type' => 'TYPE_ADDED_TO_UNION',
                'description' => "new_type_added was added to union type search_result."
            ],
            [
                'type' => 'OPTIONAL_INPUT_FIELD_ADDED',
                'description' => "An optional field field_has_been_added on input type my_mutation_input was added."
            ],
            // TODO: This should not be repeated three times.
            // TODO: OPTIONAL_ARG_ADDED is kind of the same as ADD_ARG_TO_FIELD
            [
                'type' => 'OPTIONAL_ARG_ADDED',
                'description' => 'An optional arg optional_arg_has_been_added on Query.my_query was added',
            ],
            [
                'type' => 'OPTIONAL_ARG_ADDED',
                'description' => 'An optional arg optional_arg_has_been_added on Query.my_query was added',
            ],
            [
                'type' => 'OPTIONAL_ARG_ADDED',
                'description' => 'An optional arg optional_arg_has_been_added on Query.my_query was added',
            ],
            [
                'type' => 'OPTIONAL_ARG_ADDED',
                'description' => 'An optional arg priority on my_mutation_result.optional_arg_will_be_added was added',
            ],
        ];
    }

    public function test_find_non_breaking_changes(): void {
        $differ = new schema_diff($this->old_schema, $this->new_schema);

        $expected = array_merge(
            $this->get_expected_new_arguments(),
            $this->get_expected_new_fields(),
            $this->get_expected_new_types(),
            $this->get_other_expected_non_breaking_changes()
        );

        $non_breaking_changes = $differ->find_non_breaking_changes();

        // TODO: remove debugging hack
        //exit(print_r($non_breaking_changes,1));

        $this->assertEqualsCanonicalizing($expected, $non_breaking_changes);
    }

    public function test_find_breaking_changes(): void {
        $differ = new schema_diff($this->old_schema, $this->new_schema);

        $expected = [
            [
                'type' => 'TYPE_REMOVED',
                'description' => 'interface_will_be_removed was removed.',
            ],
            [
                'type' => 'TYPE_REMOVED',
                'description' => 'input_type_will_be_removed was removed.',
            ],
            [
                'type' => 'TYPE_REMOVED',
                'description' => 'type_will_be_removed was removed.',
            ],
            [
                'type' => 'TYPE_CHANGED_KIND',
                'description' => 'type_will_change_kind changed from an Input type to an Object type.',
            ],
            [
                'type' => 'FIELD_REMOVED',
                'description' => 'my_mutation_result.field_will_be_removed was removed.',
            ],
            [
                'type' => 'FIELD_CHANGED_KIND',
                'description' => 'my_mutation_result.field_type_will_become_nullable changed type from String! to String.',
            ],
            [
                'type' => 'FIELD_CHANGED_KIND',
                'description' => 'my_mutation_input.field_will_change_kind changed type from Boolean to String.',
            ],
            [
                'type' => 'FIELD_CHANGED_KIND',
                'description' => 'my_mutation_input.input_field_type_will_be_required changed type from String to String!.',
            ],
            [
                'type' => 'FIELD_REMOVED',
                'description' => 'my_mutation_input.field_will_be_removed was removed.',
            ],
            [
                'type' => 'REQUIRED_INPUT_FIELD_ADDED',
                'description' => 'A required field required_field_has_been_added on input type my_mutation_input was added.',
            ],
            [
                'type' => 'REQUIRED_INPUT_FIELD_ADDED',
                'description' => 'A required field field_default_value_will_be_removed on input type my_mutation_input was added.',
            ],
            [
                'type' => 'TYPE_REMOVED_FROM_UNION',
                'description' => 'type_will_be_removed_from_union was removed from union type search_result.',
            ],
            [
                'type' => 'VALUE_REMOVED_FROM_ENUM',
                'description' => 'ENUM_WILL_BE_REMOVED was removed from enum type my_enum.',
            ],
            [
                'type' => 'ARG_CHANGED_KIND',
                'description' => 'Query.my_query arg arg_will_change_kind has changed type from Boolean to String',
            ],
            [
                'type' => 'ARG_REMOVED',
                'description' => 'Query.my_query arg arg_will_be_removed was removed',
            ],
            // TODO: This should not be repeated three times.
            [
                'type' => 'REQUIRED_ARG_ADDED',
                'description' => 'A required arg required_arg_has_been_added on Mutation.my_mutation was added',
            ],
            [
                'type' => 'ARG_CHANGED_KIND',
                'description' => 'Mutation.my_mutation arg arg_will_change_kind has changed type from Boolean to String',
            ],
            [
                'type' => 'REQUIRED_ARG_ADDED',
                'description' => 'A required arg required_arg_has_been_added on Mutation.my_mutation was added',
            ],
            [
                'type' => 'ARG_REMOVED',
                'description' => 'Mutation.my_mutation arg arg_will_be_removed was removed',
            ],
            [
                'type' => 'REQUIRED_ARG_ADDED',
                'description' => 'A required arg required_arg_has_been_added on Mutation.my_mutation was added',
            ],
            [
                'type' => 'ARG_REMOVED',
                'description' => 'my_mutation_result.arg_will_be_removed arg visibility was removed',
            ],
            [
                'type' => 'ARG_CHANGED_KIND',
                'description' => 'my_mutation_result.arg_will_change_kind arg visibility has changed type from my_other_enum to my_enum',
            ],
            [
                'type' => 'IMPLEMENTED_INTERFACE_REMOVED',
                'description' => 'model_two no longer implements interface interface_will_be_removed.',
            ],
            [
                'type' => 'IMPLEMENTED_INTERFACE_REMOVED',
                'description' => 'model_three no longer implements interface interface_will_be_removed.',
            ],
            [
                'type' => 'DIRECTIVE_REMOVED',
                'description' => 'directive_will_be_removed was removed',
            ],
            [
                'type' => 'DIRECTIVE_ARG_REMOVED',
                'description' => 'directive_arg_will_be_removed was removed from my_directive',
            ],
            [
                'type' => 'REQUIRED_DIRECTIVE_ARG_ADDED',
                'description' => 'A required arg required_directive_arg_added on directive my_directive was added',
            ],
            [
                'type' => 'DIRECTIVE_LOCATION_REMOVED',
                'description' => 'ENUM_VALUE was removed from my_directive',
            ],
            [
                'type' => 'ARG_DEFAULT_VALUE_CHANGE',
                'description' => 'my_mutation_result.arg_default_value_will_change arg visibility has changed defaultValue',
            ],
        ];

        $breaking_changes = $differ->find_breaking_changes();

        // TODO: remove debugging hack
        //exit(print_r($breaking_changes,1));

        $this->assertEqualsCanonicalizing($expected, $breaking_changes);
    }

}
