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
 * @package totara_topic
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\graphql;
use core\webapi\execution_context;

class totara_topic_get_topics_testcase extends advanced_testcase {
    /**
     * @param int $count
     * @return void
     */
    private function generate_topics(int $count = 2): void {
        /** @var \totara_topic\testing\generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_topic');

        for ($i = 0; $i < $count; $i++) {
            $gen->create_topic();
        }
    }

    /**
     * @return void
     */
    public function test_get_system_topics(): void {
        $this->setAdminUser();
        $this->generate_topics(2);

        $ec = execution_context::create('ajax', 'totara_topic_system_topics');
        $result = graphql::execute_operation($ec, []);

        $this->assertEmpty($result->errors);
        $this->assertArrayHasKey('topics', $result->data);

        $topics = $result->data['topics'];
        $this->assertCount(2, $topics);
    }
}