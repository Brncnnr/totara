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
 * @author Qingyang liu <qingyang.liu@totaralearning.com>
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use engage_article\totara_engage\resource\article;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_playlist_webapi_remove_resource_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    public function test_remove_resource_by_owner() {
        $user = $this->setup_user();
        $playlist = $this->create_playlist(['userid' => $user->id]);
        $data = $this->add_resource_to_plyalist($playlist, $user->id);

        $this->assertCount(3, $data);
        $result = $this->execute_mutation([
            'id' => $playlist->get_id(),
            'instanceid' => $data[0]->get_id()
        ]);

        $this->assertIsBool($result);
        $this->assertTrue($result);

        // Refresh the playlist.
        $playlist->load_resources();
        $this->assertFalse($playlist->has_resource($data[0]->get_id()));
        $this->assertTrue($playlist->has_resource($data[1]->get_id()));
        $this->assertTrue($playlist->has_resource($data[2]->get_id()));
    }

    public function test_remove_resource_by_admin() {
        $user = $this->setup_user();
        $playlist = $this->create_playlist(['userid' => $user->id]);
        $data = $this->add_resource_to_plyalist($playlist, $user->id);

        $this->setAdminUser();
        $result = $this->execute_mutation([
            'id' => $playlist->get_id(),
            'instanceid' => $data[0]->get_id()
        ]);

        $this->assertIsBool($result);
        $this->assertTrue($result);

        // Refresh the playlist.
        $playlist->load_resources();
        $this->assertFalse($playlist->has_resource($data[0]->get_id()));
        $this->assertTrue($playlist->has_resource($data[1]->get_id()));
        $this->assertTrue($playlist->has_resource($data[2]->get_id()));
    }

    public function test_remove_resource_by_random_user() {
        $user = $this->setup_user();
        $playlist = $this->create_playlist(['userid' => $user->id, 'access' => \totara_engage\access\access::PUBLIC]);
        $data = $this->add_resource_to_plyalist($playlist, $user->id);

        $this->assertTrue($playlist->is_public());
        $this->assertTrue($data[0]->is_public());

        $user1 = $this->getDataGenerator()->create_user();

        //Login as random user.
        $this->setUser($user1);

        $this->expectException(\totara_playlist\exception\playlist_exception::class);
        $this->expectExceptionMessage('Cannot remove the resource from the playlist');
        $this->execute_mutation([
            'id' => $playlist->get_id(),
            'instanceid' => $data[0]->get_id()
        ]);
    }

    public function test_remove_resource_by_recipient() {
        $user = $this->setup_user();
        $playlist = $this->create_playlist(['userid' => $user->id, 'access' => \totara_engage\access\access::PUBLIC]);
        $data = $this->add_resource_to_plyalist($playlist, $user->id);

        $this->assertTrue($playlist->is_public());
        $this->assertTrue($data[0]->is_public());

        $user1 = $this->getDataGenerator()->create_user();
        $recipient = new \core_user\totara_engage\share\recipient\user($user1->id);
        $this->create_share(
            $playlist,
            $user->id,
            [$recipient]
        );

        //Login as recipient.
        $this->setUser($user1);

        $this->expectException(\totara_playlist\exception\playlist_exception::class);
        $this->expectExceptionMessage('Cannot remove the resource from the playlist');
        $this->execute_mutation([
            'id' => $playlist->get_id(),
            'instanceid' => $data[0]->get_id()
        ]);
    }

    public function test_remove_resource_by_invalid_id() {
        $user = $this->setup_user();
        $playlist = $this->create_playlist(['userid' => $user->id]);
        $data = $this->add_resource_to_plyalist($playlist, $user->id);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Permission denied');
        $this->execute_mutation([
            'id' => 12,
            'instanceid' => $data[0]->get_id()
        ]);
    }

    public function test_remove_resource_by_invalid_instanceid() {
        $user = $this->setup_user();
        $playlist = $this->create_playlist(['userid' => $user->id]);
        $this->add_resource_to_plyalist($playlist, $user->id);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Permission denied');
        $this->execute_mutation([
            'id' => $playlist->get_id(),
            'instanceid' => '1222'
        ]);
    }

    private function add_resource_to_plyalist(\totara_playlist\playlist  $playlist, $user_id): array {
        $article1 = $this->create_article();
        $article2 = $this->create_article();
        $article3 = $this->create_article();
        $playlist->add_resource($article1, $user_id);
        $playlist->add_resource($article2, $user_id);
        $playlist->add_resource($article3, $user_id);

        return [$article1, $article2, $article3];
    }

    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        return $user;
    }

    private function create_playlist(?array $params = []): \totara_playlist\playlist {
        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $this->getDataGenerator()->get_plugin_generator('totara_playlist');
        return $playlist_generator->create_playlist($params);
    }

    private function create_article(?array $params = []): article {
        /** @var \engage_article\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('engage_article');
        return $generator->create_article($params);
    }

    private function create_share(\totara_engage\share\shareable $item, int $fromuserid, array $recipients, $ownerid = null) {
        /** @var \totara_engage\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_engage');
        return $generator->share_item($item, $fromuserid, $recipients, $ownerid);
    }

    private function execute_mutation(array $args) {
        return $this->resolve_graphql_mutation('totara_playlist_remove_resource', $args);
    }
}