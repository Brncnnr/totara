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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

use core\orm\query\builder;
use totara_msteams\botfw\auth\default_authoriser;
use totara_msteams\botfw\entity\user_state;
use totara_msteams\userdata\userstate as user_data_state;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die;

/**
 * Test userdata\userstate class.
 */
class totara_msteams_userdata_userstate_testcase extends advanced_testcase {
    /** @var stdClass */
    private $user;

    public function setUp(): void {
        $this->user = $this->getDataGenerator()->create_user();
        $someone = $this->getDataGenerator()->create_user();

        $state_user = $this->create_user_state($this->user->id, 'mES3s$keY', 'k1A0RakoUT0u');
        $state_someone = $this->create_user_state($someone->id, 'a'.random_string(9), random_string());
        $state_noone = $this->create_user_state(null, 'b'.random_string(9));

        $this->assertEquals(3, builder::table('totara_msteams_user_state')->count());
        $this->assertEquals(1, builder::table('totara_msteams_user_state')->where('userid', $this->user->id)->count());
    }

    public function tearDown(): void {
        $this->user = null;
    }

    /**
     * @param integer|null $userid
     * @param string $sesskey
     * @param string $verifycode
     * @return user_state
     */
    private function create_user_state(?int $userid, string $sesskey, string $verifycode = ''): user_state {
        $userstate = new user_state();
        $userstate->sesskey = $sesskey;
        $userstate->timeexpiry = time() + 3600;
        $userstate->timecreated = time();
        $userstate->userid = $userid;
        $userstate->verify_code = $verifycode;
        $userstate->save();
        return $userstate;
    }

    public function test_purge() {
        $user = new target_user($this->user);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, user_data_state::execute_purge($user, context_system::instance()));
        $this->assertEquals(2, builder::table('totara_msteams_user_state')->count());
        $this->assertFalse(builder::table('totara_msteams_user_state')->where('userid', $this->user->id)->exists());
    }

    public function test_count() {
        $user = new target_user($this->user);
        $this->assertEquals(1, user_data_state::execute_count($user, context_system::instance()));
    }
}
