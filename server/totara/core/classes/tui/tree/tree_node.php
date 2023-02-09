<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\tui\tree;

use coding_exception;
use moodle_url;

/**
 * Represents a selectable item within a tree structure.
 * A recursive structure for use for the Tree vue component.
 *
 * @see client/component/tui/src/components/tree/Tree.vue
 * @see client/component/tui/src/components/tree/TreeNode.vue
 */
class tree_node {

    /**
     * ID is the value of the selected item in the front end.
     *
     * @var string
     */
    private $id;

    /**
     * The label text that can be viewed in the front end.
     *
     * @var string
     */
    private $label;

    /**
     * The link URL that can be clicked in the front end.
     *
     * @var moodle_url|null
     */
    private $link_url;

    /**
     * @var tree_node
     */
    private $parent;

    /**
     * @var tree_node[]
     */
    private $children = [];

    /**
     * @var mixed
     */
    protected $content;

    /**
     * @param string $id
     * @param string $label
     */
    public function __construct(string $id, string $label) {
        $this->id = $id;
        $this->label = $label;
    }

    /**
     * Get the unique identifier of this tree node.
     *
     * @return string
     */
    final public function get_id(): string {
        return $this->id;
    }

    /**
     * Get the string to output when displaying this tree node.
     *
     * @return string
     */
    final public function get_label(): string {
        return $this->label;
    }

    /**
     * Set Get the link URL for this tree node.
     *
     * @param moodle_url $url
     * @return $this
     */
    final public function set_link_url(moodle_url $url): self {
        if (isset($this->link_url)) {
            throw new coding_exception("The link URL for the tree node with ID '{$this->id}' has already been set.");
        }
        $this->link_url = $url;
        return $this;
    }

    /**
     * Get the link URL for this tree node.
     *
     * @return moodle_url|null
     */
    final public function get_link_url(): ?moodle_url {
        return $this->link_url;
    }

    /**
     * @param tree_node $tree_node
     */
    final protected function set_parent(tree_node $tree_node): void {
        if ($tree_node === $this) {
            throw new coding_exception("A tree node can not be it's own parent", "Node ID: " . $this->id);
        }
        $this->parent = $tree_node;
    }

    /**
     * Get the node that is the parent of this node.
     *
     * @return self|null
     */
    final public function get_parent(): ?tree_node {
        return $this->parent;
    }

    /**
     * Get the very top level node in the tree.
     *
     * @return self
     */
    final public function get_root(): tree_node {
        if ($this->is_root()) {
            return $this;
        }

        $node = $this->get_parent();
        while (!$node->is_root()) {
            $node = $node->get_parent();
        }
        return $node;
    }

    /**
     * Is this node the root node in the tree?
     *
     * @return bool
     */
    final public function is_root(): bool {
        return $this->get_parent() === null;
    }

    /**
     * Get the child nodes of this node.
     *
     * @return tree_node[]
     */
    final public function get_children(): array {
        return $this->children;
    }

    /**
     * Does this node have any child nodes?
     *
     * @return bool
     */
    final public function has_children(): bool {
        return !empty($this->children);
    }

    /**
     * Add child nodes to this node.
     *
     * @param tree_node ...$children
     * @return $this
     */
    final public function add_children(tree_node ...$children): self {
        foreach ($children as $child) {
            if ($this->get_root()->has_node($child->id)) {
                // There can not be duplicate IDs in the tree.
                // The front end component requires IDs to be unique to track the state of each node (e.g. whether it is opened)
                // If you wish you have duplicate entries in the tree, you will need to give them unique IDs.
                // This can be done by prefixing the ID with the ID of it's parents, e.g: "grandparentId-parentId-childId"
                throw new coding_exception("Node with ID {$child->id} already exists in this tree.");
            }

            $child->set_parent($this);
            $this->children[] = $child;
        }

        return $this;
    }

    /**
     * Find the node with the given ID within this node or it's children and return it, or return null if it doesn't exist.
     *
     * @param string $node_id
     * @return static|null
     */
    final public function find_node(string $node_id): ?self {
        if ($node_id === $this->id) {
            return $this;
        }

        foreach ($this->children as $child) {
            $found_node = $child->find_node($node_id);
            if ($found_node !== null) {
                return $found_node;
            }
        }

        return null;
    }

    /**
     * Check if the node with the given ID exists within this node or it's children..
     *
     * @param string $node_id
     * @return bool
     */
    final public function has_node(string $node_id): bool {
        return $this->find_node($node_id) !== null;
    }

    /**
     * Set custom content for this node.
     *
     * @param mixed $content
     * @return $this
     */
    public function set_content($content): self {
        if (isset($this->content)) {
            throw new coding_exception("The content for the tree node with ID '{$this->id}' has already been set.");
        }
        $this->content = $content;
        return $this;
    }

    /**
     * Get this node's custom content.
     *
     * @return mixed
     */
    public function get_content() {
        return $this->content;
    }

}
