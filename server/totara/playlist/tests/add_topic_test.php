<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_topic\topic;
use totara_topic\topic_helper;

final class totara_playlist_add_topic_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_add_topics(): void {
        global $DB;

        $this->setAdminUser();
        $this->executeAdhocTasks();

        $gen = $this->getDataGenerator();

        /** @var \totara_topic\testing\generator $topicgen */
        $topicgen = $gen->get_plugin_generator('totara_topic');
        $topics = [];

        for ($i = 0; $i < 5; $i++) {
            $topics[] = $topicgen->create_topic();
        }

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlistgen->create_playlist();

        /** @var topic $topic */
        foreach ($topics as $topic) {
            topic_helper::add_topic_usage(
                $topic->get_id(),
                'totara_playlist',
                'playlist',
                $playlist->get_id()
            );
        }

        $sql = '
            SELECT * FROM "ttr_tag_instance" 
            WHERE itemid = :itemid 
              AND component = :component 
              AND itemtype = :itemtype
        ';

        $params = [
            'itemid' => $playlist->get_id(),
            'component' => 'totara_playlist',
            'itemtype' => 'playlist'
        ];

        // 5 topics had been added into playlist instance.
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(5, $records);
    }
}
