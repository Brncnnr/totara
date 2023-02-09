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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_core
 */

use core\collection;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\helpers\relationship_collection_manager;
use totara_core\relationship\relationship;

/**
 * @group totara_core_relationship
 * @covers \totara_core\relationship\helpers\relationship_collection_manager
 */
class totara_core_relationship_collection_manager_testcase extends advanced_testcase {

    /**
     * Tests relationship_manager does not throws exception when getting users with valid relationship ids.
     *
     * @return void
     */
    public function test_get_users_for_relationships(): void {
        $relationships = $this->get_relationships();
        $relationship_ids = $relationships->pluck('id');
        $relationship_manager = new relationship_collection_manager($relationship_ids);

        $users_for_relationships = $relationship_manager->get_users_for_relationships(['user_id' => 1]);
        $this->assertCount(count($relationship_ids), $users_for_relationships);

        $users_for_relationships = $relationship_manager->get_users_for_relationships(['user_id' => 1], $relationship_ids);
        $this->assertCount(count($relationship_ids), $users_for_relationships);

        $users_for_relationships = $relationship_manager->get_users_for_relationships(['user_id' => 1], [$relationship_ids[0]]);
        $this->assertCount(1, $users_for_relationships);
    }

    /**
     * Tests relationship_manager throws exception when getting users with invalid relationship ids.
     *
     * @return void
     */
    public function test_throws_exception_for_get_users_for_invalid_relationship(): void {
        $relationships = $this->get_relationships();
        $relationship_manager = new relationship_collection_manager($relationships);
        $this->expectExceptionMessage('Relationship ID not loaded.');
        $relationship_manager->get_users_for_relationships(['user_id' => 1], [0, -1]);
    }

    /**
     * Tests relationship_manager throws exception when constructed with empty array.
     *
     * @return void
     */
    public function test_throws_exception_when_constructed_with_empty_array(): void {
        $this->expectExceptionMessage('Relationships required');
        new relationship_collection_manager([]);
    }

    /**
     * Tests relationship_manager throws exception when constructed with invalid relationship ids.
     *
     * @return void
     */
    public function test_throws_exception_when_constructed_with_invalid_id(): void {
        $this->expectExceptionMessage('Invalid Relationship IDs.');
        new relationship_collection_manager([0, -1]);
    }

    /**
     * Tests if relationship_manager can be constructed with either relationship models or ids.
     *
     * @return void
     */
    public function test_can_be_constructed_with_relationship_models_or_ids(): void {
        $relationships = $this->get_relationships();

        // Allows array of models
        new relationship_collection_manager($relationships->all());
        $this->addToAssertionCount(1);

        // Allows array of relationship IDs
        new relationship_collection_manager($relationships->pluck('id'));
        $this->addToAssertionCount(1);

        // Allows collection
        new relationship_collection_manager($relationships);
        $this->addToAssertionCount(1);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Relationships required.');
        new relationship_collection_manager(new collection(['this is totally a relationship honest']));
    }

    /**
     * @return collection|relationship[]
     */
    private function get_relationships(): collection {
        return relationship_entity::repository()
            ->with('resolvers')
            ->where_null('component')
            ->order_by('id')
            ->get()
            ->map_to(relationship::class);
    }

}
