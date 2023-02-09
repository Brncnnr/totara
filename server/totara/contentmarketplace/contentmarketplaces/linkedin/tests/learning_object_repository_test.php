<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;
use core\orm\query\builder;
use contentmarketplace_linkedin\entity\learning_object;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_learning_object_repository_testcase extends testcase {
    /**
     * @return void
     */
    public function test_find_learning_object_by_urn(): void {
        $generator = generator::instance();
        $learning_object = $generator->create_learning_object('urn:lyndaCourse:496');

        $db = builder::get_db();
        self::assertEquals(1, $db->count_records(learning_object::TABLE));

        $repository = learning_object::repository();
        $found_entity = $repository->find_by_urn('urn:lyndaCourse:496');

        self::assertNotNull($found_entity);
        self::assertEquals($learning_object->id, $found_entity->id);

        // Try to search for the non-existing learning object.
        $not_found_entity = $repository->find_by_urn('urn:lyndaCourse:458');
        self::assertNull($not_found_entity);
    }
}