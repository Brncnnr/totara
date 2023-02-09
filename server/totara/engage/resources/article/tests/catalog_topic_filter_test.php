<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use totara_catalog\catalog_retrieval;
use totara_catalog\filter;
use totara_catalog\provider_handler;
use totara_engage\access\access;
use totara_topic\topic;
use totara_topic\formatter\topic_formatter;
use totara_catalog\local\config;

class engage_article_catalog_topic_filter_testcase extends advanced_testcase {
    /**
     * Validate the following:
     *  - Articles all load initially
     */
    public function test_articles_show_all_by_default(): void {
        $this->generate();

        $user_one = $this->getDataGenerator()->create_user();
        $this->setUser($user_one);

        $retrieval = new catalog_retrieval();
        $results = $retrieval->get_page_of_objects(10, 0);

        $this->assertIsArray($results->objects);
        $this->assertCount(5, $results->objects);
    }

    /**
     * Validate the following:
     *  - Articles are correctly filtered by topics via the sidebar panel
     */
    public function test_article_panel_filter(): void {
        /** @var filter $topic_filter */
        /** @var topic $topics */
        [$topics, $articles, $filters] = $this->generate();

        $user_one = $this->getDataGenerator()->create_user();
        $this->setUser($user_one);

        /** @var filter $filter */
        $filter = $filters[0]; // Panel filter.

        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($topics as $i => $topic) {
            $filter_data->set_current_data([$topic->get_id()]);
            $results = $catalog->get_page_of_objects(10, 0);

            $this->assertCount(1, $results->objects);
            $this->assertEquals($articles[$i], $results->objects[0]->objectid);
        }
    }

    /**
     * Validate the following:
     *  - Articles are correctly filtered by topics via the browse filter
     */
    public function test_article_browse_filter(): void {
        /** @var filter $topic_filter */
        /** @var topic $topics */
        [$topics, $articles, $filters] = $this->generate();

        $user_one = $this->getDataGenerator()->create_user();
        $this->setUser($user_one);

        /** @var filter $filter */
        $filter = $filters[1]; // Browse filter.

        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($topics as $i => $topic) {
            $filter_data->set_current_data($topic->get_id());
            $results = $catalog->get_page_of_objects(10, 0);

            $this->assertCount(1, $results->objects);
            $this->assertEquals($articles[$i], $results->objects[0]->objectid);
        }
    }

    public function test_topic_catalog_links() {
        global $CFG;

        /** @var filter $topic_filter */
        /** @var topic $topics */
        [$topics, $articles, $filters] = $this->generate();

        $topic = array_shift($topics);
        $formatter = new topic_formatter($topic);

        $reflection = new ReflectionClass($formatter);
        $method = $reflection->getMethod('topic_catalog_filter');
        $method->setAccessible(true);

        // Topic catalog filter not enabled.
        $catalog_parameter = $method->invoke($formatter, $topic);
        $this->assertStringContainsString('catalog_fts=topic+1', $catalog_parameter);

        // Enable topic filter.
        set_config('filters',  '{"tag_panel_' . $CFG->topic_collection_id . '":"Topics"}', 'totara_catalog');

        // Topic catalog filter is enabled.
        $catalog_parameter = $method->invoke($formatter, $topic);
        $this->assertStringContainsString('tag_panel_' . $CFG->topic_collection_id . '%5B0%5D=' . $topic->get_id(), $catalog_parameter);
    }

    public function test_topic_catalog_filter_enabled() {
        global $CFG;

        $store = $CFG->topic_collection_id;

        set_config('filters',  '{"tag_panel_' . $CFG->topic_collection_id . '":"Topics"}', 'totara_catalog');
        $result = totara_topic\topic_helper::topic_catalog_filter_enabled();
        $this->assertTrue($result);

        set_config('filters',  '{"tag_panel_' . $CFG->topic_collection_id . '":"Another Topic Label"}', 'totara_catalog');
        $result = totara_topic\topic_helper::topic_catalog_filter_enabled();
        $this->assertTrue($result);

        set_config('filters',  '', 'totara_catalog');
        $result = totara_topic\topic_helper::topic_catalog_filter_enabled();
        $this->assertFalse($result);

        unset($CFG->topic_collection_id);

        $result = totara_topic\topic_helper::topic_catalog_filter_enabled();
        $CFG->topic_collection_id = $store;
        $this->assertFalse($result);
    }

    /**
     * Set the tests up
     *
     * @return array
     */
    private function generate(): array {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        // Test case will be very contrived, Topic 1 => Article 1 etc...
        $topics = [];
        $articles = [];
        for ($i = 1; $i <= 5; $i++) {
            $topic = $topic_generator->create_topic("Topic {$i}");
            $article = $article_generator->create_article([
                'name' => "Article {$i}",
                'topics' => [
                    $topic->get_id()
                ],
                'access' => access::PUBLIC,
            ]);
            $articles[$i] = $article->get_instanceid();
            $topics[$i] = $topic;
        }

        // Verify filtering works
        // Get the topic collection ID as that's needed to find the topic filter
        $collection_id = \core_tag_area::get_collection('engage_article', 'engage_resource');

        provider_handler::instance()->reset_cache();

        $browse_filter = null;
        $panel_filter = null;
        $filters = provider_handler::instance()->get_provider('engage_article')->get_filters();
        foreach ($filters as $filter) {
            if ($filter->key === "tag_panel_{$collection_id}") {
                // As we only want to test the article filter, we need to split it away from
                // the playlist filter (which can interfere with unit tests). By making
                // the name unique we create two topic filters on the catalog which works for testing.
                $filter->key = "article_{$filter->key}";
                $panel_filter = $filter;
            }
            if ($filter->key === "tag_browse_{$collection_id}") {
                $filter->key = "article_{$filter->key}";
                $browse_filter = $filter;
            }

            if ($panel_filter && $browse_filter) {
                break;
            }
        }

        $this->assertNotNull($panel_filter, "topic type panel filter not loaded");
        $this->assertNotNull($browse_filter, "topic type browse filter not loaded");
        $filters = [$panel_filter, $browse_filter];

        /** @var filter $topic_filter */
        return [$topics, $articles, $filters];
    }
}