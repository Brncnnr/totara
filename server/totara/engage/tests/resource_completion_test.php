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
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\resource\resource_completion;

class totara_engage_resource_completion_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_add_resource_completion(): void {
        $gen = $this->getDataGenerator();
        $owner = $gen->create_user();
        $viewer = $gen->create_user();

        // Login as owener.
        $this->setUser($owner);
        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article();

        // Login as viewer.
        $this->setUser($viewer);
        $resource = resource_completion::instance($article->get_id(), $article->get_userid());
        $resource->can_create();
        $record = $resource->create();

        $this->assertEquals($article->get_id(), $record->resourceid);
        $this->assertEquals($viewer->id, $record->userid);
    }

    /**
     * @return void
     */
    public function test_resource_completion_capablity(): void {
        $gen = $this->getDataGenerator();
        $owner = $gen->create_user();
        $viewer = $gen->create_user();

        // Login as owener.
        $this->setUser($owner);
        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article();

        $resource = resource_completion::instance($article->get_id(), $article->get_userid());
        $this->assertFalse($resource->can_create($article->get_userid()));
        $this->assertTrue($resource->can_create($viewer->id));
    }
}