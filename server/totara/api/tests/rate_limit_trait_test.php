<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

use core_phpunit\testcase;
use totara_api\entity\client_settings;
use totara_api\model\client;
use totara_api\model\client_rate_limit;
use totara_api\model\global_rate_limit;
use totara_api\testing\generator;

/**
 * @group totara_api
 */
class totara_api_rate_limit_trait_testcase extends testcase {

    /** @var \core\testing\generator */
    protected $generator;

    /**
     * @return generator
     */
    protected function generator(): generator {
        return generator::instance();
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->generator = self::getDataGenerator();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->generator = null;

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_add_value(): void {
        self::setAdminUser();

        $global_rate_limit = global_rate_limit::create(0, 2);
        $global_rate_limit->add_value(500);

        self::assertEquals(502, $global_rate_limit->current_window_value);
    }

    /**
     * @return void
     */
    public function test_has_capacity_with_null_limit(): void {
        self::setAdminUser();

        $global_rate_limit = global_rate_limit::create(0, 0, null, null);

        self::assertTrue($global_rate_limit->has_capacity(5));
    }

    /**
     * @return void
     */
    public function test_has_capacity_value_greater_than_limit(): void {
        self::setAdminUser();

        set_config('site_rate_limit', 50, 'totara_api');
        $global_rate_limit = global_rate_limit::create(0, 0);

        self::assertFalse($global_rate_limit->has_capacity(55));
    }

    /**
     * @return void
     */
    public function test_has_capacity_within_window(): void {
        self::setAdminUser();

        $reset_time = mktime(10, 1, 0, 1, 1, 2022);
        $now = mktime(10, 1, 45, 1, 1, 2022);

        $global_rate_limit = global_rate_limit::create(
            400,
            400,
            $reset_time,
            500
        );

        self::assertFalse($global_rate_limit->has_capacity(
            1,
            60,
            $now
        ));

        $global_rate_limit = global_rate_limit::create(
            400,
            400,
            $reset_time,
            501
        );

        self::assertTrue($global_rate_limit->has_capacity(
            1,
            60,
            $now
        ));
    }

    /**
     * @return void
     */
    public function test_has_capacity_outside_window(): void {
        self::setAdminUser();

        $reset_time = mktime(10, 1, 0, 1, 1, 2022);
        $now = mktime(10, 2, 15, 1, 1, 2022);

        set_config('site_rate_limit', 550, 'totara_api');

        $global_rate_limit = global_rate_limit::create(
            400,
            549,
            $reset_time,
            550
        );

        self::assertTrue($global_rate_limit->has_capacity(
            1,
            60,
            $now
        ));

        $global_rate_limit = global_rate_limit::create(
            400,
            550,
            $reset_time,
            499
        );

        self::assertFalse($global_rate_limit->has_capacity(
            1,
            60,
            $now
        ));
    }

    /**
     * @return void
     */
    public function test_has_capacity_when_reset_time_in_future(): void {
        self::setAdminUser();

        $reset_time = mktime(12, 0, 0, 1, 1, 2023);
        $now = mktime(12, 0, 0, 1, 1, 2022);

        $global_rate_limit = global_rate_limit::create(
            400,
            500,
            $reset_time,
            500
        );

        self::assertTrue($global_rate_limit->has_capacity(1, 60, $now));
        self::assertDebuggingCalled(
            'The rate limiting reset time is in the future! It is likely that you have multiple '
            . 'web instances of Totara running and some of them have their server time out of sync',
            DEBUG_DEVELOPER
        );
    }

    /**
     * @return void
     */
    public function test_has_capacity_when_reset_time_is_3_seconds_in_future(): void {
        self::setAdminUser();

        $reset_time = mktime(12, 0, 3, 1, 1, 2022);
        $now = mktime(12, 0, 0, 1, 1, 2022);

        $global_rate_limit = global_rate_limit::create(
            499,
            0,
            $reset_time,
            500
        );

        self::assertTrue($global_rate_limit->has_capacity(1, 60, $now));

        $global_rate_limit = global_rate_limit::create(
            500,
            0,
            $reset_time,
            500
        );

        self::assertFalse($global_rate_limit->has_capacity(1, 60, $now));
    }

    /**
     * @return void
     */
    public function test_has_capacity_when_global_setting_is_lower_than_per_client(): void {
        self::setAdminUser();
        $user = $this->generator->create_user();

        $reset_time = mktime(4, 1, 0, 1, 1, 2022);
        // Let's force the rotation
        $now = mktime(4, 5, 55, 1, 1, 2022);

        $client = client::create('123', $user->id);

        set_config('client_rate_limit', 5, 'totara_api');
        $args = ['client_rate_limit' => 600,
            'default_token_expiry_time' => null
        ];
        $client->client_settings->update($args);

        $client_rate_limit = client_rate_limit::create(
            $client->id,
            0,
            0,
            $reset_time
        );

        self::assertFalse($client_rate_limit->has_capacity(6, 60, $now));
    }

    /**
     * @return void
     */
    public function test_global_rate_limit_rotation(): void {
        self::setAdminUser();

        $reset_time = mktime(21, 1, 0, 1, 1, 2022);
        $now = mktime(21, 4, 55, 1, 1, 2022);

        set_config('site_rate_limit', 500, 'totara_api');

        $global_rate_limit = global_rate_limit::create(
            400,
            750,
            $reset_time
        );

        $global_rate_limit->rotate_values($now);
        self::assertEquals(750, $global_rate_limit->prev_window_value);
        self::assertEquals(0, $global_rate_limit->current_window_value);
        self::assertEquals($now, $global_rate_limit->current_window_reset_time);
        self::assertEquals(500, $global_rate_limit->current_limit);
    }

    /**
     * @return void
     */
    public function test_client_rate_limit_rotation(): void {
        self::setAdminUser();
        $user = $this->generator->create_user();

        $reset_time = mktime(21, 1, 0, 1, 1, 2022);
        $now = mktime(21, 4, 55, 1, 1, 2022);

        $client = client::create('123', $user->id);

        $client_rate_limit = client_rate_limit::create(
            $client->id,
            300,
            550,
            $reset_time
        );

        $client_rate_limit->rotate_values($now);
        self::assertEquals(550, $client_rate_limit->prev_window_value);
        self::assertEquals(0, $client_rate_limit->current_window_value);
        self::assertEquals($now, $client_rate_limit->current_window_reset_time);
        self::assertEquals($client->client_settings->client_rate_limit, $client_rate_limit->current_limit);
    }

    /**
     * @return void
     */
    public function test_global_rate_limit_rotation_without_global_settings(): void {
        self::setAdminUser();

        set_config('site_rate_limit', null, 'totara_api');
        $global_rate_limit = global_rate_limit::create(0, 5);

        $global_rate_limit->rotate_values();
        self::assertEquals(5, $global_rate_limit->prev_window_value);
        self::assertEquals(0, $global_rate_limit->current_limit);
        self::assertEquals(null, $global_rate_limit->current_limit);
    }

    /**
     * @return void
     */
    public function test_client_rate_limit_rotation_without_client_settings(): void {
        self::setAdminUser();
        $user = $this->generator->create_user();

        $reset_time = mktime(21, 1, 0, 1, 1, 2022);
        $now = mktime(21, 4, 55, 1, 1, 2022);

        set_config('client_rate_limit', 50, 'totara_api');

        $client = client::create('123', $user->id);
        client_settings::repository()->where('client_id', '=', $client->id)->delete();

        $client_rate_limit = client_rate_limit::create(
            $client->id,
            0,
            0,
            $reset_time
        );

        $client_rate_limit->rotate_values($now);
        self::assertEquals(50, $client_rate_limit->current_limit);
    }
}