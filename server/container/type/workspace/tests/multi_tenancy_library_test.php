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

use container_workspace\member\member;
use totara_engage\answer\answer_type;
use totara_engage\access\access;
use container_workspace\totara_engage\share\recipient\library;
use core\webapi\execution_context;
use totara_webapi\graphql;
use core_user\totara_engage\share\recipient\user;

class container_workspace_multi_tenancy_library_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_shared_card(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_engage\testing\generator $engage_generator */
        $engage_generator = $generator->get_plugin_generator('totara_engage');

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();

        $user_one = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        $user_two = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        // Login as user one and start creating the workspace
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $workspace = $workspace_generator->create_workspace();

        // Login as user two and start joining workspace
        $this->setUser($user_two);
        member::join_workspace($workspace, $user_two->id);

        // Now start creating a bunch of resources/surveys.
        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $workspace_recipient = new library($workspace->get_id());

        for ($i = 0; $i < 5; $i++) {
            $survey = $survey_generator->create_survey(null, [], answer_type::MULTI_CHOICE, ['access' => access::PUBLIC]);
            $survey_generator->share_survey($survey, [$workspace_recipient]);
        }

        // Login as user one and check if the user one is able to see these resources.
        $this->setUser($user_one);

        $ec = execution_context::create('ajax', 'container_workspace_shared_cards');
        $same_result = graphql::execute_operation(
            $ec,
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'library',
                'include_footnotes' => false,
                'theme' => 'ventura',
            ]
        );

        $this->assertEmpty($same_result->errors);
        $this->assertNotEmpty($same_result->data);
        $this->assertArrayHasKey('contribution', $same_result->data);

        $this->assertArrayHasKey('cards', $same_result->data['contribution']);
        $this->assertCount(5, $same_result->data['contribution']['cards']);

        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        $different_tenant_result = graphql::execute_operation(
            $ec,
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'library',
                'include_footnotes' => false,
                'theme' => 'ventura',
            ]
        );

        $this->assertEmpty($different_tenant_result->errors);
        $this->assertNotEmpty($different_tenant_result->data);
        $this->assertArrayHasKey('contribution', $different_tenant_result->data);

        $this->assertArrayHasKey('cards', $different_tenant_result->data['contribution']);
        $this->assertEmpty($different_tenant_result->data['contribution']['cards']);
    }

    /**
     * @todo write me some descriptive text
     * @return void
     */
    public function test_fetch_shared_card_as_ex_member(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_engage\testing\generator $engage_generator */
        $engage_generator = $generator->get_plugin_generator('totara_engage');

        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topic = $topic_generator->create_topic();

        // Log in as user one and create a resource which is shared to the user two.
        $this->setUser($user_one);
        $user_two_recipient = new user($user_one->id);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article([
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()]
        ]);

        $article_generator->share_article($article, [$user_two_recipient]);

        // Log in as admin user and create the workspace.
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Join to workspace as user one and as user two.
        member::join_workspace($workspace, $user_one->id);
        member::join_workspace($workspace, $user_two->id);

        // Share an article to the workspace as user two.
        $this->setUser($user_two);
        $workspace_recipient = new library($workspace->get_id());

        $article_generator->share_article($article, [$workspace_recipient]);

        // Check if as user two, user is able to see the share.
        $ec = execution_context::create('ajax', 'container_workspace_shared_cards');
        $parameters = [
            'workspace_id' => $workspace->get_id(),
            'area' => 'library',
            'include_footnotes' => false,
            'theme' => 'ventura',
        ];

        $this->setUser($user_two);
        $result = graphql::execute_operation($ec, $parameters);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('contribution', $result->data);
        $this->assertArrayHasKey('cards', $result->data['contribution']);
        $this->assertCount(1, $result->data['contribution']['cards']);

        // Now move the two of users to different tenant, and hence we can check if the user one
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Log in as user one and check if this user is still see the articles created by this user
        // but re-shared by different user.
        $this->setUser($user_one);
        $result = graphql::execute_operation($ec, $parameters);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('contribution', $result->data);
        $this->assertArrayHasKey('cards', $result->data['contribution']);
        $this->assertCount(1, $result->data['contribution']['cards']);

        // Log in as user one and check if this user is able to see the articles created by user_one,
        // but share by self.
        $this->setUser($user_two);
        $result = graphql::execute_operation($ec, $parameters);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('contribution', $result->data);
        $this->assertArrayHasKey('cards', $result->data['contribution']);
        $this->assertEmpty($result->data['contribution']['cards']);

    }
}