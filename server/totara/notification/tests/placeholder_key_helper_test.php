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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_notification\placeholder\key_helper;

class totara_notification_placeholder_key_helper_testcase extends testcase {
    /**
     * @return void
     */
    public function test_remove_bracket(): void {
        self::assertEquals('hello:world', key_helper::remove_bracket('hello:world'));
        self::assertEquals('hello:world', key_helper::remove_bracket('[hello:world]'));
        self::assertEquals('hello_world', key_helper::remove_bracket('hello_world'));
    }

    /**
     * @return void
     */
    public function test_normalize_grouped_key(): void {
        self::assertEquals(['hello', 'world'], key_helper::normalize_grouped_key('[hello:world]'));
        self::assertEquals(['hello', 'world'], key_helper::normalize_grouped_key('hello:world'));
        self::assertEquals(['hello', 'world_x'], key_helper::normalize_grouped_key('hello:world_x'));
        self::assertEquals(['hello', 'world_x'], key_helper::normalize_grouped_key('[hello:world_x]'));
        self::assertEquals(['hello', '101_x'], key_helper::normalize_grouped_key('[hello:101_x]'));
        self::assertEquals(['hello', '101_x'], key_helper::normalize_grouped_key('hello:101_x'));

        $invalid_keys = [
            'hi', 'hello_world', 'kaboom~', 'test:test~', 'test:test-test',
            'doctor:fake_+021', 'any:other(invalid_stuff)',
        ];

        foreach ($invalid_keys as $invalid_key) {
            try {
                key_helper::normalize_grouped_key($invalid_key);
                self::fail("The invalid key '{$invalid_key}' did not yield error");
            } catch (coding_exception $e) {
                self::assertStringContainsString(
                    "Invalid key value that does not match the pattern: '{$invalid_key}'",
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * @return void
     */
    public function test_get_key(): void {
        self::assertEquals("key:key~", key_helper::get_group_key('key', 'key~'));
        self::assertEquals("[key]:[key~]", key_helper::get_group_key('[key]', '[key~]'));
        self::assertEquals("key+:+key", key_helper::get_group_key('key+', '+key'));
        self::assertEquals("key_c:key_j", key_helper::get_group_key('key_c', 'key_j'));
    }

    /**
     * @return void
     */
    public function test_get_grouped_regex(): void {
        self::assertEquals(
            "/[a-zA-Z0-9\_]+:[a-zA-Z0-9\_]+/",
            key_helper::get_grouped_key_regex()
        );

        self::assertEquals(
            "/\[[a-zA-Z0-9\_]+:[a-zA-Z0-9\_]+\]/",
            key_helper::get_grouped_key_regex(true)
        );

        self::assertEquals(
            "/^[a-zA-Z0-9\_]+:[a-zA-Z0-9\_]+$/",
            key_helper::get_grouped_key_regex(false, true)
        );

        self::assertEquals(
            "/^\[[a-zA-Z0-9\_]+:[a-zA-Z0-9\_]+\]$/",
            key_helper::get_grouped_key_regex(true, true)
        );
    }

    /**
     * @return void
     */
    public function test_validate_grouped_key(): void {
        self::assertTrue(key_helper::is_valid_grouped_key('user:firstname'));
        self::assertTrue(key_helper::is_valid_grouped_key('user:data_x'));
        self::assertTrue(key_helper::is_valid_grouped_key('user:11_7c'));
        self::assertTrue(key_helper::is_valid_grouped_key('user_1cc:11_7bci'));
        self::assertTrue(key_helper::is_valid_grouped_key('[user:firstname]', true));
        self::assertTrue(key_helper::is_valid_grouped_key('[user:data_x]', true));
        self::assertTrue(key_helper::is_valid_grouped_key('[user:11_7c]', true));
        self::assertTrue(key_helper::is_valid_grouped_key('[user_1cc:11_7bci]', true));

        self::assertFalse(key_helper::is_valid_grouped_key('bob+builder'));
        self::assertFalse(key_helper::is_valid_grouped_key('bob:~ccd'));
        self::assertFalse(key_helper::is_valid_grouped_key('bob:builder~'));
        self::assertFalse(key_helper::is_valid_grouped_key('bob#:&^_builder'));
        self::assertFalse(key_helper::is_valid_grouped_key('bob+:builder'));
        self::assertFalse(key_helper::is_valid_grouped_key('bob:(builder)'));

        self::assertFalse(key_helper::is_valid_grouped_key('[bob_builder]', true));
        self::assertFalse(key_helper::is_valid_grouped_key('[bob:~ccd]', true));
        self::assertFalse(key_helper::is_valid_grouped_key('[bob:builder~]', true));
        self::assertFalse(key_helper::is_valid_grouped_key('[bob#:&^_builder]', true));
        self::assertFalse(key_helper::is_valid_grouped_key('[bob+:builder]', true));
        self::assertFalse(key_helper::is_valid_grouped_key('[bob:(builder)]', true));

        // This is a special case, the key is in correct pattern. However since the function is notified with the bracket
        // to be appearing in the string. Hence it will fail the key.
        self::assertFalse(key_helper::is_valid_grouped_key('[bob:builder]'));
    }

    /**
     * @return void
     */
    public function test_key_helper_with_loops_key(): void {
        self::assertEquals('key:loop_start', key_helper::get_start_collection_key('key'));
        self::assertEquals('[key:loop_start]', key_helper::get_start_collection_key('key', true));

        self::assertEquals('bob:loop_end', key_helper::get_end_collection_key('bob'));
        self::assertEquals('[bob:loop_end]', key_helper::get_end_collection_key('bob', true));
    }

    /**
     * @return void
     */
    public function test_strip_invalid_characters_from_key(): void {
        self::assertEquals('hello:world', key_helper::strip_invalid_characters_from_key('hello+:world'));
        self::assertEquals('helloworld', key_helper::strip_invalid_characters_from_key('hello+world'));
        self::assertEquals('hello:world', key_helper::strip_invalid_characters_from_key('[hello:world$^&@*]'));
        self::assertEquals('hello:world', key_helper::strip_invalid_characters_from_key('hello:world'));
        self::assertEquals('helloworld', key_helper::strip_invalid_characters_from_key('helloworld'));

        self::assertEquals('bringer', key_helper::strip_invalid_characters_from_key('運命bringer'));
        self::assertEquals('bringer', key_helper::strip_invalid_characters_from_key('рокbringer'));
    }
}