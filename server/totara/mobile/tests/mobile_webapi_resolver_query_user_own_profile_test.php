<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the totara current learning query resolver
 */
class totara_mobile_webapi_resolver_query_user_own_profile_testcase extends advanced_testcase {

    /**
     * Create some users for testing.
     *
     * @return []
     */
    private function create_faux_users(array $users = []) {
        $users = [];
        $u1 = $this->getDataGenerator()->create_user([
            'idnumber' => 'u1',
            'firstname' => 'Gregory',
            'lastname' => 'Smith',
            'middlename' => "Jeremiah",
            'alternatename' => "Grog",
            'email' => "greggy291@hotmail.com",
            'city' => "Wellington",
            'country' => "NZ",
            'timezone' => "99",
            'description' => "Great Glorious Greg",
            'webpage' => "www.gregsnothere.com",
            'skypeid' => "s456",
            'institution' => "Psyc",
            'department' => "Ward",
            'phone' => "123456789",
            'mobile' => "987654321",
            'address' => "123 numbers lane",
        ]);

        $u2 = $this->getDataGenerator()->create_user([
            'idnumber' => 'u2',
            'firstname' => 'Samantha',
            'lastname' => 'Crumpets',
        ]);

        // Allow U2 to see user details, this shouldn't affect seeing their own profile.
        $context = \context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $context);
        $this->getDataGenerator()->role_assign($roleid, $u2->id, $context->id);

        $u3 = $this->getDataGenerator()->create_user([
            'idnumber' => 'u3',
            'firstname' => 'Valentina',
            'lastname' => 'westson',
        ]);

        // Prohibit U3 from seeing user details, this shouldn't actually stop them seeing their own profile.
        $context = \context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('moodle/user:viewdetails', CAP_PROHIBIT, $roleid, $context);
        $this->getDataGenerator()->role_assign($roleid, $u3->id, $context->id);

        $users = [$u1, $u2, $u3];
        return $users;
    }

    /**
     * Test the results of the embedded mobile query through the GraphQL stack.
     */
    public function test_embedded_query() {
        global $PAGE;
        $users = $this->create_faux_users();

        $user = array_shift($users);
        $this->setUser($user->id);
        $result = \totara_webapi\graphql::execute_operation(
            \core\webapi\execution_context::create('mobile', 'totara_mobile_user_own_profile'),
            []
        );
        $data = $result->toArray()['data'];

        $expected = [
            'profile' => [
                'id' => "{$user->id}",
                'idnumber' => "{$user->idnumber}",
                'username' => "{$user->username}",
                'firstname' => "{$user->firstname}",
                'surname' => "{$user->lastname}",
                'middlename' => "{$user->middlename}",
                'alternatename' => "{$user->alternatename}",
                'email' => "{$user->email}",
                'profileimage' => null,
                'city' => "{$user->city}",
                'country' => get_string($user->country, 'countries'),
                'timezone' => core_date::get_localised_timezone(core_date::get_user_timezone($user->timezone)),
                'description' => "{$user->description}",
                'descriptionformat' => 'HTML',
                'webpage' => null,
                'skypeid' => "{$user->skype}",
                'institution' => "{$user->institution}",
                'department' => "{$user->department}",
                'phone' => "{$user->phone1}",
                'mobile' => "{$user->phone2}",
                'address' => "{$user->address}",
                '__typename' => 'totara_mobile_user'
            ]
        ];
        $this->assertSame($expected, $data);
    }
}
