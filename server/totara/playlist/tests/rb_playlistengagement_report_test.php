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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_core\advanced_feature;
use totara_comment\comment_helper;
use totara_engage\access\access;
use totara_reportbuilder\report_helper;

/**
 * @group totara_reportbuilder
 * @group totara_playlist
 */
class totara_playlist_rb_playlistengagement_report_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     *  @return void
     */
    public function test_playlistengagement_report(): void {
        global $DB;

        $gen = $this->getDataGenerator();

        // Create three different creators.
        $user_one = $gen->create_user();
        $user_two = $gen->create_user();
        $user_three = $gen->create_user();

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        /** @var \container_workspace\testing\generator $workspacegen */
        $workspacegen = $gen->get_plugin_generator('container_workspace');

        /** @var \totara_topic\testing\generator $topicgen */
        $topicgen = $gen->get_plugin_generator('totara_topic');

        // Create playlists.
        $playlist1 = $playlistgen->create_playlist(['userid' => $user_one->id, 'access' => access::PUBLIC, 'name' => 'playlist1']);
        $playlist2 = $playlistgen->create_playlist(['userid' => $user_one->id, 'access' => access::RESTRICTED, 'name' => 'playlist2']);
        $playlist3 = $playlistgen->create_playlist(['userid' => $user_two->id, 'access' => access::PUBLIC, 'name' => 'playlist3']);
        $playlist4 = $playlistgen->create_playlist(['userid' => $user_two->id, 'name' => 'playlist4']);
        $playlist5 = $playlistgen->create_playlist(['userid' => $user_three->id, 'access' => access::PUBLIC, 'name' => 'playlist5']);

        // Add resources into playlist.
        for ($i = 0; $i < 6; $i++) {
            $this->setUser($user_one);
            $article = $articlegen->create_article();
            $playlist1->add_resource($article);

            if ($i < 3) {
                $this->setUser($user_two);
                $article = $articlegen->create_article();
                $playlist3->add_resource($article);
            }

            if ($i >= 4) {
                $this->setUser($user_three);
                $article = $articlegen->create_article();
                $playlist5->add_resource($article);
            }
        }

        // Add rating.
        $playlistgen->add_rating($playlist2, 3, $user_two->id);
        $playlistgen->add_rating($playlist2, 2, $user_three->id);
        $playlistgen->add_rating($playlist3, 2, $user_one->id);
        $playlistgen->add_rating($playlist4, 2, $user_one->id);
        $playlistgen->add_rating($playlist4, 2, $user_three->id);
        $playlistgen->add_rating($playlist5, 2, $user_one->id);

        // Create comments.
        $this->create_comment($playlist1->get_id(), $user_two->id, 5);
        $this->create_comment($playlist3->get_id(), $user_two->id, 2);
        $this->create_comment($playlist5->get_id(), $user_one->id, 1);

        // Share playlists to user.
        $users = $playlistgen->create_users(3);
        $recipients = $playlistgen->create_user_recipients($users);
        $this->setUser($user_two);
        $playlistgen->share_playlist($playlist1, $recipients);
        $playlistgen->share_playlist($playlist3, $recipients);

        array_shift($recipients);
        $playlistgen->share_playlist($playlist5, $recipients);

        $this->setUser($user_one);
        $playlistgen->share_playlist($playlist4, $recipients);
        array_shift($recipients);
        $playlistgen->share_playlist($playlist2, $recipients);

        // Create workspaces.
        $workspace_creator = $gen->create_user();
        $workspace_list = [];
        $this->setUser($workspace_creator);
        for ($i = 0; $i < 3; $i++) {
            $workspacegen->set_capabilities(CAP_ALLOW, $workspace_creator->id);
            $workspace_list[] = $workspacegen->create_workspace();
        }
        $workspace_recipients = $workspacegen->create_workspace_recipients($workspace_list);

        $playlistgen->share_playlist($playlist1, $workspace_recipients);
        $playlistgen->share_playlist($playlist2, $workspace_recipients);
        array_shift($workspace_recipients);
        $playlistgen->share_playlist($playlist3, $workspace_recipients);
        $playlistgen->share_playlist($playlist5, $workspace_recipients);
        array_shift($workspace_recipients);
        $playlistgen->share_playlist($playlist4, $workspace_recipients);

        // Add topics to resourecs.
        $this->setAdminUser();

        $topics[] = $topicgen->create_topic('topic1')->get_id();
        $topics[] = $topicgen->create_topic('topic2')->get_id();

        $playlist1->add_topics_by_ids($topics);
        $playlist3->add_topics_by_ids($topics);
        $playlist5->add_topics_by_ids($topics);

        $report_id = $this->get_report_id();
        $report = reportbuilder::create($report_id);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        //  There must be 5 playlists.
        $this->assertCount(5, $records);

        foreach ($records as $record) {
            if ($record->playlistengagement_title == $playlist1->get_name()) {
                $this->assertEquals(access::PUBLIC, $record->playlistengagement_visibility);
                $this->assertEquals(6, $record->playlistengagement_resources);
                $this->assertEquals(0, $record->playlistengagement_rating);
                $this->assertEquals(5, $record->playlistengagement_comments);
                $this->assertEquals(3, $record->playlistengagement_shares);
                $this->assertEquals(3, $record->playlistengagement_workspaces);
                $this->assertEquals(fullname($user_one), $record->user_namelink);
                $this->assertEquals(0, $record->playlistengagement_views);
                $this->assertNotEmpty($record->playlistengagement_topics);
            } else if ($record->playlistengagement_title == $playlist2->get_name()) {
                $this->assertEquals(access::RESTRICTED, $record->playlistengagement_visibility);
                $this->assertEquals(0, $record->playlistengagement_resources);
                $this->assertEquals(2, $record->playlistengagement_rating);
                $this->assertEquals(0, $record->playlistengagement_comments);
                $this->assertEquals(1, $record->playlistengagement_shares);
                $this->assertEquals(3, $record->playlistengagement_workspaces);
                $this->assertEquals(fullname($user_one), $record->user_namelink);
                $this->assertEquals(0, $record->playlistengagement_views);
                $this->assertEmpty($record->playlistengagement_topics);
            } else if ($record->playlistengagement_title == $playlist3->get_name()) {
                $this->assertEquals(access::PUBLIC, $record->playlistengagement_visibility);
                $this->assertEquals(3, $record->playlistengagement_resources);
                $this->assertEquals(1, $record->playlistengagement_rating);
                $this->assertEquals(2, $record->playlistengagement_comments);
                $this->assertEquals(3, $record->playlistengagement_shares);
                $this->assertEquals(2, $record->playlistengagement_workspaces);
                $this->assertEquals(fullname($user_two), $record->user_namelink);
                $this->assertEquals(0, $record->playlistengagement_views);
                $this->assertNotEmpty($record->playlistengagement_topics);
            } else if ($record->playlistengagement_title == $playlist4->get_name()) {
                $this->assertEquals(access::PRIVATE, $record->playlistengagement_visibility);
                $this->assertEquals(0, $record->playlistengagement_resources);
                $this->assertEquals(2, $record->playlistengagement_rating);
                $this->assertEquals(0, $record->playlistengagement_comments);
                $this->assertEquals(2, $record->playlistengagement_shares);
                $this->assertEquals(1, $record->playlistengagement_workspaces);
                $this->assertEquals(fullname($user_two), $record->user_namelink);
                $this->assertEquals(0, $record->playlistengagement_views);
                $this->assertEmpty($record->playlistengagement_topics);
            } else {
                $this->assertEquals(access::PUBLIC, $record->playlistengagement_visibility);
                $this->assertEquals(2, $record->playlistengagement_resources);
                $this->assertEquals(1, $record->playlistengagement_rating);
                $this->assertEquals(1, $record->playlistengagement_comments);
                $this->assertEquals(2, $record->playlistengagement_shares);
                $this->assertEquals(2, $record->playlistengagement_workspaces);
                $this->assertEquals(fullname($user_three), $record->user_namelink);
                $this->assertEquals(0, $record->playlistengagement_views);
                $this->assertNotEmpty($record->playlistengagement_topics);
            }
        }
    }

    /**
     * @param int $playlist_id
     * @param int $user_id
     * @param int $num
     */
    private function create_comment(int $playlist_id, int $user_id, int $num) {
        for ($i = 0; $i < $num; $i++) {
            comment_helper::create_comment(
                'totara_playlist',
                'comment',
                $playlist_id,
                "Hello world ". $i,
                FORMAT_PLAIN,
                null,
                $user_id
            );
        }
    }

    /**
     * @return int
     */
    private function get_report_id(): int {
        $rid = $this->create_report('playlistengagement', 'Test playlistengagement report');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'playlistengagement', 'title', null, null, null, 0);
        $this->add_column($report, 'playlistengagement', 'visibility', null, null, null, 0);
        $this->add_column($report, 'playlistengagement', 'resources', null, null, null, 0);
        $this->add_column($report, 'playlistengagement', 'rating', null, null, null, 0);
        $this->add_column($report, 'playlistengagement', 'comments', null, null, null, 0);
        $this->add_column($report, 'playlistengagement', 'shares', null, null, null, 0);
        $this->add_column($report, 'playlistengagement', 'workspaces', null, null, null, 0);
        $this->add_column($report, 'user', 'namelink', null, null, null, 0);
        $this->add_column($report, 'playlistengagement', 'create_date', null, null, null, 0);
        $this->add_column($report, 'playlistengagement', 'update_date', null, null, null, 0);
        $this->add_column($report, 'playlistengagement', 'views', null, null, null, 0);
        $this->add_column($report, 'playlistengagement', 'topics', null, null, null, 0);

        return $rid;
    }

    /**
     *  @return void
     */
    public function test_playlistengagement_report_for_multitenancy(): void {
        global $DB;

        $gen = $this->getDataGenerator();

        // Create three different creators.
        $user_one = $gen->create_user();
        $user_two = $gen->create_user();
        $user_three = $gen->create_user();

        $this->setAdminUser();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $gen->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        // Create playlists.
        $playlist1 = $playlistgen->create_playlist(['userid' => $user_one->id, 'name' => 'playlist1']);
        $playlist2 = $playlistgen->create_playlist(['userid' => $user_one->id, 'name' => 'playlist2']);
        $playlist3 = $playlistgen->create_playlist(['userid' => $user_one->id, 'name' => 'playlist3']);
        $playlist4 = $playlistgen->create_playlist(['userid' => $user_two->id, 'name' => 'playlist4']);
        $playlist5 = $playlistgen->create_playlist(['userid' => $user_two->id, 'name' => 'playlist5']);
        $playlistgen->create_playlist(['userid' => $user_three->id, 'name' => 'playlist6']);

        $report_id = $this->get_report_id();
        $report = reportbuilder::create($report_id);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(6, $records);

        // Login as user_one.
        $this->setUser($user_one);
        $report_id = $this->get_report_id();

        // Enable the content restriction.
        reportbuilder::update_setting($report_id, 'user_visibility_content', 'enable', 1);
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ALL);
        set_config('tenantsisolated', '1');

        $report = reportbuilder::create($report_id);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(4, $records);

        // User_two'playlists have to be excluded.
        foreach ($records as $record) {
            $this->assertNotEquals($playlist4->get_name(), $record->playlistengagement_title);
            $this->assertNotEquals($playlist5->get_name(), $record->playlistengagement_title);
        }
//
        // Login as user_two.
        $this->setUser($user_two);
        $report_id = $this->get_report_id();

        // Enable the content restriction.
        reportbuilder::update_setting($report_id, 'user_visibility_content', 'enable', 1);
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ALL);
        set_config('tenantsisolated', '1');

        $report = reportbuilder::create($report_id);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);

        // User_one'playlists have to be excluded.
        foreach ($records as $record) {
            $this->assertNotEquals($playlist1->get_name(), $record->playlistengagement_title);
            $this->assertNotEquals($playlist2->get_name(), $record->playlistengagement_title);
            $this->assertNotEquals($playlist3->get_name(), $record->playlistengagement_title);
        }
    }

    /**
     *  @return void
     */
    public function test_playlistengagement_report_access(): void {
        self::setAdminUser();
        $report_source_class = reportbuilder::get_source_class('playlistengagement');
        $report_embedded_class = reportbuilder::get_embedded_report_class('playlistengagement');

        $this->assertFalse($report_source_class::is_source_ignored());
        $this->assertFalse($report_embedded_class::is_report_ignored());

        advanced_feature::disable('engage_resources');
        $this->assertTrue($report_source_class::is_source_ignored());
        $this->assertTrue($report_embedded_class::is_report_ignored());

        advanced_feature::enable('engage_resources');
        advanced_feature::disable('container_workspace');
        advanced_feature::disable('ml_recommender');
        $this->assertFalse($report_source_class::is_source_ignored());
        $this->assertFalse($report_embedded_class::is_report_ignored());
    }

    /**
     * Make sure the report can be created with ml_recommender disabled.
     * There used to be a bug where this resulted in an exception.
     */
    public function test_playlistengagement_report_create_with_disabled_ml_recommender(): void {
        self::setAdminUser();
        advanced_feature::disable('ml_recommender');
        report_helper::create('playlistengagement');
    }
}