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

use container_workspace\query\discussion\query as discussion_query;
use container_workspace\loader\discussion\loader;
use container_workspace\discussion\discussion;
use core\webapi\execution_context;
use totara_webapi\graphql;
use container_workspace\query\discussion\sort;

final class container_workspace_get_discussion_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_discussions(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

        for ($i = 0; $i < 7; $i++) {
            $discussion = $workspace_generator->create_discussion($workspace->get_id());
            for ($j = 0; $j < 10; $j++) {
                $comment_generator->create_comment(
                    $discussion->get_id(),
                    'container_workspace',
                    discussion::AREA
                );
            }
        }

        $query = new discussion_query($workspace->get_id());
        $cursor = $query->get_cursor();
        $cursor->set_limit(8);

        $paginator = loader::get_discussions($query);

        $this->assertEquals(7, $paginator->get_total());
        $items = $paginator->get_items()->all();

        /** @var discussion $item */
        foreach ($items as $item) {
            $this->assertInstanceOf(discussion::class, $item);
            $this->assertEquals(10, $item->get_total_comments());
        }
    }

    /**
     * @return void
     */
    public function test_fetch_discussions_via_graphql(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        for ($i = 0; $i < 2; $i++) {
            $workspace_generator->create_discussion($workspace->get_id());
        }

        $ec = execution_context::create('ajax', 'container_workspace_get_discussions');
        $result = graphql::execute_operation($ec, [
            'workspace_id' => $workspace->get_id(),
            'sort' => sort::get_code(sort::RECENT),
            'page' => 1
        ]);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
    }
}