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

use totara_msteams\botfw\activity;
use totara_msteams\botfw\auth\token\bot_token;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\builder;
use totara_msteams\botfw\entity\bot as bot_entity;
use totara_msteams\botfw\entity\channel as channel_entity;
use totara_msteams\botfw\entity\subscription as subscription_entity;
use totara_msteams\botfw\entity\tenant as tenant_entity;
use totara_msteams\botfw\entity\user as user_entity;
use totara_msteams\botfw\notification\default_notification;
use totara_msteams\botfw\router\null_router;
use totara_msteams\my\helpers\notification_helper;

require_once(__DIR__.'/fixtures/lib.php');

class totara_msteams_notification_helper_testcase extends botfw_bot_base_testcase {
    /** @var null_router */
    private $router;

    /** @var default_notification */
    private $notification;

    /** @var stdClass */
    private $user;

    /** @var bot_entity */
    private $msbot;

    /** @var tenant_entity */
    private $mstenant;

    /** @var channel_entity */
    private $mschannel;

    /** @var user_entity */
    private $msuser;

    public function setUp(): void {
        $this->router = new null_router();
        $this->notification = new default_notification();

        parent::setUp();

        $this->mock_token();
        bot_token::refresh($this->bot);
        $this->client->reset();

        $activity = $this->mock_activity();
        $this->user = $this->getDataGenerator()->create_user(['firstname' => 'Robert', 'alternatename' => $activity->from->name]);
        $this->msbot = new bot_entity();
        $this->msbot->bot_id = $activity->recipient->id;
        $this->msbot->bot_name = $activity->recipient->name;
        $this->msbot->service_url = 'https://example.com/api';
        $this->msbot->save();
        $this->mstenant = new tenant_entity();
        $this->mstenant->tenant_id = $activity->conversation->tenantId;
        $this->mstenant->save();
        $this->mschannel = new channel_entity();
        $this->mschannel->channel_id = $activity->channelId;
        $this->mschannel->save();
        $this->msuser = new user_entity();
        $this->msuser->verified = true;
        $this->msuser->userid = $this->user->id;
        $this->msuser->teams_id = $activity->from->id;
        $this->msuser->mschannelid = $this->mschannel->id;
        $this->msuser->save();
    }

    public function tearDown(): void {
        $this->router = null;
        $this->notification = null;
        $this->user = null;
        $this->msbot = null;
        $this->mstenant = null;
        $this->mschannel = null;
        $this->msuser = null;
        parent::tearDown();
    }

    protected function create_bot(): bot {
        return builder::bot()
            ->router($this->router)
            ->authoriser($this->authoriser)
            ->client($this->client)
            ->resolver($this->resolver)
            ->notification($this->notification)
            ->storage($this->storage)
            ->validator($this->validator)
            ->logger($this->logger)
            ->build();
    }

    public function test_subscribe_and_reply() {

        $activity = $this->mock_activity();
        // 0: obtaining a conversation id, 1: the first notification, 2: the second notification
        $this->mock_response('{"id":"a:nEWc0n_v3-RSA_T10N"}');
        $this->mock_response();
        $this->mock_response();

        // Make sure we not send notification if bot is disable
        $this->bot->process_callback($activity, function (activity $activity, $x) {
            $this->assertEquals(0, subscription_entity::repository()->count());

            $result = notification_helper::subscribe_and_reply($this->bot, $activity, $this->msuser);
            $this->assertFalse($result);
            $this->assertEquals(0, subscription_entity::repository()->count());

            return true;
        });

        $requests = $this->client->get_requests();
        $this->assertCount(0, $requests);

        // Enable bot
        set_config('bot_feature_enabled', 1, 'totara_msteams');
        $this->bot->process_callback($activity, function (activity $activity, $x) {
            $this->assertEquals(0, subscription_entity::repository()->count());

            $result = notification_helper::subscribe_and_reply($this->bot, $activity, $this->msuser);
            $this->assertTrue($result);
            $this->assertEquals(1, subscription_entity::repository()->count());

            $result = notification_helper::subscribe_and_reply($this->bot, $activity, $this->msuser);
            $this->assertFalse($result);
            $this->assertEquals(1, subscription_entity::repository()->count());

            return true;
        });

        $requests = $this->client->get_requests();
        $this->assertCount(3, $requests);

        $data = json_decode($requests[1]->get_post_data());
        $this->assertEquals('message', $data->type);
        $this->assertEquals('a:nEWc0n_v3-RSA_T10N', $data->conversation->id);
        $this->assertEquals(get_string('botfw:msg_signin_done', 'totara_msteams', 'Bob'), $data->text);

        $data = json_decode($requests[2]->get_post_data());
        $this->assertEquals('message', $data->type);
        $this->assertEquals('a:nEWc0n_v3-RSA_T10N', $data->conversation->id);
        $this->assertEquals(get_string('botfw:msg_subscribe_already', 'totara_msteams', 'Bob'), $data->text);
    }
}
