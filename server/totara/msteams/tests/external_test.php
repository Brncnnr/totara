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
use totara_msteams\external;
use totara_playlist\playlist;

class totara_msteams_external_testcase extends advanced_testcase {
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

    public function test_search_catalog() {
        $result = external::search_catalog(null, 1, 3);
        $this->assertArrayNotHasKey('empty', $result);
        $this->assertCount(3, $result['items']);
        $this->assertEquals(1, $result['from']);
        $this->assertEquals(3, $result['limit']);
        $this->assertTrue($result['more']);

        $result = external::search_catalog('', 0, 10);
        $this->assertArrayNotHasKey('empty', $result);
        $this->assertCount(5, $result['items']);
        $this->assertEquals(0, $result['from']);
        $this->assertEquals(5, $result['limit']);
        $this->assertFalse($result['more']);

        $result = external::search_catalog('blahblah', 0, 10);
        $this->assertArrayHasKey('empty', $result);
        $this->assertCount(0, $result['items']);
        $this->assertEquals(0, $result['from']);
        $this->assertEquals(0, $result['limit']);
        $this->assertFalse($result['more']);
    }
}
