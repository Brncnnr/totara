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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\exception\model_exception;
use mod_approval\model\workflow\ordinal\allocate;
use mod_approval\model\workflow\ordinal\move;
use mod_approval\model\workflow\ordinal\ordinal;
use mod_approval\model\workflow\ordinal\reorder;
use mod_approval_test_child_entity as child_entity;
use mod_approval_test_child_model as child_model;
use mod_approval_test_parent_entity as parent_entity;
use mod_approval_test_parent_model as parent_model;
use mod_approval_test_notrack_ordinal as notrack_ordinal;
use mod_approval_test_track_ordinal as track_ordinal;

require_once(__DIR__ . '/fixtures/workflow/workflow_ordinal_testcase.php');

/**
 * @group approval_workflow
 */
class mod_approval_workflow_ordinal_testcase extends testcase {
    /** @var xmldb_table */
    private $table_parent;

    /** @var xmldb_table */
    private $table_child;

    /** @var ordinal */
    private $notrack;

    /** @var ordinal */
    private $track;

    /** @var parent_model */
    private $parent;

    public function setUp(): void {
        $db = builder::get_db();
        $dbman = $db->get_manager();
        $this->table_parent = parent_entity::table();
        $this->table_child = child_entity::table();
        foreach ([$this->table_child, $this->table_parent] as $table) {
            if ($dbman->table_exists($table)) {
                $dbman->drop_table($table);
            }
        }
        foreach ([$this->table_parent, $this->table_child] as $table) {
            $dbman->create_table($table);
        }
        $this->notrack = new notrack_ordinal();
        $this->track = new track_ordinal();
        $parent = new parent_entity();
        $parent->timing = time();
        $parent->save();
        $this->parent = parent_model::load_by_entity($parent);
        parent::setUp();
    }

    public function tearDown(): void {
        $db = builder::get_db();
        $dbman = $db->get_manager();
        if ($db->is_transaction_started()) {
            $db->force_transaction_rollback();
        }
        foreach ([$this->table_child, $this->table_parent] as $table) {
            if ($dbman->table_exists($table)) {
                $dbman->drop_table($table);
            }
        }
        $this->table_parent = $this->table_child = null;
        $this->notrack = $this->track = null;
        $this->parent = null;
        parent::tearDown();
    }

    /**
     * @covers mod_approval\model\workflow\ordinal\allocate::execute
     */
    public function test_allocate(): void {
        $parent = $this->parent;
        $allocator = new allocate($this->track);

        $child_entity = new child_entity();
        $allocator->execute($parent, $child_entity);
        $this->assertEquals(1, $child_entity->ordinary);
        $this->assertNull($child_entity->timer);

        builder::table(child_entity::TABLE)->insert(['parenting' => $parent->id, 'ordinary' => 3]);
        builder::table(child_entity::TABLE)->insert(['parenting' => $parent->id, 'ordinary' => 1]);
        builder::table(child_entity::TABLE)->insert(['parenting' => $parent->id, 'ordinary' => 2]);

        $child_entity = new child_entity();
        $allocator->execute($parent, $child_entity);
        $this->assertEquals(4, $child_entity->ordinary);
        $this->assertNull($child_entity->timer);
    }

    public function data_move(): array {
        return [
            // [child_index, [expected_tag => [expected_ordinal, expected_updated], ...]
            [0, [6 => [2, false], 7 => [1, false]]],
            [1, [5 => [2, true], 7 => [1, false]]],
            [2, [5 => [2, true], 6 => [1, true]]],
        ];
    }

    private function lets_move(move $mover, int $remove_where): int {
        $parent = $this->parent;

        $child_ids = [
            builder::table(child_entity::TABLE)->insert(['parenting' => $parent->id, 'tag' => 5, 'ordinary' => 3]),
            builder::table(child_entity::TABLE)->insert(['parenting' => $parent->id, 'tag' => 6, 'ordinary' => 2]),
            builder::table(child_entity::TABLE)->insert(['parenting' => $parent->id, 'tag' => 7, 'ordinary' => 1]),
        ];

        $child_entity = new child_entity($child_ids[$remove_where]);
        $child = child_model::load_by_entity($child_entity);
        $child_entity->delete();

        $time = time();
        $this->waitForSecond();
        $mover->execute($parent, $child);

        return $time;
    }

    /**
     * @covers mod_approval\model\workflow\ordinal\move::execute
     * @covers mod_approval\model\workflow\ordinal\move::get_moveable_items
     * @covers mod_approval\model\workflow\ordinal\move::do_move
     * @param integer $remove_where
     * @param array $expected
     * @dataProvider data_move
     */
    public function test_move_track(int $remove_where, array $expected): void {
        $mover = new move($this->track);
        $time = $this->lets_move($mover, $remove_where);

        /** @var array $records */
        $records = builder::table(child_entity::TABLE)->order_by('tag')->select(['tag', 'ordinary', 'timer'])->fetch(false);
        $this->assertCount(count($expected), $records);
        foreach ($expected as $tag => [$ordinal, $updated]) {
            $this->assertArrayHasKey($tag, $records);
            $record = $records[$tag];
            $this->assertEquals($tag, $record->tag);
            $this->assertEquals($ordinal, $record->ordinary, $tag);
            $this->assertEquals($updated, $record->timer > $time, $updated ? "{$tag} should be updated" : "{$tag} should not be updated");
        }
    }

    /**
     * @covers mod_approval\model\workflow\ordinal\move::execute
     * @covers mod_approval\model\workflow\ordinal\move::get_moveable_items
     * @covers mod_approval\model\workflow\ordinal\move::do_move
     * @param integer $remove_where
     * @param array $expected
     * @dataProvider data_move
     */
    public function test_move_no_track(int $remove_where, array $expected): void {
        $mover = new move($this->notrack);
        $this->lets_move($mover, $remove_where);

        /** @var array $records */
        $records = builder::table(child_entity::TABLE)->order_by('tag')->select(['tag', 'ordinary', 'timer'])->fetch(false);
        $this->assertCount(count($expected), $records);
        foreach ($expected as $tag => [$ordinal, $x]) {
            $this->assertArrayHasKey($tag, $records);
            $record = $records[$tag];
            $this->assertEquals($tag, $record->tag);
            $this->assertEquals($ordinal, $record->ordinary, $tag);
            $this->assertNull($record->timer, "{$tag}'s time should be preserved");
        }
    }

    /**
     * @covers mod_approval\model\workflow\ordinal\move::execute
     */
    public function test_move_illegally(): void {
        $parent = $this->parent;
        $mover_track = new move($this->track);
        $child_id = builder::table(child_entity::TABLE)->insert(['parenting' => $parent->id, 'ordinary' => 1]);
        $child_entity = new child_entity($child_id);
        $child = child_model::load_by_entity($child_entity);

        try {
            $mover_track->execute($parent, $child);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('item is not deleted', $ex->getMessage());
        }

        $child_entity->parenting++;
        $child_entity->set_deleted();
        try {
            $mover_track->execute($parent, $child);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('item does not belong to the parent', $ex->getMessage());
        }
    }

    public function data_reorder(): array {
        return [
            // [[new_order], [expected_tag => [expected_ordinal, expected_updated], ...]
            '765 -> 567' => [[0, 1, 2], [5 => [1, true], 6 => [2, false], 7 => [3, true]]],
            '765 -> 576' => [[0, 2, 1], [5 => [1, true], 6 => [3, true], 7 => [2, true]]],
            '765 -> 657' => [[1, 0, 2], [5 => [2, true], 6 => [1, true], 7 => [3, true]]],
            '765 -> 675' => [[1, 2, 0], [5 => [3, false], 6 => [1, true], 7 => [2, true]]],
            '765 -> 756' => [[2, 0, 1], [5 => [2, true], 6 => [3, true], 7 => [1, false]]],
            '765 -> 765' => [[2, 1, 0], [5 => [3, false], 6 => [2, false], 7 => [1, false]]],
        ];
    }

    private function lets_reorder(reorder $shuffler, array $new_order): int {
        $parent = $this->parent;

        $children = [
            child_model::load_by_entity(
                new child_entity(
                    builder::table(child_entity::TABLE)
                        ->insert(['parenting' => $parent->id, 'tag' => 5, 'ordinary' => 3])
                )
            ),
            child_model::load_by_entity(
                new child_entity(
                    builder::table(child_entity::TABLE)
                        ->insert(['parenting' => $parent->id, 'tag' => 6, 'ordinary' => 2])
                )
            ),
            child_model::load_by_entity(
                new child_entity(
                    builder::table(child_entity::TABLE)
                        ->insert(['parenting' => $parent->id, 'tag' => 7, 'ordinary' => 1])
                )
            ),
        ];
        $new_children = array_map(function ($index) use ($children) {
            return $children[$index];
        }, $new_order);

        $time = time();
        $this->waitForSecond();
        $shuffler->execute($parent, $children, $new_children);

        return $time;
    }

    /**
     * @covers mod_approval\model\workflow\ordinal\reorder::execute
     * @covers mod_approval\model\workflow\ordinal\reorder::get_ids_of_items
     * @covers mod_approval\model\workflow\ordinal\reorder::validate
     * @covers mod_approval\model\workflow\ordinal\reorder::get_updatable_items
     * @covers mod_approval\model\workflow\ordinal\reorder::do_reorder
     * @param array $new_order
     * @param array $expected
     * @dataProvider data_reorder
     */
    public function test_reorder_track(array $new_order, array $expected): void {
        $shuffler = new reorder($this->track);
        $time = $this->lets_reorder($shuffler, $new_order);

        /** @var array $records */
        $records = builder::table(child_entity::TABLE)->order_by('tag')->select(['tag', 'ordinary', 'timer'])->fetch(false);
        $this->assertCount(count($expected), $records);
        foreach ($expected as $tag => [$ordinal, $updated]) {
            $this->assertArrayHasKey($tag, $records);
            $record = $records[$tag];
            $this->assertEquals($tag, $record->tag);
            $this->assertEquals($ordinal, $record->ordinary, $tag);
            $this->assertEquals($updated, $record->timer > $time, $updated ? "{$tag} should be updated" : "{$tag} should not be updated");
        }
    }

    /**
     * @covers mod_approval\model\workflow\ordinal\reorder::execute
     * @covers mod_approval\model\workflow\ordinal\reorder::get_ids_of_items
     * @covers mod_approval\model\workflow\ordinal\reorder::validate
     * @covers mod_approval\model\workflow\ordinal\reorder::get_updatable_items
     * @covers mod_approval\model\workflow\ordinal\reorder::do_reorder
     * @param array $new_order
     * @param array $expected
     * @dataProvider data_reorder
     */
    public function test_reorder_no_track(array $new_order, array $expected): void {
        $shuffler = new reorder($this->notrack);
        $this->lets_reorder($shuffler, $new_order);

        /** @var array $records */
        $records = builder::table(child_entity::TABLE)->order_by('tag')->select(['tag', 'ordinary', 'timer'])->fetch(false);
        $this->assertCount(count($expected), $records);
        foreach ($expected as $tag => [$ordinal, $x]) {
            $this->assertArrayHasKey($tag, $records);
            $record = $records[$tag];
            $this->assertEquals($tag, $record->tag);
            $this->assertEquals($ordinal, $record->ordinary, $tag);
            $this->assertNull($record->timer, "{$tag}'s time should be preserved");
        }
    }

    /**
     * @covers mod_approval\model\workflow\ordinal\reorder::execute
     * @covers mod_approval\model\workflow\ordinal\reorder::get_ids_of_items
     * @covers mod_approval\model\workflow\ordinal\reorder::validate
     */
    public function test_reorder_illegally(): void {
        $parent = $this->parent;
        $shuffler = new reorder($this->track);
        $child_id = builder::table(child_entity::TABLE)->insert(['parenting' => $parent->id, 'ordinary' => 1]);
        $child_entity = new child_entity($child_id);
        $child = child_model::load_by_entity($child_entity);

        try {
            $shuffler->execute($parent, [$child], []);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('items do not match', $ex->getMessage());
        }
        try {
            $shuffler->execute($parent, [$child], [$child, $child]);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('items do not match', $ex->getMessage());
        }

        $adapted_child_id = builder::table(child_entity::TABLE)->insert(['parenting' => $parent->id, 'ordinary' => 2]);
        $adapted_child_entity = new child_entity($adapted_child_id);
        $adapted_child = child_model::load_by_entity($adapted_child_entity);
        try {
            $shuffler->execute($parent, [$child], [$adapted_child]);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('items do not match', $ex->getMessage());
        }

        $another_parent_id = builder::table(parent_entity::TABLE)->insert(['timing' => time()]);
        $another_child_id = builder::table(child_entity::TABLE)->insert(['parenting' => $another_parent_id, 'ordinary' => 1]);
        $another_child_entity = new child_entity($another_child_id);
        $another_child = child_model::load_by_entity($another_child_entity);
        try {
            $shuffler->execute($parent, [$another_child], [$another_child]);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('item does not belong to the parent', $ex->getMessage());
        }
    }
}
