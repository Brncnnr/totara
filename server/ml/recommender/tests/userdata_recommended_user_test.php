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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use totara_userdata\userdata\target_user;
use ml_recommender\userdata\recommended_user;

class ml_recommender_userdata_recommended_user_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_purge_recommendation(): void {
        global $DB;

        $gen = $this->getDataGenerator();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $gen->get_plugin_generator('totara_playlist');

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $gen->get_plugin_generator('container_workspace');

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $gen->get_plugin_generator('engage_article');

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $gen->get_plugin_generator('totara_topic');

        /** @var \ml_recommender\testing\generator $recommendations_generator */
        $recommendations_generator = $gen->get_plugin_generator('ml_recommender');

        $creator = $gen->create_user();
        $other_user = $gen->create_user();
        $target_user = $gen->create_user();
        $target_user1 = $gen->create_user();
        $this->setAdminUser();
        $topic = $topic_generator->create_topic();

        $playlist = $playlist_generator->create_playlist([
            'name' => 'Target Playlist',
            'userid' => $creator->id,
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()],
        ]);

        $article = $article_generator->create_article([
            'name' => 'Target Article',
            'userid' => $creator->id,
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()],
        ]);

        $workspace = $workspace_generator->create_workspace(
            'Target Workspace',
            'Summary',
            null,
            $creator->id,
            $topic->get_raw_name()
        );

        $workspace1 = $workspace_generator->create_workspace(
            'Other User Workspace',
            'Summary',
            null,
            $other_user->id,
            $topic->get_raw_name()
        );


        $article1 = $article_generator->create_article([
            'name' => 'Other User Article',
            'userid' => $other_user->id,
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()],
        ]);

        $recommendations_generator->create_user_recommendation(
            (int)$target_user->id,
            $playlist->get_id(),
            'totara_playlist'
        );

        $recommendations_generator->create_user_recommendation(
            (int)$target_user->id,
            $article->get_id(),
            'engage_article'
        );

        $recommendations_generator->create_user_recommendation(
            (int)$target_user->id,
            $workspace->get_id(),
            'container_workspace'
        );


        $recommendations_generator->create_user_recommendation(
            (int)$target_user1->id,
            $workspace1->get_id(),
            'container_workspace'
        );

        $recommendations_generator->create_user_recommendation(
            (int)$target_user1->id,
            $article1->get_id(),
            'container_workspace'
        );

        // Recommeneded user created.
        $this->assertTrue(
            $DB->record_exists('ml_recommender_users', ['user_id' => $target_user->id])
        );

        $this->assertTrue(
            $DB->record_exists('ml_recommender_users', ['user_id' => $target_user1->id])
        );

        // Five records in total.
        $this->assertCount(5, $DB->get_records('ml_recommender_users'));

        // Three records has to be in recommender_users table.
        $this->assertCount(
            3,
            $DB->get_records('ml_recommender_users', ['user_id' => $target_user->id])
        );

        // Delete target user.
        $target_user->deleted = 1;
        $DB->update_record('user', $target_user);

        $target_user = new target_user($target_user);
        $context = context_system::instance();

        $result = recommended_user::execute_purge($target_user, $context);
        $this->assertEquals(recommended_user::RESULT_STATUS_SUCCESS, $result);

        $this->assertCount(
            0,
            $DB->get_records('ml_recommender_users', ['user_id' => $target_user->id])
        );

        //After purging target_user, there are 2 records that related to target_user 1 in the table.
        $this->assertCount(
            2,
            $DB->get_records('ml_recommender_users')
        );
    }
}