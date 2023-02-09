<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package core
 */

namespace core\json_editor;

use core\json_editor\node\node;

/**
 * A singleton that represents the schema for the editor format.
 */
class schema {
    /**
     * Array of node class name, which the format is similar to Array<String, String>
     * @var array
     */
    protected $nodes;

    /**
     * @var schema
     */
    private static $instance;

    /**
     * schema constructor.
     */
    protected function __construct() {
        $this->nodes = [];
    }

    /**
     * This instance is being used
     * @return schema
     */
    public static function instance(): schema {
        if (!isset(static::$instance)) {
            static::$instance = new schema();

            // Start adding all the node type classes from the system. All the node type from core will be included
            // in here as well, because they are being placed into a same special namespace as other one in plugins.
            $classes = \core_component::get_namespace_classes(
                'json_editor\\node',
                node::class
            );

            foreach ($classes as $nodeclass) {
                static::$instance->add_node_type($nodeclass);
            }
        }

        return static::$instance;
    }

    /**
     * This function will try to call to {@see node::get_type()} to map the type with the one provided from the
     * front-end. Also the type is for hash-map as well.
     *
     * @param string $nodeclassname
     * @return void
     */
    public function add_node_type(string $nodeclassname): void {
        if (!class_exists($nodeclassname) || !is_subclass_of($nodeclassname, node::class)) {
            throw new \coding_exception("Invalid node class is being added to the schema");
        }

        $type = call_user_func([$nodeclassname, 'get_type']);
        if (isset($this->nodes[$type])) {
            debugging("Redefining node type '{$type}'", DEBUG_DEVELOPER);
        }

        $this->nodes[$type] = $nodeclassname;
    }

    /**
     * @return array
     */
    public function get_all_node_types(): array {
        return $this->nodes;
    }

    /**
     * @param array $nodeclassnames
     * @return void
     */
    public function add_node_types(array $nodeclassnames): void {
        foreach ($nodeclassnames as $nodeclassname) {
            $this->add_node_type($nodeclassname);
        }
    }

    /**
     * Returning the node type class name. Null return when it is not found in the schema.
     *
     * @param string $type
     * @return string|null
     */
    public function get_node_classname(string $type): ?string {
        if (array_key_exists($type, $this->nodes)) {
            return $this->nodes[$type];
        }

        return null;
    }

    /**
     * Constructing the node object for us. This function will try to invoke {@see node::from_node()}.
     *
     * @param string            $type
     * @param array|\stdClass   $node   The node given from the client-side. Should be a json_decoded result.
     *
     * @return node
     */
    public function get_node(string $type, $node): ?node {
        $clsname = $this->get_node_classname($type);

        if (null == $clsname) {
            return null;
        }

        if ($node instanceof \stdClass) {
            // Convert it to array.
            $node = get_object_vars($node);
        }

        return call_user_func([$clsname, 'from_node'], (array) $node);
    }

    /**
     * @param string $type
     * @return bool
     */
    public function has_node_type(string $type): bool {
        return isset($this->nodes[$type]);
    }
}
