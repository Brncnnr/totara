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
use totara_msteams\botfw\entity\bot;
use totara_msteams\botfw\entity\channel;
use totara_msteams\botfw\entity\subscription;
use totara_msteams\botfw\entity\tenant;
use totara_msteams\botfw\entity\user;
use totara_msteams\userdata\user as user_data_user;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die;

/**
 * Test userdata\user class.
 */
class totara_msteams_userdata_user_testcase extends advanced_testcase {
    /** @var stdClass[] */
    private $users = [];
    /** @var stdClass[] */
    private $others = [];

    public function setUp(): void {
        $msbot = new bot();
        $msbot->bot_id = '28:1aMAb0t';
        $msbot->bot_name = 'mybot';
        $msbot->service_url = 'https://example.com/api';
        $msbot->save();
        $mstenant = new tenant();
        $mstenant->tenant_id = '31415926-5358-9793-2384-626433832795';
        $mstenant->save();
        $mschannel = new channel();
        $mschannel->channel_id = '19:kIa0RAkoUt0u';
        $mschannel->save();

        //               user1  user2  user3  user4  other1  other2  other3  other4
        //               -----  -----  -----  -----  ------  ------  ------  ------
        // msteams_user  yes    yes    yes    no     yes     yes     yes     no
        //  -> verified  yes    yes    no     no     yes     yes     no      no
        // subscription  yes    no     no     no     yes     no      no      no

        for ($i = 1; $i <= 4; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
            $this->others[$i] = $this->getDataGenerator()->create_user();
        }
        $teamusers = [$this->users[1], $this->users[2], $this->users[3], $this->others[1], $this->others[2], $this->others[3]];
        $verified = [true, true, false, true, true, false];
        foreach ($teamusers as $i => $user) {
            $msuser = new user();
            $msuser->verified = $verified[$i];
            $msuser->userid = $user->id;
            $msuser->teams_id = '29:K1aKahAN3wzEa1ANd'.$i;
            $msuser->mschannelid = $mschannel->id;
            $msuser->save();
            $teamusers[$i] = $msuser;
        }
        $subscribers = [$this->users[1], $this->others[1]];
        foreach ($subscribers as $i => $user) {
            $subscription = new subscription();
            $subscription->msbotid = $msbot->id;
            $subscription->mstenantid = $mstenant->id;
            $subscription->msuserid = $teamusers[$i * 3]->id;
            $subscription->save();
        }
        // Verify the integrity of the setup data.
        $teamsuser = function ($user): builder {
            return builder::table('totara_msteams_user')->where('userid', $user->id);
        };
        $subscription = function ($user): builder {
            return builder::table('totara_msteams_subscription', 's')->join(['totara_msteams_user', 'u'], 'msuserid', 'id')->where('u.userid', $user->id);
        };
        $this->assertEquals(1, $teamsuser($this->users[1])->count());
        $this->assertEquals(1, $teamsuser($this->users[2])->count());
        $this->assertEquals(1, $teamsuser($this->users[3])->count());
        $this->assertEquals(0, $teamsuser($this->users[4])->count());
        $this->assertEquals(1, $teamsuser($this->others[1])->count());
        $this->assertEquals(1, $teamsuser($this->others[2])->count());
        $this->assertEquals(1, $teamsuser($this->others[3])->count());
        $this->assertEquals(0, $teamsuser($this->others[4])->count());
        $this->assertEquals(1, $subscription($this->users[1])->count());
        $this->assertEquals(0, $subscription($this->users[2])->count());
        $this->assertEquals(0, $subscription($this->users[3])->count());
        $this->assertEquals(0, $subscription($this->users[4])->count());
        $this->assertEquals(1, $subscription($this->others[1])->count());
        $this->assertEquals(0, $subscription($this->others[2])->count());
        $this->assertEquals(0, $subscription($this->others[3])->count());
        $this->assertEquals(0, $subscription($this->others[4])->count());
    }

    public function tearDown(): void {
        $this->users = [];
        $this->others = [];
    }

    public function data_purge(): array {
        return [
            [1, [0, 1, 1, 0], [0, 0, 0, 0]],
            [2, [1, 0, 1, 0], [1, 0, 0, 0]],
            [3, [1, 1, 0, 0], [1, 0, 0, 0]],
            [4, [1, 1, 1, 0], [1, 0, 0, 0]]
        ];
    }

    /**
     * @dataProvider data_purge
     */
    public function test_purge(int $i, array $expected_users, array $expected_subscriptions) {
        $teamsuser = function ($user): builder {
            return builder::table('totara_msteams_user')->where('userid', $user->id);
        };
        $subscription = function ($user): builder {
            return builder::table('totara_msteams_subscription', 's')->join(['totara_msteams_user', 'u'], 'msuserid', 'id')->where('u.userid', $user->id);
        };
        $user = new target_user($this->users[$i]);
        $this->assertEquals(user_data_user::RESULT_STATUS_SUCCESS, user_data_user::execute_purge($user, context_system::instance()));
        $this->assertEquals($expected_users[0], $teamsuser($this->users[1])->count(), 'user1');
        $this->assertEquals($expected_users[1], $teamsuser($this->users[2])->count(), 'user2');
        $this->assertEquals($expected_users[2], $teamsuser($this->users[3])->count(), 'user3');
        $this->assertEquals($expected_users[3], $teamsuser($this->users[4])->count(), 'user4');
        $this->assertEquals($expected_subscriptions[0], $subscription($this->users[1])->count(), 'user1');
        $this->assertEquals($expected_subscriptions[1], $subscription($this->users[2])->count(), 'user2');
        $this->assertEquals($expected_subscriptions[2], $subscription($this->users[3])->count(), 'user3');
        $this->assertEquals($expected_subscriptions[3], $subscription($this->users[4])->count(), 'user4');
    }

    public function test_count() {
        $user1 = new target_user($this->users[1]);
        $this->assertEquals(1, user_data_user::execute_count($user1, context_system::instance()));
        $user2 = new target_user($this->users[2]);
        $this->assertEquals(1, user_data_user::execute_count($user2, context_system::instance()));
        $user3 = new target_user($this->users[3]);
        $this->assertEquals(1, user_data_user::execute_count($user3, context_system::instance()));
        $user4 = new target_user($this->users[4]);
        $this->assertEquals(0, user_data_user::execute_count($user4, context_system::instance()));
    }
}
