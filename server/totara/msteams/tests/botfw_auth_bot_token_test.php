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
use totara_core\http\response;
use totara_msteams\botfw\auth\token\bot_token;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\builder;
use totara_msteams\botfw\logger\stdout_logger;
use totara_msteams\botfw\router\null_router;
use totara_msteams\botfw\storage\memory_storage;
use totara_msteams\botfw\storage\storage;

require_once(__DIR__.'/fixtures/lib.php');

class totara_msteams_botfw_auth_bot_token_testcase extends advanced_testcase {
    /** @var string */
    private $valid_jwt;

    /** @var authoriser */
    private $authoriser;

    /** @var mock_client */
    private $client;

    /** @var storage */
    private $storage;

    /** @var bot */
    private $bot;

    public function setUp(): void {
        set_config('bot_app_id', '31622776-6016-8379-3319-988935444327', 'totara_msteams');
        set_config('bot_app_secret', 's33krit', 'totara_msteams');
        $this->valid_jwt = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJuYW1lIjoiQm9iIn0.BMI_HUYbnGhqzQJclgarZIi-AvtIdZVwNWJFj6tJ9nc';
        $this->authoriser = new mock_authoriser();
        $this->client = new mock_client();
        $this->storage = new memory_storage();
        $this->bot = builder::bot()
            ->router(new null_router())
            ->authoriser($this->authoriser)
            ->client($this->client)
            ->storage($this->storage)
            ->logger(new stdout_logger())
            ->build();
    }

    public function tearDown(): void {
        $this->valid_jwt = null;
        $this->authoriser = null;
        $this->client = null;
        $this->storage = null;
        $this->bot = null;
    }

    public function test_try_load_cache() {
        $this->assertNull(bot_token::try_load_cache($this->bot));
        $this->storage->bot_store('@access_token', (object)['timeexpiry' => time() - HOURSECS]);
        $this->assertNull(bot_token::try_load_cache($this->bot));
        $this->storage->bot_store('@access_token', (object)['timeexpiry' => time() + HOURSECS, 'token' => '']);
        $this->assertNull(bot_token::try_load_cache($this->bot));
        $this->storage->bot_store('@access_token', (object)['timeexpiry' => time() + HOURSECS, 'token' => $this->valid_jwt]);
        $this->assertInstanceOf(bot_token::class, bot_token::try_load_cache($this->bot));
    }

    public function test_refresh_success() {
        $json = json_encode([
            'access_token' => $this->valid_jwt,
            'token_type' => 'bEAReR',
            'expires_in' => 1234
        ]);
        $this->client->mock_queue(new response($json, 200, []));
        $token = bot_token::refresh($this->bot);
        $this->assertEquals($this->valid_jwt, $token->get());
        $data = $this->storage->bot_load('@access_token');
        $this->assertNotEmpty($data);
        /** @var \totara_msteams\botfw\internal\bot_access_token $data */
        $this->assertEquals($token->get(), $data->token);
        $this->assertEqualsWithDelta(1234, $data->timeexpiry - time(), 2);
    }

    public function test_refresh_failure() {
        $json = json_encode([
            'error' => 'unauthorized_client'
        ]);
        $this->client->mock_queue(new response($json, 400, []));
        try {
            bot_token::refresh($this->bot);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
        }

        $json = 'kia kaha';
        $this->client->mock_queue(new response($json, 200, []));
        try {
            bot_token::refresh($this->bot);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
        }

        // Empty access token.
        $json = json_encode([
            'access_token' => '',
            'token_type' => 'Bearer',
            'expires_in' => 1234
        ]);
        $this->client->mock_queue(new response($json, 200, []));
        try {
            bot_token::refresh($this->bot);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
        }

        // Invalid token type.
        $json = json_encode([
            'access_token' => $this->valid_jwt,
            'token_type' => 'who am i',
            'expires_in' => 1234
        ]);
        $this->client->mock_queue(new response($json, 200, []));
        try {
            bot_token::refresh($this->bot);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
        }

        // Time expired.
        $json = json_encode([
            'access_token' => 'eyJhbGciOiJub25lIn0.W10.bG9yZW1pcHN1bSE_',
            'token_type' => 'Bearer',
            'expires_in' => 3
        ]);
        $this->client->mock_queue(new response($json, 200, []));
        try {
            bot_token::refresh($this->bot);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
        }
    }

    public function test_refresh_with_custom_expiry() {
        global $CFG;
        $json = json_encode([
            'access_token' => $this->valid_jwt,
            'token_type' => 'bEAReR',
        ]);
        $this->client->mock_queue(new response($json, 200, []));
        $this->client->mock_queue(new response($json, 200, []));
        $this->client->mock_queue(new response($json, 200, []));
        $this->client->mock_queue(new response($json, 200, []));

        // unset should become default.
        unset($CFG->totara_msteams_token_expire);
        $token = bot_token::refresh($this->bot);
        $this->assertEquals($this->valid_jwt, $token->get());
        $data = $this->storage->bot_load('@access_token');
        $this->assertNotEmpty($data);
        /** @var \totara_msteams\botfw\internal\bot_access_token $data */
        $this->assertEquals($token->get(), $data->token);
        $this->assertEqualsWithDelta(3480, $data->timeexpiry - time(), 2);

        // 0 should become default.
        $CFG->totara_msteams_token_expire = 0;
        $token = bot_token::refresh($this->bot);
        $this->assertEquals($this->valid_jwt, $token->get());
        $data = $this->storage->bot_load('@access_token');
        $this->assertNotEmpty($data);
        /** @var \totara_msteams\botfw\internal\bot_access_token $data */
        $this->assertEquals($token->get(), $data->token);
        $this->assertEqualsWithDelta(3480, $data->timeexpiry - time(), 2);

        // 777 should be 777.
        $CFG->totara_msteams_token_expire = 777;
        $token = bot_token::refresh($this->bot);
        $this->assertEquals($this->valid_jwt, $token->get());
        $data = $this->storage->bot_load('@access_token');
        $this->assertNotEmpty($data);
        /** @var \totara_msteams\botfw\internal\bot_access_token $data */
        $this->assertEquals($token->get(), $data->token);
        $this->assertEqualsWithDelta(777, $data->timeexpiry - time(), 2);

        // The expiration time must be at least 30 seconds.
        $CFG->totara_msteams_token_expire = 29;
        try {
            $token = bot_token::refresh($this->bot);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
        }
    }
}
