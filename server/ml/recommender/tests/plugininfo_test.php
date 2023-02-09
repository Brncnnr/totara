<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package ml_recommender
 */

use ml_recommender\plugininfo;
use totara_core\advanced_feature;
use totara_engage\access\access;

/**
 * @group ml_recommender
 */
class ml_recommender_plugininfo_testcase extends advanced_testcase {

    public function test_plugininfo_data() {
        $this->setAdminUser();

        $plugininfo = new plugininfo();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['recommenderenabled']);
        $this->assertEquals(0, $result['numinteractions']);
        $this->assertEquals(0, $result['numtrending']);
        $this->assertEquals(0, $result['numitems']);
        $this->assertEquals(0, $result['numusers']);

        // Generate test data
        $this->generate_data();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['recommenderenabled']);
        $this->assertEquals(1, $result['numinteractions']);
        $this->assertEquals(1, $result['numitems']);
        $this->assertEquals(1, $result['numtrending']);
        $this->assertEquals(1, $result['numusers']);

        advanced_feature::disable('ml_recommender');
        $result = $plugininfo->get_usage_for_registration_data();

        // Data should be returned even if features are disabled.
        $this->assertEquals(0, $result['recommenderenabled']);
        $this->assertEquals(1, $result['numinteractions']);
        $this->assertEquals(1, $result['numitems']);
        $this->assertEquals(1, $result['numtrending']);
        $this->assertEquals(1, $result['numusers']);
    }

    /**
     * Get recommender generator
     *
     * @return \ml_recommender\testing\generator
     * @throws coding_exception
     */
    protected function generator() {
        return \ml_recommender\testing\generator::instance();
    }

    /**
     * Generate data required to set registration stats
     */
    private function generate_data() {
        $generator = $this->getDataGenerator();
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');

        $this->setAdminUser();
        $topic = $topic_generator->create_topic();

        $user = $generator->create_user();

        // This is our target article. We're going to ask for recommendations related to this article
        $target_article = $article_generator->create_article([
            'name' => 'Target Article',
            'userid' => $user->id,
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()],
        ]);

        // Going to create another article, then recommend it
        $article = $article_generator->create_article([
            'name' => 'Article',
            'userid' => $user->id,
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()],
        ]);

        $this->generator()->create_recommender_interaction($user->id, $target_article->get_id(), $target_article->get_resourcetype(), 'view');
        $this->generator()->create_item_recommendation($target_article->get_id(), $article->get_id(), $article->get_resourcetype(), null, 2.5);
        $this->generator()->create_trending_recommendation($article->get_id(), $article->get_resourcetype(), null, 1);
        $this->generator()->create_user_recommendation($user->id, $article->get_id(), $article->get_resourcetype(), null, 3);
    }
}
