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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\workspace;
use totara_engage\card\card;
use totara_webapi\phpunit\webapi_phpunit_helper;

class container_workspace_webapi_shared_cards_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @dataProvider ws_provider
     */
    public function test_public_private_workspace_member(bool $is_private, bool $is_member) {
        [$workspace, $member] = $this->prepare($is_private);

        if ($is_member) {
            $this->setUser($member);
        } else {
            $nonmember = $this->getDataGenerator()->create_user();
            $this->setUser($nonmember);
        }

        $this->assert_positive($workspace);
    }

    public function test_private_workspace_non_member() {
        [$workspace] = $this->prepare(true);
        $nonmember = $this->getDataGenerator()->create_user();
        $this->setUser($nonmember);

        $this->assert_negative($workspace);
    }

    public function test_private_workspace_non_member_admin() {
        [$workspace] = $this->prepare(true);
        $this->setAdminUser();
        $this->assert_positive($workspace);
    }

    public function test_private_workspace_member_same_tenant() {
        [$workspace, $member] = $this->prepare(true, true);
        $this->setUser($member);
        $this->assert_positive($workspace);
    }

    public function test_private_workspace_member_different_tenant() {
        [$workspace, $member] = $this->prepare(true, true);

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_gen =  \totara_tenant\testing\generator::instance();
        $tenant2 = $tenant_gen->create_tenant();
        $tenant_gen->migrate_user_to_tenant($member->id, $tenant2->id);
        $member->tenantid = $tenant2->id;

        $this->setUser($member);
        $this->assert_negative($workspace);
    }

    public function test_private_workspace_different_tenant_manager() {
        [$workspace, $member] = $this->prepare(true, true);

        $this->make_other_tenant_manager($member);
        $this->setUser($member);
        $this->assert_negative($workspace);
    }

    public function test_public_workspace_different_tenant_manager() {
        [$workspace, $member] = $this->prepare(false, true);

        $this->make_other_tenant_manager($member);
        $this->setUser($member);
        $this->assert_negative($workspace);
    }

    protected function assert_negative(workspace $workspace) {
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('container_workspace/access_denied');
        $this->resolve_graphql_query(
            'container_workspace_shared_cards',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'library',
                'include_footnotes' => false,
                'filter' => []
            ]
        );
    }

    protected function assert_positive(workspace $workspace) {
        $result = $this->resolve_graphql_query(
            'container_workspace_shared_cards',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'library',
                'include_footnotes' => false,
                'filter' => []
            ]
        );

        $cards = $result['cards'];
        $this->assertCount(3, $cards);
        foreach ($cards as $card) {
            $this->assertInstanceOf(card::class, $card);
        }
    }

    protected function prepare(bool $isprivateworkspace = false, bool $istenants = false): array {
        $gen = $this->getDataGenerator();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        $owner = $gen->create_user();
        $member = $gen->create_user();

        $tenant = null;
        if ($istenants) {
            /** @var \totara_tenant\testing\generator $tenant_generator */
            $tenant_gen = $gen->get_plugin_generator('totara_tenant');
            $tenant_gen->enable_tenants();

            $tenant = $tenant_gen->create_tenant();
            $tenant_gen->migrate_user_to_tenant($owner->id, $tenant->id);
            $owner->tenantid = $tenant->id;

            $tenant_gen->migrate_user_to_tenant($member->id, $tenant->id);
            $member->tenantid = $tenant->id;
        }

        $this->setUser($owner);

        /**
         * @var \container_workspace\testing\generator $workspacegen
         */
        $workspacegen = $gen->get_plugin_generator('container_workspace');

        // Give user create workspace capability.
        $workspacegen->set_capabilities(CAP_ALLOW, $owner->id);

        // Create workspace.
        $workspace = $workspacegen->create_workspace('SpaceX', 'X', null, null, $isprivateworkspace);

        // Add member.
        $workspacegen->add_member($workspace, $member->id, $owner->id);

        // Create recipients.
        $recipients = $workspacegen->create_workspace_recipients([$workspace]);

        // Create and share items.
        $article = $articlegen->create_article(['content' => 'This are tickle', 'access' => \totara_engage\access\access::PUBLIC]);
        $survey = $surveygen->create_survey('2B || !2B', [], 1, ['access' => \totara_engage\access\access::PUBLIC]);
        $playlist = $playlistgen->create_playlist(['name' => 'Playing in a list', 'access' => \totara_engage\access\access::PUBLIC]);

        $articlegen->share_article($article, $recipients);
        $surveygen->share_survey($survey, $recipients);
        $playlistgen->share_playlist($playlist, $recipients);

        return [$workspace, $member];
    }

    /**
     * Make another tenant manager
     */
    protected function make_other_tenant_manager(stdClass $member) {
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_gen =  \totara_tenant\testing\generator::instance();
        $tenant2 = $tenant_gen->create_tenant();
        $tenant_gen->migrate_user_to_tenant($member->id, $tenant2->id);

        $tenant2_context = context_tenant::instance($tenant2->id);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $roleid, $tenant2_context);
        role_assign($roleid, $member->id, $tenant2_context);
    }

    public function ws_provider() {
        return [
            [false, false], // Public, non-member
            [false, true], // Public, member
            [true, true], // Private, member
        ];
    }
}
