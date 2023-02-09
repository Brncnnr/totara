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
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\query\user_query;
use totara_engage\query\user_tenant_query;
use core\pagination\offset_cursor;

class totara_engage_user_query_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_getter_setter(): void {
        $query = new user_query(42);

        // Default state
        self::assertEquals(42, $query->get_context_id());
        self::assertNull($query->get_search_term());
        self::assertTrue($query->is_including_system_user());
        self::assertTrue($query->is_including_participant());
        self::assertFalse($query->is_including_suspended());
        self::assertFalse($query->is_including_deleted());
        self::assertInstanceOf(user_tenant_query::class, $query->get_tenant_query());
        self::assertInstanceOf(offset_cursor::class, $query->get_cursor());

        // Setter assertions.
        $query->set_search_term('abcde');
        self::assertEquals('abcde', $query->get_search_term());

        $query->include_suspended();
        self::assertTrue($query->is_including_suspended());

        $query->include_deleted();
        self::assertTrue($query->is_including_deleted());

        $old_cursor = $query->get_cursor();
        $query->set_cursor(new offset_cursor(['page' => 2, 'limit' => 20]));
        $new_cursor = $query->get_cursor();

        self::assertInstanceOf(offset_cursor::class, $new_cursor);
        self::assertNotEquals($old_cursor->encode(), $new_cursor->encode());
    }

    /**
     * @return void
     */
    public function test_exclude_users(): void {
        global $CFG;

        $query = new user_query(52);
        self::assertEmpty($query->get_exclude_users());

        $query->exclude_user(50);
        self::assertEquals([50], $query->get_exclude_users());

        $query->exclude_guest_user();
        self::assertEquals([50, $CFG->siteguest], $query->get_exclude_users());

        $query->clear_exclude_user();
        self::assertEmpty($query->get_exclude_users());
    }

    /**
     * @return void
     */
    public function test_create_query_with_exclude_guest(): void {
        global $CFG;
        $query = user_query::create_with_exclude_guest_user(450);

        self::assertNotEmpty($query->get_exclude_users());
        self::assertEquals([$CFG->siteguest], $query->get_exclude_users());
    }
}