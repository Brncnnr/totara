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

use totara_webapi\phpunit\webapi_phpunit_helper;
use core\orm\pagination\offset_cursor_paginator;
use container_workspace\member\member;

class container_workspace_webapi_fetch_discussion_cursor_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_discussion_cursor_of_private_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Log in as second user to fetch the discussion cursor.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot get the discussion's cursor");

        $this->resolve_graphql_query(
            'container_workspace_discussion_cursor',
            ['workspace_id' => $workspace->get_id()]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_discussion_cursor_of_hidden_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        // Log in as second user and fetch the discussion cursor.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot get the discussion's cursor");

        $this->resolve_graphql_query(
            'container_workspace_discussion_cursor',
            ['workspace_id' => $workspace->get_id()]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_discussion_cursor_of_public_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Log in as second user and try to fetch the discussion cursor.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        /** @var offset_cursor_paginator $cursor_paginator */
        $cursor_paginator = $this->resolve_graphql_query(
            'container_workspace_discussion_cursor',
            ['workspace_id' => $workspace->get_id()]
        );

        self::assertInstanceOf(offset_cursor_paginator::class, $cursor_paginator);
        self::assertEquals(0, $cursor_paginator->get_total());
    }

    /**
     * @return void
     */
    public function test_fetch_discussion_cursor_of_private_workspace_by_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        // Log in as second user and fetch for the discussion cursor.
        $this->setUser($user_two);

        /** @var offset_cursor_paginator $cursor_paginator */
        $cursor_paginator = $this->resolve_graphql_query(
            'container_workspace_discussion_cursor',
            ['workspace_id' => $workspace->get_id()]
        );

        self::assertInstanceOf(offset_cursor_paginator::class, $cursor_paginator);
        self::assertEquals(0, $cursor_paginator->get_total());
    }
}