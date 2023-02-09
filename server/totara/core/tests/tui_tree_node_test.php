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

use core\collection;
use core_phpunit\testcase;
use totara_core\tui\tree\tree_node;

class totara_tui_tree_node_test extends testcase {

    /**
     * @var tree_node
     */
    protected $parent_node;

    /**
     * @var tree_node
     */
    protected $child_node;

    /**
     * @var tree_node
     */
    protected $grandchild_node_1;

    /**
     * @var tree_node
     */
    protected $grandchild_node_2;

    /**
     * @var tree_node
     */
    protected $grandchild_node_3;

    protected function setUp(): void {
        parent::setUp();
        $this->parent_node = new tree_node('1', 'Parent node');
        $this->child_node = new tree_node('1-1', 'Child node');

        $this->grandchild_node_1 = new tree_node('1-1-1', 'Grandchild node 1');
        $this->grandchild_node_2 = new tree_node('1-1-2', 'Grandchild node 2');
        $this->grandchild_node_3 = new tree_node('1-1-3', 'Grandchild node 3');

        $this->child_node->add_children($this->grandchild_node_1, $this->grandchild_node_2, $this->grandchild_node_3);

        $this->parent_node->add_children($this->child_node);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->parent_node = $this->child_node = null;
        $this->grandchild_node_1 = $this->grandchild_node_2 = $this->grandchild_node_3 = null;
    }

    public function test_get_id(): void {
        self::assertEquals('1', $this->parent_node->get_id());
        self::assertEquals('1-1', $this->child_node->get_id());
        self::assertEquals('1-1-1', $this->grandchild_node_1->get_id());
        self::assertEquals('1-1-2', $this->grandchild_node_2->get_id());
        self::assertEquals('1-1-3', $this->grandchild_node_3->get_id());
    }

    public function test_get_label(): void {
        self::assertEquals('Parent node', $this->parent_node->get_label());
        self::assertEquals('Child node', $this->child_node->get_label());
        self::assertEquals('Grandchild node 1', $this->grandchild_node_1->get_label());
        self::assertEquals('Grandchild node 2', $this->grandchild_node_2->get_label());
        self::assertEquals('Grandchild node 3', $this->grandchild_node_3->get_label());
    }

    public function test_nodes(): void {
        self::assertEquals([$this->child_node], $this->parent_node->get_children());
        self::assertEquals([
            $this->grandchild_node_1,
            $this->grandchild_node_2,
            $this->grandchild_node_3,
        ], $this->child_node->get_children());
        self::assertEquals([], $this->grandchild_node_1->get_children());
    }

    public function test_is_root(): void {
        self::assertTrue($this->parent_node->is_root());
        self::assertFalse($this->child_node->is_root());
        self::assertFalse($this->grandchild_node_1->is_root());
    }

    public function test_get_root(): void {
        self::assertEquals($this->parent_node, $this->parent_node->get_root());
        self::assertEquals($this->parent_node, $this->child_node->get_root());
        self::assertEquals($this->parent_node, $this->grandchild_node_1->get_root());
    }

    public function test_find_node(): void {
        self::assertEquals($this->grandchild_node_1, $this->parent_node->find_node($this->grandchild_node_1->get_id()));
        self::assertNull($this->grandchild_node_3->find_node($this->grandchild_node_1->get_id()));
        self::assertEquals($this->grandchild_node_2, $this->child_node->find_node($this->grandchild_node_2->get_id()));
        self::assertNull($this->child_node->find_node('non existent node'));
    }

    public function test_has_node(): void {
        self::assertTrue($this->parent_node->has_node($this->grandchild_node_1->get_id()));
        self::assertFalse($this->grandchild_node_3->has_node($this->grandchild_node_1->get_id()));
        self::assertTrue($this->child_node->has_node($this->grandchild_node_2->get_id()));
        self::assertFalse($this->child_node->has_node('non existent node'));
    }

    public function test_add_duplicate_child(): void {
        self::expectException(coding_exception::class);
        self::expectExceptionMessage("Node with ID {$this->grandchild_node_1->get_id()} already exists in this tree.");
        $this->grandchild_node_2->add_children($this->grandchild_node_1);
    }

    public function test_set_get_link_url(): void {
        $url1 = new moodle_url('url1');
        $this->grandchild_node_1->set_link_url($url1);

        $url2 = new moodle_url('url2');
        $this->grandchild_node_2->set_link_url($url2);

        self::assertEquals($url1, $this->grandchild_node_1->get_link_url());
        self::assertEquals($url2, $this->grandchild_node_2->get_link_url());
        self::assertNotEquals($this->grandchild_node_1->get_link_url(), $this->grandchild_node_2->get_link_url());

        self::expectException(coding_exception::class);
        self::expectExceptionMessage("The link URL for the tree node with ID '1-1-1' has already been set");
        $this->grandchild_node_1->set_link_url($url2);
    }

    public function test_set_get_content(): void {
        $content1 = "Wow!";
        $this->grandchild_node_1->set_content($content1);

        $content2 = collection::new(['key' => 'value']);
        $this->grandchild_node_2->set_content($content2);

        self::assertEquals($content1, $this->grandchild_node_1->get_content());
        self::assertEquals($content2, $this->grandchild_node_2->get_content());
        self::assertNotEquals($this->grandchild_node_1->get_content(), $this->grandchild_node_2->get_content());

        self::expectException(coding_exception::class);
        self::expectExceptionMessage("The content for the tree node with ID '1-1-1' has already been set");
        $this->grandchild_node_1->set_content($content2);
    }

}
