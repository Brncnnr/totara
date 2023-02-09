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
 * @package core_user
 */

use core_user\totara_engage\share\recipient\user;
use totara_engage\share\recipient\recipient;

class core_user_engage_share_recipient_testcase extends advanced_testcase {

    public function test_search_user() {
        global $CFG;
        $this->setAdminUser();
        $admin_id = (int) get_admin()->id;

        $user1 = $this->getDataGenerator()->create_user([
            'firstname' => 'Bonny',
            'lastname' => 'Driver',
            'middlename' => 'user_one',
            'firstnamephonetic' => 'user_one',
            'lastnamephonetic' => 'user_one',
            'alternatename' => 'user_one'
        ]);

        $user2 = $this->getDataGenerator()->create_user([
            'firstname' => 'Adam',
            'lastname' => 'Trip',
            'middlename' => 'user_two',
            'firstnamephonetic' => 'user_two',
            'lastnamephonetic' => 'user_two',
            'alternatename' => 'user_two'
        ]);

        $user3 = $this->getDataGenerator()->create_user([
            'firstname' => 'Xavier',
            'lastname' => 'Bornham',
            'middlename' => 'user_three',
            'firstnamephonetic' => 'user_three',
            'lastnamephonetic' => 'user_three',
            'alternatename' => 'user_three'
        ]);

        $user4 = $this->getDataGenerator()->create_user(['firstname' => 'Adele', 'lastname' => 'Wert', 'deleted' => 1]);
        $user5 = $this->getDataGenerator()->create_user(['firstname' => 'Clyde', 'lastname' => 'Vera', 'confirmed' => 0]);

        $users = user::search('', null);
        $this->assertCount(4, $users);

        $recipient_ids = $this->get_recipient_ids($users);
        $expected_ids = [
            $user2->id,
            $admin_id,
            $user1->id,
            $user3->id
        ];

        sort($expected_ids);
        $this->assertEquals($expected_ids, $recipient_ids);

        // Now search for user fullname
        $users = user::search('Ad', null);
        $this->assertCount(2, $users);

        $recipient_ids = $this->get_recipient_ids($users);
        $this->assertEquals([$admin_id, $user2->id], $recipient_ids);

        // Also case insensitive
        $users = user::search('ad', null);
        $this->assertCount(2, $users);

        $recipient_ids = $this->get_recipient_ids($users);
        $this->assertEquals([$admin_id, $user2->id], $recipient_ids);

        require_once("{$CFG->dirroot}/totara/engage/tests/fixtures/totara_engage_mock_shareable.php");
        $instance = new totara_engage_mock_shareable(
            42,
            context_user::instance($user2->id)->id,
            $user2->id
        );

        $users = user::search('', $instance);
        $recipient_ids = $this->get_recipient_ids($users);

        $this->assertCount(3, $users);

        $expected_ids = [
            (int) $user1->id,
            (int) $user3->id,
            $admin_id
        ];

        sort($expected_ids);
        $this->assertEquals($expected_ids, $recipient_ids);
    }

    /**
     * @param recipient[] $users
     * @return array
     */
    private function get_recipient_ids(array $users): array {
        $recipient_ids = [];
        foreach ($users as $user) {
            $recipient_ids[] = $user->get_id();
        }

        sort($recipient_ids);
        return $recipient_ids;
    }
}