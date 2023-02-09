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
 * @package totara_playlist
 */

use totara_engage\resource\resource_factory;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

class totara_playlist_userdata_playlist_testcase extends advanced_testcase {

    public function test_purge_playlist(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();
        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        for ($i = 0; $i < 3; $i++) {
            $playlistgen->create_playlist();
        }

        // Playlist created
        $this->assertTrue(
            $DB->record_exists('playlist', ['userid' => $user_one->id])
        );

        $user_one->deleted = 1;
        $DB->update_record('user', $user_one);

        $target_user = new target_user($user_one);
        $context = context_system::instance();

        $result = \totara_playlist\userdata\playlist::execute_purge($target_user, $context);
        $this->assertEquals(\totara_playlist\userdata\playlist::RESULT_STATUS_SUCCESS, $result);

        $this->assertFalse(
            $DB->record_exists('playlist', ['userid' => $user_one->id])
        );
    }

    /**
     * @return void
     */
    public function test_export_playlist(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();
        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $playlist = $playlistgen->create_playlist();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen  = $gen->get_plugin_generator('engage_survey');

        // Three resources created
        $article1 = $articlegen->create_article();
        $article2 = $articlegen->create_article();
        $survey   = $surveygen->create_survey();

        // Add resources to created playslist
        $playlist->add_resource(resource_factory::create_instance_from_id($article1->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($article2->get_id()));
        $playlist->add_resource(resource_factory::create_instance_from_id($survey->get_id()));

        // Artcile created
        $this->assertTrue(
            $DB->record_exists('playlist', ['userid' => $user_one->id])
        );

        $target_user = new target_user($user_one);
        $context = context_system::instance();

        $export = \totara_playlist\userdata\playlist::execute_export($target_user, $context);

        $this->assertNotEmpty($export->data);
        $this->assertCount(1, $export->data);

        foreach ($export->data as $record) {
            $this->assertArrayHasKey('Numberofresources', $record);
            $this->assertArrayHasKey('name', $record);
            $this->assertArrayHasKey('summary', $record);
            $this->assertArrayHasKey('time_created', $record);
            $this->assertEquals(3, $record['Numberofresources']);
        }
    }
}