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
 * @package virtualmeeting_msteams
 */

use core\entity\user as user_entity;
use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\entity\virtual_meeting_config as virtual_meeting_config_entity;
use totara_core\http\clients\matching_mock_client;
use totara_core\http\exception\auth_exception as http_auth_exception;
use totara_core\http\exception\bad_format_exception;
use totara_core\http\exception\request_exception;
use totara_core\http\method;
use totara_core\http\response;
use totara_core\virtualmeeting\dto\meeting_dto;
use totara_core\virtualmeeting\dto\meeting_edit_dto;
use totara_core\virtualmeeting\exception\auth_exception;
use totara_core\virtualmeeting\exception\meeting_exception;
use totara_core\virtualmeeting\exception\not_implemented_exception;
use totara_core\virtualmeeting\plugin\provider\provider;
use totara_core\virtualmeeting\user_auth;
use virtualmeeting_msteams\constants;
use virtualmeeting_msteams\providers\meeting;

/**
 * @coversDefaultClass virtualmeeting_msteams\providers\meeting
 */
class virtualmeeting_msteams_providers_meeting_testcase extends advanced_testcase {
    /** @var user_entity */
    private $user;

    public function setUp(): void {
        parent::setUp();
        $this->user = new user_entity($this->getDataGenerator()->create_user()->id);
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->user = null;
    }

    /**
     * @return user_auth
     */
    private function create_user_auth(): user_auth {
        return user_auth::create('msteams', $this->user, 'k1a0rak0ut0ukat0a', 'r3fr3sh', time() + DAYSECS);
    }

    /**
     * @return virtual_meeting_entity
     */
    private function create_virtual_meeting(): virtual_meeting_entity {
        $entity = new virtual_meeting_entity();
        $entity->plugin = 'msteams';
        $entity->userid = $this->user->id;
        $entity->save();
        return $entity;
    }

    /**
     * @return matching_mock_client
     */
    private function mock_client_with_creation_response(): matching_mock_client {
        $client = new matching_mock_client();
        $client->add_response(
            constants::MEETING_API_ENDPOINT,
            new response(
                json_encode([
                    'id' => 'totara314',
                    'joinWebUrl' => 'https://example.com/join/totara314',
                    'joinInformation' => [
                        'content' => 'data:text/html,<p>hello</p>',
                        'contentType' => 'Html',
                    ],
                ]
            ),
            200, []
        ), method::POST);
        return $client;
    }

    /**
     * @covers ::get_headers
     */
    public function test_get_headers(): void {
        $method = new ReflectionMethod(meeting::class, 'get_headers');
        $method->setAccessible(true);
        $client = new matching_mock_client();
        $entity = $this->create_virtual_meeting();
        $provider = new meeting($client);
        $dto = new meeting_dto($entity);
        try {
            $method->invoke($provider, $dto);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
            $this->assertStringContainsString('user is not authorised', $ex->getMessage());
        }
        $this->create_user_auth();
        $dto = new meeting_dto($entity);
        $headers = $method->invoke($provider, $dto);
        $expected = ['Authorization' => 'Bearer k1a0rak0ut0ukat0a', 'Accept-Language' => 'en'];
        $this->assertEquals($expected, $headers);
    }

    /**
     * @covers ::create_meeting
     */
    public function test_create_meeting_failure(): void {
        $client = new matching_mock_client();
        $entity = $this->create_virtual_meeting();
        $this->create_user_auth();
        $provider = new meeting($client);
        $dto = new meeting_edit_dto($entity, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $client->add_response(constants::MEETING_API_ENDPOINT, new response('', 400, []));
        try {
            $provider->create_meeting($dto);
            $this->fail('auth_exception expected');
        } catch (http_auth_exception $ex) {
        }
        $client->add_response(constants::MEETING_API_ENDPOINT, new response('', 501, []));
        try {
            $provider->create_meeting($dto);
            $this->fail('request_exception expected');
        } catch (request_exception $ex) {
        }
        $client->add_response(constants::MEETING_API_ENDPOINT, new response('not json', 200, []));
        try {
            $provider->create_meeting($dto);
            $this->fail('bad_format_exception expected');
        } catch (bad_format_exception $ex) {
        }
    }

    /**
     * @return array
     */
    public function data_api_responses(): array {
        return [
            'minimum' => [
                [
                    'id' => 'totara123',
                    'joinWebUrl' => 'https://example.com/join/totara123',
                ],
                [
                    'meeting_id' => 'totara123',
                    'join_url' => 'https://example.com/join/totara123',
                    'preview' => '',
                ]
            ],
            'all' => [
                [
                    'id' => 'totara123',
                    'joinWebUrl' => 'https://example.com/join/totara123',
                    'joinInformation' => [
                        'content' => 'data:text/html,<p>kia ora</p>',
                        'contentType' => 'Html',
                    ],
                ],
                [
                    'meeting_id' => 'totara123',
                    'join_url' => 'https://example.com/join/totara123',
                    'preview' => '<p>kia ora</p>',
                ]
            ]
        ];
    }

    /**
     * @param array $response
     * @param array $expected
     * @covers ::create_meeting
     * @dataProvider data_api_responses
     */
    public function test_create_meeting_success(array $response, array $expected): void {
        $client = new matching_mock_client();
        $entity = $this->create_virtual_meeting();
        $this->create_user_auth();
        $provider = new meeting($client);
        $dto = new meeting_edit_dto($entity, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $client->add_response(constants::MEETING_API_ENDPOINT, new response(json_encode($response), 200, []), method::POST);
        $provider->create_meeting($dto);
        foreach ($expected as $name => $expected_value) {
            $value = $dto->get_storage()->get($name, true);
            $this->assertSame($expected_value, $value, "storage->{$name}");
        }
    }

    /**
     * @param array $response
     * @param array $expected
     * @covers ::update_meeting
     * @dataProvider data_api_responses
     */
    public function test_update_meeting(array $response, array $expected): void {
        $client = $this->mock_client_with_creation_response();
        $entity = $this->create_virtual_meeting();
        $this->create_user_auth();
        $provider = new meeting($client);
        $dto = new meeting_edit_dto($entity, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $provider->create_meeting($dto);
        // When update request fails, the current room should not be deleted.
        $dto = new meeting_edit_dto($entity, 'update meeting', new DateTime('+3 hour'), new DateTime('+4 hour'));
        $client->add_response(constants::MEETING_API_ENDPOINT, new response(json_encode($response), 418, []));
        try {
            $provider->update_meeting($dto);
            $this->fail('request_exception expected');
        } catch (request_exception $ex) {
        }
        $this->assertSame('totara314', $dto->get_storage()->get('meeting_id', true));
        $this->assertSame('https://example.com/join/totara314', $dto->get_storage()->get('join_url', true));
        $this->assertSame('<p>hello</p>', $dto->get_storage()->get('preview', true));

        // When update request succeeds, the current room is overridden with new info.
        $dto = new meeting_edit_dto($entity, 'update meeting', new DateTime('+3 hour'), new DateTime('+4 hour'));
        $client->add_response(constants::MEETING_API_ENDPOINT, new response(json_encode($response), 200, []), method::POST);
        $client->add_response(constants::MEETING_API_ENDPOINT.'/totara314', new response('', 204, []), method::DELETE);
        $provider->update_meeting($dto);
        foreach ($expected as $name => $expected_value) {
            $value = $dto->get_storage()->get($name, true);
            $this->assertSame($expected_value, $value, "storage->{$name}");
        }
    }

    /**
     * @covers ::delete_meeting
     */
    public function test_delete_meeting(): void {
        $client = $this->mock_client_with_creation_response();
        $entity = $this->create_virtual_meeting();
        $this->create_user_auth();
        $provider = new meeting($client);
        $dto = new meeting_edit_dto($entity, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $provider->create_meeting($dto);
        $this->assertEquals(3, virtual_meeting_config_entity::repository()->count());
        $dto = new meeting_dto($entity);
        // Request error is completely ignored
        $client->add_response(constants::MEETING_API_ENDPOINT.'/totara314', new response('', 400, []), method::DELETE);
        $provider->delete_meeting($dto);
        $this->assertEquals(0, virtual_meeting_config_entity::repository()->count());
        // nothing happens when double deleting
        $provider->delete_meeting($dto);
    }

    /**
     * @covers ::get_join_url
     */
    public function test_get_join_url(): void {
        $client = $this->mock_client_with_creation_response();
        $entity = $this->create_virtual_meeting();
        $this->create_user_auth();
        $provider = new meeting($client);
        $dto = new meeting_edit_dto($entity, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $provider->create_meeting($dto);
        $dto = new meeting_dto($entity);
        $this->assertEquals('https://example.com/join/totara314', $provider->get_join_url($dto));
        $dto->get_storage()->delete('join_url');
        try {
            $provider->get_join_url($dto);
            $this->fail('meeting_exception expected');
        } catch (meeting_exception $ex) {
            $this->assertStringContainsString('join url not set', $ex->getMessage());
        }
    }

    /**
     * @param array $response
     * @param array $expected
     * @covers ::get_info
     * @dataProvider data_api_responses
     */
    public function test_get_info(array $response, array $expected): void {
        $client = new matching_mock_client();
        $entity = $this->create_virtual_meeting();
        $this->create_user_auth();
        $provider = new meeting($client);
        $dto = new meeting_edit_dto($entity, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $client->add_response(constants::MEETING_API_ENDPOINT, new response(json_encode($response), 200, []), method::POST);
        $provider->create_meeting($dto);
        $dto = new meeting_dto($entity);
        $this->assertEquals($expected['preview'], $provider->get_info($dto, provider::INFO_PREVIEW));
        $dto->get_storage()->delete('preview');
        try {
            $provider->get_info($dto, provider::INFO_PREVIEW);
            $this->fail('meeting_exception expected');
        } catch (meeting_exception $ex) {
            $this->assertStringContainsString('preview not set', $ex->getMessage());
        }
        try {
            $provider->get_info($dto, provider::INFO_HOST_URL);
            $this->fail('not_implemented_exception expected');
        } catch (not_implemented_exception $ex) {
        }
        try {
            $provider->get_info($dto, provider::INFO_INVITATION);
            $this->fail('not_implemented_exception expected');
        } catch (not_implemented_exception $ex) {
        }
    }
}
