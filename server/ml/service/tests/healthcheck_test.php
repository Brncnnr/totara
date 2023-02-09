<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_service
 */
defined('MOODLE_INTERNAL') || die();

use core_phpunit\testcase;
use ml_service\api;
use ml_service\healthcheck;
use totara_core\http\response;

class ml_service_healthcheck_testcase extends testcase {

    /**
     * Data provider for the test_invalid_state
     *
     * @return array
     */
    public function invalid_state_data(): array {
        return [
            [null, 'abcd12345', true],
            ['http://example.com', null, true],
            [null, null, true],
            [null, 'abcd12345', false],
            ['http://example.com', null, false],
            [null, null, false],
        ];
    }

    /**
     * @dataProvider invalid_state_data
     * @param string|null $url
     * @param string|null $key
     * @param bool $data_export
     * @return void
     */
    public function test_invalid_state(?string $url, ?string $key, bool $data_export): void {
        global $CFG;

        // Mocking the API object
        $mock_api = $this->createMock(api::class);

        $healthcheck = healthcheck::make($mock_api);
        $this->set_data_export_state($healthcheck, $data_export);

        self::assertEquals(healthcheck::STATE_UNKNOWN, $healthcheck->get_state_totara_to_service());
        self::assertSame(healthcheck::STATE_UNKNOWN, $healthcheck->get_state_service_to_totara());
        self::assertEmpty($healthcheck->get_troubleshooting());
        self::assertEmpty($healthcheck->get_service_info());

        // Assert no configured service
        $CFG->ml_service_url = $url;
        $CFG->ml_service_key = $key;
        $healthcheck->check_health();

        self::assertSame(healthcheck::STATE_UNHEALTHY, $healthcheck->get_state_totara_to_service());
        self::assertSame(healthcheck::STATE_UNKNOWN, $healthcheck->get_state_service_to_totara());

        $exp = [
            ($url ? '$CFG->ml_service_url is set to ' . $url : '$CFG->ml_service_url is not set'),
            ($key ? '$CFG->ml_service_key is set' : '$CFG->ml_service_key is not set'),
        ];
        if ($data_export) {
            $exp[] = 'Data export has been run';
        }

        self::assertEqualsCanonicalizing($exp, $healthcheck->get_totara_info());
        self::assertEmpty($healthcheck->get_service_info());

        $exp = [
            'The ml_service_url or ml_service_key configuration option have not been defined.',
        ];
        if (!$data_export) {
            $exp[] = 'Data export has not been run. Please check the script server/ml/recommender/cli/export_data.php';
        }
        self::assertEqualsCanonicalizing($exp, $healthcheck->get_troubleshooting());
    }

    /**
     * Test the behaviour when Totara cannot connect to the service
     */
    public function test_failed_communication(): void {
        global $CFG;

        // Mocking the API object
        $mock_api = $this->createMock(api::class);
        $CFG->ml_service_url = 'http://example.com';
        $CFG->ml_service_key = 'abcd1234';

        $healthcheck = healthcheck::make($mock_api);
        $this->set_data_export_state($healthcheck, true);

        $failed_response = new response(
            json_encode(['error' => 'Error message']),
            500,
            []
        );
        $mock_api->method('get')
            ->with('/health-check')
            ->willReturn($failed_response);

        $healthcheck->check_health();
        self::assertSame(healthcheck::STATE_UNHEALTHY, $healthcheck->get_state_totara_to_service());
        self::assertSame(healthcheck::STATE_UNKNOWN, $healthcheck->get_state_service_to_totara());
        self::assertEqualsCanonicalizing([
            'Service to Totara connection... Unknown',
            'Error message',
        ], $healthcheck->get_service_info());
        self::assertEmpty($healthcheck->get_troubleshooting());
    }

    /**
     * Test the behaviour when the service cannot connect back to Totara
     */
    public function test_service_failure(): void {
        global $CFG;

        // Mocking the API object
        $mock_api = $this->createMock(api::class);
        $CFG->ml_service_url = 'http://example.com';
        $CFG->ml_service_key = 'abcd1234';

        $healthcheck = healthcheck::make($mock_api);
        $this->set_data_export_state($healthcheck, true);

        $response = new response(
            json_encode([
                'success' => false,
                'errors' => ['Error 1', 'Error 2'],
                'totara' => [
                    'elapsed_seconds' => 9,
                    'totara_ip' => '127.0.0.1',
                    'url' => 'http://example.com/totara/site',
                ]
            ]),
            200,
            []
        );
        $mock_api->method('get')
            ->with('/health-check')
            ->willReturn($response);

        $healthcheck->check_health();
        self::assertSame(healthcheck::STATE_HEALTHY, $healthcheck->get_state_totara_to_service());
        self::assertSame(healthcheck::STATE_UNHEALTHY, $healthcheck->get_state_service_to_totara());
        self::assertEqualsCanonicalizing([
            '$CFG->ml_service_key is set',
            '$CFG->ml_service_url is set to http://example.com',
            'Data export has been run',
            'Totara to Service connection... Healthy',
        ], $healthcheck->get_totara_info());
        self::assertEqualsCanonicalizing([
            'Service to Totara connection... Unhealthy',
            'elapsed_seconds' => 9,
            'totara_ip' => '127.0.0.1',
            'url' => 'http://example.com/totara/site',
            'Error 1',
            'Error 2',
        ], $healthcheck->get_service_info());
        self::assertEqualsCanonicalizing([
            'The service reported problems and may not be healthy. Check the information above.',
        ], $healthcheck->get_troubleshooting());
    }

    /**
     * Test behaviour when service can connect back to totara
     */
    public function test_service_success(): void {
        global $CFG;

        // Mocking the API object
        $mock_api = $this->createMock(api::class);
        $CFG->ml_service_url = 'http://example.com';
        $CFG->ml_service_key = 'abcd1234';

        $healthcheck = healthcheck::make($mock_api);
        $this->set_data_export_state($healthcheck, true);

        $response = new response(
            json_encode([
                'success' => true,
                'totara' => [
                    'elapsed_seconds' => 12,
                    'totara_ip' => '127.0.0.1',
                    'url' => 'http://example.com/totara/site',
                ]
            ]),
            200,
            []
        );
        $mock_api->method('get')
            ->with('/health-check')
            ->willReturn($response);

        $healthcheck->check_health();
        self::assertSame(healthcheck::STATE_HEALTHY, $healthcheck->get_state_totara_to_service());
        self::assertSame(healthcheck::STATE_HEALTHY, $healthcheck->get_state_service_to_totara());
        self::assertEqualsCanonicalizing([
            'Service to Totara connection... Healthy',
            'elapsed_seconds' => 12,
            'totara_ip' => '127.0.0.1',
            'url' => 'http://example.com/totara/site'
        ], $healthcheck->get_service_info());
        self::assertEmpty($healthcheck->get_troubleshooting());
    }

    /**
     * @param healthcheck $healthcheck
     * @param bool|null $state
     */
    private function set_data_export_state(healthcheck $healthcheck, ?bool $state): void {
        $reflected = new ReflectionProperty($healthcheck, 'data_exported');
        $reflected->setAccessible(true);
        $reflected->setValue($healthcheck, $state);
        $reflected->setAccessible(false);
    }
}