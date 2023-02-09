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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package container_workspace
 */

use core_phpunit\testcase;
use engage_article\totara_engage\card\article_card;
use totara_engage\card\card;
use totara_playlist\totara_engage\card\playlist_card;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_engage_library_search_testcase extends testcase {
    use webapi_phpunit_helper;

    public function test_search_library(): void {
        $gen = $this->getDataGenerator();

        /**
         * @var \container_workspace\testing\generator $workspace_gen
         */
        $workspace_gen = $gen->get_plugin_generator('container_workspace');

        /** @var \engage_article\testing\generator $article_gen */
        $article_gen = $gen->get_plugin_generator('engage_article');

        /** @var \engage_survey\testing\generator $survey_gen */
        $survey_gen = $gen->get_plugin_generator('engage_survey');

        /** @var \totara_playlist\testing\generator $playlist_gen */
        $playlist_gen = $gen->get_plugin_generator('totara_playlist');

        $user1 = $gen->create_user();
        $this->setUser($user1);

        // Give user create workspace capability.
        $workspace_gen->set_capabilities(CAP_ALLOW, $user1->id);

        // Create workspace.
        $workspace = $workspace_gen->create_workspace('SpaceX', 'X', null, null, false);

        // Create recipients.
        $recipients = $workspace_gen->create_workspace_recipients([$workspace]);

        // Create and share items.
        $article = $article_gen->create_article(['name' => 'This are tickle', 'access' => \totara_engage\access\access::PUBLIC]);
        $survey = $survey_gen->create_survey('2B || !2B', [], 1, ['access' => \totara_engage\access\access::PUBLIC]);
        $playlist = $playlist_gen->create_playlist(['name' => 'Playing in a list', 'access' => \totara_engage\access\access::PUBLIC]);

        $article_gen->share_article($article, $recipients);
        $survey_gen->share_survey($survey, $recipients);
        $playlist_gen->share_playlist($playlist, $recipients);

        // We should only be getting the article.
        $result = $this->resolve_graphql_query(
            'container_workspace_shared_cards',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'library',
                'include_footnotes' => false,
                'filter' => [
                    'search' => 'tickle'
                ]
            ]
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('cards', $result);
        $this->assertCount(1, $result['cards']);
        $this->assertInstanceOf(article_card::class, $result['cards'][0]);
        /** @var article_card $article */
        $article = $result['cards'][0];
        $this->assertEquals('This are tickle', $article->get_name());

        // We should only be getting the playlist.
        $result = $this->resolve_graphql_query(
            'container_workspace_shared_cards',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'library',
                'include_footnotes' => false,
                'filter' => [
                    'search' => 'playing'
                ]
            ]
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('cards', $result);
        $this->assertCount(1, $result['cards']);
        $this->assertInstanceOf(playlist_card::class, $result['cards'][0]);
        /** @var playlist_card $article */
        $playlist = $result['cards'][0];
        $this->assertEquals('Playing in a list', $playlist->get_name());

        // Create another article.
        $article = $article_gen->create_article(['name' => 'Article in a playlist', 'access' => \totara_engage\access\access::PUBLIC]);
        $article_gen->share_article($article, $recipients);

        // We should now get the article and playlist.
        $result = $this->resolve_graphql_query(
            'container_workspace_shared_cards',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'library',
                'include_footnotes' => false,
                'filter' => [
                    'search' => 'play'
                ]
            ]
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('cards', $result);
        $this->assertCount(2, $result['cards']);
        $this->assertContainsCard(article_card::class, 'Article in a playlist', $result['cards']);
        $this->assertContainsCard(playlist_card::class, 'Playing in a list', $result['cards']);
    }

    /**
     * @param string $card_type
     * @param string $card_name
     * @param array $array
     */
    protected function assertContainsCard(string $card_type, string $card_name, array $array): void {
        /** @var card $card */
        foreach ($array as $card) {
            if ($card->get_name() === $card_name && $card instanceof $card_type) {
                return;
            }
        }
        $this->fail("Expected card \"{$card_type}\" with name \"{$card_name}\" not found.");
    }

}