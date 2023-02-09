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
use totara_engage\loader\user_loader;

class totara_engage_user_loader_testcase extends advanced_testcase {
    /**
     * @param int $number_of_users
     * @return stdClass[]
     */
    private function create_users(int $number_of_users = 2): array {
        $generator = $this->getDataGenerator();
        $users = [];

        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $generator->create_user([
                'firstname' => uniqid("firstname_"),
                'lastname' => uniqid("lastname_")
            ]);
        }

        return $users;
    }

    /**
     * @return void
     */
    public function test_load_normal_users(): void {
        [$user_one, $user_two, $user_three] = $this->create_users(3);
        $context = context_system::instance();

        // Search for user two.
        $query = new user_query($context->id);
        $query->set_search_term($user_two->firstname);

        $cursor_paginator = user_loader::get_users($query);
        self::assertEquals(1, $cursor_paginator->get_total());

        $users = $cursor_paginator->get_items()->all();

        self::assertNotEmpty($users);
        self::assertCount(1, $users);

        $fetched_user = reset($users);
        self::assertNotEquals($user_one->id, $fetched_user->id);
        self::assertNotEquals($user_three->id, $fetched_user->id);

        self::assertEquals($user_two->id, $fetched_user->id);
    }

    /**
     * @return void
     */
    public function test_load_deleted_user(): void {
        [$user_one, $user_two] = $this->create_users(2);
        $context = context_system::instance();

        // Deleted user one.
        delete_user($user_one);
        $user_one = core_user::get_user($user_one->id);

        $query = user_query::create_with_exclude_guest_user($context->id);
        $query->exclude_users(array_keys(get_admins()));

        $non_deleted_result = user_loader::get_users($query);
        self::assertEquals(1, $non_deleted_result->get_total());

        $non_deleted_users = $non_deleted_result->get_items()->all();
        self::assertNotEmpty($non_deleted_users);
        self::assertCount(1, $non_deleted_users);

        $non_deleted_user = reset($non_deleted_users);
        self::assertEquals($user_two->id, $non_deleted_user->id);
        self::assertNotTrue($user_one->id, $non_deleted_user->id);

        // Fetch with deleted.
        $query->include_deleted();
        $deleted_result = user_loader::get_users($query);

        self::assertEquals(1, $deleted_result->get_total());

        $deleted_users = $deleted_result->get_items()->all();
        self::assertNotEmpty($deleted_users);
        self::assertCount(1, $deleted_users);

        $deleted_user = reset($deleted_users);
        self::assertEquals($user_one->id, $deleted_user->id);
        self::assertNotEquals($user_two->id, $deleted_user->id);
    }

    /**
     * @return void
     */
    public function test_load_users_include_site_guest_and_admin(): void {
        global $CFG;

        $user_ids = array_merge(
            array_keys(get_admins()),
            [$CFG->siteguest]
        );

        $context = context_system::instance();

        $query = new user_query($context->id);
        $result = user_loader::get_users($query);

        self::assertEquals(count($user_ids), $result->get_total());
        $users = $result->get_items()->all();

        foreach ($users as $user) {
            self::assertContainsEquals($user->id, $user_ids);
        }
    }

    /**
     * @return void
     */
    public function test_load_suspended_user(): void {
        global $CFG;
        [$user_one, $user_two] = $this->create_users(2);
        $context = context_system::instance();

        // Suspend the user one.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_one->id);
        $user_one = core_user::get_user($user_one->id);

        $query = user_query::create_with_exclude_guest_user($context->id);
        $query->exclude_users(array_keys(get_admins()));

        $non_deleted_result = user_loader::get_users($query);
        self::assertEquals(1, $non_deleted_result->get_total());

        $non_deleted_users = $non_deleted_result->get_items()->all();
        self::assertNotEmpty($non_deleted_users);
        self::assertCount(1, $non_deleted_users);

        $non_deleted_user = reset($non_deleted_users);
        self::assertEquals($user_two->id, $non_deleted_user->id);
        self::assertNotTrue($user_one->id, $non_deleted_user->id);

        // Fetch with deleted.
        $query->include_suspended();
        $deleted_result = user_loader::get_users($query);

        self::assertEquals(1, $deleted_result->get_total());

        $deleted_users = $deleted_result->get_items()->all();
        self::assertNotEmpty($deleted_users);
        self::assertCount(1, $deleted_users);

        $deleted_user = reset($deleted_users);
        self::assertEquals($user_one->id, $deleted_user->id);
        self::assertNotEquals($user_two->id, $deleted_user->id);
    }
}