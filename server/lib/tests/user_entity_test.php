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
 * @category test
 */

use core\entity\user;

defined('MOODLE_INTERNAL') || die();

class core_user_entity_testcase extends advanced_testcase {

    public function test_logged_in_users() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $logged_in_user = user::logged_in();
        $this->assertTrue(is_object($logged_in_user));

        $this->assertEquals($user->id, $logged_in_user->id);
    }

    public function test_non_logged_in_user() {
        $user = user::logged_in();
        $this->assertNull($user);
    }
}
