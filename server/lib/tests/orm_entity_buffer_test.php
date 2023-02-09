<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_orm
 * @category test
 */

use core\orm\entity\buffer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_entity_relation_testcase.php');

/**
 * Tests covering the entity buffer for deferred loading
 *
 * @package core
 * @group orm
 */
class core_orm_entity_buffer_test extends orm_entity_relation_testcase {

    protected function setUp(): void {
        parent::setUp();
        buffer::clear();
    }

    public function test_unknown_relation() {
        $this->create_sample_records();

        /** @var sample_parent_entity $parent1 */
        $parent1 = sample_parent_entity::repository()
            ->where('name', 'Mobile phones')
            ->one();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Unknown relation with name 'idontexist' in entity '".sample_parent_entity::class."'");

        buffer::defer($parent1, 'idontexist');
    }

    public function test_buffer_has_many() {
        $this->create_sample_records();

        $queries_count = $this->db()->perf_get_reads();

        /** @var sample_parent_entity $parent1 */
        $parent1 = sample_parent_entity::repository()
            ->where('name', 'Mobile phones')
            ->one();

        /** @var sample_parent_entity $parent2 */
        $parent2 = sample_parent_entity::repository()
            ->where('name', 'Tablets')
            ->one();

        $queries_count = $queries_count + 2;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        $deferred1 = buffer::defer($parent1, 'children');
        $deferred2 = buffer::defer($parent2, 'children');

        // No additional queries issued
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        $children1 = $deferred1();

        // One query for all entities in the buffer was issued
        $queries_count++;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        // The result of the deferred query should match the correct parent
        $this->assertCount(3, $children1);
        $this->assertContainsOnlyInstancesOf(sample_child_entity::class, $children1);
        foreach ($children1 as $child) {
            $this->assertEquals($parent1->id, $child->parent_id);
        }

        // The original entity should now have the related entities loaded
        $this->assertCount(3, $parent1->children);
        $this->assertContainsOnlyInstancesOf(sample_child_entity::class, $parent1->children);
        foreach ($parent1->children as $child) {
            $this->assertEquals($parent1->id, $child->parent_id);
        }

        $children2 = $deferred2();

        // The result of the deferred query should match the correct parent
        $this->assertCount(3, $children2);
        $this->assertContainsOnlyInstancesOf(sample_child_entity::class, $children2);
        foreach ($children2 as $child) {
            $this->assertEquals($parent2->id, $child->parent_id);
        }

        // The original entity should now have the related entities loaded
        $this->assertCount(3, $parent2->children);
        $this->assertContainsOnlyInstancesOf(sample_child_entity::class, $parent2->children);
        foreach ($parent2->children as $child) {
            $this->assertEquals($parent2->id, $child->parent_id);
        }

        // Should result in no more queries
        $this->assertEquals($queries_count , $this->db()->perf_get_reads());
    }

    public function test_buffer_belongs_to() {
        $this->create_sample_records();

        $queries_count = $this->db()->perf_get_reads();

        /** @var sample_child_entity $child1 */
        $child1 = sample_child_entity::repository()
            ->where('name', 'HP Personal computer')
            ->one();

        /** @var sample_child_entity $child2 */
        $child2 = sample_child_entity::repository()
            ->where('name', 'DELL Personal computer')
            ->one();

        /** @var sample_child_entity $child3 */
        $child3 = sample_child_entity::repository()
            ->where('name', 'Casio vintage calculator')
            ->one();

        $queries_count = $queries_count + 3;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        $deferred1 = buffer::defer($child1, 'parent');
        $deferred2 = buffer::defer($child2, 'parent');
        $deferred3 = buffer::defer($child3, 'parent');

        // No additional queries issued
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        /** @var sample_parent_entity $parent1 */
        $parent1 = $deferred1();
        $this->assertInstanceOf(sample_parent_entity::class, $parent1);
        $this->assertEquals($parent1->id, $child1->parent_id);

        /** @var sample_parent_entity $parent2 */
        $parent2 = $deferred2();
        $this->assertInstanceOf(sample_parent_entity::class, $parent2);
        $this->assertEquals($parent2->id, $child2->parent_id);

        /** @var sample_parent_entity $parent3 */
        $parent3 = $deferred3();
        $this->assertInstanceOf(sample_parent_entity::class, $parent3);
        $this->assertEquals($parent3->id, $child3->parent_id);

        // Should result in no more queries
        $queries_count++;
        $this->assertEquals($queries_count , $this->db()->perf_get_reads());
    }

    public function test_buffer_has_one() {
        $this->create_sample_records();

        $queries_count = $this->db()->perf_get_reads();

        /** @var sample_parent_entity $parent1 */
        $parent1 = sample_parent_entity::repository()
            ->where('name', 'Mobile phones')
            ->one();

        /** @var sample_parent_entity $parent2 */
        $parent2 = sample_parent_entity::repository()
            ->where('name', 'Tablets')
            ->one();

        $queries_count = $queries_count + 2;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        $deferred1 = buffer::defer($parent1, 'passport');
        $deferred2 = buffer::defer($parent2, 'passport');

        // No additional queries issued
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        /** @var sample_passport_entity $passport1 */
        $passport1 = $deferred1();

        // In this case the parent relation is always automatically loaded together with the passport
        // so there's an additional query here.
        $queries_count = $queries_count + 2;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        // The result of the deferred query should match the correct parent
        $this->assertInstanceOf(sample_passport_entity::class, $passport1);
        $this->assertEquals($parent1->id, $passport1->parent_id);

        /** @var sample_passport_entity $passport2 */
        $passport2 = $deferred2();

        $this->assertInstanceOf(sample_passport_entity::class, $passport2);
        $this->assertEquals($parent2->id, $passport2->parent_id);

        // Should result in no more queries
        $this->assertEquals($queries_count , $this->db()->perf_get_reads());
    }

    public function test_buffer_has_many_through() {
        $this->create_sample_records();

        $queries_count = $this->db()->perf_get_reads();

        /** @var sample_passport_entity $passport1 */
        $passport1 = sample_passport_entity::repository()
            ->where('name', 'Quality passport')
            ->one();

        /** @var sample_passport_entity $parent2 */
        $passport2 = sample_passport_entity::repository()
            ->where('name', 'Durability passport')
            ->one();

        // In this special case the parent relation for the passport entity is
        // automatically loaded so there are more than just two more queries
        $queries_count = $queries_count + 4;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        $deferred1 = buffer::defer($passport1, 'children');
        $deferred2 = buffer::defer($passport2, 'children');

        // No additional queries issued
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        /** @var sample_child_entity[] $children1 */
        $children1 = $deferred1();

        // One query for all entities in the buffer was issued
        $queries_count = $queries_count + 1;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        // The result of the deferred query should match the correct parent
        $this->assertContainsOnlyInstancesOf(sample_child_entity::class, $children1);
        foreach ($children1 as $child) {
            $this->assertEquals($child->parent_id, $passport1->parent_id);
        }

        /** @var sample_child_entity[] $children2 */
        $children2 = $deferred2();

        // The result of the deferred query should match the correct parent
        $this->assertContainsOnlyInstancesOf(sample_child_entity::class, $children2);
        foreach ($children2 as $child) {
            $this->assertEquals($child->parent_id, $passport2->parent_id);
        }

        // Should result in no more queries
        $this->assertEquals($queries_count , $this->db()->perf_get_reads());
    }

    public function test_has_one_through() {
        $this->create_sample_records();

        $queries_count = $this->db()->perf_get_reads();

        /** @var sample_parent_entity $parent1 */
        $parent1 = sample_parent_entity::repository()
            ->where('name', 'Mobile phones')
            ->one();

        /** @var sample_parent_entity $parent2 */
        $parent2 = sample_parent_entity::repository()
            ->where('name', 'Tablets')
            ->one();

        /** @var sample_parent_entity $parent3 */
        $parent3 = sample_parent_entity::repository()
            ->where('name', 'Personal Computers')
            ->one();

        $queries_count = $queries_count + 3;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        $deferred1 = buffer::defer($parent1, 'a_sibling');
        $deferred2 = buffer::defer($parent2, 'a_sibling');
        $deferred3 = buffer::defer($parent3, 'a_sibling');

        // No additional queries issued
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        /** @var sample_sibling_entity $sibling1 */
        $sibling1 = $deferred1();

        $queries_count++;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        // The result of the deferred query should match the correct parent
        $this->assertInstanceOf(sample_sibling_entity::class, $sibling1);
        $this->assertEquals('Red Mi Note 7', $sibling1->name);

        /** @var sample_sibling_entity $sibling2 */
        $sibling2 = $deferred2();

        $this->assertInstanceOf(sample_sibling_entity::class, $sibling2);
        $this->assertEquals('Red Mi Note 7', $sibling2->name);

        /** @var sample_sibling_entity $sibling3 */
        $sibling3 = $deferred3();

        $this->assertInstanceOf(sample_sibling_entity::class, $sibling3);
        $this->assertEquals('Apple iPhone XS', $sibling3->name);

        // Should result in no more queries
        $this->assertEquals($queries_count , $this->db()->perf_get_reads());
    }

    public function test_add_to_buffer_multiple_times() {
        $this->create_sample_records();

        /** @var sample_parent_entity $parent1 */
        $parent1 = sample_parent_entity::repository()
            ->where('name', 'Mobile phones')
            ->one();

        /** @var sample_parent_entity $parent2 */
        $parent2 = sample_parent_entity::repository()
            ->where('name', 'Tablets')
            ->one();

        // Initial count
        $queries_count = $this->db()->perf_get_reads();

        // Add the entities to the buffer once
        $deferred1 = buffer::defer($parent1, 'children');
        $deferred2 = buffer::defer($parent2, 'children');

        /** @var sample_child_entity[] $children1 */
        $children1 = $deferred1();

        // One query for all entities in the buffer was issued
        $queries_count++;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        /** @var sample_child_entity[] $children2 */
        $children2 = $deferred2();
        $this->assertContainsOnlyInstancesOf(sample_child_entity::class, $children2);

        // Should result in no more queries
        $this->assertEquals($queries_count , $this->db()->perf_get_reads());

        // If the relation is already loaded it won't add it to the buffer but return the already loaded relation
        /** @var sample_parent_entity $parent1 */
        $this->assertTrue($parent1->relation_loaded('children'));

        // Add to the buffer again but with relation already loaded
        $deferred1 = buffer::defer($parent1, 'children');

        /** @var sample_child_entity[] $children1_second_try */
        $children1_second_try = $deferred1();

        $this->assertEquals($children1, $children1_second_try);

        // Should result in no more queries
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        // Reload and add to buffer again would trigger another query as the relation is not loaded on the entity
        /** @var sample_parent_entity $parent3 */
        $parent3 = sample_parent_entity::repository()
            ->where('name', 'Mobile phones')
            ->one();

        /** @var sample_parent_entity $parent4 */
        $parent4 = sample_parent_entity::repository()
            ->where('name', 'Personal Computers')
            ->one();

        $queries_count = $queries_count + 2;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        // Add to the buffer again, this time the relation are not loaded, so they will be treated as fresh entities
        $deferred3 = buffer::defer($parent3, 'children');
        $deferred4 = buffer::defer($parent4, 'children');

        /** @var sample_child_entity[] $children3 */
        $children3 = $deferred3();
        $this->assertContainsOnlyInstancesOf(sample_child_entity::class, $children3);
        $this->assertEquals(count($children1), count($children3));

        /** @var sample_child_entity[] $children4 */
        $children4 = $deferred4();
        // This one was not previously in the buffer and now got loaded as well
        $this->assertCount(3, $children4);
        $this->assertContainsOnlyInstancesOf(sample_child_entity::class, $children4);
        foreach ($children4 as $child) {
            $this->assertEquals($parent4->id, $child->parent_id);
        }

        // Should result in an additional query
        $queries_count++;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());
    }

    public function test_buffer_mix_entities() {
        $this->create_sample_records();

        $queries_count = $this->db()->perf_get_reads();

        /** @var sample_parent_entity $parent1 */
        $parent1 = sample_parent_entity::repository()
            ->where('name', 'Mobile phones')
            ->one();

        /** @var sample_parent_entity $parent2 */
        $parent2 = sample_parent_entity::repository()
            ->where('name', 'Tablets')
            ->one();

        /** @var sample_child_entity $child1 */
        $child1 = sample_child_entity::repository()
            ->where('name', 'HP Personal computer')
            ->one();

        /** @var sample_child_entity $child2 */
        $child2 = sample_child_entity::repository()
            ->where('name', 'DELL Personal computer')
            ->one();

        /** @var sample_child_entity $child3 */
        $child3 = sample_child_entity::repository()
            ->where('name', 'Casio vintage calculator')
            ->one();

        $queries_count = $queries_count + 5;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        $deferred1 = buffer::defer($parent1, 'children');
        $deferred2 = buffer::defer($parent2, 'children');
        $deferred3 = buffer::defer($child1, 'parent');
        $deferred4 = buffer::defer($child2, 'parent');
        $deferred5 = buffer::defer($child3, 'parent');

        // No additional queries issued
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        $children1 = $deferred1();

        // Two queries for all entities in the buffer were issued,
        // one for parent->children and one for child->parent
        $queries_count = $queries_count + 2;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        // The result of the deferred query should match the correct parent
        $this->assertCount(3, $children1);
        foreach ($children1 as $child) {
            $this->assertEquals($parent1->id, $child->parent_id);
        }

        // The result of the deferred query should match the correct parent
        $children2 = $deferred2();
        $this->assertCount(3, $children2);
        foreach ($children2 as $child) {
            $this->assertEquals($parent2->id, $child->parent_id);
        }

        /** @var sample_parent_entity $parent1 */
        $parent1 = $deferred3();
        $this->assertInstanceOf(sample_parent_entity::class, $parent1);
        $this->assertEquals($parent1->id, $child1->parent_id);

        /** @var sample_parent_entity $parent2 */
        $parent2 = $deferred4();
        $this->assertInstanceOf(sample_parent_entity::class, $parent2);
        $this->assertEquals($parent2->id, $child2->parent_id);

        /** @var sample_parent_entity $parent3 */
        $parent3 = $deferred5();
        $this->assertInstanceOf(sample_parent_entity::class, $parent3);
        $this->assertEquals($parent3->id, $child3->parent_id);

        // Should result in no more queries
        $this->assertEquals($queries_count , $this->db()->perf_get_reads());
    }

    public function test_defer_does_handle_empty_relations() {
        $this->create_sample_records();

        $queries_count = $this->db()->perf_get_reads();

        /** @var sample_child_entity $child1 */
        $child1 = sample_child_entity::repository()
            ->where('name', 'HP Personal computer')
            ->one();

        /** @var sample_child_entity $child2 */
        $child2 = sample_child_entity::repository()
            ->where('name', 'DELL Personal computer')
            ->one();

        // No set this relation to null
        $child2->type = null;
        $child2->save();

        /** @var sample_child_entity $child3 */
        $child3 = sample_child_entity::repository()
            ->where('name', 'Casio vintage calculator')
            ->one();

        $queries_count = $queries_count + 3;
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        $deferred1 = buffer::defer($child1, 'a_type');
        $deferred2 = buffer::defer($child2, 'a_type');
        $deferred3 = buffer::defer($child3, 'a_type');

        // No additional queries issued
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        /** @var sample_child_entity $type1 */
        $type1 = $deferred1();
        // Just checking that there's a result
        $this->assertInstanceOf(sample_child_entity::class, $type1);

        $type2 = $deferred2();
        $this->assertNull($type2);

        /** @var sample_child_entity $type3 */
        $type3 = $deferred3();
        // Just checking that there's a result
        $this->assertInstanceOf(sample_child_entity::class, $type3);
    }

}
