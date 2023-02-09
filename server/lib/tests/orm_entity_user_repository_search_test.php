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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 * @category test
 */

use core\entity\user;
use core\entity\user_repository;
use totara_tenant\local\util;

defined('MOODLE_INTERNAL') || die();

class core_orm_entity_user_repository_search_testcase extends advanced_testcase {

    public function test_search_for_users() {
        $context = context_user::instance(get_admin()->id);

        $this->create_users();

        // Without any pattern and limit
        $users = user_repository::search($context);
        $this->assertCount(5, $users);

        $this->assertEquals(
            ['Admin', 'Arnold', 'John', 'Richard', 'Xavier'],
            $users->pluck('firstname')
        );

        // Filter by keyword
        $users = user_repository::search($context, 'Rich');
        $this->assertCount(1, $users);
        $this->assertEquals(['Richard'], $users->pluck('firstname'));

        // Make sure it's case insensitive
        $users = user_repository::search($context, 'rich');
        $this->assertCount(1, $users);
        $this->assertEquals(['Richard'], $users->pluck('firstname'));

        $users = user_repository::search($context, 'ic');
        $this->assertCount(2, $users);
        $this->assertEquals(['Arnold', 'Richard'], $users->pluck('firstname'));

        // Now limit it
        $users = user_repository::search($context, '', 3);
        $this->assertCount(3, $users);

        $this->assertEquals(
            ['Admin', 'Arnold', 'John'],
            $users->pluck('firstname')
        );

        // Now include the guest user
        $users = user_repository::search($context, '', 0, true);
        $this->assertCount(6, $users);

        $this->assertEquals(
            ['Admin', 'Arnold', 'Guest user', 'John', 'Richard', 'Xavier'],
            $users->pluck('firstname')
        );
    }

    public function test_search_for_users_with_multi_tenancy() {
        $this->create_users_for_multi_tenancy();

        // Tenant 1 user
        $user1 = user::repository()->where('firstname', 'Xavier')->one();
        // Tenant 2 user
        $user2 = user::repository()->where('firstname', 'Silvester')->one();
        // Outside of tenant
        $user3 = user::repository()->where('firstname', 'Vladimir')->one();

        $context1 = context_user::instance($user1->id);
        $context2 = context_user::instance($user2->id);
        $context3 = context_user::instance($user3->id);

        // Now we should be limited to participants in tenant 1 only
        $users = user_repository::search($context1);
        $this->assertCount(5, $users);

        $this->assertEquals(
            ['Arnold', 'John', 'Mickey', 'Richard', 'Xavier'],
            $users->pluck('firstname')
        );

        $users = user_repository::search($context2);
        $this->assertCount(4, $users);

        $this->assertEquals(
            ['Bruce', 'Denny', 'Glenn', 'Silvester'],
            $users->pluck('firstname')
        );

        $users = user_repository::search($context3);
        $this->assertCount(12, $users);

        $this->assertEquals(
            [
                'Admin',
                'Arnold',
                'Bruce',
                'Denny',
                'Donald',
                'Glenn',
                'John',
                'Mickey',
                'Richard',
                'Silvester',
                'Vladimir',
                'Xavier'
            ],
            $users->pluck('firstname')
        );

        // Now turn on isolation
        set_config('tenantsisolated', 1);

        // This should still work
        $users = user_repository::search($context1);
        $this->assertCount(5, $users);

        $this->assertEquals(
            ['Arnold', 'John', 'Mickey', 'Richard', 'Xavier'],
            $users->pluck('firstname')
        );

        $users = user_repository::search($context3);
        $this->assertCount(4, $users);

        $this->assertEquals(
            [
                'Admin',
                'Donald',
                'Mickey',
                'Vladimir',
            ],
            $users->pluck('firstname')
        );
    }

    public function test_search_for_users_filter_by_suspended() {
        $context = context_user::instance(get_admin()->id);

        $this->create_users_with_suspended();

        // Test that by default it returns with suspended users
        $users = user_repository::search($context);
        $this->assertCount(8, $users);
        $this->assertEquals(
            [
                'Admin',
                'Arnold',
                'Jack',
                'John',
                'Margaret',
                'Nick',
                'Richard',
                'Xavier'
            ],
            $users->pluck('firstname')
        );

        // Test without suspended users: 'Xavier' and 'Richard'
        $users = user_repository::search($context, '', 0, false, true);
        $this->assertCount(6, $users);
        $this->assertEquals(
            [
                'Admin',
                'Arnold',
                'Jack',
                'John',
                'Margaret',
                'Nick'
            ],
            $users->pluck('firstname')
        );
    }

    public function test_search_for_users_filter_by_current_user() {
        $context = context_user::instance(get_admin()->id);

        $this->create_users_with_suspended();

        // Test that by default it returns with current user
        $users = user_repository::search($context);
        $this->assertCount(8, $users);
        $this->assertEquals(
            [
                'Admin',
                'Arnold',
                'Jack',
                'John',
                'Margaret',
                'Nick',
                'Richard',
                'Xavier'
            ],
            $users->pluck('firstname')
        );

        // Test without current user: 'Admin'
        $this->setAdminUser();
        $users = user_repository::search($context, '', 0, false, false, true);
        $this->assertCount(7, $users);
        $this->assertEquals(
            [
                'Arnold',
                'Jack',
                'John',
                'Margaret',
                'Nick',
                'Richard',
                'Xavier'
            ],
            $users->pluck('firstname')
        );
    }

    private function create_users() {
        $users_to_create = [
            // firstname, middlename, lastname, deleted, confirmed
            ['John', 'Milford', 'Doe', 0, 1],
            ['Jack', 'John', 'Kasinscki', 1, 1],
            ['Margaret', 'Ruth', 'Matcher', 0, 0],
            ['Nick', 'Southerly', 'Rutherford', 1, 0],
            ['Arnold', 'Franz', 'Gregovic', 0, 1],
            ['Xavier', 'Pope', 'Naferville', 0, 1],
            ['Richard', 'Sullivan', 'Nickidum', 0, 1],
        ];

        $this->do_create_users($users_to_create);
    }

    private function create_users_for_multi_tenancy() {
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = \totara_tenant\testing\generator::instance();
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $users_to_create1 = [
            // firstname, middlename, lastname, deleted, confirmed
            ['John', 'Milford', 'Doe', 0, 1],
            ['Jack', 'John', 'Kasinscki', 1, 1],
            ['Margaret', 'Ruth', 'Matcher', 0, 0],
            ['Nick', 'Southerly', 'Rutherford', 1, 0],
            ['Arnold', 'Franz', 'Gregovic', 0, 1],
            ['Xavier', 'Pope', 'Naferville', 0, 1],
            ['Richard', 'Sullivan', 'Nickidum', 0, 1],
        ];

        $this->do_create_users($users_to_create1, $tenant1);

        $users_to_create2 = [
            // firstname, middlename, lastname, deleted, confirmed
            ['Silvester', 'Marlow', 'Stallone', 0, 1],
            ['Arnold', 'Xaver', 'Schwarzenegger', 1, 1],
            ['Uma', 'Margot', 'Thurman', 0, 0],
            ['Steven', 'John', 'Segal', 1, 0],
            ['Glenn', 'Trudl', 'Matthews', 0, 1],
            ['Denny', 'Peter', 'Trejo', 0, 1],
            ['Bruce', 'Will', 'Willis', 0, 1],
        ];

        $this->do_create_users($users_to_create2, $tenant2);

        // And create a few system users
        $users_to_create3 = [
            // firstname, middlename, lastname, deleted, confirmed
            ['Vladimir', 'Blood', 'Dracula', 0, 1],
            ['Donald', 'Hagrid', 'Duck', 0, 1],
            ['Mickey', 'Mike', 'Mouse', 0, 1],
        ];

        $this->do_create_users($users_to_create3);

        $user = user::repository()->where('firstname', 'Mickey')->one();

        // Add Mickey as participant
        util::add_other_participant($tenant1->id, $user->id);
    }

    private function create_users_with_suspended() {
        $users_to_create = [
            // firstname, middlename, lastname, deleted, confirmed, suspended
            ['John', 'Milford', 'Doe', 0, 1, 0],
            ['Jack', 'John', 'Kasinscki', 0, 1, 0],
            ['Margaret', 'Ruth', 'Matcher', 0, 1, 0],
            ['Nick', 'Southerly', 'Rutherford', 0, 1, 0],
            ['Arnold', 'Franz', 'Gregovic', 0, 1, 0],
            ['Xavier', 'Pope', 'Naferville', 0, 1, 1],
            ['Richard', 'Sullivan', 'Nickidum', 0, 1, 1],
        ];

        $this->do_create_users($users_to_create);
    }

    private function do_create_users(array $users_to_create, $tenant = null) {
        foreach ($users_to_create as $user_info) {
            $user_info[5] = $user_info[5] ?? 0;
            [$firstname, $middlename, $lastname, $deleted, $confirmed, $suspended] = $user_info;
            $user = [
                'firstname' => $firstname,
                'middlename' => $middlename,
                'lastname' => $lastname,
                'deleted' => $deleted,
                'confirmed' => $confirmed,
                'suspended' => $suspended
            ];

            if ($tenant) {
                $user['tenantid'] = $tenant->id;
            }

            $this->getDataGenerator()->create_user($user);
        }
    }

}
