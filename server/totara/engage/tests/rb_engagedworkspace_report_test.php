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
use container_workspace\discussion\discussion;
use totara_comment\comment_helper;
use core\json_editor\node\image;
use core\json_editor\node\paragraph;

/**
 * @group totara_reportbuilder
 * @group totara_engage
 */
class totara_engage_rb_engagedworkspace_report_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     *  @return void
     */
    public function test_engagedworkspace_report(): void {
        global $DB;

        $gen = $this->getDataGenerator();

        // Create different creators.
        $user_one = $gen->create_user();
        $user_two = $gen->create_user();
        $user_three = $gen->create_user();

        /** @var \container_workspace\testing\generator $workspacegen */
        $workspacegen = $gen->get_plugin_generator('container_workspace');

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        $this->setUser($user_one);
        $workspacegen->set_capabilities(CAP_ALLOW, $user_one->id);
        $public_workspace1 = $workspacegen->create_workspace('public from user_one');
        $private_workspace1 = $workspacegen->create_workspace(
            'private from user_one',
            null,
            null,
            null,
            true,
            false
        );

        $this->setUser($user_two);
        $workspacegen->set_capabilities(CAP_ALLOW, $user_two->id);
        $private_workspace2 = $workspacegen->create_workspace(
            'private from user_two',
            null,
            null,
            null,
            true,
            false
        );
        $hidden_workspace1 = $workspacegen->create_workspace(
            'hidden from user_two',
            null,
            null,
            null,
            true,
            true
        );

        $this->setUser($user_three);
        $workspacegen->set_capabilities(CAP_ALLOW, $user_three->id);
        $public_workspace2 = $workspacegen->create_workspace('public from user_three');
        $hidden_workspace2 = $workspacegen->create_workspace('hidden from user_three');

        // Create discussions and comments.
        $this->create_discussion(3, $private_workspace2->get_id());
        $this->create_discussion(2, $public_workspace2->get_id());

        $discussion1 = discussion::create("Discussion content", $public_workspace1->get_id());
        $discussion2 = discussion::create("Discussion content", $public_workspace1->get_id());
        $discussion3 = discussion::create("Discussion content", $private_workspace1->get_id());
        $discussion4 = discussion::create("Discussion content", $private_workspace1->get_id());
        $discussion5 = discussion::create("Discussion content", $hidden_workspace1->get_id());
        $discussion6 = discussion::create("Discussion content", $hidden_workspace1->get_id());

        $this->create_comments(2, $discussion1, $user_two->id);
        $this->create_comments(2, $discussion2, $user_two->id);
        $this->create_comments(2, $discussion3, $user_one->id);
        $this->create_comments(2, $discussion4, $user_one->id);
        $this->create_comments(3, $discussion5, $user_one->id);
        $this->create_comments(3, $discussion6, $user_one->id);


        // Create files.
        $this->create_file_in_dicussion($public_workspace1->get_id());
        $this->create_file_in_dicussion($public_workspace1->get_id());
        $this->create_file_in_dicussion($private_workspace1->get_id());

        // Create resources.
        $article1 = $articlegen->create_article(['userid' => $user_two->id]);
        $article2 = $articlegen->create_article(['userid' => $user_three->id]);

        // Create recipients.
        $recipients = $workspacegen->create_workspace_recipients([
            $public_workspace1,
            $public_workspace2,
            $private_workspace1,
            $private_workspace2
        ]);

        $articlegen->share_article($article1, $recipients);
        $articlegen->share_article($article2, $recipients);

        // Create playlists.
        $playlist1 = $playlistgen->create_playlist(['userid' => $user_two->id]);
        $playlist2 = $playlistgen->create_playlist(['userid' => $user_three->id]);
        $playlist3 = $playlistgen->create_playlist(['userid' => $user_three->id]);

        $playlistgen->share_playlist($playlist1, $recipients);
        $playlistgen->share_playlist($playlist2, $recipients);
        $playlistgen->share_playlist($playlist3, $recipients);

        $report_id = $this->get_report_id();
        $report = reportbuilder::create($report_id);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        //  There must be 6 workspaces.
        $this->assertCount(6, $records);

        foreach ($records as $record) {
            if ($record->engagedworkspace_title === $public_workspace1->get_name()) {
                $this->assertEquals(4, $record->engagedworkspace_discussions);
                $this->assertEquals(4, $record->engagedworkspace_commentsindiscussions);
                $this->assertEquals(3, $record->engagedworkspace_playlists);
                $this->assertEquals(2, $record->engagedworkspace_resources);
                $this->assertEquals(2, $record->engagedworkspace_files);
            } else if ($record->engagedworkspace_title === $public_workspace2->get_name()) {
                $this->assertEquals(2, $record->engagedworkspace_discussions);
                $this->assertEquals(0, $record->engagedworkspace_commentsindiscussions);
                $this->assertEquals(3, $record->engagedworkspace_playlists);
                $this->assertEquals(2, $record->engagedworkspace_resources);
                $this->assertEquals(0, $record->engagedworkspace_files);
            } else if ($record->engagedworkspace_title === $private_workspace1->get_name()) {
                $this->assertEquals(3, $record->engagedworkspace_discussions);
                $this->assertEquals(4, $record->engagedworkspace_commentsindiscussions);
                $this->assertEquals(3, $record->engagedworkspace_playlists);
                $this->assertEquals(2, $record->engagedworkspace_resources);
                $this->assertEquals(1, $record->engagedworkspace_files);
            } else if ($record->engagedworkspace_title === $private_workspace2->get_name()) {
                $this->assertEquals(3, $record->engagedworkspace_discussions);
                $this->assertEquals(0, $record->engagedworkspace_commentsindiscussions);
                $this->assertEquals(3, $record->engagedworkspace_playlists);
                $this->assertEquals(2, $record->engagedworkspace_resources);
                $this->assertEquals(0, $record->engagedworkspace_files);
            } else if ($record->engagedworkspace_title === $hidden_workspace1->get_name()) {
                $this->assertEquals(2, $record->engagedworkspace_discussions);
                $this->assertEquals(6, $record->engagedworkspace_commentsindiscussions);
                $this->assertEquals(0, $record->engagedworkspace_playlists);
                $this->assertEquals(0, $record->engagedworkspace_resources);
                $this->assertEquals(0, $record->engagedworkspace_files);
            } else if ($record->engagedworkspace_title === $hidden_workspace2->get_name()) {
                $this->assertEquals(0, $record->engagedworkspace_discussions);
                $this->assertEquals(0, $record->engagedworkspace_commentsindiscussions);
                $this->assertEquals(0, $record->engagedworkspace_playlists);
                $this->assertEquals(0, $record->engagedworkspace_resources);
                $this->assertEquals(0, $record->engagedworkspace_files);
            }
        }
    }

    /**
     *  @return void
     */
    public function test_engagedworkspace_report_for_multitenancy(): void {
        global $DB;

        $gen = $this->getDataGenerator();

        // Create different creators.
        $user_one = $gen->create_user();
        $user_two = $gen->create_user();
        $user_three = $gen->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $gen->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        /** @var \container_workspace\testing\generator $workspacegen */
        $workspacegen = $gen->get_plugin_generator('container_workspace');

        $this->setUser($user_one);
        $workspacegen->set_capabilities(CAP_ALLOW, $user_one->id);
        $workspace1 = $workspacegen->create_workspace('workspace from user_one');
        $workspace2 = $workspacegen->create_workspace('workspace from user_one');

        $this->setUser($user_two);
        $workspacegen->set_capabilities(CAP_ALLOW, $user_two->id);
        $workspace3 = $workspacegen->create_workspace('workspace from user_two');
        $workspace4 = $workspacegen->create_workspace('workspace from user_two');
        $workspace5 = $workspacegen->create_workspace('workspace from user_two');

        $this->setUser($user_three);
        $workspacegen->set_capabilities(CAP_ALLOW, $user_three->id);
        $workspacegen->create_workspace('workspace from user_three');
        $workspacegen->create_workspace('workspace from user_three');

        // Login as admin.
        $this->setAdminUser();
        $report_id = $this->get_report_id();
        $report = reportbuilder::create($report_id);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(7, $records);

        // Login as user_two.
        $this->setUser($user_two);
        $report_id = $this->get_report_id();
        $this->enable_setting($report_id);
        $report = reportbuilder::create($report_id);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);

        // User_one has to be excluded.
        $this->assertCount(5, $records);
        foreach ($records as $record) {
            $this->assertNotEquals($workspace1->get_name(), $record->engagedworkspace_title);
            $this->assertNotEquals($workspace2->get_name(), $record->engagedworkspace_title);
        }

        // Login as user_two.
        $this->setUser($user_one);
        $report_id = $this->get_report_id();
        $this->enable_setting($report_id);
        $report = reportbuilder::create($report_id);
        list($sql, $params) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);

        $this->assertCount(4, $records);
        foreach ($records as $record) {
            $this->assertNotEquals($workspace3->get_name(), $record->engagedworkspace_title);
            $this->assertNotEquals($workspace4->get_name(), $record->engagedworkspace_title);
            $this->assertNotEquals($workspace5->get_name(), $record->engagedworkspace_title);
        }
    }

    /**
     *  @return void
     */
    public function test_engagedworkspace_report_access(): void {
        self::setAdminUser();
        $report_source_class = reportbuilder::get_source_class('engagedworkspace');
        $report_embedded_class = reportbuilder::get_embedded_report_class('engagedworkspace');

        $this->assertFalse($report_source_class::is_source_ignored());
        $this->assertFalse($report_embedded_class::is_report_ignored());

        advanced_feature::disable('engage_resources');
        $this->assertFalse($report_source_class::is_source_ignored());
        $this->assertFalse($report_embedded_class::is_report_ignored());

        advanced_feature::enable('engage_resources');
        advanced_feature::disable('container_workspace');
        $this->assertTrue($report_source_class::is_source_ignored());
        $this->assertTrue($report_embedded_class::is_report_ignored());
    }

    /**
     * @param int $num
     * @param int $workspace_id
     */
    private function create_discussion(int $num, int $workspace_id):void {
        for ($i = 0; $i < $num; $i++) {
            discussion::create("Discussion content".$i, $workspace_id);
        }
    }

    /**
     * @param int $workspace_id
     */
    private function create_file_in_dicussion(int $workspace_id): void {
        global $CFG, $USER;

        require_once("{$CFG->dirroot}/lib/filelib.php");

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);
        $draft_id = file_get_unused_draft_itemid();

        $file_record = new \stdClass();
        $file_record->contextid = $context->id;
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->itemid = $draft_id;
        $file_record->filepath = '/';
        $file_record->filename = 'image.png';

        $file = $fs->create_file_from_string($file_record, "This is the file");
        $document = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text('This is the content'),
                image::create_raw_node_from_image($file)
            ],
        ]);

        discussion::create($document, $workspace_id, $draft_id, FORMAT_JSON_EDITOR);
    }

    /**
     * @param int $num
     * @param discussion $discussion
     * @param int $user_id
     */
    private function create_comments(int $num, discussion $discussion, int $user_id):void {
        for ($i = 0; $i < $num; $i++) {
            comment_helper::create_comment(
                'container_workspace'. $i,
                'discussion',
                $discussion->get_id(),
                "Hello world",
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
        $rid = $this->create_report('engagedworkspace', 'Test engagedworkspace report');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'engagedworkspace', 'title', null, null, null, 0);
        $this->add_column($report, 'engagedworkspace', 'discussions', null, null, null, 0);
        $this->add_column($report, 'engagedworkspace', 'commentsindiscussions', null, null, null, 0);
        $this->add_column($report, 'engagedworkspace', 'playlists', null, null, null, 0);
        $this->add_column($report, 'engagedworkspace', 'resources', null, null, null, 0);
        $this->add_column($report, 'engagedworkspace', 'files', null, null, null, 0);
        $this->add_column($report, 'engagedworkspace', 'members', null, null, null, 0);

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