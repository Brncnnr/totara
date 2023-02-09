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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

use totara_core\entity\virtual_meeting_auth as virtual_meeting_auth_entity;
use totara_core\http\clients\matching_mock_client;
use totara_core\http\formdata;
use totara_core\http\method;
use totara_core\http\request;
use totara_core\http\response;
use totara_core\virtualmeeting\authoriser\oauth2_authoriser;
use totara_core\virtualmeeting\exception\auth_exception;
use virtualmeeting_poc_app\poc_factory;

/**
 * @group virtualmeeting
 * @coversDefaultClass totara_core\virtualmeeting\authoriser\oauth2_authoriser
 */
class totara_core_virtual_meeting_oauth2_testcase extends advanced_testcase {
    /**
     * @covers ::__construct
     */
    public function test_constructor(): void {
        $client = new matching_mock_client();
        $auth = new oauth2_authoriser($client, 'https://example.com/token', 'More Scooopes', ['apikey' => 'k1a0R4', 'secret' => 'K0u7oU']);
        $prop = new ReflectionProperty(oauth2_authoriser::class, 'client');
        $prop->setAccessible(true);
        $this->assertSame($client, $prop->getValue($auth));
        $prop = new ReflectionProperty(oauth2_authoriser::class, 'token_endpoint');
        $prop->setAccessible(true);
        $this->assertEquals('https://example.com/token', $prop->getValue($auth));
        $prop = new ReflectionProperty(oauth2_authoriser::class, 'formdata');
        $prop->setAccessible(true);
        /** @var formdata */
        $formdata = $prop->getValue($auth);
        $this->assertEquals('apikey=k1a0R4&secret=K0u7oU&scope=More+Scooopes', $formdata->as_string());
    }

    /**
     * @covers ::get_auth_redirect_url
     */
    public function test_get_auth_redirect_url(): void {
        global $CFG;
        $method = new ReflectionMethod(oauth2_authoriser::class, 'get_auth_redirect_url');
        $method->setAccessible(true);
        $this->assertEquals($CFG->wwwroot.'/integrations/virtualmeeting/auth_callback.php/poc_user', $method->invoke(null, 'poc_user'));
        // Uh-oh, get_auth_redirect_url() doesn't validate plugin's existence or availability
        poc_factory::toggle('poc_user', false);
        $this->assertEquals($CFG->wwwroot.'/integrations/virtualmeeting/auth_callback.php/poc_user', $method->invoke(null, 'poc_user'));
        $this->assertEquals($CFG->wwwroot.'/integrations/virtualmeeting/auth_callback.php/poc_none', $method->invoke(null, 'poc_none'));
    }

    /**
     * @covers ::make_login_url
     */
    public function test_make_login_url(): void {
        global $CFG;
        $url = oauth2_authoriser::make_login_url(
            'poc_user',
            'https://example.com/authorise',
            'K!a0R4koUT0u',
            'More Scooopes',
            [
                'Iam' => 'Cool',
                'client_id' => 'nah',
                'scope' => 'baa',
                'response_type' => 'boo',
                'redirect_uri' => 'https://example.com/noewhere',
            ]);
        $redirect_url = rawurlencode($CFG->wwwroot.'/integrations/virtualmeeting/auth_callback.php/poc_user');
        $this->assertEquals('https://example.com/authorise?Iam=Cool&client_id=K%21a0R4koUT0u&scope=More%20Scooopes&response_type=code&redirect_uri='.$redirect_url, $url);
        poc_factory::toggle('poc_user', false);
        try {
            oauth2_authoriser::make_login_url('poc_user', 'https://example.com/authorise', 'x', 'x', []);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('plugin not available: poc_user', $ex->getMessage());
        }
        try {
            oauth2_authoriser::make_login_url('poc_none', 'https://example.com/authorise', 'x', 'x', []);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('unknown plugin name: poc_none', $ex->getMessage());
        }
    }

    /**
     * @covers ::store
     */
    public function test_store(): void {
        $method = new ReflectionMethod(oauth2_authoriser::class, 'store');
        $method->setAccessible(true);
        $user = $this->getDataGenerator()->create_user();
        $entity = new virtual_meeting_auth_entity();
        $entity->plugin = 'poc_user';
        $entity->userid = $user->id;
        $entity->access_token = 'oLd70K3N';
        $entity->refresh_token = 'rEfR35h';
        $entity->timeexpiry = 999;
        $entity->save();
        $client = new matching_mock_client();
        $auth = new oauth2_authoriser($client, 'https://example.com/token', 'x', []);
        $json = json_encode([
            'access_token' => 'k1aoRa',
            'refresh_token' => 'A0t3aRo4',
        ]);
        $response = new response($json, 200, []);
        $method->invoke($auth, $entity, $response, 1111);
        $entity->refresh();
        $this->assertEquals('k1aoRa', $entity->access_token);
        $this->assertEquals('A0t3aRo4', $entity->refresh_token);
        $this->assertEquals(4709, $entity->timeexpiry);
        $json = json_encode([
            'access_token' => 'h3l1O',
            'expires_in' => '186',
            'refresh_token' => 'n3WzEa14Nd',
        ]);
        $response = new response($json, 200, []);
        $method->invoke($auth, $entity, $response, 7777);
        $entity->refresh();
        $this->assertEquals('h3l1O', $entity->access_token);
        $this->assertEquals('n3WzEa14Nd', $entity->refresh_token);
        $this->assertEquals(7963, $entity->timeexpiry);

        $errors = [
            303 => 'Request failed with 303',
            418 => 'Request failed with 418',
            501 => 'Request failed with 501',
            400 => 'Request failed with 400, Authentication failed',
            401 => 'Request failed with 401, Authentication failed',
        ];
        foreach ($errors as $code => $expected) {
            $response = new response('', $code, []);
            try {
                $method->invoke($auth, $entity, $response, 1111);
                $this->fail('auth_exception expected');
            } catch (auth_exception $ex) {
                $this->assertStringContainsString($expected, $ex->getMessage());
            }
        }

        $response = new response('{"refresh_token":"A0t3aRo4"}', 200, []);
        try {
            $method->invoke($auth, $entity, $response, 1111);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
            $this->assertStringContainsString('invalid access token response', $ex->getMessage());
        }

        $response = new response('{"access_token":"k1aoRa"}', 200, []);
        try {
            $method->invoke($auth, $entity, $response, 1111);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
            $this->assertStringContainsString('invalid access token response', $ex->getMessage());
        }
    }

    /**
     * @covers ::authorise
     */
    public function test_authorise(): void {
        $user = $this->getDataGenerator()->create_user();
        $entity = new virtual_meeting_auth_entity();
        $entity->plugin = 'poc_user';
        $entity->userid = $user->id;
        $entity->access_token = 'oLd70K3N';
        $entity->refresh_token = 'rEfR35h';
        $entity->timeexpiry = 999;
        $entity->save();
        $client = new matching_mock_client();
        $json = json_encode([
            'access_token' => 'k1aoRa',
            'refresh_token' => 'A0t3aRo4',
        ]);
        $matcher = function (request $request) {
            global $CFG;
            parse_str($request->get_post_data(), $data);
            return $request->get_url() === 'https://example.com/token'
                && $request->get_method() === method::POST
                && $data['apikey'] === 'Kee'
                && $data['scope'] === 'More Scooopes'
                && $data['code'] === 'tH1sISC0d3'
                && $data['grant_type'] === 'authorization_code'
                && $data['redirect_uri'] === $CFG->wwwroot.'/integrations/virtualmeeting/auth_callback.php/poc_user';
        };
        $client->add_response_callable($matcher, new response($json, 200, []));
        $auth = new oauth2_authoriser(
            $client,
            'https://example.com/token',
            'More Scooopes',
            [
                'apikey' => 'Kee',
                'code' => 'nah',
                'grant_type' => 'baa',
                'redirect_uri' => 'boo',
            ]);
        $auth->authorise($entity, 'tH1sISC0d3');
        $entity->refresh();
        $this->assertEquals('k1aoRa', $entity->access_token);
        $this->assertEquals('A0t3aRo4', $entity->refresh_token);
    }

    /**
     * @covers ::refresh
     */
    public function test_refresh(): void {
        $user = $this->getDataGenerator()->create_user();
        $entity = new virtual_meeting_auth_entity();
        $entity->plugin = 'poc_user';
        $entity->userid = $user->id;
        $entity->access_token = 'oLd70K3N';
        $entity->refresh_token = 'rEfR35h';
        $entity->timeexpiry = 999;
        $entity->save();
        $client = new matching_mock_client();
        $json = json_encode([
            'access_token' => 'k1aoRa',
            'refresh_token' => 'A0t3aRo4',
        ]);
        $matcher = function (request $request) {
            parse_str($request->get_post_data(), $data);
            return $request->get_url() === 'https://example.com/token'
                && $request->get_method() === method::POST
                && $data['apikey'] === 'Kee'
                && $data['scope'] === 'More Scooopes'
                && $data['refresh_token'] === 'rEfR35h'
                && $data['grant_type'] === 'refresh_token';
        };
        $client->add_response_callable($matcher, new response($json, 200, []));
        $auth = new oauth2_authoriser(
            $client,
            'https://example.com/token',
            'More Scooopes',
            [
                'apikey' => 'Kee',
                'refresh_token' => 'nah',
                'grant_type' => 'baa',
            ]);
        $auth->refresh($entity);
        $entity->refresh();
        $this->assertEquals('k1aoRa', $entity->access_token);
        $this->assertEquals('A0t3aRo4', $entity->refresh_token);
    }
}
