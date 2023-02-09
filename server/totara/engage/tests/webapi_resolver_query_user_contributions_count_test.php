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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_engage
 */

defined('MOODLE_INTERNAL') || die();

use engage_article\totara_engage\resource\article;
use totara_engage\access\access;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the user_contributions_count query
 */
class totara_engage_webapi_resolver_query_user_contributions_count_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private const QUERY = 'totara_engage_user_contributions_count';
    private const OPERATION_NAME = 'totara_engage_user_contribution_cards';

    public function test_user_contributions_count(): void {
        $user = $this->setup_user();
        $this->create_article('test', $user->id);
        $result = $this->execute_query(['component' => 'engage_article', 'user_id' => $user->id, 'area' => 'otheruserlib']);
        $this->assertIsNumeric($result);
    }

    public function test_no_arguments(): void {
        $this->setup_user();
        self::expectException(coding_exception::class);
        $this->execute_query([]);
    }

    public function test_invalid_component_name_not_accepted(): void {
        $user = $this->setup_user();
        self::expectException(coding_exception::class);
        self::expectExceptionMessage("Component is a required field.");
        $this->execute_query(['user_id' => $user->id, 'area' => 'otheruserlib']);
    }

    public function test_invalid_area_not_accepted(): void {
        $user = $this->setup_user();
        self::expectException(coding_exception::class);
        self::expectExceptionMessage("Query user_contributions_count does not support the 'test' area.");
        $this->execute_query(['component' => 'engage_article', 'user_id' => $user->id, 'area' => 'test']);
    }

    public function test_invalid_userid_not_accepted(): void {
        $this->setup_user();
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Query user_contributions_count must specify the "user_id" field');
        $this->execute_query(['component' => 'engage_article']);
    }

    public function test_count_exclude_private(): void {
        $user1 = $this->setup_user();
        $this->create_article('Private 1', $user1->id, access::PRIVATE);
        $this->create_article('Public 1', $user1->id, access::PUBLIC);

        // View as another usr
        $this->setup_user();

        $result = $this->execute_query(['component' => 'engage_article', 'user_id' => $user1->id, 'area' => 'otheruserlib']);
        $this->assertEquals(1, $result);
    }

    public function test_count_includes_shared(): void {
        $user1 = $this->setup_user();
        $this->create_article('Public 1', $user1->id, access::PUBLIC);
        $article = $this->create_article('Restricted 1', $user1->id, access::RESTRICTED);

        // View as another user
        $user2 = $this->setup_user();

        /** @var \engage_article\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('engage_article');
        $recipients = $generator->create_user_recipients([$user2]);

        $this->setUser($user2);

        // Only see the public result first
        $result = $this->execute_query(['component' => 'engage_article', 'user_id' => $user1->id, 'area' => 'otheruserlib']);
        $this->assertEquals(1, $result);

        // Share the resource
        $generator->share_article($article, $recipients);

        // Should see both now
        $result = $this->execute_query(['component' => 'engage_article', 'user_id' => $user1->id, 'area' => 'otheruserlib']);
        $this->assertEquals(2, $result);
    }

    /**
     * Helper to call the query
     *
     * @param array $args
     * @return mixed|null
     */
    private function execute_query(array $args) {
        return $this->resolve_graphql_query(self::QUERY, $args);
    }

    /***
     * Make a simple user
     *
     * @return array|stdClass
     */
    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        return $user;
    }

    /**
     * Create a test article
     *
     * @param string $name
     * @param int $userid
     * @param int $access
     * @return article
     */
    private function create_article(string $name, int $userid, int $access = access::PUBLIC): article {
        /** @var \engage_article\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('engage_article');
        $params = compact('name', 'userid', 'access');
        return $generator->create_article($params);
    }
}