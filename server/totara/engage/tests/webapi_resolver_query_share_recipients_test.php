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
 * @package totara_engage
 */

use core_user\totara_engage\share\recipient\user as user_recipient;
use container_workspace\totara_engage\share\recipient\library as library_recipient;

defined('MOODLE_INTERNAL') || die();

class totara_engage_webapi_resolver_query_share_recipients_testcase extends advanced_testcase {
    use \totara_webapi\phpunit\webapi_phpunit_helper;

    private function execute_query(array $args) {
        return $this->resolve_graphql_query('totara_engage_share_recipients', $args);
    }

    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        return $user;
    }

    private function create_article($name, $userid, $content = null): \engage_article\totara_engage\resource\article {
        /** @var \engage_article\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('engage_article');
        $params = [
            'name' => $name,
            'userid' => $userid,
        ];
        if ($content !== null) {
            $params['content'] = $content;
        }
        return $generator->create_article($params);
    }

    private function create_share(\totara_engage\share\shareable $item, int $fromuserid, array $recipients, $ownerid = null) {
        /** @var \totara_engage\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_engage');
        return $generator->share_item($item, $fromuserid, $recipients, $ownerid);
    }


    private function create_workspace($name, $userid, $summary = null, $private = false, $hidden = false): \container_workspace\workspace {
        /** @var \container_workspace\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('container_workspace');
        return $generator->create_workspace($name, $summary ?? "{$name} summary", FORMAT_PLAIN, $userid, $private, $hidden);
    }

    public function test_share_recipients_with_logged_owner() {
        $user1 = $this->setup_user();
        $article = $this->create_article('test', $user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $workspace = $this->create_workspace('test workspace', $user2->id);
        $recipient1 = new user_recipient($user3->id);
        $recipient2 = new user_recipient($user2->id);
        $recipient3 = new library_recipient($workspace->id);

        $this->create_share(
            $article,
            $user2->id,
            [$recipient1, $recipient2, $recipient3]
        );

        $result = $this->execute_query([
                'component' => $article::get_resource_type(),
                'itemid' => $article->get_id(),
                'theme' => 'ventura',
        ]);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $instance_ids = array_column($result, 'instanceid');
        $this->assertContainsEquals($user2->id, $instance_ids);
        $this->assertContainsEquals($user3->id, $instance_ids);
        $this->assertContainsEquals($workspace->id, $instance_ids);
    }

    public function test_share_recipients_with_invalid_itemid() {
        $user1 = $this->setup_user();
        $article = $this->create_article('test', $user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $workspace = $this->create_workspace('test workspace', $user2->id);
        $recipient1 = new user_recipient($user3->id);
        $recipient2 = new user_recipient($user2->id);
        $recipient3 = new library_recipient($workspace->id);

        $this->create_share(
            $article,
            $user2->id,
            [$recipient1, $recipient2, $recipient3]
        );

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Permission denied');
        $this->execute_query([
            'component' => $article::get_resource_type(),
            'itemid' => 11,
            'theme' => 'ventura',
        ]);
    }

    /**
     * Mock user create resource, recipients always return empty.
     */
    public function test_share_recipients_with_empty_itemid() {
        $this->setup_user();
        $result = $this->execute_query([
            'component' => 'engage_article',
            'itemid' => 0,
            'theme' => 'ventura',
        ]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_share_recipients_with_invalid_component() {
        $user1 = $this->setup_user();
        $article = $this->create_article('test', $user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $workspace = $this->create_workspace('test workspace', $user2->id);
        $recipient1 = new user_recipient($user3->id);
        $recipient2 = new user_recipient($user2->id);
        $recipient3 = new library_recipient($workspace->id);

        $this->create_share(
            $article,
            $user2->id,
            [$recipient1, $recipient2, $recipient3]
        );

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Permission denied');
        $this->execute_query([
            'component' => 'engage_aaarticle',
            'itemid' => $article->get_id(),
            'theme' => 'ventura',
        ]);
    }

    public function test_share_recipients_with_different_logged_user() {
        $user1 = $this->setup_user();
        $article = $this->create_article('test', $user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $workspace = $this->create_workspace('test workspace', $user2->id);
        $recipient1 = new user_recipient($user3->id);
        $recipient2 = new user_recipient($user2->id);
        $recipient3 = new library_recipient($workspace->id);

        $this->create_share(
            $article,
            $user2->id,
            [$recipient1, $recipient2, $recipient3]
        );

        $current_user = $this->getDataGenerator()->create_user();
        // Logging as current user
        $this->setUser($current_user);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Permission denied');
        $this->execute_query([
            'component' => $article::get_resource_type(),
            'itemid' => $article->get_id(),
            'theme' => 'ventura',
        ]);
    }

    public function test_share_recipients_with_admin() {
        $user1 = $this->setup_user();
        $article = $this->create_article('test', $user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $workspace = $this->create_workspace('test workspace', $user2->id);
        $recipient1 = new user_recipient($user3->id);
        $recipient2 = new user_recipient($user2->id);
        $recipient3 = new library_recipient($workspace->id);

        $this->create_share(
            $article,
            $user2->id,
            [$recipient1, $recipient2, $recipient3]
        );

        $this->setAdminUser();
        $result = $this->execute_query([
            'component' => $article::get_resource_type(),
            'itemid' => $article->get_id(),
            'theme' => 'ventura',
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $instance_ids = array_column($result, 'instanceid');
        $this->assertContainsEquals($user2->id, $instance_ids);
        $this->assertContainsEquals($user3->id, $instance_ids);
        $this->assertContainsEquals($workspace->id, $instance_ids);
    }
}