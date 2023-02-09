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

use totara_msteams\botfw\entity\bot as bot_entity;
use totara_msteams\botfw\entity\channel as channel_entity;
use totara_msteams\botfw\entity\tenant as tenant_entity;
use totara_msteams\botfw\entity\user as user_entity;
use totara_msteams\botfw\exception\user_not_found_exception;
use totara_msteams\botfw\notification\subscription;

class totara_msteams_botfw_notification_subscription_testcase extends advanced_testcase {
    /** @var stdClass */
    private $user;

    /** @var subscription */
    private $subscription;

    public function setUp(): void {
        $this->user = $this->getDataGenerator()->create_user(['firstname' => 'Bob']);
        $record = (object)[
            'id' => 42,
            'conversation_id' => 'a:k1a0RA-_-koUT0u',
            'userid' => $this->user->id,
            'lang' => 'en',
            'teams_id' => '29:K1aKahAN3wzEa1ANd',
            'channel_id' => '19:kIa0RAkoUt0u',
            'tenant_id' => '31415926-5358-9793-2384-626433832795',
            'bot_id' => '28:tH1siSAb0t',
            'bot_name' => 'mybot',
            'service_url' => 'https://example.com/api',
        ];
        $this->subscription = subscription::from_record($record);
    }

    public function tearDown(): void {
        $this->user = null;
        $this->subscription = null;
    }

    public function test_from_record() {
        $this->assertEquals(42, $this->subscription->get_id());
        $this->assertEquals('a:k1a0RA-_-koUT0u', $this->subscription->get_conversation_id());
        $this->assertEquals($this->user->id, $this->subscription->get_userid());
        $this->assertEquals('29:K1aKahAN3wzEa1ANd', $this->subscription->get_teams_id());
        $this->assertEquals('19:kIa0RAkoUt0u', $this->subscription->get_channel_id());
        $this->assertEquals('31415926-5358-9793-2384-626433832795', $this->subscription->get_tenant_id());
        $this->assertEquals('28:tH1siSAb0t', $this->subscription->get_bot_id());
        $this->assertEquals('mybot', $this->subscription->get_bot_name());
        $this->assertEquals('https://example.com/api', $this->subscription->get_service_url());
    }

    public function test_get_user() {
        $this->assertEquals((object)['firstname' => 'Bob'], $this->subscription->get_user('firstname'));
        $rp = new ReflectionProperty($this->subscription, 'userid');
        $rp->setAccessible(true);
        $rp->setValue($this->subscription, $this->user->id + 1);
        try {
            $user = $this->subscription->get_user('*');
            $this->fail('user_not_found_exception expected');
        } catch (user_not_found_exception $ex) {
        }
    }

    public function test_get_msuser() {
        try {
            $user = $this->subscription->get_msuser();
            $this->fail('user_not_found_exception expected');
        } catch (user_not_found_exception $ex) {
        }

        $msbot = new bot_entity();
        $msbot->bot_id = '28:tH1siSAb0t';
        $msbot->bot_name = 'mybot';
        $msbot->service_url = 'https://example.com/api';
        $msbot->save();
        $mstenant = new tenant_entity();
        $mstenant->tenant_id = '31415926-5358-9793-2384-626433832795';
        $mstenant->save();
        $mschannel = new channel_entity();
        $mschannel->channel_id = '19:kIa0RAkoUt0u';
        $mschannel->save();
        $msuser = new user_entity();
        $msuser->verified = true;
        $msuser->userid = $this->user->id;
        $msuser->teams_id = '29:K1aKahAN3wzEa1ANd';
        $msuser->mschannelid = $mschannel->id;
        $msuser->save();

        $rp = new ReflectionProperty($this->subscription, 'id');
        $rp->setAccessible(true);
        $rp->setValue($this->subscription, $msuser->id);
        $this->assertEquals($msuser->id, $this->subscription->get_msuser()->id);
    }
}
