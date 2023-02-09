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
 * @package totara_topic
 */

use totara_topic\topic;

defined('MOODLE_INTERNAL') || die();

class totara_topic_webapi_resolver_query_find_topics_testcase extends advanced_testcase {
    use \totara_webapi\phpunit\webapi_phpunit_helper;

    private function execute_query(array $args) {
        return $this->resolve_graphql_query('totara_topic_find_topics', $args);
    }

    public function test_find_topics(): void {
        $this->setAdminUser();
        $t1 = topic::create("Green");
        $t2 = topic::create("Glue");
        $t3 = topic::create("Groove");
        $t4 = topic::create("regression");
        $t5 = topic::create("hang");
        topic::create("no");

        $result = $this->execute_query([
            'search' => 'G'
        ]);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);

        $ids = array_map(function ($topic) {
            return $topic->get_id();
        }, $result);

        $this->assertContainsEquals($t1->get_id(), $ids);
        $this->assertContainsEquals($t2->get_id(), $ids);
        $this->assertContainsEquals($t3->get_id(), $ids);
        $this->assertContainsEquals($t4->get_id(), $ids);
        $this->assertContainsEquals($t5->get_id(), $ids);

        $result1 = $this->execute_query([
            'search' => 'g'
        ]);

        $this->assertIsArray($result1);
        $this->assertCount(5, $result);
        $this->assertEqualsCanonicalizing($result1, $result);
    }

}