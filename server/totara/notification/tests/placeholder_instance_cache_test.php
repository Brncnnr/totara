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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder_group;

/**
 * @group totara_notification
 */
class totara_notification_placeholder_instance_cache_testcase extends testcase {
    /**
     * @return void
     */
    public function test_cache_size_limit(): void {
        global $DB;
        self::setAdminUser();

        $user = [];
        for ($i = 1; $i <= 20; $i ++) {
            $user[$i] = self::getDataGenerator()->create_user();
        }

        // We expect 1 read per first instantiation.
        $query_count = $DB->perf_get_reads();
        for ($i = 1; $i <= 20; $i ++) {
            user_placeholder_group::from_id($user[$i]->id);
        }
        self::assertEquals($query_count + 20, $DB->perf_get_reads());

        // We expect no additional reads because all are cached.
        for ($i = 1; $i <= 20; $i ++) {
            user_placeholder_group::from_id($user[$i]->id);
        }
        self::assertEquals($query_count + 20, $DB->perf_get_reads());

        // Add another user to the cache and it should remove the oldest one.
        $user21 = self::getDataGenerator()->create_user();
        $query_count = $DB->perf_get_reads();
        user_placeholder_group::from_id($user21->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());
        // Second oldest should still be in cache.
        user_placeholder_group::from_id($user[2]->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());
        // Oldest was removed so will be re-added.
        user_placeholder_group::from_id($user[1]->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
        user_placeholder_group::from_id($user21->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }

    public function test_clear_instance_cache(): void {
        global $DB;
        self::setAdminUser();

        $user = self::getDataGenerator()->create_user();
        $query_count = $DB->perf_get_reads();
        user_placeholder_group::from_id($user->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());
        user_placeholder_group::from_id($user->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        user_placeholder_group::clear_instance_cache();
        user_placeholder_group::from_id($user->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }
}