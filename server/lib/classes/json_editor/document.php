<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\json_editor;

use core\json_editor\helper\document_helper;
use core\json_editor\node\node;

/**
 * A class that contain the array of document, and it allows dev to do anything with it.
 */
final class document {
    /**
     * @var array
     */
    private $document;

    /**
     * document constructor.
     *
     * @param array     $document
     */
    private function __construct(array $document) {
        $this->document = $document;
    }

    /**
     * @param string|array  $document
     * @return document
     */
    public static function create($document): document {
        $document = document_helper::parse_document($document);
        if (!array_key_exists('type', $document) || 'doc' !== $document['type']) {
            throw new \coding_exception("Invalid document being constructed");
        }

        return new static($document);
    }

    /**
     * @return array
     */
    public function get_document(): array {
        return $this->document;
    }

    /**
     * @return string
     */
    public function to_json(): string {
        return document_helper::json_encode_document($this->document);
    }

    /**
     * Given the node type, this function will try to find the raw nodes from document.
     *
     * @param string $type
     * @return array
     */
    public function find_raw_nodes(string $type): array {
        return $this->find_raw_nodes_by_types([$type]);
    }

    /**
     * Given an array of types, this function will find the raw nodes from the document.
     * @param array $types
     * @return array
     */
    public function find_raw_nodes_by_types(array $types): array {
        $schema = schema::instance();
        foreach ($types as $type) {
            if (!$schema->has_node_type($type)) {
                debugging("No node type '{$type}' found in the schema", DEBUG_DEVELOPER);
                return [];
            }

            if (!array_key_exists('content', $this->document) || !is_array($this->document['content'])) {
                debugging("Invalid document schema", DEBUG_DEVELOPER);
                return [];
            }
        }

        return $this->do_find_raw_nodes($this->document['content'], $types);
    }

    /**
     * Given the node type, this function will try to find the nodes from the document.
     * @param string $type
     * @return node[]
     */
    public function find_nodes(string $type): array {
        return $this->find_nodes_by_types([$type]);
    }

    /**
     * Given an array of node types, this function will try to find the nodes from the document.
     * @param array $types
     * @return node[]
     */
    public function find_nodes_by_types(array $types): array {
        $rawnodes = $this->find_raw_nodes_by_types($types);
        $schema = schema::instance();

        $nodes = [];

        foreach ($rawnodes as $rawnode) {
            $nodes[] = $schema->get_node($rawnode['type'], $rawnode);
        }

        return $nodes;
    }

    /**
     * @param array $nodes
     * @param array $types String types of what nodes to find
     * @return array
     */
    private function do_find_raw_nodes(array $nodes, array $types): array {
        if (empty($nodes)) {
            return [];
        }

        $rtn = [];
        foreach ($nodes as $node) {
            if (!is_array($node) || !array_key_exists('type', $node)) {
                continue;
            }

            if (in_array($node['type'], $types)) {
                $rtn[] = $node;
            } else if (array_key_exists('content', $node) && is_array($node['content'])) {
                $extra = $this->do_find_raw_nodes($node['content'], $types);
                $rtn = array_merge($rtn, $extra);
            }
        }

        return $rtn;
    }

    /**
     * @param string $node_type
     * @param callable $callback
     *
     * @return void
     */
    public function modify_node(string $node_type, callable $callback): void {
        if (!array_key_exists('content', $this->document)) {
            return;
        }

        $nodes = $this->document['content'];
        $this->document['content'] = $this->do_modify_node($node_type, $nodes, $callback);
    }

    /**
     * Given the collection of raw json editor node, this function will try to fetch
     * for the raw nodes that match with the asking type and run it pass the callback to modify
     * it. And return the modified version one.
     *
     * @param string $node_type
     * @param array $raw_nodes
     * @param callable $callback
     *
     * @return array
     */
    protected function do_modify_node(string $node_type, array $raw_nodes, callable $callback): array {
        $modified_nodes = [];

        foreach ($raw_nodes as $raw_node) {
            if (!array_key_exists('type', $raw_node)) {
                debugging("Node does not have key 'type'", DEBUG_DEVELOPER);
                continue;
            }

            $type = $raw_node['type'];
            if ($type == $node_type) {
                $result = $callback($raw_node);
                if ($result === null) {
                    // remove node
                } else if (isset($result['type'])) {
                    // single node
                    $modified_nodes[] = $result;
                } else if (is_array($result)) {
                    // multiple nodes
                    foreach ($result as $result_node) {
                        $modified_nodes[] = $result_node;
                    }
                }
            } else {
                $modified_nodes[] = $raw_node;
            }
        }

        foreach ($modified_nodes as &$raw_node) {
            if (array_key_exists('content', $raw_node) && is_array($raw_node['content'])) {
                // This node contains other nodes, recursively process them.
                $raw_node['content'] = $this->do_modify_node($node_type, $raw_node['content'], $callback);
            }
        }

        return $modified_nodes;
    }
}