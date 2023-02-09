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
defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use totara_engage\access\access;
use totara_engage\resource\resource_factory;
use totara_webapi\graphql;

/**
 * Test how the playlist reacts when a resource is removed.
 */
class totara_playlist_remove_resource_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_remove_resource(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();
        $user_two = $gen->create_user();
        $this->setUser($user_one);
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlistgen->create_playlist();

        $this->setUser($user_two);
        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article1 = $articlegen->create_article(['access' => access::PUBLIC]);
        $article2 = $articlegen->create_article(['access' => access::PUBLIC]);
        $article3 = $articlegen->create_article(['access' => access::PUBLIC]);

        $playlist->add_resource(resource_factory::create_instance_from_id($article1->get_id()), $user_one->id);
        $playlist->add_resource(resource_factory::create_instance_from_id($article2->get_id()), $user_one->id);
        $playlist->add_resource(resource_factory::create_instance_from_id($article3->get_id()), $user_one->id);

        $this->assertEquals(3, $DB->count_records('playlist_resource', ['playlistid' => $playlist->get_id()]));

        // Remove resource.
        $playlist->remove_resource(resource_factory::create_instance_from_id($article1->get_id()), $user_one->id);

        $this->assertEquals(2, $DB->count_records('playlist_resource', ['playlistid' => $playlist->get_id()]));
        $this->assertFalse($DB->record_exists('playlist_resource', ['resourceid' => $article1->get_id()]));
    }

    /**
     * @return void
     */
    public function test_remove_resource_via_graphql(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();
        $user_two = $gen->create_user();
        $this->setUser($user_one);
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlistgen->create_playlist();

        $this->setUser($user_two);
        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article1 = $articlegen->create_article(['access' => access::PUBLIC]);
        $article2 = $articlegen->create_article(['access' => access::PUBLIC]);
        $article3 = $articlegen->create_article(['access' => access::PUBLIC]);

        $this->setUser($user_one);
        $playlist->add_resource(resource_factory::create_instance_from_id($article1->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article2->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article3->get_id()));

        $this->assertEquals(3, $DB->count_records('playlist_resource', ['playlistid' => $playlist->get_id()]));

        $ec = execution_context::create('ajax', 'totara_playlist_remove_resource');
        $result = graphql::execute_operation($ec, [
            'id' => $playlist->get_id(),
            'instanceid' => $article2->get_id()
        ]);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertEquals(2, $DB->count_records('playlist_resource', ['playlistid' => $playlist->get_id()]));
        $this->assertFalse($DB->record_exists('playlist_resource', ['resourceid' => $article2->get_id()]));
    }

    /**
     * Validate that when a resources is added or removed from
     * a playlist, the image processor is triggered to handle
     * images.
     */
    public function test_remove_resource_calls_update_image(): void {
        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();
        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_gen */
        $playlist_gen = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlist_gen->create_playlist();

        /** @var \engage_article\testing\generator $article_gen */
        $article_gen = $gen->get_plugin_generator('engage_article');
        $item_one = $article_gen->create_article();

        // We want to mock and replace the image processor used by the playlist.
        // The intent here is to test that when the playlist is updated without
        // specifically running the image_processor itself (as that's handled
        // in another group of tests). This mock object will keep count of
        // how many times any method is called so we can check that the playlist
        // triggered the image processor the expected number of times.
        $mock = $playlist_gen->get_mock_image_processor();
        $playlist->set_image_processor($mock);
        $this->assertSame(0, $mock->count('update_playlist_images'));

        // Add the resource to the playlist, check the update_playlist_images was triggered
        $playlist->add_resource($item_one, $user_one->id);
        $this->assertSame(1, $mock->count('update_playlist_images'));

        // Remove the resource to the playlist, check the generate was triggered
        $playlist->remove_resource($item_one);
        $this->assertSame(2, $mock->count('update_playlist_images'));
    }
}