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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi;

use coding_exception;
use core\webapi\type_resolver;
use core_component;
use GraphQL\Error\Error;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaExtender;
use GraphQL\Utils\SchemaPrinter;
use totara_webapi\endpoint_type\base as endpoint_type;

class schema_builder {

    /**
     * @var schema_file_loader
     */
    protected $schema_file_loader;

    /**
     * @var endpoint_type|null
     */
    protected $type;

    /**
     * @param schema_file_loader $schema_file_loader
     * @param endpoint_type|null $type
     */
    public function __construct(schema_file_loader $schema_file_loader, endpoint_type $type = null) {
        $this->schema_file_loader = $schema_file_loader;
        $this->type = $type;
    }

    /**
     * Parse the existing graphqls files and build the schema
     *
     * @return Schema
     */
    public function build(): Schema {
        $schema = $this->get_parsed_schema();

        $this->add_support_for_custom_scalars($schema);
        $this->add_support_for_param_types($schema);
        $this->add_support_for_interface_types($schema);
        $this->add_support_for_union_types($schema);

        return $schema;
    }

    /**
     * Hack custom scalars to use our parsing and serialisation.
     *
     * @param Schema $schema
     */
    protected function add_support_for_custom_scalars(Schema $schema) {
        $scalars = core_component::get_namespace_classes('webapi\scalar', 'core\webapi\scalar');
        foreach ($scalars as $classname) {
            $parts = explode('\\', $classname);
            $component = reset($parts);
            $name = end($parts);
            $scalarname = $component . '_' . $name;
            $type = $schema->getType($scalarname);
            if (!$type) {
                debugging("GraphQL scalar '$scalarname' described by class {$classname} is not defined in GraphQL schema", DEBUG_DEVELOPER);
                continue;
            }
            if (!($type instanceof \GraphQL\Type\Definition\CustomScalarType)) {
                debugging("GraphQL scalar '$scalarname' described by class {$classname} collides with another type in GraphQL schema", DEBUG_DEVELOPER);
                continue;
            }
            $type->config['serialize'] = [$classname, 'serialize'];
            $type->config['parseValue'] = [$classname, 'parse_value'];
            $type->config['parseLiteral'] = [$classname, 'parse_literal'];
        }
    }

    /**
     * Add support for PARAM_ equivalents - note those are intended to be used for input cleaning only!
     *
     * @param Schema $schema
     */
    protected function add_support_for_param_types(Schema $schema) {
        $params = core_component::get_namespace_classes(
            'webapi\param',
            'core\webapi\param',
            'core'
        );
        foreach ($params as $classname) {
            $parts = explode('\\', $classname);
            $name = end($parts);
            $paramname = 'param_' . $name;
            $type = $schema->getType($paramname);
            if (!$type) {
                debugging("GraphQL param '$paramname' described by class {$classname} is not defined in GraphQL schema", DEBUG_DEVELOPER);
                continue;
            }
            if (!($type instanceof \GraphQL\Type\Definition\CustomScalarType)) {
                debugging("GraphQL param '$paramname' described by class {$classname} collides with another type in GraphQL schema", DEBUG_DEVELOPER);
                continue;
            }
            $type->config['serialize'] = [$classname, 'serialize'];
            $type->config['parseValue'] = [$classname, 'parse_value'];
            $type->config['parseLiteral'] = [$classname, 'parse_literal'];
        }
    }

    /**
     * Add interface type support to the schema. All existing type resolvers
     * implementing the interface_resolver interface will be supported.
     *
     * @param Schema $schema
     */
    protected function add_support_for_interface_types(Schema $schema) {
        // Add support for resolving type at runtime - aka interface type.
        $interface_resolvers = core_component::get_namespace_classes(
            'webapi\resolver\type',
            'core\webapi\interface_resolver'
        );
        foreach ($interface_resolvers as $classname) {
            $parts = explode('\\', $classname);

            $component = reset($parts);
            $name = end($parts);

            $interfacename = "{$component}_{$name}";
            $type = $schema->getType($interfacename);

            if (!$type) {
                debugging(
                    "GraphQL interface '{$interfacename}' described by class {$classname} " .
                    "is not defined in GraphQL schema",
                    DEBUG_DEVELOPER
                );
                continue;
            }

            if (!($type instanceof InterfaceType)) {
                debugging(
                    "GraphQL interface '{$interfacename}' described by class {$classname} " .
                    "collides with another type in GraphQL schema",
                    DEBUG_DEVELOPER
                );
                continue;
            }

            $fn = function ($object_value, $context, ResolveInfo $info) use ($classname) {
                $typestr = call_user_func_array([$classname, 'resolve'], [$object_value, $context, $info]);

                if (!class_exists($typestr)) {
                    // Not a class returned.
                    return $typestr;
                }

                $parts = explode("\\", $typestr);
                $component = reset($parts);
                $innertype = array_pop($parts);

                return "{$component}_{$innertype}";
            };

            $type->config['resolveType'] = $fn;
        }
    }

    /**
     * Add support for union types, so that they can be resolved into concrete types.
     *
     * @param Schema $schema
     */
    protected function add_support_for_union_types(Schema $schema) {
        $unions = $this->load_union_classes();
        foreach ($unions as $class_name) {
            $parts = explode('\\', $class_name);
            $component = reset($parts);
            $name = end($parts);
            $union_name = $component . '_' . $name;
            try {
                $union_type = $schema->getType($union_name);
            } catch (Error $e) {
                // A GraphQL Error may be thrown if $union_name is not found.
                $union_type = null;
            }
            if (!$union_type) {
                // Just return. The union is likely defined in a different schema.
                return;
            }

            $union_type->config['resolveType'] = function (
                $object_value,
                $context,
                ResolveInfo $info
            ) use ($union_name, $class_name, $schema) {
                 $type_class_name = call_user_func_array([$class_name, 'resolve_type'], [$object_value, $context, $info]);

                // Not an existing type resolver class return returned.
                if (!class_exists($type_class_name) || !is_subclass_of($type_class_name, type_resolver::class)) {
                    throw new coding_exception('Invalid type resolver class returned');
                }

                $parts = explode("\\", $type_class_name);
                $component = reset($parts);
                $innertype = array_pop($parts);

                $type_name = "{$component}_{$innertype}";

                $type = $schema->getType($type_name);
                if (!$type) {
                    throw new coding_exception(
                        "Concrete type '{$type_name}' returned by GraphQL union resolver '{$class_name}' ".
                        "is not defined in GraphQL schema",
                        DEBUG_DEVELOPER
                    );
                }
                return $type;
            };
        }
    }

    /**
     * Load union classes
     *
     * @return array
     */
    protected function load_union_classes(): array {
        return \core_component::get_namespace_classes('webapi\resolver\union', 'core\webapi\union_resolver');
    }

    /**
     * Get the schema based on all the existing graphqls files.
     * If developer mode is not turned on the parsed schema will be cached to improve performance
     *
     * @return Schema
     */
    protected function get_parsed_schema(): Schema {
        global $CFG;
        // The caching can be turned off by setting a config flag
        if (isset($CFG->cache_graphql_schema) && $CFG->cache_graphql_schema == false) {
            return $this->do_build();
        }

        $cache = \cache::make('totara_webapi', 'schema');
        // Cache schema by type.
        $cache_key = 'parsed_schema_' . $this->type::get_name();
        $parsed_schema = $cache->get($cache_key);
        if ($parsed_schema) {
            $parsed_schema = AST::fromArray($parsed_schema);
            return BuildSchema::build(
                $parsed_schema,
                null,
                [
                    'assumeValid' => true,
                    'assumeValidSDL' => true
                ]
            );
        }

        $schema = $this->do_build();

        // Extending a schema is very expensive compared to building the schema
        // in one BuildScheme::build() call. Unfortunately we cannot just concatenate
        // all graphqls files as we rely on extending the core schema file.
        // Printing the final schema and caching the result
        // will make subsequent build calls possible without extending
        //
        // Note: we cache schema separately per-type (dev, ajax, mobile, etc) as each
        // has its own separate schema.
        $parsed_schema = Parser::parse(SchemaPrinter::doPrint($schema));
        $cache->set($cache_key, AST::toArray($parsed_schema));

        return $schema;
    }

    /**
     * @return \GraphQL\Type\Schema
     */
    protected function do_build(): Schema {
        $schema = $this->get_core_schema_file_content();
        if (empty($schema)) {
            throw new \Exception('Core schema file content cannot be empty.');
        }

        // We need to build with the core schema file first as
        // all other schema files will be treated as extension
        $schema = \GraphQL\Utils\BuildSchema::build($schema);

        $schemas = $this->schema_file_loader->load();
        if (empty($schemas)) {
            throw new \Exception('Schema file contents cannot be empty.');
        }

        return SchemaExtender::extend($schema, Parser::parse(implode("\n", $schemas)));
    }

    /**
     * Returns content of core schema file
     *
     * @return string
     */
    protected function get_core_schema_file_content(): string {
        global $CFG;

        // NOTE: Subsystems schemas are not supported intentionally,
        //       the reason is that Frankenstyle in subsystems would
        //       make the schema structure illogical.

        // The core schema file comes first
        $root_schema_file = $CFG->dirroot . '/lib/webapi/schema.graphqls';
        if (!file_exists($root_schema_file) || !is_readable($root_schema_file)) {
            throw new coding_exception('Core has to have at least a schema.graphqls file');
        }

        $content = file_get_contents($root_schema_file);
        if ($content === false) {
            throw new \Exception('Could not read schema file '.$root_schema_file);
        }

        return $content;
    }

}