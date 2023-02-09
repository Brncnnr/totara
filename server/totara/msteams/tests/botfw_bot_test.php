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

use totara_core\http\exception\auth_exception;
use totara_core\http\exception\bad_format_exception;
use totara_core\http\exception\request_exception;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\auth\token\bot_token;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\builder;
use totara_msteams\botfw\entity\bot as bot_entity;
use totara_msteams\botfw\entity\channel as channel_entity;
use totara_msteams\botfw\entity\tenant as tenant_entity;
use totara_msteams\botfw\entity\user as user_entity;
use totara_msteams\botfw\exception\bot_unavailable_exception;
use totara_msteams\botfw\exception\botfw_exception;
use totara_msteams\botfw\hook\null_hook;
use totara_msteams\botfw\notification\default_notification;
use totara_msteams\botfw\storage\memory_storage;

require_once(__DIR__.'/fixtures/lib.php');

class totara_msteams_botfw_bot_testcase extends botfw_bot_base_testcase {
    /** @var mock_router */
    private $router;

    /** @var mock_notification */
    private $notification;

    public function setUp(): void {
        $this->router = new mock_router();
        $this->notification = new mock_notification();
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->router = null;
        $this->notification = null;
    }

    /**
     * @inheritDoc
     */
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

    public function test_get_bot_id() {
        try {
            $this->bot->get_bot_id();
            $this->fail('bot_unavailable_exception expected');
        } catch (bot_unavailable_exception $ex) {
        }

        // get_bot_id() is available only inside process() or process_callback()
        $activity = $this->mock_activity();
        $this->bot->process_callback($activity, function ($x, $y) {
            $this->assertEquals('28:1aMAb0t', $this->bot->get_bot_id());
            return true;
        });
    }

    public function test_get_service_url() {
        try {
            $this->bot->get_service_url();
            $this->fail('bot_unavailable_exception expected');
        } catch (bot_unavailable_exception $ex) {
        }

        // get_service_url() is available only inside process() or process_callback()
        $activity = $this->mock_activity();
        $this->bot->process_callback($activity, function ($x, $y) {
            $this->bot->get_service_url();
            return true;
        });
    }

    public function test_get_hook() {
        $bot = builder::bot()->build();
        $rm = new ReflectionMethod($bot, 'get_hook');
        $rm->setAccessible(true);
        $hook = $rm->invoke($bot);
        // get_hook() returns null_hook if not set.
        $this->assertInstanceOf(null_hook::class, $hook);

        $bot->set_hook(new mock_hook());
        $hook = $rm->invoke($bot);
        $this->assertInstanceOf(mock_hook::class, $hook);
    }

    public function test_set_hook() {
        $bot = builder::bot()->build();
        $hook = new mock_hook();
        $bot->set_hook($hook);
        // set_hook() cannot be called twice
        try {
            $bot->set_hook(new mock_hook());
            $this->fail('botfw_exception expected');
        } catch (botfw_exception $ex) {
        }
        // Unless the same instance is passed.
        $bot->set_hook($hook);
    }

    public function test_get_account() {
        $this->mock_token();
        $this->router->silent();
        $activity = $this->mock_activity();
        $this->bot->process_callback($activity, function ($x, $y) {
            $account = $this->bot->get_account();
            $this->assertEquals('28:1aMAb0t', $account->id);
            $this->assertEquals('mybot', $account->name);
            return true;
        });

        // get_account() is available only inside process() or process_callback()
        try {
            $this->bot->get_account();
            $this->fail('bot_unavailable_exception expected');
        } catch (bot_unavailable_exception $ex) {
        }
    }

    public function test_get_access_token() {
        $method = new ReflectionMethod($this->bot, 'get_access_token');
        $method->setAccessible(true);
        $this->mock_token();
        $token = $method->invoke($this->bot);
        $this->assertEquals($this->valid_jwt, $token);

        // Load token from cache.
        $token = $method->invoke($this->bot);
        $this->assertEquals($this->valid_jwt, $token);
    }

    public function test_process() {
        $this->mock_token();
        bot_token::refresh($this->bot);

        $activity = $this->mock_activity();
        $this->router->silent();

        $this->client->reset();
        $this->hook->reset();
        $this->validator->header_result = true;
        $this->assertTrue($this->bot->process($activity, []));
        $this->assertSame($activity, $this->router->last_activity());
        $this->assertCount(0, $this->client->get_requests());
        $this->assertSame(1, $this->hook->opens);
        $this->assertSame(1, $this->hook->closes);

        // process() should return false when header validation fails
        $this->client->reset();
        $this->hook->reset();
        $this->validator->header_result = false;
        $this->assertFalse($this->bot->process($activity, []));
        $this->assertCount(0, $this->client->get_requests());
        $this->assertSame(1, $this->hook->opens);
        $this->assertSame(1, $this->hook->closes);

        // Test a route with the typing indicator
        $this->validator->header_result = true;
        $this->client->reset();
        $this->hook->reset();
        $this->router->silent(false);
        $this->mock_response(''); // null response for typing
        $this->assertTrue($this->bot->process($activity, []));
        $this->assertCount(1, $this->client->get_requests());
        $request = $this->client->get_requests()[0];
        $data = json_decode($request->get_post_data());
        $this->assertEquals('typing', $data->type);
        $this->assertSame($activity, $this->router->last_activity());
        $this->assertSame(1, $this->hook->opens);
        $this->assertSame(1, $this->hook->closes);
    }

    public function test_process_callback() {
        $this->mock_token();
        bot_token::refresh($this->bot);

        $this->router->silent();
        $activity = $this->mock_activity();
        // service_url is not updated when a callback function returns false.
        $this->assertFalse($this->bot->process_callback($activity, function ($x, $y) {
            return false;
        }));
        $msbot = bot_entity::repository()->find_by_id('28:1aMAb0t', true);
        $this->assertNotEquals('https://api.example.com/bot/', $msbot->service_url);

        // service_url is updated when a callback function returns true.
        $this->assertTrue($this->bot->process_callback($activity, function ($x, $y) {
            return true;
        }));
        $msbot = bot_entity::repository()->find_or_fail($msbot->id);
        $this->assertEquals('https://api.example.com/bot/', $msbot->service_url);

        // process_callback() does not allow re-entrance.
        $this->bot->process_callback($activity, function ($x, $y) {
            $activity = $this->mock_activity();
            try {
                $this->bot->process_callback($activity, function ($x, $y) {
                    return true;
                });
                $this->fail('bot_unavailable_exception expected');
            } catch (bot_unavailable_exception $ex) {
            }
            return true;
        });
    }

    public function test_reply_messaging_extension() {
        $this->mock_token();
        bot_token::refresh($this->bot);

        $this->router->silent();
        $activity = $this->mock_messaging_extension();
        $this->bot->process_callback($activity, function ($x, $y) {
            $message = builder::messaging_extension()
                ->text('kia ora')
                ->build();
            ob_start();
            $this->bot->reply_messaging_extension($message);
            $json = json_decode(ob_get_contents());
            ob_end_clean();
            $this->assertFalse(empty($json->composeExtension));
            $this->assertEquals('kia ora', $json->composeExtension->text);
            return true;
        });

        // reply_messaging_extension() is available only inside process() or process_callback()
        try {
            $message = builder::messaging_extension()
                ->text('kia ora')
                ->build();
            $this->bot->reply_messaging_extension($message);
            $this->fail('bot_unavailable_exception expected');
        } catch (bot_unavailable_exception $ex) {
        }
    }

    public function test_reply_to() {
        $this->mock_token();
        bot_token::refresh($this->bot);

        $this->router->silent();
        $activity = $this->mock_activity();
        $this->bot->process_callback($activity, function (activity $activity, $val) {
            $message = builder::message()
                ->conversation($activity->conversation)
                ->from($activity->recipient)
                ->recipient($activity->from)
                ->text('hey, sup bro?')
                ->build();

            $this->mock_response('', 200); // 1
            $this->mock_response('', 500); // 2
            $this->mock_response('', 400); // 3
            $this->mock_response('', 500); // 4
            $this->mock_response('', 400); // 5

            // 1
            $this->bot->reply_to($activity, $message);

            // 2
            try {
                $this->bot->reply_to($activity, $message);
                $this->fail('request_exception expected');
            } catch (request_exception $ex) {
            }

            // 3
            try {
                $this->bot->reply_to($activity, $message);
                $this->fail('auth_exception expected');
            } catch (auth_exception $ex) {
            }

            // 4
            $this->bot->reply_to($activity, $message, true);

            // 5
            $this->bot->reply_to($activity, $message, true);
            return true;
        });
    }

    public function test_reply_text_to() {
        $this->mock_token();
        bot_token::refresh($this->bot);

        $this->router->silent();
        $activity = $this->mock_activity();
        $this->bot->process_callback($activity, function (activity $activity, $val) {
            $this->client->reset();
            $this->mock_response();
            $this->bot->reply_text_to($activity, 'sup mate?');
            $this->assertCount(1, $this->client->get_requests());
            $request = $this->client->get_requests()[0];
            $data = json_decode($request->get_post_data());
            $this->assertEquals('a:k1a0RA-_-koUT0u', $data->conversation->id);
            $this->assertEquals('28:1aMAb0t', $data->from->id);
            $this->assertEquals('29:K1aKahAN3wzEa1ANd', $data->recipient->id);
            return true;
        });
    }

    public function test_invoke_rest_on_conversation() {
        $this->mock_token();
        bot_token::refresh($this->bot);

        $this->router->silent();
        $activity = $this->mock_activity();
        $this->bot->process_callback($activity, function ($x, $y) {
            $this->client->reset();
            $this->mock_response('yeah nah', 402);
            $response = $this->bot->invoke_rest_on_conversation('a:k1a0RA-_-koUT0u', 'lorem', 'ipsum');
            $this->assertCount(1, $this->client->get_requests());
            $request = $this->client->get_requests()[0];
            $this->assertEquals($this->resolver->conversation_url('https://api.example.com/bot/', 'a:k1a0RA-_-koUT0u', 'lorem', 'ipsum'), $request->get_url());
            $this->assertEquals(402, $response->get_http_code());
            $this->assertEquals('yeah nah', $response->get_body());
            return true;
        });

        // invoke_rest_on_conversation() is available only inside process() or process_callback()
        try {
            $this->mock_response('yeah', 200);
            $this->bot->invoke_rest_on_conversation('a:k1a0RA-_-koUT0u', 'lorem', 'ipsum');
            $this->fail('bot_unavailable_exception expected');
        } catch (bot_unavailable_exception $ex) {
        }
    }

    public function test_send_notification() {
        $this->mock_token();
        bot_token::refresh($this->bot);

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Bob']);
        $message = builder::message()
            ->text('kia kaha 2020')
            ->build();
        $this->assertFalse($this->bot->send_notification($user->id, $message));

        // Set up two subscription sets for a single user and make sure two notifications are sent.
        $this->notification->mock_subscription('28:1aMAb0t', 'https://example.com/bot/', 'a:k1a0RA-_-koUT0u', 'msteams', '31415926-5358-9793-2384-626433832795', '29:K1aKahAN3wzEa1ANd', $user->id);
        $this->notification->mock_subscription('28:An0ThEr', 'https://example.com/bot/', 'a:k1a0RA-_-koRUa', 'msteams', '01234567-89AB-CDEF-FEDC-BA9876543210', '29:K1AKahAA0t3Ar0a', $user->id);
        $this->mock_response();
        $this->mock_response();

        $this->client->reset();
        $message = builder::message()
            ->text('kia kaha 2020')
            ->build();
        $this->assertTrue($this->bot->send_notification($user->id, $message));
        $this->assertCount(2, $this->client->get_requests());

        $request = $this->client->get_requests()[0];
        $json = json_decode($request->get_post_data());
        $this->assertEquals('kia kaha 2020', $json->text);
        $this->assertEquals('a:k1a0RA-_-koUT0u', $json->conversation->id);
        $this->assertEquals('28:1aMAb0t', $json->from->id);
        $this->assertEquals('29:K1aKahAN3wzEa1ANd', $json->recipient->id);

        $request = $this->client->get_requests()[1];
        $json = json_decode($request->get_post_data());
        $this->assertEquals('kia kaha 2020', $json->text);
        $this->assertEquals('a:k1a0RA-_-koRUa', $json->conversation->id);
        $this->assertEquals('28:An0ThEr', $json->from->id);
        $this->assertEquals('29:K1AKahAA0t3Ar0a', $json->recipient->id);

        $this->assertSame(2, $this->hook->opens);
        $this->assertSame(2, $this->hook->closes);
    }

    private function set_up_subscription(): stdClass {
        $this->notification = new default_notification();
        $this->storage = new memory_storage();
        $this->bot = builder::bot()
            ->router($this->router)
            ->authoriser($this->authoriser)
            ->client($this->client)
            ->resolver($this->resolver)
            ->notification($this->notification)
            ->logger($this->logger)
            ->storage($this->storage)
            ->build();
        $this->bot->set_hook($this->hook);

        $this->mock_token();
        bot_token::refresh($this->bot);

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Bob']);
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
        $msuser->userid = $user->id;
        $msuser->teams_id = '29:K1aKahAN3wzEa1ANd';
        $msuser->mschannelid = $mschannel->id;
        $msuser->save();
        $this->assertTrue($this->bot->get_notification()->subscribe($msuser, $mstenant->tenant_id, $msbot->to_account()));
        return $user;
    }

    public function test_send_notification_message() {
        // this test requires real setup.
        $this->set_up_subscription();
        $msuser = user_entity::repository()->where('teams_id', '29:K1aKahAN3wzEa1ANd')->one(true);
        $subscription = $this->bot->get_notification()->get_subscription($msuser, '31415926-5358-9793-2384-626433832795', '19:kIa0RAkoUt0u', '28:tH1siSAb0t');
        $this->assertNotNull($subscription);

        $message = builder::message()
            ->text('Test notification')
            ->build();

        // Test a generic notification.
        $this->client->reset();
        $this->hook->reset();
        $this->mock_response('{"id":"a:k1a0RA-_-koUT0u"}');
        $this->mock_response();
        $this->bot->send_notification_message($subscription, $message);
        $requests = $this->client->get_requests();
        $this->assertCount(2, $requests);
        // Make sure conversation, from and recipient are filled by the bot framework.
        $data = json_decode($requests[1]->get_post_data());
        $this->assertEquals('message', $data->type);
        $this->assertEquals('Test notification', $data->text);
        $this->assertEquals('a:k1a0RA-_-koUT0u', $data->conversation->id);
        $this->assertEquals('28:tH1siSAb0t', $data->from->id);
        $this->assertEquals('29:K1aKahAN3wzEa1ANd', $data->recipient->id);
        $this->assertSame(1, $this->hook->opens);
        $this->assertSame(1, $this->hook->closes);

        // Test an alert notification.
        $this->client->reset();
        $this->hook->reset();
        $this->mock_response();
        $this->bot->send_notification_message($subscription, $message, true);
        $requests = $this->client->get_requests();
        $this->assertCount(1, $requests);
        // Make sure conversation, from and recipient are filled by the bot framework.
        $data = json_decode($requests[0]->get_post_data());
        $this->assertEquals('message', $data->type);
        $this->assertEquals('Test notification', $data->text);
        $this->assertEquals('a:k1a0RA-_-koUT0u', $data->conversation->id);
        $this->assertEquals('28:tH1siSAb0t', $data->from->id);
        $this->assertEquals('29:K1aKahAN3wzEa1ANd', $data->recipient->id);
        $this->assertTrue($data->channelData->notification->alert);
        $this->assertSame(1, $this->hook->opens);
        $this->assertSame(1, $this->hook->closes);

        // hook::open() and hook::close() should not be called when sending a notification inside process_callback().
        $this->client->reset();
        $this->hook->reset();
        $this->mock_response();
        $activity = $this->mock_activity();
        $this->bot->process_callback($activity, function ($x, $y) use ($subscription, $message) {
            $this->assertSame(1, $this->hook->opens);
            $this->assertSame(0, $this->hook->closes);
            $this->bot->send_notification_message($subscription, $message);
            $this->assertSame(1, $this->hook->opens);
            $this->assertSame(0, $this->hook->closes);
            return true;
        });
    }

    public function test_initiate_conversation() {
        global $DB;
        /** @var moodle_database $DB */

        // this test requires real setup.
        $user = $this->set_up_subscription();
        $subscriptions = $this->bot->get_notification()->get_subscriptions($user->id);
        $this->assertCount(1, $subscriptions);
        $subscription = $subscriptions[0];

        $method = new ReflectionMethod($this->bot, 'initiate_conversation');
        $method->setAccessible(true);

        $this->mock_response('{"error":"nah"}', 400);       // 1
        $this->mock_response('invalid json');               // 2
        $this->mock_response('{"invalid": "format"}');      // 3
        $this->mock_response('{"id":"a:k1a0RA-_-koUT0u"}'); // 4

        // 1
        try {
            $method->invoke($this->bot, $subscription, false);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
        }

        // 2
        try {
            $method->invoke($this->bot, $subscription, false);
            $this->fail('bad_format_exception expected');
        } catch (bad_format_exception $ex) {
        }

        // 3
        try {
            $method->invoke($this->bot, $subscription, false);
            $this->fail('bad_format_exception expected');
        } catch (bad_format_exception $ex) {
        }

        // 4
        $result = $method->invoke($this->bot, $subscription, false);
        $this->assertEquals('a:k1a0RA-_-koUT0u', $result);
        $this->assertEquals('a:k1a0RA-_-koUT0u', $DB->get_field('totara_msteams_subscription', 'conversation_id', ['id' => $subscription->get_id()], MUST_EXIST));

        // no HTTP request
        $result = $method->invoke($this->bot, $subscription, false);

        // renew conversation id
        $this->mock_response('{"id":"a:kAK1t3aNoaPop0"}');
        $result = $method->invoke($this->bot, $subscription, true);
        $this->assertEquals('a:kAK1t3aNoaPop0', $result);
        $this->assertEquals('a:kAK1t3aNoaPop0', $DB->get_field('totara_msteams_subscription', 'conversation_id', ['id' => $subscription->get_id()], MUST_EXIST));
    }
}
