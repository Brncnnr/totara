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

defined('MOODLE_INTERNAL') || die();

use totara_msteams\botfw\account\channel_account;
use totara_msteams\my\helpers\user_helper;
use totara_msteams\botfw\entity\channel as channel_entity;
use totara_msteams\botfw\entity\tenant as tenant_entity;
use totara_msteams\botfw\entity\user as user_entity;

class totara_msteams_user_helper_testcase extends advanced_testcase {
    /** @var stdClass */
    private $user1;

    /** @var stdClass */
    private $user2;

    /** @var tenant_entity */
    private $mstenant;

    /** @var channel_entity */
    private $mschannel;

    /** @var user_entity */
    private $msuser1;

    /** @var user_entity */
    private $msuser2;

    public function setUp(): void {
        $this->user1 = $this->getDataGenerator()->create_user(['firstname' => 'Robert', 'alternatename' => 'Bobby']);
        $this->user2 = $this->getDataGenerator()->create_user(['firstname' => 'Robert', 'alternatename' => '']);
        $this->mstenant = new tenant_entity();
        $this->mstenant->tenant_id = '31415926-5358-9793-2384-626433832795';
        $this->mstenant->save();
        $this->mschannel = new channel_entity();
        $this->mschannel->channel_id = 'a:k1a0RA-_-koUT0u';
        $this->mschannel->save();
        $this->msuser1 = new user_entity();
        $this->msuser1->verified = true;
        $this->msuser1->userid = $this->user1->id;
        $this->msuser1->teams_id = '29:K1aKahAN3wzEa1ANd';
        $this->msuser1->mschannelid = $this->mschannel->id;
        $this->msuser1->save();
        $this->msuser2 = new user_entity();
        $this->msuser2->verified = true;
        $this->msuser2->userid = $this->user2->id;
        $this->msuser2->teams_id = '29:K1AKahAA0t3Ar0a';
        $this->msuser2->mschannelid = $this->mschannel->id;
        $this->msuser2->save();
    }

    public function tearDown(): void {
        $this->user1 = null;
        $this->user2 = null;
        $this->mstenant = null;
        $this->mschannel = null;
        $this->msuser1 = null;
        $this->msuser2 = null;
    }

    public function test_get_friendly_name() {
        $this->assertEquals('Bobby', user_helper::get_friendly_name($this->user1->id));
        $this->assertEquals('Bobby', user_helper::get_friendly_name($this->msuser1));
        $this->assertEquals('Robert', user_helper::get_friendly_name($this->user2->id));
        $this->assertEquals('Robert', user_helper::get_friendly_name($this->msuser2));
    }

    public function test_get_friendly_name_from_channel() {
        $account = channel_account::from_object((object)['name' => 'Rob']);
        $this->assertEquals('Rob', user_helper::get_friendly_name_from_channel($account));
        $account = channel_account::from_object((object)['name' => '']);
        $this->assertNull(user_helper::get_friendly_name_from_channel($account));
        $account = channel_account::from_object((object)[]);
        $this->assertNull(user_helper::get_friendly_name_from_channel($account));
        $account = channel_account::from_object((object)[]);
        $account->name = 42;
        $this->assertNull(user_helper::get_friendly_name_from_channel($account));
    }
}
