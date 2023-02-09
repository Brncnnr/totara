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
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\node\paragraph;
use core_user\totara_engage\share\recipient\user as user_recipient;
use engage_article\totara_engage\resource\article;
use totara_core\advanced_feature;
use totara_engage\entity\engage_resource;
use totara_engage\share\recipient\helper as recipient_helper;
use totara_webapi\phpunit\webapi_phpunit_helper;

class engage_article_webapi_create_article_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_create_article_with_different_format_from_json_editor(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $formats = [
            FORMAT_PLAIN,
            FORMAT_MARKDOWN,
            FORMAT_HTML,
            FORMAT_HTML
        ];

        $error_counter = 0;
        // These formats will give us error.
        foreach ($formats as $format) {
            try {
                $this->resolve_graphql_mutation(
                    'engage_article_create',
                    [
                        'name' => 'Hello world',
                        'content' => 'Hello world',
                        'format' => $format
                    ]
                );
            } catch (coding_exception $e) {
                self::assertStringContainsString('The format value is invalid', $e->getMessage());
                $error_counter += 1;
            }
        }

        self::assertCount($error_counter, $formats);
    }

    /**
     * @return void
     */
    public function test_create_article_with_format_json_editor(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var article $article */
        $article = $this->resolve_graphql_mutation(
            'engage_article_create',
            [
                'name' => 'Hello world',
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text('Hello world')]
                ]),
                'format' => FORMAT_JSON_EDITOR
            ]
        );

        self::assertInstanceOf(article::class, $article);
        self::assertEquals('Hello world', $article->get_name());
    }

    public function test_successful_ajax_call(): void {
        $this->assertEquals(
            0, engage_resource::repository()->get()->count(), 'wrong article count'
        );

        $owner = $this->getDataGenerator()->create_user();
        $this->setUser($owner);

        $name = 'Hello world';
        $result = $this->parsed_graphql_operation(
            'engage_article_create_article',
            [
                'name' => $name,
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text('Hello world')]
                ]),
                'format' => FORMAT_JSON_EDITOR
            ]
        );
        $this->assert_webapi_operation_successful($result);

        $retrieved = engage_resource::repository()
            ->where('name', $name)
            ->get();
        $this->assertEquals(1, $retrieved->count(), 'wrong article count');

        $saved = $retrieved->first();
        $resource = $this->get_webapi_operation_data($result);

        $this->assertEquals($saved->instanceid, $resource['id'], 'wrong instance id');
        $this->assertEquals($saved->id, $resource['resource']['id'], 'wrong resource id');
        $this->assertEquals($saved->name, $resource['resource']['name'], 'wrong name');
        $this->assertEquals($owner->id, $resource['resource']['user']['id'], 'wrong user');
    }

    public function test_failed_ajax_call(): void {
        $generator = $this->getDataGenerator();
        $this->setUser($generator->create_user());

        $mutation = 'engage_article_create_article';
        $args = [
            'name' => 'Hello world',
            'content' => json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('Hello world')]
            ]),
            'format' => FORMAT_JSON_EDITOR,
            'shares' => [
                [
                    'instanceid' => $generator->create_user()->id,
                    'component' => recipient_helper::get_component(user_recipient::class),
                    'area' => user_recipient::AREA
                ]
            ]
        ];

        $feature = 'engage_resources';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation($mutation, $args);
        $this->assert_webapi_operation_failed($result, 'Feature engage_resources is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation($mutation, ['name' => 'testing']);
        $this->assert_webapi_operation_failed($result, 'Variable "$content" of required type "String!" was not provided.');

        $new_args = $args;
        $new_args['format'] = FORMAT_PLAIN;
        $result = $this->parsed_graphql_operation($mutation, $new_args);
        $this->assert_webapi_operation_failed($result, 'The format value is invalid');

        $new_args = $args;
        $area = 'unknown_share_area';
        $new_args['shares'][0]['area'] = $area;
        $result = $this->parsed_graphql_operation($mutation, $new_args);
        $this->assert_webapi_operation_failed($result, "No recipient handler found for '$area'");

        self::setGuestUser();
        $result = $this->parsed_graphql_operation($mutation, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot create a resource');

        self::setUser(null);
        $result = $this->parsed_graphql_operation($mutation, $args);
        $this->assert_webapi_operation_failed($result, 'You are not logged in');
    }
}