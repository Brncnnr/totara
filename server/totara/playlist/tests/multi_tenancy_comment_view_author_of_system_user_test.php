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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_playlist\playlist;
use totara_comment\interactor\comment_interactor;

class totara_playlist_multi_tenancy_comment_view_author_of_system_user_testcase extends advanced_testcase {
    /**
     * @var stdClass|null
     */
    private $tenant_user;

    /**
     * @var stdClass|null
     */
    private $tenant_participant;

    /**
     * The main user actor in this test case.
     * @var stdClass|null
     */
    private $system_user;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();

        $this->tenant_user = $generator->create_user([
            'firstname' => 'tenant_user',
            'lastname' => 'tenant_user',
            'tenantid' => $tenant->id
        ]);

        $this->system_user = $generator->create_user([
            'firstname' => 'system_user',
            'lastname' => 'system_user'
        ]);

        $this->tenant_participant = $generator->create_user([
            'firstname' => 'tenant_participant',
            'lastname' => 'tenant_participant'
        ]);

        $tenant_generator->set_user_participation($this->tenant_participant->id, [$tenant->id]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_user = null;
        $this->tenant_participant = null;
        $this->system_user = null;
    }

    /**
     * @return \totara_playlist\testing\generator
     */
    private function get_playlist_generator(): \totara_playlist\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        return $playlist_generator;
    }

    /**
     * @return \totara_comment\testing\generator
     */
    private function get_comment_generator(): \totara_comment\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        return $comment_generator;
    }

    /**
     * @return void
     */
    public function test_check_can_view_author_of_tenant_member_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist(['userid' => $this->system_user->id]);

        // Create a comment as of tenant member.
        $this->setUser($this->tenant_user);
        $comment_generator = $this->get_comment_generator();

        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        $interactor = new comment_interactor($comment, $this->system_user->id);
        self::assertTrue($interactor->can_view_author());
    }

    /**
     * @return void
     */
    public function test_check_can_view_author_of_tenant_member_with_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist(['userid' => $this->system_user->id]);

        // Create a comment as of tenant member.
        $this->setUser($this->tenant_user);
        $comment_generator = $this->get_comment_generator();

        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        set_config('tenantsisolated', 1);

        $interactor = new comment_interactor($comment, $this->system_user->id);
        self::assertFalse($interactor->can_view_author());
    }

    /**
     * @return void
     */
    public function test_check_can_view_author_of_tenant_participant_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist(['userid' => $this->system_user->id]);

        // Create a comment as of tenant member.
        $this->setUser($this->tenant_participant);
        $comment_generator = $this->get_comment_generator();

        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        $interactor = new comment_interactor($comment, $this->system_user->id);
        self::assertTrue($interactor->can_view_author());
    }

    /**
     * @return void
     */
    public function test_check_can_view_author_of_tenant_participant_with_isolation(): void {
        set_config('tenantsisolated', 1);

        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist(['userid' => $this->system_user->id]);

        // Create a comment as of tenant member.
        $this->setUser($this->tenant_participant);
        $comment_generator = $this->get_comment_generator();

        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        $interactor = new comment_interactor($comment, $this->system_user->id);
        self::assertTrue($interactor->can_view_author());
    }
}