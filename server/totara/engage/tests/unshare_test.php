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

use core_phpunit\testcase;
use totara_engage\access\access;
use totara_engage\bookmark\bookmark;
use totara_engage\share\manager as share_manager;
use core\webapi\execution_context;
use totara_engage\share\share;
use totara_webapi\graphql;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_playlist\testing\generator as playlist_generator;
use container_workspace\testing\generator as workspace_generator;

class totara_engage_unshare_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     *  @return void
     */
    public function test_unshare(): void {
        global $DB;

        $gen = $this->getDataGenerator();

        /** @var \engage_article\testing\generator $article_gen */
        $article_gen = $gen->get_plugin_generator('engage_article');
        /** @var \engage_survey\testing\generator $survey_gen */
        $survey_gen = $gen->get_plugin_generator('engage_survey');

        // Create users.
        $users = $article_gen->create_users(2);
        $this->setUser($users[0]);

        // Create items to share.
        $article = $article_gen->create_article();
        $survey = $survey_gen->create_survey();

        // Setup recipients.
        $recipients = $article_gen->create_user_recipients([$users[1]]);

        // Get shares.
        $article_shares = $article_gen->share_article($article, $recipients);
        $survey_shares = $survey_gen->share_survey($survey, $recipients);
        $article_share = reset($article_shares);
        $survey_share = reset($survey_shares);

        // Confirm that the shares exist and that they are visible.
        $this->assertTrue($DB->record_exists('engage_share', ['id' => $article_share->get_id()]));
        $this->assertEquals(1, $DB->get_field('engage_share_recipient', 'visibility', ['id' => $article_share->get_recipient_id()]));
        $this->assertTrue($DB->record_exists('engage_share', ['id' => $survey_share->get_id()]));
        $this->assertEquals(1, $DB->get_field('engage_share_recipient', 'visibility', ['id' => $survey_share->get_recipient_id()]));

        // Unshare the items and confirm that they are no longer visible by the user.
        share_manager::unshare($article_share->get_recipient_id(), $article);
        share_manager::unshare($survey_share->get_recipient_id(), $survey);
        $this->assertTrue($DB->record_exists('engage_share', ['id' => $article_share->get_id()]));
        $this->assertEquals(0, $DB->get_field('engage_share_recipient', 'visibility', ['id' => $article_share->get_recipient_id()]));
        $this->assertTrue($DB->record_exists('engage_share', ['id' => $survey_share->get_id()]));
        $this->assertEquals(0, $DB->get_field('engage_share_recipient', 'visibility', ['id' => $survey_share->get_recipient_id()]));

        // Try to unshare a recipient that does not link up to the share.
        $this->expectException('coding_exception', 'Invalid recipient_id for shared item');
        share_manager::unshare($survey_share->get_recipient_id(), $article);
    }

    /**
     *  @return void
     */
    public function test_unshare_user_via_graphql(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        /** @var playlist_generator $playlistgen */
        $playlist_gen = $gen->get_plugin_generator('totara_playlist');

        // Create users.
        $users = $playlist_gen->create_users(2);

        // Create playlist.
        $this->setUser($users[0]);
        $playlist = $playlist_gen->create_playlist(['access' => access::PUBLIC]);

        $recipients = $playlist_gen->create_user_recipients([$users[1]]);
        $shares = $playlist_gen->share_playlist($playlist, $recipients);

        /** @var share $share */
        $share = reset($shares);

        $this->assertTrue($DB->record_exists('engage_share', ['id' => $share->get_id()]));
        $this->assertEquals(1, $DB->get_field('engage_share_recipient', 'visibility', ['id' => $share->get_recipient_id()]));

        $this->setUser($users[0]);
        $parameters = [
            'recipient_id' => $share->get_recipient_id(),
            'component' => 'totara_playlist',
            'item_id' => $playlist->get_id()
        ];

        // Remove share via graphql.
        $ec = execution_context::create('ajax', 'totara_engage_unshare');
        $result = graphql::execute_operation($ec, $parameters);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertTrue($DB->record_exists('engage_share', ['id' => $share->get_id()]));
        $this->assertEquals(0, $DB->get_field('engage_share_recipient', 'visibility', ['id' => $share->get_recipient_id()]));
    }

    /**
     *  @return void
     */
    public function test_unshare_workspace_via_graphql(): void {
        global $DB;

        // Get generators.
        $gen = $this->getDataGenerator();
        /** @var playlist_generator $playlistgen */
        $playlist_gen = $gen->get_plugin_generator('totara_playlist');
        /** @var workspace_generator $workspace_gen */
        $workspace_gen = $gen->get_plugin_generator('container_workspace');

        // Create a user.
        $user = $gen->create_user();

        // Create a playlist.
        $this->setUser($user);
        $playlist = $playlist_gen->create_playlist(['access' => access::PUBLIC]);

        // Create a few workspaces we can share to.
        $workspaces = [
            $workspace_gen->create_workspace(),
            $workspace_gen->create_workspace(),
            $workspace_gen->create_workspace(),
            $workspace_gen->create_workspace(),
            $workspace_gen->create_workspace(),
        ];

        // Share the playlist to all workspaces.
        $recipients = $workspace_gen->create_workspace_recipients($workspaces);
        $shares = $playlist_gen->share_playlist($playlist, $recipients);

        // Unshare all shares.
        /** @var share $share */
        foreach ($shares as $share) {
            $this->assertTrue($DB->record_exists('engage_share', ['id' => $share->get_id()]));
            $this->assertEquals(1, $DB->get_field('engage_share_recipient', 'visibility', ['id' => $share->get_recipient_id()]));

            $parameters = [
                'recipient_id' => $share->get_recipient_id(),
                'component' => 'totara_playlist',
                'item_id' => $playlist->get_id()
            ];

            // Remove share via graphql.
            $ec = execution_context::create('ajax', 'totara_engage_unshare');
            $result = graphql::execute_operation($ec, $parameters);

            $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message : '');
            $this->assertNotEmpty($result->data);

            $this->assertTrue($DB->record_exists('engage_share', ['id' => $share->get_id()]));
            $this->assertEquals(0, $DB->get_field('engage_share_recipient', 'visibility', ['id' => $share->get_recipient_id()]));
        }
    }

    public function test_unshare_before_bookmark_item(): void {
        global $DB;

        $gen = $this->getDataGenerator();

        /** @var \engage_article\testing\generator $article_gen */
        $article_gen = $gen->get_plugin_generator('engage_article');

        // Create users.
        $users = $article_gen->create_users(2);
        $this->setUser($users[0]);
        // Create items to share.
        $article = $article_gen->create_article(['access' => access::RESTRICTED]);

        // Setup recipients.
        $recipients = $article_gen->create_user_recipients([$users[1]]);

        // Get shares.
        $article_shares = $article_gen->share_article($article, $recipients);

        $article_share = reset($article_shares);
        $this->assertTrue($DB->record_exists('engage_share', ['id' => $article_share->get_id()]));

        // Bookmark shared resource.
        $this->setUser($users[1]);
        $bookmark = new bookmark($users[1]->id, $article->get_id(), $article::get_resource_type());
        $bookmark->add_bookmark();
        $this->assertTrue($DB->record_exists('engage_bookmark', ['itemid' => $bookmark->get_itemid()]));

        // Unshare the resource.
        share_manager::unshare($article_share->get_recipient_id(), $article);
        $this->assertEquals(0, $DB->get_field('engage_share_recipient', 'visibility', ['id' => $article_share->get_recipient_id()]));

        // User still has permission to visit the shared resource.
        $result = $this->resolve_graphql_query(
            'engage_article_get_article',
            ['id' => $article->get_id()]
        );

        self::assertEquals($article->get_id(), $result->get_id());
    }
}