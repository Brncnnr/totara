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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_engage\access\access;
use core\json_editor\node\paragraph;

class totara_playlist_webapi_create_playlist_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_create_playlist_without_summary(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $result = $this->execute_graphql_operation(
            'totara_playlist_create_playlist',
            [
                'name' => "Playlist one 101",
                'access' => access::get_code(access::PRIVATE)
            ]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertArrayHasKey('playlist', $result->data);

        $playlist = $result->data['playlist'];

        self::assertIsArray($playlist);
        self::assertArrayHasKey('id', $playlist);

        self::assertTrue($DB->record_exists('playlist', ['id' => $playlist['id']]));
    }

    /**
     * @return void
     */
    public function test_create_playlist_with_summary_and_invalid_format(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $result = $this->execute_graphql_operation(
            'totara_playlist_create_playlist',
            [
                'name' => "Playlist one 101",
                'access' => access::get_code(access::PRIVATE),
                'summary' => json_encode([
                    'type' => 'doc',
                    'content' => [
                        paragraph::create_json_node_from_text('This is the playlist summary')
                    ]
                ]),
                'summary_format' => 42
            ]
        );

        self::assertNotEmpty($result->errors);
        self::assertIsArray($result->errors);
        self::assertCount(1, $result->errors);

        $error = reset($result->errors);
        self::assertStringContainsString("The format value is invalid", $error->getMessage());
    }

    /**
     * @return void
     */
    public function test_create_playlist_with_empty_summary(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $result = $this->execute_graphql_operation(
            'totara_playlist_create_playlist',
            [
                'name' => "Playlist one 101",
                'access' => access::get_code(access::PRIVATE),
                'summary' => json_encode([
                    'type' => 'doc',
                    'content' => []
                ]),
                'summary_format' => FORMAT_JSON_EDITOR
            ]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);

        self::assertIsArray($result->data);
        self::assertArrayHasKey('playlist', $result->data);

        $playlist = $result->data['playlist'];

        self::assertIsArray($playlist);
        self::assertArrayHasKey('id', $playlist);

        self::assertTrue($DB->record_exists('playlist', ['id' => $playlist['id']]));
    }

    /**
     * @return void
     */
    public function test_create_playlist_with_different_format_from_json_editor(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The format value is invalid");
        $this->resolve_graphql_mutation(
            'totara_playlist_create',
            [
                'name' => 'doctor',
                'summary' => 'Some random text',
                'summary_format' => FORMAT_PLAIN
            ]
        );
    }
}