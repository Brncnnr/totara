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

use ml_recommender\userdata\interaction;
use totara_userdata\userdata\target_user;

class ml_recommender_userdata_interaction_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_interation_purge(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $creator = $gen->create_user();
        $target_user = $gen->create_user();
        $target_user1 = $gen->create_user();
        $this->setUser($creator);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();
        $playlist1 = $playlist_generator->create_playlist();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $gen->get_plugin_generator('engage_article');
        $article = $article_generator->create_article();

        /** @var \ml_recommender\testing\generator $recommendations_generator */
        $recommendations_generator = $gen->get_plugin_generator('ml_recommender');
        $recommendations_generator->create_recommender_interaction($target_user1->id, $playlist->get_id(), 'totara_playlist');
        $recommendations_generator->create_recommender_interaction($target_user1->id, $article->get_id(), 'engage_article');
        $recommendations_generator->create_recommender_interaction($target_user1->id, $playlist1->get_id(), 'totara_playlist');
        $recommendations_generator->create_recommender_interaction($target_user->id, $article->get_id(), 'engage_article');
        $recommendations_generator->create_recommender_interaction($target_user->id, $playlist->get_id(), 'totara_playlist');

        // Recommender_interactions created
        $this->assertTrue(
            $DB->record_exists('ml_recommender_interactions', ['user_id' => $target_user->id])
        );

        // Five records in table.
        $this->assertCount(
            5,
            $DB->get_records('ml_recommender_interactions')
        );

        $this->assertCount(
            2,
            $DB->get_records('ml_recommender_interactions', ['user_id' => $target_user->id])
        );

        // Delete target user.
        $target_user->deleted = 1;
        $DB->update_record('user', $target_user);

        $target_user = new target_user($target_user);
        $context = context_system::instance();

        $result = interaction::execute_purge($target_user, $context);
        $this->assertEquals(interaction::RESULT_STATUS_SUCCESS, $result);
        $this->assertCount(0, $DB->get_records('ml_recommender_interactions', ['user_id' => $target_user->id]));

        // There has to be 3 records left.
        $this->assertCount(
            3,
            $DB->get_records('ml_recommender_interactions')
        );

    }
}