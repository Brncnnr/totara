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
use ml_service\auth\token_manager;

class ml_service_token_manager_testcase extends testcase {
    /** @var ReflectionProperty */
    private $base_time;

    /**
     * @return array[]
     */
    public function extract_request_time_token_data(): array {
        return [
            [null, null, 0, null],
            [1, null, 1, null],
            [null, 'abc', 0, 'abc'],
            [1234, 'abc', 1234, 'abc'],
        ];
    }

    /**
     * @dataProvider extract_request_time_token_data
     * @param mixed $request_time
     * @param mixed $request_token
     * @param mixed $expected_time
     * @param mixed|null $expected_token
     */
    public function test_extract_request_time_token($request_time, $request_token, $expected_time,
        $expected_token): void {
        $_SERVER['HTTP_X_TOTARA_TIME'] = $request_time;
        $_SERVER['HTTP_X_TOTARA_ML_KEY'] = $request_token;

        [$time, $token] = token_manager::extract_request_time_token();
        self::assertEquals($expected_time, $time);
        self::assertEquals($expected_token, $token);
    }

    /**
     * Data for the test_make_token test
     *
     * @return array[]
     */
    public function make_token_data(): array {
        return [
            [123456, 'a44934344c553000cb198d77cae685d33563617a01b17dc60d45a5557ad9c21d'],
            [99999999999, '91dfe719d147209b01a133bbe80bf7aaa4d850ac93c7169eb2c065aa90bdd8f8'],
            [0, 'e4564920dabbb068898fbb32f98a67fe6d98520686d726d10dbc7e5badc37ead'],
        ];
    }

    /**
     * @dataProvider make_token_data
     * @param float $request_time
     * @param string $expected_token
     */
    public function test_make_token(float $request_time, string $expected_token): void {
        global $CFG;

        $CFG->ml_service_key = 'abc123';
        $result = token_manager::make_token($request_time);
        self::assertEquals($expected_token, $result);

        $CFG->ml_service_key = 'abc1234';
        $result = token_manager::make_token($request_time);
        self::assertNotEquals($expected_token, $result);
    }

    /**
     * Test that the ml service key is required
     */
    public function test_ml_service_key_required(): void {
        global $CFG;

        $CFG->ml_service_key = 'abc123';
        $result = token_manager::make_token(123456789);
        self::assertNotEmpty($result);

        $CFG->ml_service_key = null;
        self::expectExceptionMessage('No ml_service_key was defined, cannot connect to the machine learning service.');
        token_manager::make_token(123456789);
    }

    /**
     * Data to test against valid_token. We have a base timestamp of 1000
     *
     * @return array[]
     */
    public function valid_token_data(): array {
        return [
            [1000, '03203b76ce4a4cf093f39eeedb61a5966a20dccfca1cc4fdfb18e0006f444e9b', true], // 0, +token, accepted
            [990, 'd1dbd8c1610d6fcd7cd69cfeb45af3be6c9126d58f57807e8d6cf637bb8b1a28', true], // -10, +token, accepted
            [1010, '38f9c57476b1e572177b40e22a82c169475222c0496086999bc5aaaabef08eed', true], // +10, +token, accepted
            [968, 'd87fa22e053053cd5672dc1fda649d3f05ca0eb94bd6e0fcfb988aa2f17da6f1', false], // -32, +token, rejected
            [1032, 'd87fa22e053053cd5672dc1fda649d3f05ca0eb94bd6e0fcfb988aa2f17da6f1', false], // +32, +token, rejected
            [1000, '2bbfd2c04414378481dcf41056dccae281eba1c549e6646eb43e0e0d6c9e324a', false], // 0, -token, rejected
            [990, '2bbfd2c04414378481dcf41056dccae281eba1c549e6646eb43e0e0d6c9e324a', false], // -10, -token, rejected
            [1010, '2bbfd2c04414378481dcf41056dccae281eba1c549e6646eb43e0e0d6c9e324a', false], // +10, -token, rejected
            [968, '2bbfd2c04414378481dcf41056dccae281eba1c549e6646eb43e0e0d6c9e324a', false], // -32, -token, rejected
            [1032, '2bbfd2c04414378481dcf41056dccae281eba1c549e6646eb43e0e0d6c9e324a', false], // +32, -token, rejected
        ];
    }

    /**
     * @dataProvider valid_token_data
     * @param mixed $request_time
     * @param string $request_token
     * @param bool $expected_result
     */
    public function test_valid_token($request_time, string $request_token, bool $expected_result): void {
        global $CFG;
        $CFG->ml_service_key = 'abc123';

        $result = token_manager::valid_token($request_time, $request_token);
        self::assertSame($expected_result, $result);
    }

    /**
     * Setup
     */
    protected function setUp(): void {
        // Tests based on time() can be problematic, so we force in a set time
        // instead, to prevent any time quirks polluting the tests.
        $this->base_time = new ReflectionProperty(token_manager::class, 'base_time');
        $this->base_time->setAccessible(true);
        $this->base_time->setValue(1000);
    }

    /**
     * Reset the base time back to nothing
     */
    protected function tearDown(): void {
        $this->base_time->setValue(null);
        $this->base_time = null;
    }
}