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

use totara_core\advanced_feature;
use totara_engage\access\access;
use totara_engage\answer\answer_type;
use totara_comment\comment_helper;
use engage_article\totara_engage\resource\article;
use totara_playlist\playlist;

/**
 * @group totara_reportbuilder
 * @group totara_engage
 */
class totara_engage_rb_engagedusers_report_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     *  @return void
     */
    public function test_engagedusers_report(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/reaction/tests/fixtures/default_reaction_resolver.php");

        $gen = $this->getDataGenerator();
        $engagegen = $gen->get_plugin_generator('totara_engage');

        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $user3 = $gen->create_user();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        /** @var \container_workspace\testing\generator $workspacegen */
        $workspacegen = $gen->get_plugin_generator('container_workspace');

        // Create artcles.
        // Creator is user1.
        $public_articles1 = $this->create_reources(
            'article',
            2,
            $user1->id,
            $articlegen,
            access::PUBLIC
        );

        $private_articles1 = $this->create_reources(
            'article',
            2,
            $user1->id,
            $articlegen
        );

        // Creator is user2.
        $restricted_articles1 = $this->create_reources(
            'article',
            2,
            $user2->id,
            $articlegen,
            access::RESTRICTED
        );

        $public_articles2 = $this->create_reources(
            'article',
            2,
            $user2->id,
            $articlegen,
            access::PUBLIC
        );

        // Creator is user3.
        $restricted_articles2 = $this->create_reources(
            'article',
            2,
            $user3->id,
            $articlegen,
            access::RESTRICTED
        );

        $private_articles2 = $this->create_reources(
            'article',
            2,
            $user3->id,
            $articlegen
        );

        // Create playlists
        $private_playlist1 = $this->create_reources(
            'playlist',
            2,
            $user1->id,
            $playlistgen
        );

        $public_playlist1 = $this->create_reources(
            'playlist',
            2,
            $user2->id,
            $playlistgen,
            access::PUBLIC
        );

        $restricted_playlist1 = $this->create_reources(
            'playlist',
            2,
            $user2->id,
            $playlistgen,
            access::RESTRICTED
        );

        $public_playlist2 = $this->create_reources(
            'playlist',
            2,
            $user3->id,
            $playlistgen,
            access::PUBLIC
        );

        // Create surveys.
        $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, ['userid' => $user1->id]);
        $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, ['userid' => $user1->id]);
        $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, ['userid' => $user2->id]);
        $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, ['userid' => $user2->id]);
        $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, ['userid' => $user3->id]);
        $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, ['userid' => $user3->id]);

        $comment_size1 = 10;
        $comment_size2 = 8;

        /** @var article $article */
        foreach ($public_articles1 as $article) {
            for ($i = 0; $i < $comment_size1; $i++) {
                // Viewer make the comments.
                comment_helper::create_comment(
                    'engage_article',
                    'comment',
                    $article->get_id(),
                    "Hello world {$i}",
                    FORMAT_PLAIN,
                    null,
                    $user2->id
                );

                comment_helper::create_comment(
                    'engage_article',
                    'comment',
                    $article->get_id(),
                    "Hello world {$i}",
                    FORMAT_PLAIN,
                    null,
                    $user1->id
                );
            }
        }

        /** @var article $article */
        foreach ($public_articles2 as $article) {
            for ($i = 0; $i < $comment_size2; $i++) {
                comment_helper::create_comment(
                    'engage_article',
                    'comment',
                    $article->get_id(),
                    "Hello world {$i}",
                    FORMAT_PLAIN,
                    null,
                    $user2->id
                );

                comment_helper::create_comment(
                    'engage_article',
                    'comment',
                    $article->get_id(),
                    "Hello world {$i}",
                    FORMAT_PLAIN,
                    null,
                    $user1->id
                );
            }
        }

        /** @var playlist $playlist */
        foreach ($public_playlist2 as $playlist) {
            for ($i = 0; $i < $comment_size2; $i++) {
                comment_helper::create_comment(
                    'totara_playlist',
                    'comment',
                    $playlist->get_id(),
                    "Hello world {$i}",
                    FORMAT_PLAIN,
                    null,
                    $user3->id
                );
            }
        }

        // Create workspaces.
        $workspaces1 = $this->create_workspaces(3, $user1, $workspacegen);
        $workspaces2 = $this->create_workspaces(2, $user2, $workspacegen);
        $workspaces3 = $this->create_workspaces(4, $user3, $workspacegen);

        // Share resources to workspace.
        $workspace_recipient1 = $workspacegen->create_workspace_recipients($workspaces1);
        $workspace_recipient2 = $workspacegen->create_workspace_recipients($workspaces2);

        foreach ($private_articles1 as $article) {
            $this->setUser($user1);
            $articlegen->share_article($article, $workspace_recipient1);
        }
        foreach ($private_playlist1 as $playlist) {
            $this->setUser($user1);
            $playlistgen->share_playlist($playlist, $workspace_recipient2);
        }
        foreach ($restricted_articles2 as $article) {
            $this->setUser($user2);
            $articlegen->share_article($article, $workspace_recipient2);
        }

        foreach ($public_playlist1 as $playlist) {
            $this->setUser($user2);
            $playlistgen->share_playlist($playlist, $workspace_recipient2);
        }

        // Create report.
        $rid = $this->get_report_id();
        $report = reportbuilder::create($rid);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);

        // There must be 3 created users and 1 admin user but each column for admin user must be 0.
        $this->assertCount(4, $records);

        foreach ($records as $record) {
            if ($record->id === $user1->id) {
                // 4 artciles and 2 surveys.
                $this->assertEquals(6, $record->engagedusers_created_resource);

                // 2 public artcles.
                $this->assertEquals(2, $record->engagedusers_public_resource);

                // 2 private articles and 2 private surveies.
                $this->assertEquals(4, $record->engagedusers_private_resource);

                // No any restricted resources.
                $this->assertEquals(0, $record->engagedusers_restricted_resource);

                // User1 makes comments for 4 artcles, two of them belong to himself, it will be countable.
                // However, the rest will be countable.
                $this->assertEquals($comment_size2 * 2, $record->engagedusers_created_comment);

                // 2 playlists created.
                $this->assertEquals(2, $record->engagedusers_created_playlist);

                // Two resources share to 3 different workspaces and Two resources share to 2 different workspaces.
                $this->assertEquals(10, $record->engagedusers_resource_in_workspace);

                // 3 workspaces created.
                $this->assertEquals(3, $record->engagedusers_created_workspace);

                // User1 created 3 workspaces, so he has to be a member of 3 workspaces.
                $this->assertNotEmpty($record->engagedusers_memberofworkspace);
                $this->assertCount(3, explode(',', $record->engagedusers_memberofworkspace));
            } else if ($record->id === $user2->id) {
                // 4 artciles and 2 surveys.
                $this->assertEquals(6, $record->engagedusers_created_resource);

                // 2 public artcles.
                $this->assertEquals(2, $record->engagedusers_public_resource);

                // 2 private surveies.
                $this->assertEquals(2, $record->engagedusers_private_resource);

                // 2 restricted articles.
                $this->assertEquals(2, $record->engagedusers_restricted_resource);
                $this->assertEquals($comment_size1*2, $record->engagedusers_created_comment);

                // 4 playlists created
                $this->assertEquals(4, $record->engagedusers_created_playlist);

                // Each resource share to 2 different workspaces and The number of resources are 4
                $this->assertEquals(8, $record->engagedusers_resource_in_workspace);

                // 2 workspaces created.
                $this->assertEquals(2, $record->engagedusers_created_workspace);

                // User2 created 2 workspaces, so he has to be a member of 2 workspaces.
                $this->assertNotEmpty($record->engagedusers_memberofworkspace);
                $this->assertCount(2, explode(',', $record->engagedusers_memberofworkspace));
            } else if ($record->id === $user3->id) {
                // 4 artciles and 2 surveys.
                $this->assertEquals(6, $record->engagedusers_created_resource);

                // 0 public artcles.
                $this->assertEquals(0, $record->engagedusers_public_resource);

                // 2 private articles and 2 private surveies.
                $this->assertEquals(4, $record->engagedusers_private_resource);

                // 2 restricted articles.
                $this->assertEquals(2, $record->engagedusers_restricted_resource);

                // User3 only comment on his own resource not others' resources, so the number has to be 0.
                $this->assertEquals(0, $record->engagedusers_created_comment);

                // 2 playlists created.
                $this->assertEquals(2, $record->engagedusers_created_playlist);

                // No resources share to workspaces.
                $this->assertEquals(0, $record->engagedusers_resource_in_workspace);

                // 4 workspaces created.
                $this->assertEquals(4, $record->engagedusers_created_workspace);

                // User3 created 4 workspaces, so he has to be a member of 4 workspaces.
                $this->assertNotEmpty($record->engagedusers_memberofworkspace);
                $this->assertCount(4, explode(',', $record->engagedusers_memberofworkspace));
            } else {
                $this->assertEquals(2, $record->id);
                $this->assertEquals(0, $record->engagedusers_created_resource);
                $this->assertEquals(0, $record->engagedusers_public_resource);
                $this->assertEquals(0, $record->engagedusers_private_resource);
                $this->assertEquals(0, $record->engagedusers_restricted_resource);
                $this->assertEquals(0, $record->engagedusers_created_comment);
                $this->assertEquals(0, $record->engagedusers_resource_in_workspace);
                $this->assertEquals(0, $record->engagedusers_created_playlist);
                $this->assertEquals(0, $record->engagedusers_created_workspace);
                $this->assertEmpty($record->engagedusers_memberofworkspace);
            }
        }

    }

    /**
     * @param string $name
     * @param int $number
     * @param int $userid
     * @param \core\testing\component_generator $generator
     * @param int|null $access
     * @return array
     */
    private function create_reources(
        string $name,
        int $number,
        int $userid,
        \core\testing\component_generator $generator,
        ?int $access = access::PRIVATE
    ): array {
        $list = [];
        $method = "create_{$name}";
        for ($i = 0; $i < $number; $i++) {
            $list[] = $generator->{$method}(
                [
                    'name' => $name.$i,
                    'access' => $access,
                    'userid' => $userid
                ]
            );
        }

        return $list;
    }

    /**
     * @param int $number
     * @param stdClass $user
     * @param \core\testing\component_generator $generator
     * @return array
     */
    private function create_workspaces(
        int $number,
        stdClass $user,
        \core\testing\component_generator $generator
    ): array {
        $list = [];
        for ($i = 0; $i < $number; $i++) {
            $this->setUser($user);
            $generator->set_capabilities(CAP_ALLOW, $user->id);
            $list[] = $generator->create_workspace();
        }
        return $list;
    }

    /**
     *  @return void
     */
    public function test_engagedusers_report_for_multitenancy(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $generator->get_plugin_generator('engage_article');

        // Create articles for user_one.
        $this->create_reources(
            'article',
            3,
            $user_one->id,
            $articlegen,
            access::PUBLIC
        );

        // Create articles for user_two.
        $this->create_reources(
            'article',
            2,
            $user_two->id,
            $articlegen,
            access::PUBLIC
        );

        // Login as admin.
        $this->setAdminUser();
        $rid = $this->get_report_id();
        $report = reportbuilder::create($rid);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(4, $records);

        // Login as user_two.
        $this->setUser($user_two);
        $rid = $this->get_report_id();
        $this->enable_setting($rid);
        $report = reportbuilder::create($rid);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);

        // User_one has to be excluded.
        $this->assertCount(3, $records);
        foreach ($records as $record) {
            $this->assertNotEquals($user_one->id, $record->engagedusers_creator);
        }

        // Login as user_two.
        $this->setUser($user_one);
        $rid = $this->get_report_id();
        $this->enable_setting($rid);
        $report = reportbuilder::create($rid);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);

        // User_two has to be excluded.
        $this->assertCount(3, $records);
        foreach ($records as $record) {
            $this->assertNotEquals($user_two->id, $record->engagedusers_creator);
        }
    }

    /**
     *  @return void
     */
    public function test_engagedusers_report_access(): void {
        self::setAdminUser();
        $report_source_class = reportbuilder::get_source_class('engagedusers');
        $report_embedded_class = reportbuilder::get_embedded_report_class('engagedusers');

        $this->assertFalse($report_source_class::is_source_ignored());
        $this->assertFalse($report_embedded_class::is_report_ignored());

        advanced_feature::disable('engage_resources');
        $this->assertFalse($report_source_class::is_source_ignored());
        $this->assertFalse($report_embedded_class::is_report_ignored());

        advanced_feature::enable('engage_resources');
        advanced_feature::disable('container_workspace');
        $this->assertFalse($report_source_class::is_source_ignored());
        $this->assertFalse($report_embedded_class::is_report_ignored());

        advanced_feature::disable('engage_resources');
        $this->assertTrue($report_source_class::is_source_ignored());
        $this->assertTrue($report_embedded_class::is_report_ignored());
    }

    /**
     * @return int
     */
    private function get_report_id(): int {
        $rid = $this->create_report('engagedusers', 'Test User Engagement report');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'engagedusers', 'creator', null, null, null, 0);
        $this->add_column($report, 'engagedusers', 'created_resource', null, null, null, 0);
        $this->add_column($report, 'engagedusers', 'public_resource', null, null, null, 0);
        $this->add_column($report, 'engagedusers', 'private_resource', null, null, null, 0);
        $this->add_column($report, 'engagedusers', 'restricted_resource', null, null, null, 0);
        $this->add_column($report, 'engagedusers', 'created_comment', null, null, null, 0);
        $this->add_column($report, 'engagedusers', 'created_playlist', null, null, null, 0);
        $this->add_column($report, 'engagedusers', 'resource_in_workspace', null, null, null, 0);
        $this->add_column($report, 'engagedusers', 'created_workspace', null, null, null, 0);
        $this->add_column($report, 'engagedusers', 'memberofworkspace', null, null, null, 0);

        return $rid;
    }

    /**
     * @param int $rid
     */
    private function enable_setting(int $rid): void {
        global $DB;

        // Enable the content restriction.
        reportbuilder::update_setting($rid, 'user_visibility_content', 'enable', 1);
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ALL);
        set_config('tenantsisolated', '1');
    }
}