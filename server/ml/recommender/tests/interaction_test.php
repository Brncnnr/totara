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
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use engage_article\totara_engage\resource\article;
use engage_survey\totara_engage\resource\survey;
use ml_recommender\observer\interaction_observer;
use totara_core\advanced_feature;
use totara_engage\access\access;
use totara_playlist\event\playlist_viewed;
use totara_playlist\playlist;
use totara_webapi\graphql;
use core\json_editor\node\paragraph;

class ml_recommender_interaction_testcase extends advanced_testcase {
    /**
     * Test rating a playlist records an interaction
     */
    public function test_rating_interaction() {
        global $DB;
        $user = $this->setup_user();

        // Need a playlist
        $playlist = playlist::create(
            'Rated Playlist',
            access::PUBLIC
        );

        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user2);
        advanced_feature::enable('ml_recommender');
        $ec = execution_context::create('ajax', 'totara_playlist_add_rating');
        $parameters = [
            'playlistid' => $playlist->get_id(),
            'rating' => 4,
            'ratingarea' => 'playlist'
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        // Check the record exists
        $record = $DB->get_record_sql('
            SELECT *
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND area = :area AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'totara_playlist',
            'area' => 'playlist',
            'interaction' => 'rate',
            'item_id' => $playlist->get_id(),
            'user_id' => $user2->id,
        ]);

        $this->assertNotFalse($record);
        $this->assertEquals($playlist->get_id(), $record->item_id);

        $this->setUser($user);
        // Disable the feature
        $playlist = playlist::create(
            'Rated Playlist 2',
            access::PUBLIC
        );

        advanced_feature::disable('ml_recommender');

        $this->setUser($user2);
        $ec = execution_context::create('ajax', 'totara_playlist_add_rating');
        $parameters = [
            'playlistid' => $playlist->get_id(),
            'rating' => 5,
            'ratingarea' => 'playlist'
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        // Check the record exists
        $record = $DB->get_record_sql('
            SELECT *
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND area = :area AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'totara_playlist',
            'area' => 'playlist',
            'interaction' => 'rate',
            'item_id' => $playlist->get_id(),
            'user_id' => $user2->id,
        ]);

        $this->assertFalse($record);
    }

    /**
     * Test Like/Unliking an article records reactions
     */
    public function test_reaction_interaction() {
        global $DB;
        $user = $this->setup_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Make an article
        $article = article::create([
            'name' => 'Test Resource',
            'access' => access::PUBLIC,
            'content' => 'Test',
            'format' => FORMAT_PLAIN,
        ], $user2->id);
        $article2 = article::create([
            'name' => 'Test Resource 2',
            'access' => access::PUBLIC,
            'content' => 'Test',
            'format' => FORMAT_PLAIN,
        ], $user2->id);

        advanced_feature::enable('ml_recommender');
        $ec = execution_context::create('ajax', 'totara_reaction_create_like');
        $parameters = [
            'component' => 'engage_article',
            'area' => 'media',
            'instanceid' => $article->get_id(),
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        $record = $DB->get_record_sql('
            SELECT *
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND area = :area AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'engage_article',
            'area' => 'media',
            'interaction' => 'like',
            'item_id' => $article->get_id(),
            'user_id' => $user->id,
        ]);

        $this->assertNotFalse($record);
        $this->assertEquals($article->get_id(), $record->item_id);

        // Disable feature
        advanced_feature::disable('ml_recommender');
        $ec = execution_context::create('ajax', 'totara_reaction_create_like');
        $parameters = [
            'component' => 'engage_article',
            'area' => 'media',
            'instanceid' => $article2->get_id(),
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        $record = $DB->get_record_sql('
            SELECT *
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND area = :area AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'engage_article',
            'area' => 'media',
            'interaction' => 'like',
            'item_id' => $article2->get_id(),
            'user_id' => $user->id,
        ]);

        $this->assertFalse($record);

        // Now an unlike
        advanced_feature::enable('ml_recommender');
        $ec = execution_context::create('ajax', 'totara_reaction_remove_like');
        $parameters = [
            'component' => 'engage_article',
            'area' => 'media',
            'instanceid' => $article->get_id(),
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        $record = $DB->get_record_sql('
            SELECT *
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND area = :area AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'engage_article',
            'area' => 'media',
            'interaction' => 'unlike',
            'item_id' => $article->get_id(),
            'user_id' => $user->id,
        ]);

        $this->assertNotFalse($record);
        $this->assertEquals($article->get_id(), $record->item_id);

        // Disable feature
        advanced_feature::disable('ml_recommender');
        $ec = execution_context::create('ajax', 'totara_reaction_remove_like');
        $parameters = [
            'component' => 'engage_article',
            'area' => 'media',
            'instanceid' => $article2->get_id(),
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        $record = $DB->get_record_sql('
            SELECT *
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND area = :area AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'engage_article',
            'area' => 'media',
            'interaction' => 'unlike',
            'item_id' => $article2->get_id(),
            'user_id' => $user->id,
        ]);

        $this->assertFalse($record);
    }

    /**
     * Test commenting on an article & playlist records a reaction
     */
    public function test_comment_created_interaction() {
        global $DB;
        $user = $this->setup_user();

        // Make an article to comment on
        $article = article::create([
            'name' => 'Test Resource',
            'access' => access::PUBLIC,
            'content' => 'Test',
            'format' => FORMAT_PLAIN,
        ], $user->id);
        $article2 = article::create([
            'name' => 'Test Resource',
            'access' => access::PUBLIC,
            'content' => 'Test',
            'format' => FORMAT_PLAIN,
        ], $user->id);

        // Make a playlist to comment on
        $playlist = playlist::create(
            'My Playlist',
            access::PUBLIC,
            null, $user->id,
            'Test Playlist'
        );
        $playlist2 = playlist::create(
            'My Playlist',
            access::PUBLIC,
            null, $user->id,
            'Test Playlist'
        );

        // test both, first 2 will be enabled, second 2 will be disabled
        foreach ([$article, $playlist, $article2, $playlist2] as $i => $component) {
            $disabled = $i >= 2;

            if ($disabled) {
                advanced_feature::disable('ml_recommender');
            } else {
                advanced_feature::enable('ml_recommender');
            }
            $ec = execution_context::create('ajax', 'totara_comment_create_comment');
            $parameters = [
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text('My Comment')]
                ]),
                'component' => $component::get_resource_type(),
                'area' => 'comment',
                'instanceid' => $component->get_id(),
                'format' => FORMAT_JSON_EDITOR,
            ];
            $result = graphql::execute_operation($ec, $parameters);
            $this->assertNotNull($result->data);

            $record = $DB->get_record_sql('
            SELECT *
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND area = :area AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
                'component' => $component::get_resource_type(),
                'area' => 'comment',
                'interaction' => 'comment',
                'item_id' => $component->get_id(),
                'user_id' => $user->id,
            ]);

            if ($disabled) {
                $this->assertFalse($record);
            } else {
                $this->assertNotFalse($record);
                $this->assertEquals($component->get_id(), $record->item_id);
            }
        }
    }

    /**
     * Test resharing an article, survey & playlist records a reaction
     */
    public function test_reshared_interaction() {
        global $DB;
        $user = $this->setup_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Assign capability to Engage with other users
        $roleid = $this->getDataGenerator()->create_role();
        $syscontext = context_system::instance();
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $syscontext);
        role_assign($roleid, $user->id, $syscontext);
        role_assign($roleid, $user2->id, $syscontext);
        role_assign($roleid, $user3->id, $syscontext);

        // Make an article to reshare
        $article = article::create([
            'name' => 'Test Resource',
            'access' => access::PUBLIC,
            'content' => 'Test',
            'format' => FORMAT_PLAIN,
        ], $user2->id);
        $article2 = article::create([
            'name' => 'Test Resource 2',
            'access' => access::PUBLIC,
            'content' => 'Test',
            'format' => FORMAT_PLAIN,
        ], $user2->id);

        // Make a playlist to reshare
        $playlist = playlist::create(
            'My Playlist',
            access::PUBLIC,
            null,
            $user2->id,
            'Test Playlist'
        );
        $playlist2 = playlist::create(
            'My Playlist 2',
            access::PUBLIC,
            null,
            $user2->id,
            'Test Playlist'
        );

        // Make a survey to reshare
        $survey = survey::create([
            'name' => 'Test Survey',
            'access' => access::PUBLIC,
            'questions' => [
                ['value' => 'Q1', 'answertype' => 2, 'options' => ['A', 'B', 'C']]
            ]
        ], $user2->id);
        $survey2 = survey::create([
            'name' => 'Test Survey',
            'access' => access::PUBLIC,
            'questions' => [
                ['value' => 'Q1', 'answertype' => 2, 'options' => ['A', 'B', 'C']]
            ]
        ], $user2->id);

        $recipient = [
            'instanceid' => (int) $user3->id,
            'component' => 'core_user',
            'area' => 'user',
        ];

        foreach ([$article, $playlist, $survey, $article2, $playlist2, $survey2] as $i => $component) {
            $disabled = $i >= 3;

            if ($disabled) {
                advanced_feature::disable('ml_recommender');
            } else {
                advanced_feature::enable('ml_recommender');
            }
            $ec = execution_context::create('ajax', 'totara_engage_share');
            $parameters = [
                'component' => $component::get_resource_type(),
                'itemid' => $component->get_id(),
                'recipients' => [$recipient],
            ];
            $result = graphql::execute_operation($ec, $parameters);
            $this->assertNotNull($result->data);

            $record = $DB->get_record_sql('
            SELECT *
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
                'component' => $component::get_resource_type(),
                'interaction' => 'reshare',
                'item_id' => $component->get_id(),
                'user_id' => $user->id,
            ]);

            if ($disabled) {
                $this->assertFalse($record);
            } else {
                $this->assertNotFalse($record, $component::get_resource_type());
                $this->assertEquals($component->get_id(), $record->item_id);
            }
        }
    }

    public function test_interaction_observer_watch_interaction() {
        global $DB;
        $user = $this->setup_user();

        // Need a playlist
        $playlist = playlist::create(
            'Rated Playlist',
            access::PUBLIC
        );
        $playlist_viewed = playlist_viewed::create([
            'objectid' => $playlist->get_id(),
            'context' => context_system::instance(),
            'other' => [
                'is_public' => true
            ]
        ]);

        interaction_observer::watch_interaction($playlist_viewed, 123);

        // Check if there is only one record
        $records = $DB->get_records_sql('
            SELECT CONCAT(mri.id, mri.time_created) as id, mri.item_id, mri.time_created
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'totara_playlist',
            'interaction' => 'view',
            'item_id' => $playlist->get_id(),
            'user_id' => $user->id,
        ]);

        $this->assertNotFalse($records);
        $this->assertCount(1, $records);
        $this->assertEquals($playlist->get_id(), reset($records)->item_id);
        $this->assertEquals('123', reset($records)->time_created);

        interaction_observer::watch_interaction($playlist_viewed, 123);

        // Check if there is only one record
        $records = $DB->get_records_sql('
            SELECT CONCAT(mri.id, mri.time_created) as id, mri.item_id, mri.time_created
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'totara_playlist',
            'interaction' => 'view',
            'item_id' => $playlist->get_id(),
            'user_id' => $user->id,
        ]);

        $this->assertNotFalse($records);
        $this->assertCount(1, $records);
        $this->assertEquals($playlist->get_id(), reset($records)->item_id);
        $this->assertEquals('123', reset($records)->time_created);

        //Now without passing time
        interaction_observer::watch_interaction($playlist_viewed);

        $records = $DB->get_records_sql('
            SELECT CONCAT(mri.id, mri.time_created) as id, mri.item_id
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'totara_playlist',
            'interaction' => 'view',
            'item_id' => $playlist->get_id(),
            'user_id' => $user->id,
        ]);

        $this->assertNotFalse($records);
        $this->assertGreaterThan(1, $records);
    }

    public function test_interaction_observer_watch_core() {
        global $DB;
        $user = $this->setup_user();

        // Need a playlist
        $playlist = playlist::create(
            'Rated Playlist',
            access::PUBLIC
        );
        $playlist_viewed = playlist_viewed::create([
            'objectid' => $playlist->get_id(),
            'context' => context_system::instance(),
            'other' => [
                'is_public' => true
            ]
        ]);

        interaction_observer::watch_core($playlist_viewed, 123);

        // Check if there is only one record
        $records = $DB->get_records_sql('
            SELECT CONCAT(mri.id, mri.time_created) as id, mri.item_id, mri.time_created
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'totara_playlist',
            'interaction' => 'view',
            'item_id' => $playlist->get_id(),
            'user_id' => $user->id,
        ]);

        $this->assertNotFalse($records);
        $this->assertCount(1, $records);
        $this->assertEquals($playlist->get_id(), reset($records)->item_id);
        $this->assertEquals('123', reset($records)->time_created);

        interaction_observer::watch_core($playlist_viewed, 123);

        // Check if there is only one record
        $records = $DB->get_records_sql('
            SELECT CONCAT(mri.id, mri.time_created) as id, mri.item_id, mri.time_created
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'totara_playlist',
            'interaction' => 'view',
            'item_id' => $playlist->get_id(),
            'user_id' => $user->id,
        ]);

        $this->assertNotFalse($records);
        $this->assertCount(1, $records);
        $this->assertEquals($playlist->get_id(), reset($records)->item_id);
        $this->assertEquals('123', reset($records)->time_created);

        //Now without passing time
        interaction_observer::watch_core($playlist_viewed);

        $records = $DB->get_records_sql('
            SELECT CONCAT(mri.id, mri.time_created) as id, mri.item_id
            FROM {ml_recommender_interactions} mri
            INNER JOIN  {ml_recommender_components} mrc ON (mrc.id = mri.component_id)
            INNER JOIN  {ml_recommender_interaction_types} mrit ON (mrit.id = mri.interaction_type_id)
            WHERE component = :component AND interaction = :interaction AND item_id = :item_id AND user_id = :user_id
            ', [
            'component' => 'totara_playlist',
            'interaction' => 'view',
            'item_id' => $playlist->get_id(),
            'user_id' => $user->id,
        ]);

        $this->assertNotFalse($records);
        $this->assertGreaterThan(1, $records);
    }

    /**
     * @return array|stdClass|null
     */
    private function setup_user(): ?stdClass {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        return $user;
    }
}