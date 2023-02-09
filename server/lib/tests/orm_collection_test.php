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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 * @group orm
 */

use core\collection as core_collection;
use core\orm\collection;

defined('MOODLE_INTERNAL') || die();

/**
 * Class core_orm_collection_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_collection_testcase extends advanced_testcase {

    public function test_it_extends_core_collection() {
        $this->assertTrue(new collection([]) instanceof core_collection);
    }

    public function test_it_handles_collections_without_entities_gracefully() {
        $collection = new collection([
            new class extends stdClass {

            },
            new class extends core\orm\entity\entity {
                public const TABLE = 'table';
            },
        ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('This must be a collection of entities');

        $collection->get_entity_class();
    }

    public function test_it_ignores_empty_collection() {
        $collection = new collection([]);

        $this->assertSame($collection->load('abracadabra'), $collection);
    }

    // To test loading of relations see relation_test::class
}
