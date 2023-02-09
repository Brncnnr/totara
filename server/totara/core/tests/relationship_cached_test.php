<?php
/*
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_core
 */

use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\cached_relationship;

require_once(__DIR__ . '/relationship_resolver_test.php');

/**
 * @group totara_core_relationship
 * @covers \totara_core\relationship\cached_relationship
 */
class totara_core_relationship_cached_testcase extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        relationship_entity::repository()->delete();
        cached_relationship::reset_cache();
    }

    protected function tearDown(): void {
        parent::tearDown();
        cached_relationship::reset_cache();
    }

    public function test_load_by_idnumber(): void {
        $relationship_one = cached_relationship::create([test_resolver_one::class], 'one', 1);
        $relationship_two = cached_relationship::create([test_resolver_two::class], 'two', 2);

        $db_count = builder::get_db()->perf_get_reads();

        $this->assertEquals($relationship_one->id, cached_relationship::load_by_idnumber('one')->id);
        // No new DB query got triggered
        $this->assertSame($db_count, builder::get_db()->perf_get_reads());

        $this->assertEquals($relationship_two->id, cached_relationship::load_by_idnumber('two')->id);
        // No new DB query got triggered
        $this->assertSame($db_count, builder::get_db()->perf_get_reads());

        // Can not load a deleted relationship via idnumber - throws exception instead.
        $relationship_one->delete();
        try {
            cached_relationship::load_by_idnumber('one');
            $this->fail('Expected exception, none thrown.');
        } catch (record_not_found_exception $e) {
            // We should have gotten an exception
        }

        cached_relationship::reset_cache();

        $db_count = builder::get_db()->perf_get_reads();

        $relationship_two_reloaded = cached_relationship::load_by_idnumber('two');

        $this->assertEquals($relationship_two->id, $relationship_two_reloaded->id);
        // No new DB query got triggered
        $this->assertSame($db_count + 1, builder::get_db()->perf_get_reads());
    }

}
