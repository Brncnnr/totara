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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

defined('MOODLE_INTERNAL') || die();

use engage_article\totara_engage\resource\article;
use totara_msteams\my\helpers\catalog_helper;
use totara_playlist\playlist;

class totara_msteams_catalog_helper_testcase extends advanced_testcase {
    /** @var stdClass */
    private $user;
    /** @var stdClass */
    private $course;
    /** @var stdClass */
    private $program;
    /** @var stdClass */
    private $certification;
    /** @var article */
    private $article;
    /** @var playlist */
    private $playlist;

    public function setUp(): void {
        $gen = $this->getDataGenerator();
        $this->user = $gen->create_user();
        $this->setUser($this->user);

        $pgmgen = $gen->get_plugin_generator('totara_program');
        /** @var \totara_program\testing\generator $pgmgen */

        $this->course = $gen->create_course(['fullname' => 'Test course', 'summary' => 'akoranga']);
        $this->program = $pgmgen->create_program(['fullname' => 'Test program', 'summary' => 'marau']);
        $this->certification = $pgmgen->create_certification(['fullname' => 'Test certification', 'summary' => 'pukapuka']);
        $this->article = article::create(['name' => 'Test article', 'content' => 'atikara']);
        $this->playlist = playlist::create('Test playlist');
    }

    public function tearDown(): void {
        $this->user = null;
        $this->course = null;
        $this->program = null;
        $this->certification = null;
        $this->article = null;
        $this->playlist = null;
    }

    public function test_search() {
        global $DB;

        if ($DB->get_dbfamily() == 'mssql') {
            // See my_router_test::test_messaging_extension_search()
            $this->markTestSkipped("Skipped as catalog is not indexed properly in phpunit environment.");
        }

        // We don't need thorough testing as this function is just a thin wrapper around the catalogue interface.

        $items = catalog_helper::search(null, 0, 3);
        $this->assertCount(3, $items);

        $items = catalog_helper::search('', 0, 2);
        $this->assertCount(2, $items);

        $items = catalog_helper::search('blahblah', 0, 10);
        $this->assertCount(0, $items);

        $items = catalog_helper::search('test', 0, 10);
        $this->assertCount(5, $items);

        usort($items, function ($x, $y) {
            return $x->type <=> $y->type;
        });

        $this->assertEquals('Test certification', $items[0]->name);
        $this->assertEquals('Certifications', $items[0]->type);
        $this->assertEquals('Miscellaneous', $items[0]->category);
        $this->assertEquals('pukapuka', $items[0]->summary);
        $this->assertEquals('Test certification', $items[0]->image->alt);
        $this->assertEquals('Go to certification', $items[0]->link->label);
        $this->assertObjectHasAttribute('url', $items[0]->image);
        $this->assertObjectHasAttribute('url', $items[0]->link);

        $this->assertEquals('Test course', $items[1]->name);
        $this->assertEquals('Courses', $items[1]->type);
        $this->assertEquals('Miscellaneous', $items[1]->category);
        $this->assertEquals('akoranga', $items[1]->summary);
        $this->assertEquals('Test course', $items[1]->image->alt);
        $this->assertEquals('Go to course', $items[1]->link->label);
        $this->assertObjectHasAttribute('url', $items[1]->image);
        $this->assertObjectHasAttribute('url', $items[1]->link);

        $this->assertEquals('Test playlist', $items[2]->name);
        $this->assertEquals('Playlists', $items[2]->type);
        $this->assertEquals('', $items[2]->category);
        $this->assertEquals('', $items[2]->summary);
        $this->assertEquals('Test playlist', $items[2]->image->alt);
        $this->assertEquals('View', $items[2]->link->label);
        $this->assertObjectHasAttribute('url', $items[2]->image);
        $this->assertObjectHasAttribute('url', $items[2]->link);

        $this->assertEquals('Test program', $items[3]->name);
        $this->assertEquals('Programs', $items[3]->type);
        $this->assertEquals('Miscellaneous', $items[3]->category);
        $this->assertEquals('marau', $items[3]->summary);
        $this->assertEquals('Test program', $items[3]->image->alt);
        $this->assertEquals('Go to program', $items[3]->link->label);
        $this->assertObjectHasAttribute('url', $items[3]->image);
        $this->assertObjectHasAttribute('url', $items[3]->link);

        $this->assertEquals('Test article', $items[4]->name);
        $this->assertEquals('Resources', $items[4]->type);
        $this->assertEquals('', $items[4]->category);
        $this->assertEquals('', $items[4]->summary);
        $this->assertEquals('Test article', $items[4]->image->alt);
        $this->assertEquals('View', $items[4]->link->label);
        $this->assertObjectHasAttribute('url', $items[4]->image);
        $this->assertObjectHasAttribute('url', $items[4]->link);
    }
}
