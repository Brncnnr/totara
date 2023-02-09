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
 * @package engage_survey
 */

defined('MOODLE_INTERNAL') || die();

class engage_survey_topic_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_add_topic_to_survey(): void {
        global $DB;
        $this->setAdminUser();
        $this->executeAdhocTasks();

        $gen = $this->getDataGenerator();

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');
        $survey = $surveygen->create_survey();

        /** @var \totara_topic\testing\generator $topicgen */
        $topicgen = $gen->get_plugin_generator('totara_topic');
        $topicids = [];

        for ($i = 0; $i < 4; $i++) {
            $topic = $topicgen->create_topic();
            $topicids[] = $topic->get_id();
        }

        $survey->add_topics_by_ids($topicids);
        $params = [
            'itemid' => $survey->get_id(),
            'component' => $survey::get_resource_type(),
            'itemtype' => 'engage_resource'
        ];

        $this->assertTrue($DB->record_exists('tag_instance', $params));
        $this->assertEquals(4, $DB->count_records('tag_instance', $params));
    }
}
