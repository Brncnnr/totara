<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_playlist
 */

use core\webapi\execution_context;
use totara_engage\resource\resource_factory;
use totara_playlist\exception\playlist_exception;
use totara_playlist\local\helper;
use totara_playlist\playlist;
use totara_webapi\graphql;

defined('MOODLE_INTERNAL') || die();

class totara_playlist_update_card_order_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_swap_card(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlistgen->create_playlist();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article();
        $article1 = $articlegen->create_article();
        $article2 = $articlegen->create_article();

        $playlist->add_resource(resource_factory::create_instance_from_id($article->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article1->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article2->get_id()));

        $playlist->load_resources();
        foreach ($playlist->get_resources() as $resource) {
            if ((int) $resource->resourceid === $article->get_id()) {
                $this->assertEquals(1, $resource->sortorder);
            }
        }
        $this->assertEquals(
            1,
            $DB->get_field(
                'playlist_resource',
                'sortorder',
                ['playlistid' => $playlist->get_id(), 'resourceid' => $article->get_id()]
            )
        );

        helper::swap_card_sort_order($playlist, $article->get_id(), 0);

        $this->assertEquals(
            3,
            $DB->get_field(
                'playlist_resource',
                'sortorder',
                ['playlistid' => $playlist->get_id(), 'resourceid' => $article->get_id()]
            )
        );

        $this->assertEquals(
            2,
            $DB->get_field(
                'playlist_resource',
                'sortorder',
                ['playlistid' => $playlist->get_id(), 'resourceid' => $article2->get_id()]
            )
        );

        $this->assertEquals(
            1,
            $DB->get_field(
                'playlist_resource',
                'sortorder',
                ['playlistid' => $playlist->get_id(), 'resourceid' => $article1->get_id()]
            )
        );

        // Test order is negative.
        $this->expectException(playlist_exception::class);
        $this->expectExceptionMessage(get_string('error:update_order', 'totara_playlist'));
        helper::swap_card_sort_order($playlist, $article->get_id(), -1);
    }

    /**
     * @return void
     */
    public function test_swap_card_order_with_exception(): void {
        $gen = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlistgen->create_playlist();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article();
        $article1 = $articlegen->create_article();
        $article2 = $articlegen->create_article();
        $article3 = $articlegen->create_article();

        $playlist->add_resource(resource_factory::create_instance_from_id($article->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article1->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article2->get_id()));

        // Test resource is not in the playlist
        $this->expectException('coding_exception');
        helper::swap_card_sort_order($playlist, $article3->get_id(), 2);
    }

    /**
     * @return void
     */
    public function test_swap_card_order_out_of_boundary(): void {
        $gen = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlistgen->create_playlist();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article();
        $article1 = $articlegen->create_article();
        $article2 = $articlegen->create_article();

        $playlist->add_resource(resource_factory::create_instance_from_id($article->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article1->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article2->get_id()));

        // Test is out of boundary.
        $this->expectException(playlist_exception::class);
        $this->expectExceptionMessage(get_string('error:update_order', 'totara_playlist'));
        helper::swap_card_sort_order($playlist, $article->get_id(), 5);
    }

    /**
     * @return void
     */
    public function test_swap_card_order_with_graphql(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlistgen->create_playlist();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article();
        $article1 = $articlegen->create_article();
        $article2 = $articlegen->create_article();

        $playlist->add_resource(resource_factory::create_instance_from_id($article->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article1->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article2->get_id()));

        $this->assertEquals(
            1,
            $DB->get_field(
                'playlist_resource',
                'sortorder',
                ['resourceid' => $article->get_id(), 'playlistid' => $playlist->get_id()])
        );

        $parameters = [
            'id' => $playlist->get_id(),
            'order' => 0,
            'instanceid' => $article->get_id()
        ];

        $ec = execution_context::create('ajax', 'totara_playlist_update_card_order');
        $result = graphql::execute_operation($ec, $parameters);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertEquals(
            3,
            $DB->get_field(
                'playlist_resource',
                'sortorder',
                ['resourceid' => $article->get_id(), 'playlistid' => $playlist->get_id()])
        );

        // Check the resource is not in the playlist, exception will be fired.
        $article3 = $articlegen->create_article();
        $parameters = [
            'id' => $playlist->get_id(),
            'order' => 2,
            'instanceid' => $article3->get_id()
        ];

        $ec = execution_context::create('ajax', 'totara_playlist_update_card_order');
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotEmpty($result->errors);

        // Check order is out of boundary
        $parameters = [
            'id' => $playlist->get_id(),
            'order' => -1,
            'instanceid' => $article->get_id()
        ];

        $ec = execution_context::create('ajax', 'totara_playlist_update_card_order');
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotEmpty($result->errors);

        // Check order is out of boundary
        $parameters = [
            'id' => $playlist->get_id(),
            'order' => 3,
            'instanceid' => $article3->get_id()
        ];

        $ec = execution_context::create('ajax', 'totara_playlist_update_card_order');
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotEmpty($result->errors);
        $error = current($result->errors);
        $this->assertEquals(get_string('error:update_order', 'totara_playlist'), $error->getMessage());
    }

    /**
     * @return void
     */
    public function test_swap_card_order_as_admin_with_graphql(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user1 = $gen->create_user();
        $this->setUser($user1);

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlistgen->create_playlist();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article();
        $article1 = $articlegen->create_article();
        $article2 = $articlegen->create_article();

        $playlist->add_resource(resource_factory::create_instance_from_id($article->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article1->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article2->get_id()));

        $this->setAdminUser();

        $this->assertEquals(
            1,
            $DB->get_field(
                'playlist_resource',
                'sortorder',
                ['resourceid' => $article->get_id(), 'playlistid' => $playlist->get_id()]
            )
        );

        $parameters = [
            'id' => $playlist->get_id(),
            'order' => 0,
            'instanceid' => $article->get_id()
        ];

        $ec = execution_context::create('ajax', 'totara_playlist_update_card_order');
        $result = graphql::execute_operation($ec, $parameters);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertEquals(
            3,
            $DB->get_field(
                'playlist_resource',
                'sortorder',
                ['resourceid' => $article->get_id(), 'playlistid' => $playlist->get_id()]
            )
        );

        // Check the resource is not in the playlist, exception will be fired.
        $article3 = $articlegen->create_article();
        $parameters = [
            'id' => $playlist->get_id(),
            'order' => 2,
            'instanceid' => $article3->get_id()
        ];

        $ec = execution_context::create('ajax', 'totara_playlist_update_card_order');
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotEmpty($result->errors);

        // Check order is out of boundary
        $parameters = [
            'id' => $playlist->get_id(),
            'order' => -1,
            'instanceid' => $article->get_id()
        ];

        $ec = execution_context::create('ajax', 'totara_playlist_update_card_order');
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotEmpty($result->errors);

        // Check order is out of boundary
        $parameters = [
            'id' => $playlist->get_id(),
            'order' => 3,
            'instanceid' => $article3->get_id()
        ];

        $ec = execution_context::create('ajax', 'totara_playlist_update_card_order');
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotEmpty($result->errors);
        $error = current($result->errors);
        $this->assertEquals(get_string('error:update_order', 'totara_playlist'), $error->getMessage());
    }

    /**
     * @return void
     */
    public function test_upgrade_flip_card_order(): void {
        global $DB, $CFG;

        $this->setAdminUser();
        $gen = $this->getDataGenerator();

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlistgen->create_playlist();

        $this->add_resources_for_playlist($playlist, 3);
        $records = $DB->get_records('playlist_resource', ['playlistid' => $playlist->get_id()], 'sortorder ASC');
        $first = reset($records);
        $last = end($records);

        $this->assertEquals(1, (int)$first->sortorder);
        $this->assertEquals(3, (int)$last->sortorder);

        require_once($CFG->dirroot.'/totara/playlist/db/upgradelib.php');
        totara_playlist_upgrade_fix_card_sort_order();

        $records = $DB->get_records('playlist_resource', ['playlistid' => $playlist->get_id()], 'id');
        $first = reset($records);
        $expect_sort = $first->sortorder;
        foreach ($records as $record) {
            $this->assertEquals($expect_sort, $record->sortorder);
            $expect_sort--;
        }
    }

    /**
     * @return void
     */
    public function test_upgrade_flip_card_order_in_multiple_playlists(): void {
        global $DB, $CFG;

        $this->setAdminUser();
        $gen = $this->getDataGenerator();

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $playlist1 = $playlistgen->create_playlist();
        $playlist2 = $playlistgen->create_playlist();
        $playlist3 = $playlistgen->create_playlist();

        $this->add_resources_for_playlist($playlist1, 3);
        $this->add_resources_for_playlist($playlist2, 4);
        $this->add_resources_for_playlist($playlist3, 5);

        $records = $DB->get_records('playlist_resource', ['playlistid' => $playlist1->get_id()], 'sortorder ASC');
        $first = reset($records);
        $last = end($records);
        $this->assertCount(3, $records);

        $this->assertEquals(1, (int)$first->sortorder);
        $this->assertEquals(3, (int)$last->sortorder);

        $records = $DB->get_records('playlist_resource', ['playlistid' => $playlist2->get_id()], 'sortorder ASC');
        $first = reset($records);
        $last = end($records);
        $this->assertCount(4, $records);
        $this->assertEquals(1, (int)$first->sortorder);
        $this->assertEquals(4, (int)$last->sortorder);

        $records = $DB->get_records('playlist_resource', ['playlistid' => $playlist3->get_id()], 'sortorder ASC');
        $first = reset($records);
        $last = end($records);
        $this->assertCount(5, $records);
        $this->assertEquals(1, (int)$first->sortorder);
        $this->assertEquals(5, (int)$last->sortorder);

        require_once($CFG->dirroot.'/totara/playlist/db/upgradelib.php');
        totara_playlist_upgrade_fix_card_sort_order();

        $records = $DB->get_records('playlist_resource', ['playlistid' => $playlist1->get_id()], 'id');

        $first = reset($records);
        $expect_sort = $first->sortorder;
        foreach ($records as $record) {
            $this->assertEquals($expect_sort, $record->sortorder);
            $expect_sort--;
        }

        $records = $DB->get_records('playlist_resource', ['playlistid' => $playlist2->get_id()], 'id');

        $first = reset($records);
        $expect_sort = $first->sortorder;
        foreach ($records as $record) {
            $this->assertEquals($expect_sort, $record->sortorder);
            $expect_sort--;
        }

        $records = $DB->get_records('playlist_resource', ['playlistid' => $playlist3->get_id()], 'id');

        $first = reset($records);
        $max_sort = $first->sortorder;
        foreach ($records as $record) {
            $this->assertEquals($max_sort, $record->sortorder);
            $max_sort--;
        }
    }

    /**
     * @param playlist $playlist
     * @param int $number
     */
    private function add_resources_for_playlist(playlist $playlist, int $number): void {
        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $this->getDataGenerator()->get_plugin_generator('engage_article');
        for ($i = 0; $i < $number; $i++) {
            $playlist->add_resource($articlegen->create_article());
        }
    }
}