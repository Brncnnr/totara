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
 * @package totara_competency
 * @category test
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_user_service_testcase extends advanced_testcase {

    use \totara_core\phpunit\webservice_utils;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_it_lists_users() {
        $this->generate_users();

        $res = $this->call_webservice_api('totara_competency_user_index', [
            'filters' => [],
            'page' => 1,
            'order' => 'name',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);

        $this->assertEqualsCanonicalizing([
            'Jane Doe',
            'John Doe',
            'Boris Ivanovich',
            'Ivan Ivanovich',
            'John Smith',
            'Admin User',
        ], array_column($data['items'], 'display_name'));
    }

    public function test_it_searches_users() {
        $this->generate_users();

        $res = $this->call_webservice_api('totara_competency_user_index', [
            'filters' => ['text' => 'john'],
            'page' => 1,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);

        $this->assertEquals(
            [
                'John Smith',
                'John Doe',
            ],
            array_column($data['items'], 'display_name')
        );

        // Only id + display name is returned
        $this->assertCount(2, array_keys($data['items'][0]));
    }

    public function test_it_searches_users_by_basket() {
        $users = $this->generate_users();

        $basket = new \totara_core\basket\session_basket('users');
        $basket->add([$users[0]->id, $users[4]->id]);

        $res = $this->call_webservice_api('totara_competency_user_index', [
            'filters' => ['basket' => 'users'],
            'page' => 1,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;


        $this->assert_webservice_success($res);
        $this->assertEquals(
            [
                'Ivan Ivanovich',
                'Jane Doe',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_searches_users_by_non_existent_basket() {
        $users = $this->generate_users();

        $res = $this->call_webservice_api('totara_competency_user_index', [
            'filters' => ['basket' => 'idonotexist'],
            'page' => 1,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEmpty($data['items']);
    }

    public function test_it_paginates_users() {
        $this->generate_n_users();

        $res = $this->call_webservice_api('totara_competency_user_index', [
            'filters' => [],
            'page' => 1,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $first = $data['items']);
        $this->assertEquals(51, $data['total']); // 50 we've created + admin is already there and guest is filtered out
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(3, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertEquals(2, $data['next']);

        $res = $this->call_webservice_api('totara_competency_user_index', [
            'filters' => [],
            'page' => 2,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $second = $data['items']);
        $this->assertEquals(51, $data['total']); // 50 we've created + admin is already there and guest is filtered out
        $this->assertEquals(2, $data['page']);
        $this->assertEquals(3, $data['pages']);
        $this->assertEquals(1, $data['prev']);
        $this->assertEquals(3, $data['next']);

        $res = $this->call_webservice_api('totara_competency_user_index', [
            'filters' => [],
            'page' => 3,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(11, $third = $data['items']);
        $this->assertEquals(51, $data['total']); // 50 we've created + admin is already there and guest is filtered out
        $this->assertEquals(3, $data['page']);
        $this->assertEquals(3, $data['pages']);
        $this->assertEquals(2, $data['prev']);
        $this->assertNull($data['next']);

        $res = $this->call_webservice_api('totara_competency_user_index', [
            'filters' => [],
            'page' => 4,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(0, $fourth = $data['items']);
        $this->assertEquals(51, $data['total']); // 50 we've created + admin is already there and guest is filtered out
        $this->assertEquals(4, $data['page']);
        $this->assertEquals(3, $data['pages']);
        $this->assertEquals(3, $data['prev']);
        $this->assertNull($data['next']);


        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($second, 'id')));
        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($third, 'id')));
        $this->assertEmpty(array_intersect(array_column($second, 'id'), array_column($third, 'id')));
    }

    /**
     * Create a few users with knows names to test search
     */
    protected function generate_users(): array {
        $gen = $this->getDataGenerator();

        $users = [];

        // Create Ivan Ivanovich
        $users[] = $gen->create_user([
            'firstname' => 'Ivan',
            'lastname' => 'Ivanovich',
            'username' => 'iamthebest',
            'email' => 'notaspy@kgb.org',
        ]);

        // Create Boris Ivanovich
        $users[] = $gen->create_user([
            'firstname' => 'Boris',
            'lastname' => 'Ivanovich',
            'username' => 'bi123',
            'email' => 'bbb@example.com',
        ]);

        // Create John Smith
        $users[] = $gen->create_user([
            'firstname' => 'John',
            'lastname' => 'Smith',
            'username' => 'js12345',
            'email' => 'js12345@gmail.com',
        ]);

        // Create John Doe
        $users[] = $gen->create_user([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'username' => 'JD',
            'email' => 'jdfirst@gmail.com',
        ]);

        // Create Jane Doe
        $users[] = $gen->create_user([
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'username' => 'JD2',
            'email' => 'jdesecond@gmail.com',
        ]);

        return $users;
    }

    /**
     * Create n users
     *
     * @param int $n Number of users
     * @return \stdClass[]
     */
    protected function generate_n_users(int $n = 50) {
        $i = 1;

        $users = [];

        do {
            $users[] = $this->getDataGenerator()->create_user();

            $i++;
        } while ($i <= $n);

        return $users;
    }
}
