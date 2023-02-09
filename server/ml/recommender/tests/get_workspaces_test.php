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
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\workspace;
use ml_recommender\loader\recommended_item\workspaces_loader;
use ml_recommender\query\recommended_item\user_query;

class ml_recommender_get_workspaces_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_recommended_workspace_excluded_delete_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Log in as user one and create a workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        /** @var \ml_recommender\testing\generator $recommender_generator */
        $recommender_generator = $generator->get_plugin_generator('ml_recommender');
        $recommender_generator->create_user_recommendation(
            $user_two->id,
            $workspace->get_id(),
            workspace::get_type()
        );

        $query = new user_query($user_two->id, workspace::get_type());
        $before_result = workspaces_loader::get_recommended_for_user($query, $user_two->id);

        self::assertEquals(1, $before_result->get_total());
        $before_result_items = $before_result->get_items()->all();

        self::assertCount(1, $before_result_items);
        $fetched_workspace = reset($before_result_items);

        self::assertInstanceOf(workspace::class, $fetched_workspace);
        self::assertEquals($workspace->get_id(), $fetched_workspace->get_id());

        // Flag the workspace to be deleted - and check if the loader still load it.
        $workspace->mark_to_be_deleted();
        $after_result = workspaces_loader::get_recommended_for_user($query, $user_two->id);

        self::assertEquals(0, $after_result->get_total());
    }
}