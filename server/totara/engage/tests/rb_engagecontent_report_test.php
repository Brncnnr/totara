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
use totara_reaction\resolver\resolver_factory as reaction_resolver_factory;
use totara_reaction\reaction_helper;
use totara_engage\resource\resource_factory;
use totara_reportbuilder\report_helper;

/**
 * @group totara_reportbuilder
 * @group totara_engage
 */
class totara_engage_rb_engagecontent_report_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;
    /**
     *  @return void
     */
    public function test_engagecontent_report(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/reaction/tests/fixtures/default_reaction_resolver.php");

        $gen = $this->getDataGenerator();
        $engagegen = $gen->get_plugin_generator('totara_engage');

        // Create different creators.
        $workspace_creator = $gen->create_user();
        $recipient2 = $gen->create_user();
        $recipient3 = $gen->create_user();

        /** @var \container_workspace\testing\generator $workspacegen */
        $workspacegen = $gen->get_plugin_generator('container_workspace');

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        /** @var \totara_topic\testing\generator $topicgen */
        $topicgen = $gen->get_plugin_generator('totara_topic');

        // Create 4 workspaces.
        $workspace_list = [];
        for ($i = 0; $i < 6; $i++) {
            $this->setUser($workspace_creator);
            $workspacegen->set_capabilities(CAP_ALLOW, $workspace_creator->id);
            $workspace_list[] = $workspacegen->create_workspace();
        }
        $workspace_recipient = $workspacegen->create_workspace_recipients($workspace_list);

        // Create three artcles.
        $article1 = $articlegen->create_article(['name' => 'article1', 'access' => access::PUBLIC]);
        $article2 = $articlegen->create_article(['name' => 'article2', 'access' => access::PUBLIC]);
        $article3 = $articlegen->create_article(['name' => 'article3', 'access' => access::PUBLIC]);

        // Create four surveys.
        $survey1 = $surveygen->create_survey('survey1?', [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);
        $survey2 = $surveygen->create_survey('survey2?', [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);
        $survey3 = $surveygen->create_survey('survey3?', [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);
        $survey4 = $surveygen->create_survey('survey4?', [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        // Share article1 and survey2-3 to 6 workspaces.
        $articlegen->share_article($article1, $workspace_recipient);
        $surveygen->share_survey($survey2, $workspace_recipient);
        $surveygen->share_survey($survey3, $workspace_recipient);

        // Change 6 workspaces to 4 workspaces.
        array_shift($workspace_list);
        array_shift($workspace_list);
        $workspace_recipient = $workspacegen->create_workspace_recipients($workspace_list);

        // Share article2 and survey1 to 4 workspaces.
        $articlegen->share_article($article2, $workspace_recipient);
        $surveygen->share_survey($survey1, $workspace_recipient);

        // Change 4 workspaces to 2 workspaces.
        array_shift($workspace_list);
        array_shift($workspace_list);
        $workspace_recipient = $workspacegen->create_workspace_recipients($workspace_list);

        // Share article3 and survey4 to 2 workspaces.
        $articlegen->share_article($article3, $workspace_recipient);
        $surveygen->share_survey($survey4, $workspace_recipient);

        // Create recipient users.
        $users1 = $engagegen->create_users(3);
        $users2 = $engagegen->create_users(5);
        $users3 = $engagegen->create_users(7);

        // Share resources to user_recipients.
        $article_recipient1 = $articlegen->create_user_recipients($users1);
        $survey_recipient1 = $surveygen->create_user_recipients($users1);
        $articlegen->share_article($article1, $article_recipient1);
        $surveygen->share_survey($survey2, $survey_recipient1);

        $article_recipient2 = $articlegen->create_user_recipients($users2);
        $survey_recipient2 = $surveygen->create_user_recipients($users2);
        $articlegen->share_article($article3, $article_recipient2);
        $surveygen->share_survey($survey1, $survey_recipient2);
        $surveygen->share_survey($survey4, $survey_recipient2);

        $article_recipient3 = $articlegen->create_user_recipients($users3);
        $survey_recipient3 = $surveygen->create_user_recipients($users3);
        $articlegen->share_article($article2, $article_recipient3);
        $surveygen->share_survey($survey3, $survey_recipient3);

        // Create likes.
        $resolver = new default_reaction_resolver();
        $resolver->set_component('engage_article');

        reaction_resolver_factory::phpunit_set_resolver($resolver);

        // Some articles have likes, but some do not have.
        foreach ($users2 as $user) {
            $reaction1 = reaction_helper::create_reaction($article1->get_id(), 'engage_article', 'media', $user->id);
            $reaction2 = reaction_helper::create_reaction($article3->get_id(), 'engage_article', 'media', $user->id);

            $this->assertTrue($DB->record_exists('reaction', ['id' => $reaction1->get_id()]));
            $this->assertTrue($DB->record_exists('reaction', ['id' => $reaction2->get_id()]));
        }

        // Some survey have likes, but some do not have.
        $resolver->set_component('engage_survey');
        foreach ($users3 as $user) {
            $reaction1 = reaction_helper::create_reaction($survey3->get_id(), 'engage_survey', 'media', $user->id);
            $reaction2 = reaction_helper::create_reaction($survey2->get_id(), 'engage_survey', 'media', $user->id);

            $this->assertTrue($DB->record_exists('reaction', ['id' => $reaction1->get_id()]));
            $this->assertTrue($DB->record_exists('reaction', ['id' => $reaction2->get_id()]));
        }

        // Create comments.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $gen->get_plugin_generator('totara_comment');

        $comment_size1 = 10;
        $comment_size2 = 8;
        for ($i = 0; $i < $comment_size1; $i++) {
            $comment1 = $comment_generator->create_comment($article1->get_id(), 'engage_article', 'comment');
            $comment2 = $comment_generator->create_comment($survey1->get_id(), 'engage_survey', 'comment');
            $comment3 = $comment_generator->create_comment($survey3->get_id(), 'engage_survey', 'comment');

            if ($i < $comment_size2) {
                $comment4 = $comment_generator->create_comment($article2->get_id(), 'engage_article', 'comment');
                $comment5 = $comment_generator->create_comment($survey2->get_id(), 'engage_survey', 'comment');
                $comment6 = $comment_generator->create_comment($survey4->get_id(), 'engage_survey', 'comment');

                $this->assertEquals($article2->get_id(), $comment4->get_instanceid());
                $this->assertEquals($survey2->get_id(), $comment5->get_instanceid());
                $this->assertEquals($survey4->get_id(), $comment6->get_instanceid());
            }
            $this->assertEquals($article1->get_id(), $comment1->get_instanceid());
            $this->assertEquals($survey1->get_id(), $comment2->get_instanceid());
            $this->assertEquals($survey3->get_id(), $comment3->get_instanceid());
        }

        // Create playlists
        $playlist1 = $playlistgen->create_playlist([
            'name' => 'playlist1',
            'access'=> totara_engage\access\access::PUBLIC
        ]);

        $playlist2 = $playlistgen->create_playlist([
            'name' => 'playlist2',
            'access'=> totara_engage\access\access::PUBLIC
        ]);

        $playlist3 = $playlistgen->create_playlist([
            'name' => 'playlist3',
            'access'=> totara_engage\access\access::PUBLIC
        ]);


        $playlist1->add_resource(resource_factory::create_instance_from_id($article1->get_id()));
        $playlist1->add_resource(resource_factory::create_instance_from_id($article2->get_id()));
        $playlist1->add_resource(resource_factory::create_instance_from_id($survey1->get_id()));
        $playlist1->add_resource(resource_factory::create_instance_from_id($survey2->get_id()));
        $playlist1->add_resource(resource_factory::create_instance_from_id($survey3->get_id()));

        $playlist2->add_resource(resource_factory::create_instance_from_id($survey1->get_id()));
        $playlist2->add_resource(resource_factory::create_instance_from_id($survey2->get_id()));
        $playlist2->add_resource(resource_factory::create_instance_from_id($survey3->get_id()));
        $playlist2->add_resource(resource_factory::create_instance_from_id($article1->get_id()));
        $playlist2->add_resource(resource_factory::create_instance_from_id($article2->get_id()));

        $playlist3->add_resource(resource_factory::create_instance_from_id($article2->get_id()));
        $playlist3->add_resource(resource_factory::create_instance_from_id($survey2->get_id()));
        $playlist3->add_resource(resource_factory::create_instance_from_id($survey3->get_id()));

        // Add topics to resourecs.
        $this->executeAdhocTasks();
        $this->setAdminUser();

        $topics[] = $topicgen->create_topic('topic1')->get_id();
        $topics[] = $topicgen->create_topic('topic2')->get_id();

        $article1->add_topics_by_ids($topics);
        $survey2->add_topics_by_ids($topics);

        $rid = $this->get_report_id();
        $report = reportbuilder::create($rid);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        //  There must be 3 artcles and 4 surveys.
        $this->assertCount(7, $records);


        foreach ($records as $record) {
            if ($record->engagecontent_resource_name === $article1->get_name()) {
                $this->assertEquals(count($users2), $record->engagecontent_likes);
                $this->assertEquals($comment_size1, $record->engagecontent_comments);
                $this->assertEquals(count($article_recipient1), $record->engagecontent_shares);
                $this->assertEquals(6, $record->engagecontent_workspaces);
                $this->assertEquals(2, $record->engagecontent_playlists);
                $this->assertNotEmpty($record->engagecontent_topics);
                $this->assertCount(2, explode(',', $record->engagecontent_topics));
            }

            if ($record->engagecontent_resource_name === $survey1->get_name()) {
                // No likes for survey1.
                $this->assertEquals(0, $record->engagecontent_likes);
                $this->assertEquals($comment_size1, $record->engagecontent_comments);
                $this->assertEquals(count($survey_recipient2), $record->engagecontent_shares);
                $this->assertEquals(4, $record->engagecontent_workspaces);
                $this->assertEquals(2, $record->engagecontent_playlists);
                $this->assertEmpty($record->engagecontent_topics);
            }

            if ($record->engagecontent_resource_name === $article2->get_name()) {
                // No likes for article2.
                $this->assertEquals(0, $record->engagecontent_likes);
                $this->assertEquals($comment_size2, $record->engagecontent_comments);
                $this->assertEquals(count($article_recipient3), $record->engagecontent_shares);
                $this->assertEquals(4, $record->engagecontent_workspaces);
                $this->assertEquals(3, $record->engagecontent_playlists);
                $this->assertEmpty($record->engagecontent_topics);
            }

            if ($record->engagecontent_resource_name === $survey2->get_name()) {
                $this->assertEquals(count($users3), $record->engagecontent_likes);
                $this->assertEquals($comment_size2, $record->engagecontent_comments);
                $this->assertEquals(count($survey_recipient1), $record->engagecontent_shares);
                $this->assertEquals(6, $record->engagecontent_workspaces);
                $this->assertEquals(3, $record->engagecontent_playlists);
                $this->assertNotEmpty($record->engagecontent_topics);
                $this->assertCount(2, explode(',', $record->engagecontent_topics));
            }

            if ($record->engagecontent_resource_name === $article3->get_name()) {
                $this->assertEquals(count($users2), $record->engagecontent_likes);
                $this->assertEquals(0, $record->engagecontent_comments);
                $this->assertEquals(count($article_recipient2), $record->engagecontent_shares);
                $this->assertEquals(2, $record->engagecontent_workspaces);
                // No article3 in playlist.
                $this->assertEquals(0, $record->engagecontent_playlists);
                $this->assertEmpty($record->engagecontent_topics);
            }

            if ($record->engagecontent_resource_name === $survey3->get_name()) {
                $this->assertEquals(count($users3), $record->engagecontent_likes);
                $this->assertEquals($comment_size1, $record->engagecontent_comments);
                $this->assertEquals(count($survey_recipient3), $record->engagecontent_shares);
                $this->assertEquals(6, $record->engagecontent_workspaces);
                $this->assertEquals(3, $record->engagecontent_playlists);
                $this->assertEmpty($record->engagecontent_topics);
            }

            if ($record->engagecontent_resource_name === $survey4->get_name()) {
                // No likes for survey4.
                $this->assertEquals(0, $record->engagecontent_likes);
                $this->assertEquals($comment_size2, $record->engagecontent_comments);
                $this->assertEquals(count($survey_recipient2), $record->engagecontent_shares);
                $this->assertEquals(2, $record->engagecontent_workspaces);
                // No survey4 in playlist.
                $this->assertEquals(0, $record->engagecontent_playlists);
                $this->assertEmpty($record->engagecontent_topics);
            }
        }
    }

    /**
     *  @return void
     */
    public function test_engagecontent_report_for_multitenancy(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

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
        $this->setUser($user_one);
        $article1 = $articlegen->create_article(['name' => 'article 1']);
        $article2 = $articlegen->create_article(['name' => 'article 2']);
        $article3 = $articlegen->create_article(['name' => 'article 3']);

        // Create articles for user_two.
        $this->setUser($user_two);
        $article4 = $articlegen->create_article(['name' => 'article 4']);
        $article5 = $articlegen->create_article(['name' => 'article 5']);

        $this->setAdminUser();
        $articlegen->create_article();

        $rid = $this->get_report_id();
        $report = reportbuilder::create($rid);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(6, $records);

        // Login as user_one.
        $this->setUser($user_one);
        $rid = $this->get_report_id();
        $this->enable_setting($rid);
        $report = reportbuilder::create($rid);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(4, $records);

        // User_two'articles have to be excluded.
        foreach ($records as $record) {
            $this->assertNotEquals($article4->get_name(), $record->engagecontent_resource_name);
            $this->assertNotEquals($article5->get_name(), $record->engagecontent_resource_name);
        }

        // Login as user_two.
        $this->setUser($user_two);
        $rid = $this->get_report_id();
        $this->enable_setting($rid);
        $report = reportbuilder::create($rid);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(3, $records);

        // User_one'articles have to be excluded.
        foreach ($records as $record) {
            $this->assertNotEquals($article1->get_name(), $record->engagecontent_resource_name);
            $this->assertNotEquals($article2->get_name(), $record->engagecontent_resource_name);
            $this->assertNotEquals($article3->get_name(), $record->engagecontent_resource_name);
        }
    }

    /**
     *  @return void
     */
    public function test_engagecontent_report_access(): void {
        self::setAdminUser();
        $report_source_class = reportbuilder::get_source_class('engagecontent');
        $report_embedded_class = reportbuilder::get_embedded_report_class('engagecontent');

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
    public function test_engagecontent_report_create_with_disabled_ml_recommender(): void {
        self::setAdminUser();
        advanced_feature::disable('ml_recommender');
        report_helper::create('engagecontent');
    }

    /**
     * @return int
     */
    private function get_report_id(): int {
        $rid = $this->create_report('engagecontent', 'Test engagecontnt report');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'engagecontent', 'resource_name', null, null, null, 0);
        $this->add_column($report, 'engagecontent', 'playlists', null, null, null, 0);
        $this->add_column($report, 'engagecontent', 'likes', null, null, null, 0);
        $this->add_column($report, 'engagecontent', 'comments', null, null, null, 0);
        $this->add_column($report, 'engagecontent', 'shares', null, null, null, 0);
        $this->add_column($report, 'engagecontent', 'workspaces', null, null, null, 0);
        $this->add_column($report, 'engagecontent', 'visibility', null, null, null, 0);
        $this->add_column($report, 'engagecontent', 'create_date', null, null, null, 0);
        $this->add_column($report, 'engagecontent', 'topics', null, null, null, 0);

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