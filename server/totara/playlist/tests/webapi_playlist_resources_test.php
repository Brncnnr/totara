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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use totara_engage\access\access;
use totara_engage\answer\answer_type;
use totara_engage\query\option\section;
use totara_playlist\playlist;
use totara_webapi\graphql;
use totara_webapi\phpunit\webapi_phpunit_helper;


class totara_playlist_webapi_playlist_resources_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * Verify the playlist resources call returns the resource topics
     *
     * @return void
     */
    public function test_graphql_playlist_resources_include_topics(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setAdminUser();
        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topic1 = $topic_generator->create_topic('topic1');
        $topic2 = $topic_generator->create_topic('topic2');
        $topics = [
            $topic1->get_id(),
            $topic2->get_id()
        ];

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article([
            'topics' => $topics,
        ]);

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_survey(
            null,
            [],
            answer_type::MULTI_CHOICE,
            [
                'topics' => $topics,
            ]
        );

        // Load the playlist resources call which will returns resources *not* in this playlist.
        // Then verify the topics were correctly loaded. We're using the graphql test and not phpunit helper
        // as we're testing the results of the type resolve as well.
        $ec = execution_context::create('ajax', 'totara_playlist_resources');
        $parameters = [
            'playlist_id' => $playlist->get_id(),
            'area' => '',
            'filter' => [
                'page' => 1,
            ],
            'include_footnotes' => false,
            'theme' => 'ventura',
        ];
        $results = graphql::execute_operation($ec, $parameters);

        $this->assertEmpty($results->errors);
        $this->assertNotEmpty($results->data);
        $this->assertIsArray($results->data['resources']['cards']);
        $this->assertCount(2, $results->data['resources']['cards']);

        $topic_ids = [$topic1->get_id(), $topic2->get_id()];
        $topic_names = [$topic1->get_display_name(), $topic2->get_display_name()];

        $cards = $results->data['resources']['cards'];
        foreach ($cards as $card) {
            $resource = $card['component'] === 'engage_article' ? $article : $survey;
            $this->assertEquals($resource->get_id(), $card['instanceid']);
            $this->assertNotEmpty($card['topics']);
            $this->assertIsArray($card['topics']);

            foreach ($card['topics'] as $topic) {
                $this->assertTrue(in_array($topic['id'], $topic_ids));
                $this->assertTrue(in_array($topic['value'], $topic_names));
            }
        }
    }

    public function test_no_access_validation() {
        [$playlist, $user] = $this->prepare();
        $this->setUser($user);
        $this->assert_negative($playlist);
    }

    public function test_admin_access() {
        [$playlist] = $this->prepare();
        $this->setAdminUser();
        $this->assert_positive($playlist);
    }

    public function test_tenant_manager_access() {
        [$playlist, $user, $tenant] = $this->prepare(true);

        $tenant_context = context_tenant::instance($tenant->id);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $roleid, $tenant_context);
        role_assign($roleid, $user->id, $tenant_context);

        $this->setUser($user);
        $this->assert_positive($playlist);
    }

    public function test_different_tenant_manager() {
        [$playlist, $user] = $this->prepare(true);

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_gen =  \totara_tenant\testing\generator::instance();
        $tenant2 = $tenant_gen->create_tenant();
        $tenant_gen->migrate_user_to_tenant($user->id, $tenant2->id);

        $tenant2_context = context_tenant::instance($tenant2->id);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $roleid, $tenant2_context);
        role_assign($roleid, $user->id, $tenant2_context);

        $this->setUser($user);
        $this->assert_negative($playlist);
    }

    protected function prepare(bool $istenants = false) {
        $generator = $this->getDataGenerator();
        $owner = $generator->create_user();
        $user = $generator->create_user();

        $tenant = null;
        if ($istenants) {
            /** @var \totara_tenant\testing\generator $tenant_generator */
            $tenant_gen = $generator->get_plugin_generator('totara_tenant');
            $tenant_gen->enable_tenants();

            $tenant = $tenant_gen->create_tenant();
            $tenant_gen->migrate_user_to_tenant($owner->id, $tenant->id);
            $tenant_gen->migrate_user_to_tenant($user->id, $tenant->id);
        }

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article1 = $article_generator->create_article(['userid' => $user->id, 'access' => access::PUBLIC]);
        $article_generator->create_article(['userid' => $user->id, 'access' => access::PUBLIC]);

        $this->setUser($owner);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();
        $playlist->add_resource($article1);

        return [$playlist, $user, $tenant];
    }

    protected function assert_negative(playlist $playlist) {
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Permission denied');
        $this->resolve_graphql_query(
            'totara_playlist_resources',
            [
                'playlist_id' => $playlist->get_id(),
                'area' => '',
                'filter' => [
                    'page' => 1,
                ],
                'include_footnotes' => false,
            ]
        );
    }

    protected function assert_positive(playlist $playlist) {
        $result = $this->resolve_graphql_query(
            'totara_playlist_resources',
            [
                'playlist_id' => $playlist->get_id(),
                'area' => '',
                'filter' => [
                    'page' => 1,
                    'section' => section::ALLSITE
                ],
                'include_footnotes' => false,
            ]
        );

        $this->assertIsArray($result['cards']);
        $this->assertCount(1, $result['cards']);
    }

    /**
     * @return void
     */
    public function test_fetch_resources_exclude_components(): void {
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        // Login as user_one.
        $this->setUser($user_one);
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article_generator->create_public_article();
        $article_generator->create_public_article();

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey_generator->create_public_survey();
        $survey_generator->create_public_survey();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        $result = $this->resolve_graphql_query(
            'totara_playlist_resources',
            [
                'playlist_id' => $playlist->get_id(),
                'area' => '',
                'filter' => [
                    'page' => 1,
                    'section' => section::ALLSITE
                ],
                'include_footnotes' => false,
            ]
        );

        self::assertNotEmpty($result['cards']);
        self::assertEquals(4, count($result['cards']));

        // Prevent survey share capability.
        $role_user = \core\orm\query\builder::get_db()->get_field('role', 'id', ['shortname' => 'user'], MUST_EXIST);
        assign_capability('engage/survey:share', CAP_PREVENT, $role_user, context_user::instance($user_one->id)->id);

        $result = $this->resolve_graphql_query(
            'totara_playlist_resources',
            [
                'playlist_id' => $playlist->get_id(),
                'area' => '',
                'filter' => [
                    'page' => 1,
                    'section' => section::ALLSITE
                ],
                'include_footnotes' => false,
            ]
        );

        self::assertNotEmpty($result['cards']);
        self::assertEquals(2, count($result['cards']));
    }
}