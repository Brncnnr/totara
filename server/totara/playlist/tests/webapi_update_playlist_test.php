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
use core\json_editor\node\paragraph;
use totara_playlist\playlist;
use totara_playlist\exception\playlist_exception;

class totara_playlist_webapi_update_playlist_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_resolve_update_playlist_mutation(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['summaryformat' => FORMAT_JSON_EDITOR]);

        $this->assertEquals(FORMAT_JSON_EDITOR, $playlist->get_summaryformat());
        $this->assertEmpty($playlist->get_summary());

        /** @var playlist $updated_playlist */
        $updated_playlist = $this->resolve_graphql_mutation(
            'totara_playlist_update',
            [
                'id' => $playlist->get_id(),
                'summary' => json_encode([
                    'type' => 'doc',
                    'content' => [
                        paragraph::create_json_node_from_text("This is text")
                    ]
                ])
            ]
        );

        $this->assertInstanceOf(playlist::class, $updated_playlist);
        $this->assertNotEmpty($updated_playlist->get_summary());
    }

    /**
     * Update playlist with empty name should just using the current playlist name.
     *
     * @return void
     */
    public function test_resolve_update_playlist_mutation_with_empty_name(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        $playlist_id = $playlist->get_id();
        $current_name = $playlist->get_name(false);

        /** @var playlist $updated_playlist */
        $updated_playlist = $this->resolve_graphql_mutation(
            'totara_playlist_update',
            [
                'id' => $playlist_id,
                'summary' => null,
                'summary_format' => null,
                'name' => ''
            ]
        );

        $this->assertEquals($playlist_id, $updated_playlist->get_id());
        $this->assertEquals($current_name, $updated_playlist->get_name(false));
    }

    /**
     * @return void
     */
    public function test_update_playlist_summary_with_persist_operation(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['summaryformat' => FORMAT_JSON_EDITOR]);

        $playlist_id = $playlist->get_id();
        $document = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text("Wohooo this is playlist summary")
            ]
        ]);

        $result = $this->execute_graphql_operation(
            'totara_playlist_update_playlist_summary',
            [
                'id' => $playlist_id,
                'summary' => $document,
                'summary_format' => FORMAT_JSON_EDITOR
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('playlist', $result->data);

        $playlist_data = $result->data['playlist'];
        $this->assertArrayHasKey('id', $playlist_data);
        $this->assertEquals($playlist_id, $playlist_data['id']);

        $this->assertArrayHasKey('summary', $playlist_data);
        $this->assertEquals(
            format_text($document, FORMAT_JSON_EDITOR, ['formatter' => 'totara_tui']),
            $playlist_data['summary']
        );
    }

    /**
     * @return void
     */
    public function test_update_playlist_name_with_persist_operation(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        $playlist_id = $playlist->get_id();

        // Update playlist's nameo via persist operation.
        $result = $this->execute_graphql_operation(
            'totara_playlist_update_playlist_name',
            [
                'id' => $playlist_id,
                'name' => "Wohoo playlist 1"
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('playlist', $result->data);
        $playlist_data = $result->data['playlist'];

        $this->assertArrayHasKey('id', $playlist_data);
        $this->assertEquals($playlist_id, $playlist_data['id']);

        $this->assertArrayHasKey('name', $playlist_data);
        $this->assertEquals('Wohoo playlist 1', $playlist_data['name']);

        $this->assertNotEquals($playlist->get_name(false), $playlist_data['name']);
        $this->assertNotEquals($playlist->get_name(true), $playlist_data['name']);
    }

    /**
     * @return void
     */
    public function test_update_playlist_from_different_format_from_json_editor(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        $playlist_id = $playlist->get_id();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The format value is invalid");

        $this->resolve_graphql_mutation(
            'totara_playlist_update',
            [
                'id' => $playlist_id,
                'name' => uniqid('da'),
                'summary' => 'd',
                'summary_format' => FORMAT_MOODLE
            ]
        );
    }

    /**
     * This is to prevent changes in the codebase. Well not really prevent changing but
     * whoever going to change the rule will need to take care of this test.
     *
     * @return void
     */
    public function test_update_playlist_with_name_has_more_than_75_characters(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        $playlist_id = $playlist->get_id();

        $this->expectException(playlist_exception::class);
        $this->expectExceptionMessage(get_string('error:update', 'totara_playlist'));

        $this->resolve_graphql_mutation(
            'totara_playlist_update',
            [
                'id' => $playlist_id,
                'name' => str_repeat('dd', 100)
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_playlist_with_name_has_less_than_75_characters(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        $playlist_id = $playlist->get_id();

        /** @var playlist $updated_playlist */
        $updated_playlist = $this->resolve_graphql_mutation(
            'totara_playlist_update',
            [
                'id' => $playlist_id,
                'name' => 'This is less than 75 chars'
            ]
        );

        self::assertInstanceOf(playlist::class, $updated_playlist);
        self::assertEquals($playlist_id, $updated_playlist->get_id());

        self::assertEquals("This is less than 75 chars", $updated_playlist->get_name(false));
    }
}