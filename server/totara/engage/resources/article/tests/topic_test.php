<?php
/**
 * This file is part of Totara LMS
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

class engage_article_topic_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_add_topic_to_article(): void {
        global $DB;

        $this->executeAdhocTasks();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article();

        /** @var \totara_topic\testing\generator $topicgen */
        $topicgen = $gen->get_plugin_generator('totara_topic');
        $topics = [];

        for ($i = 0; $i < 2; $i++) {
            $topics[] = $topicgen->create_topic()->get_id();
        }

        $article->add_topics_by_ids($topics);
        $params = [
            'itemid' => $article->get_id(),
            'itemtype' => 'engage_resource',
        ];

        $this->assertTrue(
            $DB->record_exists('tag_instance', $params)
        );
    }
}
