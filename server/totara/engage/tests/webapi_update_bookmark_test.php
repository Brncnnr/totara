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

defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use totara_webapi\phpunit\webapi_phpunit_helper;
use engage_article\totara_engage\resource\article;
use core_user\totara_engage\share\recipient\user as usr_recipient;

class totara_engage_webapi_update_bookmark_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    public function test_update_bookmark(): void {
        $data = $this->create_mock_data();

        // Login as recipient.
        $this->setUser($data['recipient']);
        $resource = $data['resource'];
        $result = $this->execute_query(
            [
                'itemid' => $resource->get_id(),
                'component' => $resource::get_resource_type(),
                'bookmarked' => true
            ]
        );

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    public function test_update_bookmark_with_logged_owner(): void {
        $data = $this->create_mock_data();
        // Login as owner.
        $this->setUser($data['owner']);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Permission denied');
        $resource = $data['resource'];
        $this->execute_query(
            [
                'itemid' => $resource->get_id(),
                'component' => $resource::get_resource_type(),
                'bookmarked' => true
            ]
        );
    }

    public function test_update_bookmark_with_logged_admin(): void {
        $data = $this->create_mock_data(access::RESTRICTED);

        $this->setAdminUser();
        $resource = $data['resource'];
        $result = $this->execute_query(
            [
                'itemid' => $resource->get_id(),
                'component' => $resource::get_resource_type(),
                'bookmarked' => true
            ]
        );

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    public function test_update_bookmark_with_logged_random_user(): void {
        $data = $this->create_mock_data(access::RESTRICTED);
        $user1 = $this->getDataGenerator()->create_user();

        // Login as random user
        $this->setUser($user1);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Permission denied');
        $resource = $data['resource'];
        $this->execute_query(
            [
                'itemid' => $resource->get_id(),
                'component' => $resource::get_resource_type(),
                'bookmarked' => true
            ]
        );
    }

    public function test_update_bookmark_with_invalid_id(): void {
        $data = $this->create_mock_data();
        $this->setUser($data['recipient']);

        $this->expectException(moodle_exception::class);
        $this->execute_query(
            [
                'itemid' => '22',
                'component' => $data['resource']::get_resource_type(),
                'bookmarked' => true
            ]
        );
    }

    public function test_update_bookmark_with_invalid_component(): void {
        $data = $this->create_mock_data();
        $this->setUser($data['recipient']);

        $this->expectException(moodle_exception::class);
        $this->execute_query(
            [
                'itemid' => $data['resource']->get_id(),
                'component' => 'engage_survey',
                'bookmarked' => true
            ]
        );
    }

    public function test_update_bookmark_for_private_resource(): void {
        $user = $this->setup_user();
        $article = $this->create_article(['user_id' => $user->id, 'access' => access::PRIVATE]);
        $user1 = $this->getDataGenerator()->create_user();

        // Login as random user
        $this->setUser($user1);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Permission denied');
        $this->execute_query(
            [
                'itemid' => $article->get_id(),
                'component' => $article::get_resource_type(),
                'bookmarked' => true
            ]
        );
    }

    public function test_update_bookmark_for_playlist(): void {
        $user = $this->setup_user();

        $generator = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['userid' => $user->id, 'access' => access::RESTRICTED]);

        $user1 = $generator->create_user();
        $this->create_share(
            $playlist,
            $user1->id,
            [new usr_recipient($user1->id)]
        );

        $this->setUser($user1);
        $result = $this->execute_query(
            [
                'itemid' => $playlist->get_id(),
                'component' => $playlist::get_resource_type(),
                'bookmarked' => true
            ]
        );

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    public function test_update_bookmark_for_survey(): void {
        $this->setup_user();

        $generator = $this->getDataGenerator();
        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $generator->get_plugin_generator('engage_survey');
        $survey = $surveygen->create_public_survey();

        $user1 = $generator->create_user();
        $this->create_share(
            $survey,
            $user1->id,
            [new usr_recipient($user1->id)]
        );

        $this->setUser($user1);
        $result = $this->execute_query(
            [
                'itemid' => $survey->get_id(),
                'component' => $survey::get_resource_type(),
                'bookmarked' => true
            ]
        );

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    public function test_update_bookmark_for_private_survey(): void {
        $this->setup_user();

        $generator = $this->getDataGenerator();
        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $generator->get_plugin_generator('engage_survey');
        // Private survey.
        $survey = $surveygen->create_survey();

        $user1 = $generator->create_user();
        // Login as random user
        $this->setUser($user1);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Permission denied');
        $this->execute_query(
            [
                'itemid' => $survey->get_id(),
                'component' => $survey::get_resource_type(),
                'bookmarked' => true
            ]
        );
    }

    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        return $user;
    }

    private function create_mock_data(?int $access = access::PUBLIC): array {
        $user = $this->setup_user();
        $article = $this->create_article(['user_id' => $user->id, 'access' => $access]);
        $user1 = $this->getDataGenerator()->create_user();
        $this->create_share(
            $article,
            $user1->id,
            [new usr_recipient($user1->id)]
        );

        return ['resource' => $article, 'owner' => $user, 'recipient' => $user1];
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

    private function execute_query(array $args) {
        return $this->resolve_graphql_mutation('totara_engage_update_bookmark', $args);
    }
}