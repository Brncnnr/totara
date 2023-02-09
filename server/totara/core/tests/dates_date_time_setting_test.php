<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_core
 */

use totara_core\dates\date_time_setting;

require_once(__DIR__ . '/relationship_resolver_test.php');

/**
 * @group totara_core_relationship
 * @covers \totara_core\relationship\relationship
 */
class totara_core_dates_date_time_setting_testcase extends \advanced_testcase {

    public function test_now_for_user(): void {
        self::setAdminUser();
        $now = time();
        $now_for_user = date_time_setting::now_server_timezone();

        self::assertGreaterThanOrEqual($now_for_user->get_timestamp(), $now);
        self::assertEquals(core_date::get_server_timezone(), $now_for_user->get_timezone());
    }

    /**
     * @param string $iso
     * @param string|null $timezone
     * @param string $expected_iso
     * @param int $expected_timestamp
     * @dataProvider create_from_array_provider
     */
    public function test_create_from_array(string $iso, ?string $timezone, string $expected_iso, int $expected_timestamp): void {
        $setting = date_time_setting::create_from_array([
            'iso' => $iso,
            'timezone' => $timezone,
        ]);

        $expected_timezone = $timezone ?? core_date::get_user_timezone();

        self::assertEquals($expected_timezone, $setting->get_timezone());
        self::assertEquals($expected_iso, $setting->get_iso());
        self::assertEquals($expected_timestamp, $setting->get_timestamp());
    }

    public function create_from_array_provider(): array {
        return [
            'Date only and explicit timezone' => [
                '2019-01-01', 'Pacific/Auckland', '2019-01-01T00:00:00', 1546254000,
            ],
            'Date and time and explicit timezone' => [
                '2019-01-01T00:00:30', 'Pacific/Auckland', '2019-01-01T00:00:30', 1546254000 + 30,
            ],
            'Date only and implicit timezone' => [
                '2019-01-01', null, '2019-01-01T00:00:00', 1546272000,
            ],
            'Date and time and implicit timezone' => [
                '2019-01-01T00:00:30', null, '2019-01-01T00:00:30', 1546272000 + 30,
            ],
        ];
    }

    public function test_create_from_array_missing_iso(): void {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('iso must be supplied');

        date_time_setting::create_from_array([]);
    }

    public function test_create_from_array_invalid_timezone(): void {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Invalid timezone supplied');

        date_time_setting::create_from_array([
            'iso' => '2019-01-01',
            'timezone' => 'Fake TZ',
        ]);
    }

    /**
     * @param int $timestamp
     * @param string|null $timezone
     * @param string $expected_iso
     * @dataProvider get_iso_provider
     */
    public function test_get_iso(int $timestamp, ?string $timezone, string $expected_iso): void {
        $setting = new date_time_setting($timestamp, $timezone);

        // These fields should be unchanged.
        $expected_timezone = $timezone ?? core_date::get_user_timezone();
        self::assertEquals($expected_timezone, $setting->get_timezone());
        self::assertEquals($timestamp, $setting->get_timestamp());

        // This is dependant on the combination of timestamp and timezone.
        self::assertEquals($expected_iso, $setting->get_iso());
    }

    public function get_iso_provider(): array {
        return [
            'Date only and explicit timezone' => [
                1546254000, 'Pacific/Auckland', '2019-01-01T00:00:00',
            ],
            'Date and time and explicit timezone' => [
                1546254000 + 30, 'Pacific/Auckland', '2019-01-01T00:00:30',
            ],
            'Date only and implicit timezone' => [
                1546272000, null, '2019-01-01T00:00:00',
            ],
            'Date and time and implicit timezone' => [
                1546272000 + 30, null, '2019-01-01T00:00:30',
            ],
        ];
    }

    /**
     * @param int $original_timestamp
     * @param string|null $timezone
     * @param string $expected_start_iso
     * @param string $expected_end_iso
     * @dataProvider start_and_end_of_day_provider
     */
    public function test_to_start_and_end_of_day(
        int $original_timestamp,
        ?string $timezone,
        string $expected_start_iso,
        string $expected_end_iso
    ): void {
        $start_of_day_setting = (new date_time_setting($original_timestamp, $timezone))->to_start_of_day();
        $end_of_day_setting = (new date_time_setting($original_timestamp, $timezone))->to_end_of_day();

        // These fields should be unchanged.
        $expected_timezone = $timezone ?? core_date::get_user_timezone();
        self::assertEquals($expected_timezone, $start_of_day_setting->get_timezone());
        self::assertEquals($expected_timezone, $end_of_day_setting->get_timezone());

        // Iso should not match as all of the initial values are not at the boundary of the day.
        self::assertNotEquals($original_timestamp, $start_of_day_setting->get_timestamp());
        self::assertNotEquals($original_timestamp, $end_of_day_setting->get_timestamp());

        // This is dependant on the combination of timestamp and timezone.
        self::assertEquals($expected_start_iso, $start_of_day_setting->get_iso());
        self::assertEquals($expected_end_iso, $end_of_day_setting->get_iso());
    }

    public function start_and_end_of_day_provider(): array {
        return [
            'Date only and explicit timezone' => [
                1546254000 + 1, 'Pacific/Auckland', '2019-01-01T00:00:00', '2019-01-01T23:59:59',
            ],
            'Date and time and explicit timezone' => [
                1546254000 + 30, 'Pacific/Auckland', '2019-01-01T00:00:00', '2019-01-01T23:59:59',
            ],
            'Date only and implicit timezone' => [
                1546272000 + 1, null, '2019-01-01T00:00:00', '2019-01-01T23:59:59',
            ],
            'Date and time and implicit timezone' => [
                1546272000 + 30, null, '2019-01-01T00:00:00', '2019-01-01T23:59:59',
            ],
        ];
    }

}
