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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package ml_recommender
 */

use block_totara_recommendations\repository\recommendations_repository;
use engage_article\totara_engage\resource\article;
use totara_core\advanced_feature;
use totara_engage\access\access;
use engage_article\event\article_viewed;

defined('MOODLE_INTERNAL') || die();

/**
 * Test item_user import
 */
class ml_recommender_seen_recommended_items_testcase extends advanced_testcase {

    public function test_seen_recommended_items() {
        // Set up the test data.
        list($user1, $user2, $articles) = $this->set_up_data();
        $this->setAdminUser();

        // Ensure that ML recommender is enabled.
        advanced_feature::enable('ml_recommender');

        // Retrieve initial recommendations list.
        $items_limit = 10;
        $initial_user1_recs = recommendations_repository::get_recommended_micro_learning($items_limit, $user1->id);
        $initial_user2_recs = recommendations_repository::get_recommended_micro_learning($items_limit, $user2->id);

        // Trigger article viewed events for each user.
        $this->setUser($user1);
        $resource = article::from_resource_id($articles[0]->get_id());
        $event = article_viewed::from_article($resource);
        $event->trigger();

        $this->setUser($user2);
        $resource = article::from_resource_id($articles[1]->get_id());
        $event = article_viewed::from_article($resource);
        $event->trigger();

        // Refetch items after visits.
        $secondary_user1_recs = recommendations_repository::get_recommended_micro_learning($items_limit, $user1->id);
        $secondary_user2_recs = recommendations_repository::get_recommended_micro_learning($items_limit, $user2->id);

        // Ensure that item lists are in new sequence.
        $this->assertNotEquals($initial_user1_recs, $secondary_user1_recs);
        $this->assertNotEquals($initial_user2_recs, $secondary_user2_recs);

        // Ensure that what was once first shall now be the last.
        $this->assertEquals($initial_user1_recs[array_key_first($initial_user1_recs)]->item_id, $secondary_user1_recs[array_key_last($secondary_user1_recs)]->item_id);
        $this->assertEquals($initial_user2_recs[array_key_first($initial_user2_recs)]->item_id, $secondary_user2_recs[array_key_last($secondary_user2_recs)]->item_id);

        // Disable ML recommender.
        advanced_feature::disable('ml_recommender');

        // Trigger another article viewed event for each user.
        $this->setUser($user1);
        $resource = article::from_resource_id($articles[1]->get_id());
        $event = article_viewed::from_article($resource);
        $event->trigger();

        $this->setUser($user2);
        $resource = article::from_resource_id($articles[2]->get_id());
        $event = article_viewed::from_article($resource);
        $event->trigger();

        // Refetch items after visits.
        $disabled_user1_recs = recommendations_repository::get_recommended_micro_learning($items_limit, $user1->id);
        $disabled_user2_recs = recommendations_repository::get_recommended_micro_learning($items_limit, $user2->id);

        // Ensure that item lists are unchanged.
        $this->assertEquals($disabled_user1_recs, $secondary_user1_recs);
        $this->assertEquals($disabled_user2_recs, $secondary_user2_recs);
    }

    private function set_up_data() {
        global $DB;

        $generator = $this->getDataGenerator();
        $article_generator = $generator->get_plugin_generator('engage_article');
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        $this->setAdminUser();
        $topic = $topic_generator->create_topic();

        // Create 2 users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create a few articles.
        $articles = [];
        for ($i = 1; $i <= 3; $i++) {
            $article = $article_generator->create_article([
                'name' => 'A' . $i,
                'userid' => $user1->id,
                'access' => access::PUBLIC,
                'topics' => [$topic->get_id()],
            ]);
            $articles[] = $article;
        }

        // Create a few playlists.
        $playlists = [];
        for ($i = 1; $i <= 3; $i++) {
            $playlist = $playlist_generator->create_playlist([
                'name' => 'P' . $i,
                'userid' => $user2->id,
                'access' => access::PUBLIC,
                'topics' => [$topic->get_id()],
            ]);
            $playlists[] = $playlist;
        }

        // Create some recommendations.
        $i2u = [
            ['uid' => $user1->id, 'iid' => 'engage_article' . $articles[0]->get_id(), 'ranking' => 2.203594684601],
            ['uid' => $user1->id, 'iid' => 'engage_article' . $articles[1]->get_id(), 'ranking' => 1.936550498009],
            ['uid' => $user1->id, 'iid' => 'engage_microlearning' . $articles[2]->get_id(), 'ranking' => 1.879107402397],
            ['uid' => $user1->id, 'iid' => 'totara_playlist' . $playlists[0]->get_id(), 'ranking' => 1.845853328705],
            ['uid' => $user1->id, 'iid' => 'totara_playlist' . $playlists[2]->get_id(), 'ranking' => 1.669565081596],
            ['uid' => $user2->id, 'iid' => 'totara_playlist' . $playlists[2]->get_id(), 'ranking' => 1.879106402397],
            ['uid' => $user2->id, 'iid' => 'engage_article' . $articles[1]->get_id(), 'ranking' => 1.892577528954],
            ['uid' => $user2->id, 'iid' => 'engage_microlearning' . $articles[2]->get_id(), 'ranking' => 1.825851328705],
        ];

        // Upload items per user.
        $user_recommendations = new \ml_recommender\local\import\item_user();
        $user_recommendations->import(new ArrayIterator($i2u));

        // Check that all records were uploaded.
        $count_i2u = $DB->count_records_sql("SELECT COUNT(mru.id) FROM  {ml_recommender_users} mru");
        $this->assertCount($count_i2u, $i2u);

        return [$user1, $user2, $articles];
    }
}