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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use totara_engage\share\manager as share_manager;
use core_user\totara_engage\share\recipient\user;
use totara_engage\access\access_manager;
use container_workspace\member\member;
use container_workspace\totara_engage\share\recipient\library;

class container_workspace_share_to_workspace_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_share_article_to_private_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three  = $generator->create_user();

        $this->engage_capabilize($user_one);
        $this->setUser($user_one);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $restricted_article = $article_generator->create_article(['access' => access::RESTRICTED]);
        $user_three_recipient = new user($user_three->id);

        $this->assertTrue($restricted_article->is_restricted());

        share_manager::share_to_recipient($restricted_article, $user_three_recipient, $user_one->id);
        $this->assertTrue(access_manager::can_access($restricted_article, $user_three->id));
        $this->assertFalse(access_manager::can_access($restricted_article, $user_two->id));

        // Create a workspace.

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Add user two to the workspace.
        member::added_to_workspace($workspace, $user_two->id);

        // Then share the article to a workspace library.
        $library_recipient = new library($workspace->get_id());
        share_manager::share_to_recipient($restricted_article, $library_recipient, $user_one->id);

        // Make sure that user two has the access to this article now. But also make sure that user three
        // does not loose the access.
        $this->assertTrue(access_manager::can_access($restricted_article, $user_two->id));
        $this->assertTrue(access_manager::can_access($restricted_article, $user_three->id));
    }

    /**
     * @return void
     */
    public function test_share_survey_to_hidden_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        $this->engage_capabilize($user_one);
        $this->setUser($user_one);

        // Create survey and exclusively share to user_three only.
        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $restricted_survey = $survey_generator->create_restricted_survey();

        $this->assertTrue($restricted_survey->is_restricted());
        $this->assertFalse(access_manager::can_access($restricted_survey, $user_three->id));
        $this->assertFalse(access_manager::can_access($restricted_survey, $user_two->id));

        $user_three_recipient = new user($user_three->id);
        share_manager::share_to_recipient($restricted_survey, $user_three_recipient, $user_one->id);

        $this->assertTrue(access_manager::can_access($restricted_survey, $user_three->id));
        $this->assertFalse(access_manager::can_access($restricted_survey, $user_two->id));

        // Create workspace and add user two to the workspace.

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        member::added_to_workspace($workspace, $user_two->id);

        // Share survey to the workspace.
        $library = new library($workspace->get_id());
        share_manager::share_to_recipient($restricted_survey, $library, $user_one->id);

        $this->assertTrue(access_manager::can_access($restricted_survey, $user_two->id));
        $this->assertTrue(access_manager::can_access($restricted_survey, $user_three->id));
    }

    /**
     * @return void
     */
    public function test_share_playlist_to_private_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $restricted_playlist = $playlist_generator->create_playlist(['access' => access::RESTRICTED]);

        $this->assertTrue($restricted_playlist->is_restricted());
        $this->assertFalse(access_manager::can_access($restricted_playlist, $user_two->id));
        $this->assertFalse(access_manager::can_access($restricted_playlist, $user_three->id));

        // Share the playlist to the workspace.
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $library = new library($workspace->get_id());
        share_manager::share_to_recipient($restricted_playlist, $library);

        $this->assertFalse(access_manager::can_access($restricted_playlist, $user_two->id));
        $this->assertFalse(access_manager::can_access($restricted_playlist, $user_three->id));

        // Add user two to the workspace.
        member::added_to_workspace($workspace, $user_two->id);
        $this->assertTrue(access_manager::can_access($restricted_playlist, $user_two->id));
        $this->assertFalse(access_manager::can_access($restricted_playlist, $user_three->id));

        member::added_to_workspace($workspace, $user_three->id);
        $this->assertTrue(access_manager::can_access($restricted_playlist, $user_three->id));
        $this->assertTrue(access_manager::can_access($restricted_playlist, $user_two->id));
    }

    private function engage_capabilize($user) {
        $roleid = $this->getDataGenerator()->create_role();
        $syscontext = context_system::instance();
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $syscontext);
        role_assign($roleid, $user->id, $syscontext);
    }
}