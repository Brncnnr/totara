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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package container_workspace
 */

defined('MOODLE_INTERNAL') || die();

use totara_engage\entity\share as share_entity;
use totara_engage\entity\share_recipient as recipient_entity;
use totara_engage\share\provider as share_provider;
use engage_article\totara_engage\resource\article;
use engage_survey\totara_engage\resource\survey;
use totara_playlist\playlist;
use totara_engage\repository\share_repository;
use totara_engage\repository\share_recipient_repository;
use container_workspace\totara_engage\share\recipient\library as library_recipient;

class container_workspace_share_testcase extends advanced_testcase {

    /**
     * Validate the following:
     *   1. Articles can be shared with workspace (no capability validation).
     *   2. Surveys can be shared with workspace (no capability validation).
     *   3. Playlists can be shared with workspace (no capability validation).
     *   4. Sharing an item creates a database record.
     *   5. Sharing record contains the correct sharer and recipient details.
     *   6. Item can be constructed from share record.
     */
    public function test_create_share() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspacegen */
        $workspacegen = $gen->get_plugin_generator('container_workspace');

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Give user create workspace capability.
        $workspacegen->set_capabilities(CAP_ALLOW, $user->id);

        // Create workspace.
        $workspace = $workspacegen->create_workspace('SpaceX', 'X');

        // Create recipients.
        $recipients = $workspacegen->create_workspace_recipients([$workspace]);

        // Create items.
        $article = $articlegen->create_article(['content' => 'This are tickle']);
        $survey = $surveygen->create_survey('2B || !2B');
        $playlist = $playlistgen->create_playlist(['name' => 'Playing in a list']);

        // Share article.
        $shares = $articlegen->share_article($article, $recipients);
        $this->assertNotEmpty($shares);
        $this->assertEquals(1, sizeof($shares));
        $share1 = reset($shares);

        // Share survey.
        $shares = $surveygen->share_survey($survey, $recipients);
        $this->assertNotEmpty($shares);
        $this->assertEquals(1, sizeof($shares));
        $share2 = reset($shares);

        // Share playlist.
        $shares = $playlistgen->share_playlist($playlist, $recipients);
        $this->assertNotEmpty($shares);
        $this->assertEquals(1, sizeof($shares));
        $share3 = reset($shares);

        // Load the share recipient_entity from the DB. This should fail if record not found.
        $recipient_entity1 = new recipient_entity($share1->get_recipient_id());
        $recipient_entity2 = new recipient_entity($share2->get_recipient_id());
        $recipient_entity3 = new recipient_entity($share3->get_recipient_id());

        // Confirm that sharer is correct.
        $this->assertEquals($share1->get_sharer_id(), $recipient_entity1->sharerid);
        $this->assertEquals($share2->get_sharer_id(), $recipient_entity2->sharerid);
        $this->assertEquals($share3->get_sharer_id(), $recipient_entity3->sharerid);

        // Confirm that the recipient is correct.
        $this->assertEquals($share1->get_recipient_instanceid(), $recipient_entity1->instanceid);
        $this->assertEquals($share2->get_recipient_instanceid(), $recipient_entity2->instanceid);
        $this->assertEquals($share3->get_recipient_instanceid(), $recipient_entity3->instanceid);

        // Fetch item from the share.
        $provider1 = share_provider::create($share1->get_component());
        $provider2 = share_provider::create($share2->get_component());
        $provider3 = share_provider::create($share3->get_component());

        /** @var article $instance */
        $instance1 = $provider1->get_item_instance($share1->get_item_id());
        /** @var survey $instance */
        $instance2 = $provider2->get_item_instance($share2->get_item_id());
        /** @var playlist $instance */
        $instance3 = $provider3->get_item_instance($share3->get_item_id());

        // Confirm that the instance fetched is of the correct type.
        $this->assertInstanceOf(article::class, $instance1);
        $this->assertInstanceOf(survey::class, $instance2);
        $this->assertInstanceOf(playlist::class, $instance3);

        // Confirm that the loaded instances are correct.
        $this->assertEquals('This are tickle', $instance1->get_content());
        $this->assertEquals('2B || !2B', $instance2->get_name());
        $this->assertEquals('Playing in a list', $instance3->get_name());
    }

    /**
     * Validate the following:
     *   1. Same item can be shared with multiple workspaces.
     */
    public function test_total_recipients() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspacegen */
        $workspacegen = $gen->get_plugin_generator('container_workspace');

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        // Give user create workspace capability.
        $workspacegen->set_capabilities(CAP_ALLOW, $user->id);

        // Create workspace.
        $workspace1 = $workspacegen->create_workspace('SpaceX', 'X');
        $workspace2 = $workspacegen->create_workspace('SpaceY', 'Y');
        $workspace3 = $workspacegen->create_workspace('SpaceZ', 'Z');

        // Create article.
        $article = $articlegen->create_article();

        // Share article.
        $shares = $articlegen->share_article($article, $workspacegen->create_workspace_recipients([
            $workspace1,
            $workspace2,
            $workspace3
        ]));

        // Get total number of recipients.
        /** @var share_repository $repo */
        $repo = share_entity::repository();
        $total = $repo->get_total_recipients($article->get_id(), article::get_resource_type());
        $this->assertEquals(3, $total);

        // Get recipient totals per area.
        /** @var share_recipient_repository $repo */
        $repo = recipient_entity::repository();
        $totals = $repo->get_total_recipients_per_area($shares[0]->get_id());

        // Expected totals.
        $t = [
            library_recipient::AREA => 3
        ];

        // Confirm the totals for each recipient area.
        foreach ($totals as $total) {
            $this->assertEquals($t[$total->area], $total->total);
        }
    }

}