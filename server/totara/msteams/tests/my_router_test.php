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

use totara_core\util\base64url;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\auth\default_authoriser;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\builder as botfw_builder;
use totara_msteams\botfw\entity\bot as bot_entity;
use totara_msteams\botfw\entity\channel as channel_entity;
use totara_msteams\botfw\entity\tenant as tenant_entity;
use totara_msteams\botfw\entity\user as user_entity;
use totara_msteams\botfw\entity\user_state as user_state_entity;
use totara_msteams\botfw\exception\unexpected_exception;
use totara_msteams\botfw\notification\default_notification;
use totara_msteams\botfw\resolver\v3_resolver;
use totara_msteams\botfw\storage\memory_storage;
use totara_msteams\botfw\validator\default_validator;
use totara_msteams\botfw\validator\validator;
use totara_msteams\my\dispatcher\cant_hear_you;
use totara_msteams\my\dispatcher\messaging_extension;
use totara_msteams\my\dispatcher\private_only;
use totara_msteams\my\dispatcher\show_help;
use totara_msteams\my\dispatcher\signin_request;
use totara_msteams\my\dispatcher\signout_request;
use totara_msteams\my\router as my_router;

require_once(__DIR__.'/fixtures/lib.php');

class totara_msteams_my_router_testcase extends botfw_jwks_base_testcase {
    /** @var stdClass */
    private $user;
    /** @var my_router */
    private $router;
    /** @var mock_botframework_client */
    private $client;
    /** @var mock_logger */
    private $logger;
    /** @var mock_hook */
    private $hook;
    /** @var bot */
    private $bot;
    /** @var string */
    private $bot_app_id;
    /** @var string */
    private $bot_app_secret;
    /** @var string */
    private $bot_local_name;
    /** @var string */
    private $bot_service_url;
    /** @var string */
    private $bot_post_url1;
    /** @var string */
    private $bot_post_url2;
    /** @var string */
    private $bot_access_token;
    /** @var string[] */
    private $bot_headers;
    /** @var string */
    private $ms_tenant_id;
    /** @var string */
    private $ms_conversation_id1;
    /** @var string */
    private $ms_conversation_id2;
    /** @var string */
    private $ms_bot_id;
    /** @var string */
    private $ms_user_id;
    /** @var string */
    private $ms_user_name;
    /** @var string */
    private $manifest_app_id;
    /** @var stdClass */
    private $course1;
    /** @var stdClass */
    private $course2;

    public function setUp(): void {
        global $PAGE;
        parent::setUp();

        $PAGE->set_context(context_system::instance());
        $PAGE->set_url('/totara/msteams/tests/my_router_test.php');

        $this->manifest_app_id = '27182818-2845-9045-2353-602874713526';
        $this->bot_app_id = '31622776-6016-8379-3319-988935444327';
        $this->bot_app_secret = 's33krit';
        $this->bot_local_name = 'totarabotrooten';
        $this->bot_service_url = 'https://api.bot.example.com';
        $this->ms_conversation_id1 = 'a:k1a0RA-_-koUT0u';
        $this->ms_conversation_id2 = 'a:k0R3RorErO_tAwaRA';
        $this->bot_post_url1 = $this->bot_service_url.'/v3/conversations/'.rawurlencode($this->ms_conversation_id1).'/activities';
        $this->bot_post_url2 = $this->bot_service_url.'/v3/conversations/'.rawurlencode($this->ms_conversation_id2).'/activities';
        $this->bot_access_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.e30.BMI_HUYbnGhqzQJclgarZIi-AvtIdZVwNWJFj6tJ9nc';
        $this->ms_tenant_id = '31415926-5358-9793-2384-626433832795';
        $this->ms_bot_id = '28:'.$this->bot_app_id;
        $this->ms_user_id = '29:K1aKahAN3wzEa1ANd6Ek1ND5tAys4Fe';
        $this->ms_user_name = 'Bob';

        set_config('bot_feature_enabled', 1, 'totara_msteams');
        set_config('messaging_extension_enabled', 1, 'totara_msteams');
        set_config('manifest_app_id', $this->manifest_app_id, 'totara_msteams');
        set_config('bot_app_id', $this->bot_app_id, 'totara_msteams');
        set_config('bot_app_secret', $this->bot_app_secret, 'totara_msteams');

        $this->user = $this->getDataGenerator()->create_user([
            'firstname' => 'Bob',
            'lastname' => 'Sponge',
            'alternatename' => 'Bobby',
        ]);
        $this->router = new my_router();
        $this->client = new mock_botframework_client();
        $this->logger = new mock_logger();
        $this->hook = new mock_hook();
        $this->bot = botfw_builder::bot()
            ->router($this->router)
            ->authoriser(new default_authoriser())
            ->client($this->client)
            ->resolver(new v3_resolver())
            ->notification(new default_notification())
            ->storage(new memory_storage())
            ->validator(new default_validator())
            ->logger($this->logger)
            ->build();
        $this->bot->set_hook($this->hook);
        $this->course1 = $this->getDataGenerator()->create_course([
            'fullname' => 'Culinary art',
            'shortname' => 'culinaryarts',
            'summary' => "Let's cook"
        ]);
        $this->course2 = $this->getDataGenerator()->create_course([
            'fullname' => 'Machine learning',
            'shortname' => 'ML101',
            'summary' => ''
        ]);

        $payload = [
            'serviceUrl' => $this->bot_service_url,
            'nbf' => time() - 10,
            'exp' => time() + 1000,
            'iss' => 'https://api.botframework.com',
            'aud' => $this->bot_app_id
        ];
        $this->bot_headers = ['Authorization' => 'Bearer '.$this->create_signed_jwt(256, $payload)];
        $json = json_encode([
            'access_token' => $this->bot_access_token,
            'token_type' => 'Bearer',
            'expires_in' => 3598
        ]);
        $this->client->mock('https://login.microsoftonline.com/botframework.com/oauth2/v2.0/token', $json);
        $this->client->mock($this->bot_post_url1, '');
        $this->client->mock($this->bot_post_url2, '');
        $json = json_encode([
            'issuer' => 'https://api.botframework.com',
            'authorization_endpoint' => 'https://invalid.example.com',
            'jwks_uri' => 'https://login.example.com/.well-known/keys',
            'id_token_signing_alg_values_supported' => ['RS256', 'RS384', 'RS512'],
            'token_endpoint_auth_methods_supported' => ['private_key_jwt']
        ]);
        $this->client->mock('https://login.botframework.com/v1/.well-known/openidconfiguration', $json);
        $this->client->mock('https://login.example.com/.well-known/keys', $this->jwks);
        $this->client->mock('https://api.bot.example.com/v3/conversations', json_encode(['id' => $this->ms_conversation_id2]));
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->user = null;
        $this->router = null;
        $this->client = null;
        $this->logger = null;
        $this->hook = null;
        $this->bot = null;
        $this->bot_app_id = null;
        $this->bot_app_secret = null;
        $this->bot_local_name = null;
        $this->bot_service_url = null;
        $this->bot_post_url1 = null;
        $this->bot_post_url2 = null;
        $this->bot_access_token = null;
        $this->bot_headers = null;
        $this->ms_tenant_id = null;
        $this->ms_conversation_id1 = null;
        $this->ms_conversation_id2 = null;
        $this->ms_bot_id = null;
        $this->ms_user_id = null;
        $this->ms_user_name = null;
        $this->manifest_app_id = null;
        $this->course1 = null;
        $this->course2 = null;
    }

    private function set_up_subscription(): void {
        $msbot = bot_entity::repository()->find_by_id($this->ms_bot_id) ?? new bot_entity();
        $msbot->bot_id = $this->ms_bot_id;
        $msbot->bot_name = $this->bot_local_name;
        $msbot->service_url = $this->bot_service_url;
        $msbot->save();
        $mstenant = new tenant_entity();
        $mstenant->tenant_id = $this->ms_tenant_id;
        $mstenant->save();
        $mschannel = new channel_entity();
        $mschannel->channel_id = 'msteams';
        $mschannel->save();
        $msuser = new user_entity();
        $msuser->verified = true;
        $msuser->userid = $this->user->id;
        $msuser->teams_id = $this->ms_user_id;
        $msuser->mschannelid = $mschannel->id;
        $msuser->save();
        $this->assertTrue($this->bot->get_notification()->subscribe($msuser, $mstenant->tenant_id, $msbot->to_account()));
    }

    /**
     * @param boolean $group
     * @return stdClass
     */
    private function mock_data_template(bool $group): stdClass {
        $time = new DateTime();
        $data = (object)[
            'timestamp' => $time->format('Y-m-d\TH:i:s.vZ'),
            'localTimestamp' => $time->format('Y-m-d\TH:i:s.vP'),
            'id' => 'f:'.rand(1000000, 9999999).rand(1000000, 9999999),
            'channelId' => 'msteams',
            'serviceUrl' => $this->bot_service_url,
            'channelData' => (object)[
                'tenant' => (object)[
                    'id' => $this->ms_tenant_id,
                ],
            ],
            'conversation' => (object)[
                'conversationType' => 'personal',
                'tenantId' => $this->ms_tenant_id,
                'id' => $this->ms_conversation_id1,
            ],
            'from' => (object)[
                'id' => $this->ms_user_id,
                'name' => $this->ms_user_name,
            ],
            'recipient' => (object)[
                'id' => $this->ms_bot_id,
                'name' => $this->bot_local_name,
            ],
            'locale' => 'en-GB',
            'entities' => [
                (object)[
                    'locale' => 'en-GB',
                    'country' => 'GB',
                    'platform' => 'Windows',
                    'type' => 'clientInfo',
                ]
            ],
        ];
        if ($group) {
            $data->conversation->isGroup = true;
            $data->conversation->conversationType = 'channel';
        } else {
            $data->conversation->conversationType = 'personal';
        }
        return $data;
    }

    /**
     * Create a message activity.
     *
     * @param boolean $group
     * @param string $text
     * @return activity
     */
    private function message(bool $group, string $text): activity {
        $data = $this->mock_data_template($group);
        $data->type = 'message';
        $data->textFormat = 'plain';
        $data->text = $text;
        return activity::from_object($data);
    }

    /**
     * Create a messaging extension activity.
     *
     * @param boolean $group
     * @param string $text
     * @return activity
     */
    private function messaging_extension(bool $group, string $text = ''): activity {
        $data = $this->mock_data_template($group);
        $data->name = 'composeExtension/query';
        $data->type = 'invoke';
        $data->channelData->source = (object)['name' => 'compose'];
        $data->value = (object)[
            'commandId' => 'searchCommand',
            'queryOptions' => (object)[
                'skip' => 0,
                'count' => 25
            ],
        ];
        if (empty($text)) {
            $data->value->parameters = [
                (object)[
                    'name' => 'initialRun',
                    'value' => 'true'
                ]
            ];
        } else {
            $data->value->parameters = [
                (object)[
                    'name' => 'search',
                    'value' => $text
                ]
            ];
        }
        return activity::from_object($data);
    }

    /**
     * Create a conversationUpdate activity.
     *
     * @return stdClass
     */
    private function mock_conversation_update_template(): stdClass {
        $time = new DateTime();
        return (object)[
            'timestamp' => $time->format('Y-m-d\TH:i:s.vZ'),
            'id' => 'f:'.rand(1000000, 9999999).rand(1000000, 9999999),
            'channelId' => 'msteams',
            'serviceUrl' => $this->bot_service_url,
            'channelData' => (object)[
                'tenant' => (object)[
                    'id' => $this->ms_tenant_id,
                ],
            ],
            'conversation' => (object)[
                'conversationType' => 'personal',
                'tenantId' => $this->ms_tenant_id,
                'id' => $this->ms_conversation_id1,
            ],
            'from' => (object)[
                'id' => $this->ms_user_name,
            ],
            'recipient' => (object)[
                'id' => $this->ms_bot_id,
                'name' => $this->bot_local_name,
            ],
            'type' => 'conversationUpdate',
        ];
    }

    /**
     * Create an activity containing the membersAdded event.
     *
     * @return activity
     */
    private function members_added(): activity {
        $data = $this->mock_conversation_update_template();
        $data->membersAdded = [
            (object)[
                'id' => $this->ms_user_id,
            ],
            (object)[
                'id' => $this->ms_bot_id,
            ]
        ];
        return activity::from_object($data);
    }

    /**
     * Create an activity containing the membersRemoved event.
     *
     * @return activity
     */
    private function members_removed(): activity {
        $data = $this->mock_conversation_update_template();
        $data->membersRemoved = [
            (object)[
                'id' => $this->ms_user_id,
            ],
            (object)[
                'id' => $this->ms_bot_id,
            ]
        ];
        return activity::from_object($data);
    }

    /**
     * Post an activity to a bot.
     *
     * @param activity $activity
     * @return stdClass
     */
    private function post_activity(activity $activity): stdClass {
        $this->client->reset();
        $this->assertTrue($this->bot->process($activity, $this->bot_headers));
        $request = $this->client->get_request($this->bot_post_url1);
        $headers = $request->get_headers();
        $this->assertContains('Authorization: Bearer '.$this->bot_access_token, $headers);
        $json = json_decode($request->get_post_data());
        $this->assertNotNull($json);
        $this->assertEquals($activity->id, $json->replyToId);
        $this->assertEquals($this->ms_conversation_id1, $json->conversation->id);
        $this->assertEquals($this->ms_bot_id, $json->from->id);
        $this->assertEquals($this->ms_user_id, $json->recipient->id);
        return $json;
    }

    /**
     * Post a message to a bot.
     *
     * @param string $message
     * @return stdClass
     */
    private function post_message(string $message, bool $group): stdClass {
        $activity = $this->message($group, $message);
        return $this->post_activity($activity);
    }

    public function data_send_message(): array {
        return [
            '??? personal' => [
                '???',
                false,
                get_string('botfw:msg_canthearyou', 'totara_msteams'),
                get_string('botfw:msg_canthearyou', 'totara_msteams'),
            ],
            'signout personal' => [
                get_string('botfw:cmd_signout', 'totara_msteams'),
                false,
                get_string('botfw:msg_signout_already', 'totara_msteams'),
                get_string('botfw:msg_signout_done', 'totara_msteams', 'Bobby'),
            ],
            'help team' => [
                get_string('botfw:cmd_help', 'totara_msteams'),
                true,
                get_string('botfw:msg_private', 'totara_msteams'),
                get_string('botfw:msg_private_name', 'totara_msteams', 'Bobby'),
            ],
            'signin team' => [
                get_string('botfw:cmd_signin', 'totara_msteams'),
                true,
                get_string('botfw:msg_private', 'totara_msteams'),
                get_string('botfw:msg_private_name', 'totara_msteams', 'Bobby'),
            ],
            'signout team' => [
                get_string('botfw:cmd_signout', 'totara_msteams'),
                true,
                get_string('botfw:msg_private', 'totara_msteams'),
                get_string('botfw:msg_private_name', 'totara_msteams', 'Bobby'),
            ],
            '??? team' => [
                '???',
                true,
                get_string('botfw:msg_private', 'totara_msteams'),
                get_string('botfw:msg_private_name', 'totara_msteams', 'Bobby'),
            ],
        ];
    }

    /**
     * Send a message.
     *
     * @param string $message
     * @param boolean $group
     * @param string $response
     * @dataProvider data_send_message
     */
    public function test_send_message(string $message, bool $group, string $response_out, string $response_in) {
        $json = $this->post_message($message, $group);
        $this->assertEquals('message', $json->type);
        $this->assertEquals($response_out, $json->text);

        $this->set_up_subscription();
        $json = $this->post_message($message, $group);
        $this->assertEquals('message', $json->type);
        $this->assertEquals($response_in, $json->text);
    }

    /**
     * Send show_help.
     */
    public function test_send_show_help_message(): void {
        $json = $this->post_message(get_string('botfw:cmd_help', 'totara_msteams'), false);
        
        $this->assertEquals('message', $json->type);
        $this->assertEquals(get_string('botfw:msg_help_title', 'totara_msteams'), $json->attachments[0]->content->title);
        $this->assertEquals(get_string('botfw:msg_help_body', 'totara_msteams'), $json->attachments[0]->content->text);
        $this->assertEquals(get_string('botfw:msg_signin_button', 'totara_msteams'), $json->attachments[0]->content->buttons[0]->title);
        $this->assertEquals(get_string('botfw:msg_signout_button', 'totara_msteams'), $json->attachments[0]->content->buttons[1]->title);
        $this->assertEquals(get_string('botfw:msg_help_button', 'totara_msteams'), $json->attachments[0]->content->buttons[2]->title);
    }

    /**
     * Send signin.
     */
    public function test_send_signin_message() {
        global $CFG;
        $json = $this->post_message(get_string('botfw:cmd_signin', 'totara_msteams'), false);
        $this->assertEquals('message', $json->type);
        $this->assertEquals(get_string('botfw:msg_signin', 'totara_msteams'), $json->attachments[0]->content->text);
        $this->assertEquals('signin', $json->attachments[0]->content->buttons[0]->type);
        $this->assertEquals(get_string('botfw:msg_signin_button', 'totara_msteams'), $json->attachments[0]->content->buttons[0]->title);
        $this->assertEquals(get_string('botfw:msg_signin_button', 'totara_msteams'), $json->attachments[0]->content->buttons[0]->text);
        $this->assertEquals($CFG->wwwroot.'/totara/msteams/botlogin.php', $json->attachments[0]->content->buttons[0]->value);

        $this->set_up_subscription();
        $json = $this->post_message(get_string('botfw:cmd_signin', 'totara_msteams'), false);
        $this->assertEquals('message', $json->type);
        $this->assertEquals(get_string('botfw:msg_signin_already', 'totara_msteams', 'Bobby'), $json->text);
    }

    /**
     * Simulate a situation where a user opens a chat window for the first time.
     */
    public function test_conversation_update_members_add() {
        $activity = $this->members_added();
        $this->assertTrue($this->bot->process($activity, $this->bot_headers));
        $request = $this->client->get_request($this->bot_post_url1);
        $headers = $request->get_headers();
        $this->assertContains('Authorization: Bearer '.$this->bot_access_token, $headers);
        $json = json_decode($request->get_post_data());
        $this->assertNotNull($json);
        $this->assertEquals($this->ms_user_id, $json->replyToId);
        $this->assertEquals($this->ms_conversation_id1, $json->conversation->id);
        $this->assertEquals($this->ms_bot_id, $json->from->id);
        $this->assertEquals($this->ms_user_id, $json->recipient->id);
        $this->assertEquals('message', $json->type);
        $this->assertEquals(get_string('botfw:msg_welcome', 'totara_msteams'), $json->attachments[0]->content->text);
        $this->assertEquals('messageBack', $json->attachments[0]->content->buttons[0]->type);
        $this->assertEquals(get_string('botfw:msg_signin_button', 'totara_msteams'), $json->attachments[0]->content->buttons[0]->title);
        $this->assertEquals(get_string('botfw:cmd_signin', 'totara_msteams'), $json->attachments[0]->content->buttons[0]->text);
        $this->assertEquals(get_string('botfw:cmd_signin', 'totara_msteams'), $json->attachments[0]->content->buttons[0]->displayText);
    }

    /**
     * Simulate a situation where a user removes a bot?
     */
    public function test_conversation_update_members_removed() {
        $activity = $this->members_removed();
        $this->assertTrue($this->bot->process($activity, $this->bot_headers));
        $requests = $this->client->get_requests();
        $this->assertArrayNotHasKey($this->bot_post_url1, $requests);
        $this->assertArrayNotHasKey($this->bot_post_url2, $requests);
    }

    public function data_messaging_extension_initial_run(): array {
        return [
            [false],
            [true]
        ];
    }

    /**
     * Simulate a situation where a user opens the messaging extension flyout.
     *
     * @dataProvider data_messaging_extension_initial_run
     */
    public function test_messaging_extension_initial_run(bool $group) {
        global $CFG;
        $this->setUser($this->user);
        $this->overrideLangString('botfw:mx_signin', 'totara_msteams', '516N 1N');

        $activity = $this->messaging_extension($group, '');
        ob_start();
        $this->assertTrue($this->bot->process($activity, $this->bot_headers));
        $output = ob_get_contents();
        ob_end_clean();
        $json = json_decode($output);
        $this->assertIsObject($json->composeExtension);
        $this->assertEquals('auth', $json->composeExtension->type);
        $this->assertEquals('openUrl', $json->composeExtension->suggestedActions->actions[0]->type);
        $this->assertEquals('516N 1N', $json->composeExtension->suggestedActions->actions[0]->title);
        $this->assertEquals($CFG->wwwroot.'/totara/msteams/botlogin.php', $json->composeExtension->suggestedActions->actions[0]->value);

        $this->set_up_subscription();
        $activity = $this->messaging_extension($group, '');
        ob_start();
        $this->assertTrue($this->bot->process($activity, $this->bot_headers));
        $output = ob_get_contents();
        ob_end_clean();
        $json = json_decode($output);
        $this->assertIsObject($json->composeExtension);
        $this->assertEquals('message', $json->composeExtension->type);
        $this->assertEquals(get_string('botfw:mx_initialrun', 'totara_msteams'), $json->composeExtension->text);
    }

    public function data_messaging_extension_search(): array {
        return [
            [false, false, false, true],
            [false, false, true, true],
            [false, true, false, false],
            [false, true, true, true],
            [true, false, false, true],
            [true, false, true, true],
            [true, true, false, false],
            [true, true, true, true],
        ];
    }

    /**
     * Simulate a situation where a user enters a search term into the messaging extension flyout.
     *
     * @param boolean $group
     * @param boolean $forcelogin
     * @param boolean $publishimage
     * @param boolean $hasimage
     * @dataProvider data_messaging_extension_search
     */
    public function test_messaging_extension_search(bool $group, bool $forcelogin, bool $publishimage, bool $hasimage) {
        global $CFG, $DB;
        if ($DB->get_dbfamily() == 'mssql') {
            $this->markTestSkipped("Skipped as catalog is not indexed properly in phpunit environment.");

            // To check that word 'art' present is in catalog:
            // var_dump($DB->get_records_sql("SELECT id, ftshigh FROM {catalog}", []));
            // To check that it cannot be found:
            // var_dump($DB->get_records_sql('SELECT * FROM FREETEXTTABLE({catalog},ftshigh, \'art\',LANGUAGE \'English\')', []));die();
            //
            // Run same queries in mssql client on phpunit tables and data will be found.
            // Repopulating indexes didn't help:
            // $DB->execute("ALTER FULLTEXT CATALOG {$prefix} search_catalog REBUILD");
            // $DB->execute('ALTER FULLTEXT INDEX ON {catalog} START FULL POPULATION');
        }

        $CFG->forcelogin = $forcelogin;
        $CFG->publishgridcatalogimage = $publishimage;

        $this->setUser($this->user);
        // Change the sign-in string so that it's comparable.
        $this->overrideLangString('botfw:mx_signin', 'totara_msteams', '516N 1N');

        $activity = $this->messaging_extension($group, 'art');
        ob_start();
        $this->assertTrue($this->bot->process($activity, $this->bot_headers));
        $output = ob_get_contents();
        ob_end_clean();
        $json = json_decode($output);
        $this->assertIsObject($json->composeExtension);
        $this->assertEquals('auth', $json->composeExtension->type);
        $this->assertEquals('openUrl', $json->composeExtension->suggestedActions->actions[0]->type);
        $this->assertEquals('516N 1N', $json->composeExtension->suggestedActions->actions[0]->title);
        $this->assertEquals($CFG->wwwroot.'/totara/msteams/botlogin.php', $json->composeExtension->suggestedActions->actions[0]->value);

        $context = rawurlencode(json_encode([
            'subEntityId' => base64url::encode(json_encode([
                'type' => 'openUrl',
                'value' => "{$CFG->wwwroot}/course/view.php?id={$this->course1->id}"
            ], JSON_UNESCAPED_SLASHES))
        ]));
        $deeplinkurl = "https://teams.microsoft.com/l/entity/{$this->manifest_app_id}/catalog?label=Culinary%20art&context={$context}";

        $this->set_up_subscription();
        $activity = $this->messaging_extension($group, 'art');
        ob_start();
        $this->assertTrue($this->bot->process($activity, $this->bot_headers));
        $output = ob_get_contents();
        ob_end_clean();
        $json = json_decode($output);
        $this->assertIsObject($json->composeExtension);
        $this->assertEquals('result', $json->composeExtension->type);
        $this->assertEquals('list', $json->composeExtension->attachmentLayout);
        $this->assertCount(1, $json->composeExtension->attachments);
        $this->assertStringContainsString('Culinary art', $json->composeExtension->attachments[0]->content->title);
        $this->assertEquals('Courses', $json->composeExtension->attachments[0]->content->subtitle);
        $this->assertStringContainsString("Let's cook", $json->composeExtension->attachments[0]->content->text);
        $this->assertCount(1, $json->composeExtension->attachments[0]->content->buttons);
        $this->assertEquals('openUrl', $json->composeExtension->attachments[0]->content->buttons[0]->type);
        $this->assertEquals(get_string('catalog_go_to_course', 'moodle'), $json->composeExtension->attachments[0]->content->buttons[0]->title);
        $this->assertEquals(get_string('catalog_go_to_course', 'moodle'), $json->composeExtension->attachments[0]->content->buttons[0]->text);
        $this->assertEquals($deeplinkurl, $json->composeExtension->attachments[0]->content->buttons[0]->value);
        $this->assertEquals($hasimage, isset($json->composeExtension->attachments[0]->content->images[0]));

        $activity = $this->messaging_extension($group, 'blahblah');
        ob_start();
        $this->assertTrue($this->bot->process($activity, $this->bot_headers));
        $output = ob_get_contents();
        ob_end_clean();
        $json = json_decode($output);
        $this->assertIsObject($json->composeExtension);
        $this->assertEquals('message', $json->composeExtension->type);
        $this->assertEquals(get_string('botfw:mx_nomatches', 'totara_msteams'), $json->composeExtension->text);
    }

    /**
     * Simulate two factor authentication within a chat room.
     */
    public function test_signin_verify_personal() {
        $data = $this->mock_data_template(false);
        $data->type = 'invoke';
        $data->name = 'signin/verifyState';
        $data->value = (object)['state' => 'l0R3m1P5uM'];
        $activity = activity::from_object($data);
        try {
            $json = $this->post_activity($activity);
            $this->fail('unexpected_exception expected');
        } catch (unexpected_exception $ex) {
        }

        $userstate = new user_state_entity();
        $userstate->sesskey = sesskey();
        $userstate->userid = $this->user->id;
        $userstate->timeexpiry = time() + 1000;
        $userstate->timecreated = time();
        $userstate->verify_code = 'LoremIpsum';
        $userstate->save();
        try {
            $json = $this->post_activity($activity);
            $this->fail('unexpected_exception expected');
        } catch (unexpected_exception $ex) {
        }

        $userstate->verify_code = 'l0R3m1P5uM';
        $userstate->save();
        $this->client->reset();
        $this->assertTrue($this->bot->process($activity, $this->bot_headers));

        // conversationid1 has the typing indicator.
        $request = $this->client->get_request($this->bot_post_url1);
        $headers = $request->get_headers();
        $this->assertContains('Authorization: Bearer '.$this->bot_access_token, $headers);
        $json = json_decode($request->get_post_data());
        $this->assertNotNull($json);
        $this->assertEquals($activity->id, $json->replyToId);
        $this->assertEquals('typing', $json->type);

        // conversationid2 has the notification.
        $request = $this->client->get_request($this->bot_post_url2);
        $headers = $request->get_headers();
        $this->assertContains('Authorization: Bearer '.$this->bot_access_token, $headers);
        $json = json_decode($request->get_post_data());
        $this->assertNotNull($json);
        $this->assertEquals($this->ms_conversation_id2, $json->conversation->id);
        $this->assertEquals($this->ms_bot_id, $json->from->id);
        $this->assertEquals($this->ms_user_id, $json->recipient->id);
        $this->assertEquals('message', $json->type);
        $this->assertEquals(get_string('botfw:msg_signin_done', 'totara_msteams', 'Bobby'), $json->text);
    }

    /**
     * Simulate two factor authentication within a group.
     * The request must be ignored.
     */
    public function test_signin_verify_group() {
        $data = $this->mock_data_template(true);
        $data->type = 'invoke';
        $data->name = 'signin/verifyState';
        $data->value = (object)['state' => 'l0R3m1P5uM'];
        $activity = activity::from_object($data);

        // Call validator::validate_header() to validate the JWT.
        $this->assertTrue($this->bot->process_callback($activity, function (activity $activity, validator $validator) {
            return $validator->validate_header($this->bot, $this->bot_headers);
        }));
        $this->client->reset();

        // The actual request must be ignored.
        $this->assertTrue($this->bot->process($activity, $this->bot_headers));
        $this->assertEmpty($this->client->get_requests());
    }

    public function data_dispatcher_classes(): array {
        return [
            [signin_request::class, get_string('botfw:msg_signin_already', 'totara_msteams', 'Bobby')],
            [signout_request::class, get_string('botfw:msg_signout_done', 'totara_msteams', 'Bobby')],
            [cant_hear_you::class, get_string('botfw:msg_canthearyou', 'totara_msteams')],
            [private_only::class, get_string('botfw:msg_private_name', 'totara_msteams', 'Bobby')],
        ];
    }

    /**
     * Test the direct_dispatch function by passing a dispatcher class.
     *
     * @param string $classname
     * @param string $expectedmessage
     * @dataProvider data_dispatcher_classes
     */
    public function test_direct_dispatch_success(string $classname, string $expectedmessage) {
        $this->set_up_subscription();
        $activity = $this->message(false, '');
        $result = $this->bot->process_callback($activity, function(activity $activity, $x) use ($classname) {
            return $this->router->direct_dispatch($classname, $this->bot, $activity);
        });
        $this->assertTrue($result);
        $request = $this->client->get_request($this->bot_post_url1);
        $headers = $request->get_headers();
        $this->assertContains('Authorization: Bearer '.$this->bot_access_token, $headers);
        $json = json_decode($request->get_post_data());
        $this->assertEquals('message', $json->type);
        if ($classname == show_help::class) {
            $this->assertEquals($expectedmessage, $json->attachments[0]->content->title);
        } else {
            $this->assertEquals($expectedmessage, $json->text);
        }
    }

    /**
     * @retrun void
     */
    public function test_direct_dispatch_success_for_help_command(): void {
        $this->set_up_subscription();
        $activity = $this->message(false, '');
        $result = $this->bot->process_callback($activity, function(activity $activity, $x)  {
            return $this->router->direct_dispatch(show_help::class, $this->bot, $activity);
        });
        $this->assertTrue($result);
        $request = $this->client->get_request($this->bot_post_url1);
        $json = json_decode($request->get_post_data());
        $this->assertEquals('message', $json->type);
        $this->assertEquals(get_string('botfw:msg_help_title', 'totara_msteams'), $json->attachments[0]->content->title);
    }

    /**
     * Test the direct_dispatch function by passing a bogus class.
     */
    public function test_direct_dispatch_fail() {
        $activity = $this->message(false, '');
        $result = $this->bot->process_callback($activity, function(activity $activity, $x) {
            // Pass a bogus class.
            return $this->router->direct_dispatch(self::class, $this->bot, $activity);
        });
        $this->assertFalse($result);
        $this->assertEmpty($this->client->get_requests());
    }

    /**
     * Test the direct_dispatch function by passing the messaging_extension class.
     */
    public function test_direct_dispatch_messaging_extension() {
        $this->set_up_subscription();
        $activity = $this->messaging_extension(false);
        $this->hook->reset();
        $this->bot->process_callback($activity, function (activity $activity, $x) {
            $this->assertEquals([], $this->hook->setusers);
            ob_start();
            $result = $this->router->direct_dispatch(messaging_extension::class, $this->bot, $activity);
            ob_end_clean();
            $this->assertTrue($result);
            // The messaging_extension class should call set_user() during the catalogue retrieval process.
            $this->assertEquals([$this->user->id], $this->hook->setusers);
            return true;
        });
    }

    /**
     * Test the direct_dispatch function by passing the messaging_extension class with sign-in workflow.
     */
    public function test_direct_dispatch_messaging_extension_with_signin() {
        $this->set_up_subscription();
        user_entity::repository()->delete();

        $activity = $this->messaging_extension(false);
        $this->hook->reset();
        $this->bot->process_callback($activity, function (activity $activity, $x) {
            // Let the bot send a signin card.
            ob_start();
            $result = $this->router->direct_dispatch(messaging_extension::class, $this->bot, $activity);
            $data = json_decode(ob_get_contents());
            ob_end_clean();
            $this->assertTrue($result);
            $this->assertEquals('auth', $data->composeExtension->type);
            $this->assertEquals('Sign in', $data->composeExtension->suggestedActions->actions[0]->title);
            $this->assertEquals([], $this->hook->setusers);

            // Prepare 2FA.
            $userstate = new user_state_entity();
            $userstate->sesskey = sesskey();
            $userstate->verify_code = 'LoremIpsum';
            $userstate->timeexpiry = time() + 42;
            $userstate->timecreated = time();
            $userstate->userid = $this->user->id;
            $userstate->save();

            // Verification fails.
            $this->logger->reset();
            $activity = $this->messaging_extension(false);
            $activity->value->state = 'InvalidCode';
            ob_start();
            $result = $this->router->direct_dispatch(messaging_extension::class, $this->bot, $activity);
            $data = json_decode(ob_get_contents());
            ob_end_clean();
            $this->assertTrue($result);
            $this->assertEquals('auth', $data->composeExtension->type);
            $this->assertEquals('Something went wrong. Please try again.', $data->composeExtension->suggestedActions->actions[0]->title);
            $this->assertCount(1, $this->logger->debugs);
            $this->assertStringContainsString('Sign-in failed', $this->logger->debugs[0]);
            $this->assertEquals([], $this->hook->setusers);

            // Verification succeeds.
            $activity = $this->messaging_extension(false);
            $activity->value->state = 'LoremIpsum';
            ob_start();
            $result = $this->router->direct_dispatch(messaging_extension::class, $this->bot, $activity);
            $data = json_decode(ob_get_contents());
            ob_end_clean();
            $this->assertTrue($result);
            $this->assertEquals('message', $data->composeExtension->type);
            $this->assertEquals('Browse the Totara catalogue to share learning content.', $data->composeExtension->text);

            // The messaging_extension class should call set_user() during the catalogue retrieval process.
            $this->assertEquals([$this->user->id], $this->hook->setusers);
            return true;
        });
    }
}
