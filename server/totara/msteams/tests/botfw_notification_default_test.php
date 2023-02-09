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

use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\entity\bot;
use totara_msteams\botfw\entity\channel;
use totara_msteams\botfw\entity\subscription as subscription_entity;
use totara_msteams\botfw\entity\tenant;
use totara_msteams\botfw\entity\user;
use totara_msteams\botfw\notification\default_notification;
use totara_msteams\botfw\notification\notification;

class totara_msteams_botfw_notification_default_testcase extends advanced_testcase {
    /** @var notification */
    private $notification;

    /** @var stdClass */
    private $user;

    /** @var bot */
    private $msbot1;

    /** @var bot */
    private $msbot2;

    /** @var tenant */
    private $mstenant;

    /** @var channel */
    private $mschannel;

    /** @var user */
    private $msuser;

    public function setUp(): void {
        $this->notification = new default_notification();
        $this->user = $this->getDataGenerator()->create_user(['firstname' => 'Bob']);
        $this->msbot1 = new bot();
        $this->msbot1->bot_id = '28:tH1siSAb0t';
        $this->msbot1->bot_name = 'mybot';
        $this->msbot1->service_url = 'https://example.com/api';
        $this->msbot1->save();
        $this->msbot2 = new bot();
        $this->msbot2->bot_id = '28:tH1siSaN0tH3rb0t';
        $this->msbot2->bot_name = 'anotherbot';
        $this->msbot2->service_url = 'https://example.com/api';
        $this->msbot2->save();
        $this->mstenant = new tenant();
        $this->mstenant->tenant_id = '31415926-5358-9793-2384-626433832795';
        $this->mstenant->save();
        $this->mschannel = new channel();
        $this->mschannel->channel_id = '19:kIa0RAkoUt0u';
        $this->mschannel->save();
        $this->msuser = new user();
        $this->msuser->verified = true;
        $this->msuser->userid = $this->user->id;
        $this->msuser->teams_id = '29:K1aKahAN3wzEa1ANd';
        $this->msuser->mschannelid = $this->mschannel->id;
        $this->msuser->save();
        // Add a few more users just to confuse the system.
        for ($i = 0; $i < 2; $i++) {
            $this->getDataGenerator()->create_user();
        }
    }

    public function tearDown(): void {
        $this->notification = null;
        $this->user = null;
        $this->msbot1 = null;
        $this->msbot2 = null;
        $this->mstenant = null;
        $this->mschannel = null;
        $this->msuser = null;
    }

    public function test_subscribe_success() {
        $account1 = new channel_account();
        $account1->id = $this->msbot1->bot_id;
        $account1->name = $this->msbot1->bot_name;
        $this->assertTrue($this->notification->subscribe($this->msuser, $this->mstenant->tenant_id, $account1));
        $this->assertCount(1, subscription_entity::repository()->get()->all());
        $this->assertFalse($this->notification->subscribe($this->msuser, $this->mstenant->tenant_id, $account1));

        $account2 = new channel_account();
        $account2->id = $this->msbot2->bot_id;
        $account2->name = $this->msbot2->bot_name;
        $this->assertFalse($this->notification->subscribe($this->msuser, $this->mstenant->tenant_id, $account2));
        $this->assertCount(1, subscription_entity::repository()->get()->all());

        $this->assertTrue($this->notification->subscribe($this->msuser, '27182818-2845-9045-2353-602874713526', $account2));
        $this->assertCount(2, subscription_entity::repository()->get()->all());
    }

    public function test_subscribe_failure() {
        $this->expectException('totara_msteams\botfw\exception\bot_unavailable_exception');

        $account1 = new channel_account();
        $account1->id = 'nope';
        $account1->name = '???';
        $this->notification->subscribe($this->msuser, $this->mstenant->id, $account1);
    }

    public function test_unsubscribe() {
        $account1 = new channel_account();
        $account1->id = $this->msbot1->bot_id;
        $account1->name = $this->msbot1->bot_name;
        $this->assertTrue($this->notification->subscribe($this->msuser, $this->mstenant->tenant_id, $account1));
        $this->assertTrue($this->notification->unsubscribe($this->msuser, $this->mstenant->tenant_id));
        $this->assertFalse($this->notification->unsubscribe($this->msuser, $this->mstenant->tenant_id));
    }

    public function test_get_subscriptions_success() {
        $account1 = new channel_account();
        $account1->id = $this->msbot1->bot_id;
        $account1->name = $this->msbot1->bot_name;
        $this->assertTrue($this->notification->subscribe($this->msuser, $this->mstenant->tenant_id, $account1));

        $subscriptions = $this->notification->get_subscriptions($this->user->id);
        $this->assertCount(1, $subscriptions);
        $subscription = $subscriptions[0];
        $this->assertEquals($this->user->id, $subscription->get_userid());
        $this->assertEquals($this->msbot1->bot_id, $subscription->get_bot_id());
        $this->assertEquals($this->msbot1->bot_name, $subscription->get_bot_name());
        $this->assertEquals($this->msbot1->service_url, $subscription->get_service_url());
        $this->assertEquals($this->mstenant->tenant_id, $subscription->get_tenant_id());
        $this->assertEquals($this->mschannel->channel_id, $subscription->get_channel_id());
        $this->assertEquals($this->msuser->teams_id, $subscription->get_teams_id());

        $account2 = new channel_account();
        $account2->id = $this->msbot2->bot_id;
        $account2->name = $this->msbot2->bot_name;
        $this->assertTrue($this->notification->subscribe($this->msuser, '27182818-2845-9045-2353-602874713526', $account2));
        $subscriptions = $this->notification->get_subscriptions($this->user->id);
        $this->assertCount(2, $subscriptions);
        $subscription1 = current(array_filter($subscriptions, function ($e) {
            return $e->get_bot_id() === $this->msbot1->bot_id;
        }));
        $subscription2 = current(array_filter($subscriptions, function ($e) {
            return $e->get_bot_id() === $this->msbot2->bot_id;
        }));
        $this->assertEquals($this->user->id, $subscription1->get_userid());
        $this->assertEquals($this->msbot1->bot_id, $subscription1->get_bot_id());
        $this->assertEquals($this->msbot1->bot_name, $subscription1->get_bot_name());
        $this->assertEquals($this->msbot1->service_url, $subscription1->get_service_url());
        $this->assertEquals($this->mstenant->tenant_id, $subscription1->get_tenant_id());
        $this->assertEquals($this->mschannel->channel_id, $subscription1->get_channel_id());
        $this->assertEquals($this->msuser->teams_id, $subscription1->get_teams_id());
        $this->assertEquals($this->user->id, $subscription2->get_userid());
        $this->assertEquals($this->msbot2->bot_id, $subscription2->get_bot_id());
        $this->assertEquals($this->msbot2->bot_name, $subscription2->get_bot_name());
        $this->assertEquals($this->msbot2->service_url, $subscription2->get_service_url());
        $this->assertEquals('27182818-2845-9045-2353-602874713526', $subscription2->get_tenant_id());
        $this->assertEquals($this->mschannel->channel_id, $subscription2->get_channel_id());
        $this->assertEquals($this->msuser->teams_id, $subscription2->get_teams_id());
    }

    public function test_get_subscriptions_failure() {
        $this->expectException('totara_msteams\botfw\exception\user_not_found_exception');
        $this->notification->get_subscriptions(42);
    }

    public function test_subscription_update_conversation_id() {
        global $DB;
        /** @var moodle_database $DB */
        $account1 = new channel_account();
        $account1->id = $this->msbot1->bot_id;
        $account1->name = $this->msbot1->bot_name;
        $this->assertTrue($this->notification->subscribe($this->msuser, $this->mstenant->tenant_id, $account1));
        $subscriptions = $this->notification->get_subscriptions($this->user->id);
        $this->assertCount(1, $subscriptions);
        $subscription = $subscriptions[0];
        $this->assertEquals('', $subscription->get_conversation_id());
        $subscription->update_conversation_id('a:k1a0RA-_-koUT0u');
        $this->assertEquals('a:k1a0RA-_-koUT0u', $subscription->get_conversation_id());
        $conversation_id = $DB->get_field('totara_msteams_subscription', 'conversation_id', ['id' => $subscription->get_id()], MUST_EXIST);
        $this->assertEquals('a:k1a0RA-_-koUT0u', $conversation_id);
    }
}
