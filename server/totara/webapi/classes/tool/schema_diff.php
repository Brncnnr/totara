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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 */

namespace totara_webapi\tool;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Schema;
use GraphQL\Utils\BreakingChangesFinder;
use html_writer;

/**
 * Class to compare two GraphQL schemas and report on the differences.
 */
class schema_diff {
    /**  Non-breaking changes **/
    public const NEW_CHANGE_ADD_ARG_TO_FIELD = 'ADD_ARG_TO_FIELD';
    public const NEW_CHANGE_ADD_FIELD_TO_TYPE = 'ADD_FIELD_TO_TYPE';
    public const NEW_CHANGE_ADD_OBJECT_TYPE = 'ADD_OBJECT_TYPE';
    public const NEW_CHANGE_ADD_INTERFACE_TYPE = 'ADD_INTERFACE_TYPE';
    public const NEW_CHANGE_ADD_ENUM_TYPE = 'ADD_ENUM_TYPE';
    public const NEW_CHANGE_ADD_INPUT_OBJECT_TYPE = 'ADD_INPUT_OBJECT_TYPE';
    public const NEW_CHANGE_ADD_UNION_TYPE = 'ADD_UNION_TYPE';
    public const NEW_CHANGE_ADD_SCALAR_TYPE = 'ADD_SCALAR_TYPE';

    /**
     * @var Schema
     */
    private $old_schema;

    /**
     * @var Schema
     */
    private $new_schema;

    /**
     * @param string $file
     */
    public function __construct(Schema $old_schema, Schema $new_schema) {
        $this->old_schema = $old_schema;
        $this->new_schema = $new_schema;
    }

    /**
     * Finds breaking changes, plus dangerous arg changes.
     * @return string[][]
     */
    public function find_breaking_changes(): array {
        // We count ARG_DEFAULT_VALUE_CHANGE as a breaking change, whereas BreakingChangesFinder counts it as dangerous.
        $dangerous_arg_changes = BreakingChangesFinder::findArgChanges($this->old_schema, $this->new_schema)['dangerousChanges'];
        foreach ($dangerous_arg_changes as $index => $change) {
            if ($change['type'] != 'ARG_DEFAULT_VALUE_CHANGE') {
                unset($dangerous_arg_changes[$index]);
            }
        }

        return array_merge(
            BreakingChangesFinder::findBreakingChanges($this->old_schema, $this->new_schema),
            $dangerous_arg_changes
        );
    }

    /**
     * @return string[][]
     */
    public function find_non_breaking_changes(): array {
        // We count arg changes that are not ARG_DEFAULT_VALUE_CHANGE as non-breaking.
        $dangerous_arg_changes = BreakingChangesFinder::findArgChanges($this->old_schema, $this->new_schema)['dangerousChanges'];
        foreach ($dangerous_arg_changes as $index => $change) {
            if ($change['type'] == 'ARG_DEFAULT_VALUE_CHANGE') {
                unset($dangerous_arg_changes[$index]);
            }
        }

        return array_merge(
            BreakingChangesFinder::findValuesAddedToEnums($this->old_schema, $this->new_schema),
            BreakingChangesFinder::findInterfacesAddedToObjectTypes($this->old_schema, $this->new_schema),
            BreakingChangesFinder::findTypesAddedToUnions($this->old_schema, $this->new_schema),
            BreakingChangesFinder::findFieldsThatChangedTypeOnInputObjectTypes($this->old_schema, $this->new_schema)['dangerousChanges'],
            $dangerous_arg_changes,
            $this->find_add_new_argument(),
            $this->find_add_new_field(),
            $this->find_add_new_type(),
        );
    }

    /**
     * @return string[][]
     */
    public function find_add_new_argument(): array {
        $old_type_map = $this->old_schema->getTypeMap();
        $new_type_map = $this->new_schema->getTypeMap();

        $new_changes = [];
        foreach ($old_type_map as $type_name => $old_type) {
            $new_type = $new_type_map[$type_name] ?? null;
            // Only check Interfaces and Object Types
            if (!($old_type instanceof ObjectType || $old_type instanceof InterfaceType) ||
                !($new_type instanceof ObjectType || $new_type instanceof InterfaceType) ||
                !($new_type instanceof $old_type)
            ) {
                continue;
            }
            $old_type_fields_def = $old_type->getFields();
            $new_type_fields_def = $new_type->getFields();
            foreach ($new_type_fields_def as $field_name => $field_definition) {
                // Look for fields that exist in the old schema.
                if (!isset($old_type_fields_def[$field_name])) {
                    continue;
                }
                $old_field_args_def = $old_type_fields_def[$field_name]->args;
                $old_field_args_def_by_name = [];
                foreach ($old_field_args_def as $index => $arg_definition) {
                    $old_field_args_def_by_name[$arg_definition->name] = $arg_definition;
                }
                $new_field_args_def = $new_type_fields_def[$field_name]->args;
                foreach ($new_field_args_def as $index => $arg_definition) {
                    $arg_name = $arg_definition->name;
                    // Look for args that do not exist in the old schema.
                    if (isset($old_field_args_def_by_name[$arg_name])) {
                        continue;
                    }
                    $new_changes[] = [
                        'type' => self::NEW_CHANGE_ADD_ARG_TO_FIELD,
                        'description' => "New arg '${arg_name}' added to field '${field_name}' in Type '${type_name}'.",
                    ];
                }
            }
        }
        return $new_changes;
    }

    /**
     * @return string[][]
     */
    public function find_add_new_field(): array {
        $old_type_map = $this->old_schema->getTypeMap();
        $new_type_map = $this->new_schema->getTypeMap();

        $new_changes = [];
        foreach ($old_type_map as $type_name => $old_type) {
            $new_type = $new_type_map[$type_name] ?? null;
            // Only check Interfaces and Object Types
            if (!($old_type instanceof ObjectType || $old_type instanceof InterfaceType) ||
                !($new_type instanceof ObjectType || $new_type instanceof InterfaceType) ||
                !($new_type instanceof $old_type)
            ) {
                continue;
            }
            $old_type_fields_def = $old_type->getFields();
            $new_type_fields_def = $new_type->getFields();
            foreach ($new_type_fields_def as $field_name => $field_definition) {
                if (isset($old_type_fields_def[$field_name])) {
                    continue;
                }
                $new_changes[] = [
                    'type' => self::NEW_CHANGE_ADD_FIELD_TO_TYPE,
                    'description' => "New field '${field_name}' is added to Type '${type_name}'.",
                ];
            }
        }
        return $new_changes;
    }

    /**
     * @return string[][]
     */
    public function find_add_new_type(): array {
        $old_type_map = $this->old_schema->getTypeMap();
        $new_type_map = $this->new_schema->getTypeMap();

        $new_changes = [];
        foreach ($new_type_map as $type_name => $new_type) {
            if (isset($old_type_map[$type_name])) {
                continue;
            }

            if ($new_type instanceof ObjectType) {
                $new_changes[] = [
                    'type' => self::NEW_CHANGE_ADD_OBJECT_TYPE,
                    'description' => "New object type '${type_name}' is added.",
                ];
            }

            if ($new_type instanceof InputObjectType) {
                $new_changes[] = [
                    'type' => self::NEW_CHANGE_ADD_INPUT_OBJECT_TYPE,
                    'description' => "New input object type '${type_name}' is added.",
                ];
            }
            if ($new_type instanceof InterfaceType) {
                $new_changes[] = [
                    'type' => self::NEW_CHANGE_ADD_INTERFACE_TYPE,
                    'description' => "New interface type '${type_name}' is added.",
                ];
            }

            if ($new_type instanceof EnumType) {
                $new_changes[] = [
                    'type' => self::NEW_CHANGE_ADD_ENUM_TYPE,
                    'description' => "New enum type '${type_name}' is added.",
                ];
            }

            if ($new_type instanceof UnionType) {
                $new_changes[] = [
                    'type' => self::NEW_CHANGE_ADD_UNION_TYPE,
                    'description' => "New union type '${type_name}' is added.",
                ];
            }

            if ($new_type instanceof ScalarType) {
                $new_changes[] = [
                    'type' => self::NEW_CHANGE_ADD_SCALAR_TYPE,
                    'description' => "New scalar type '${type_name}' is added.",
                ];
            }
        }
        return $new_changes;
    }
}