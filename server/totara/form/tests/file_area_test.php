<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

use totara_form\file_area;

/**
 * Tests for \totara_form\file_area class.
 */
class totara_form_file_area_testcase extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        // Clear the file status cache between tests to reduce interference
        clearstatcache();
    }

    public function test_normalise_accept_attribute() {

        // File extensions.
        $this->assertSame('.gif', file_area::normalise_accept_attribute('.gif'));
        $this->assertSame('.jpg', file_area::normalise_accept_attribute('.jpg'));
        $this->assertSame('.doc', file_area::normalise_accept_attribute('.doc'));

        // Wild cards
        $this->assertSame('audio/*', file_area::normalise_accept_attribute('audio/*'));
        $this->assertSame('video/*', file_area::normalise_accept_attribute('video/*'));
        $this->assertSame('image/*', file_area::normalise_accept_attribute('image/*'));

        // Mime types.
        $this->assertSame('text/plain', file_area::normalise_accept_attribute('text/plain'));

        // Totara mime groups.
        $this->assertSame('video', file_area::normalise_accept_attribute('video'));
        $this->assertSame('web_video', file_area::normalise_accept_attribute('web_video'));

        // Totara wildcard.
        $this->assertSame(null, file_area::normalise_accept_attribute(''));
        $this->assertSame(null, file_area::normalise_accept_attribute('*'));

        // Normalisation.
        $this->assertSame(null, file_area::normalise_accept_attribute(array('*', '.dd')));
        $this->assertSame(null, file_area::normalise_accept_attribute('*,.dd'));
        $this->assertSame(null, file_area::normalise_accept_attribute(',.dd'));
        $this->assertSame(null, file_area::normalise_accept_attribute('* '));
        $this->assertSame(null, file_area::normalise_accept_attribute(' '));
        $this->assertSame('.txt', file_area::normalise_accept_attribute('.txt'));
        $this->assertSame('.txt', file_area::normalise_accept_attribute('.txt '));
        $this->assertSame('.txt', file_area::normalise_accept_attribute(array('.txt')));
        $this->assertSame('.txt', file_area::normalise_accept_attribute(array('.txt ')));
        $this->assertSame('.txt,.doc', file_area::normalise_accept_attribute('.txt,.doc'));
        $this->assertSame('.txt,.doc', file_area::normalise_accept_attribute('.txt, .doc'));
        $this->assertSame('.txt,.doc', file_area::normalise_accept_attribute(array('.txt', '.doc')));

        // TODO TL-9424: add debugging stuff on malformed data
    }

    public function test_accept_attribute_to_accepted_types() {
        $this->assertSame('*', file_area::accept_attribute_to_accepted_types('*'));
        $this->assertSame('*', file_area::accept_attribute_to_accepted_types('*,.doc'));
        $this->assertSame('*', file_area::accept_attribute_to_accepted_types(''));
        $this->assertSame('*', file_area::accept_attribute_to_accepted_types(null));

        $this->assertSame('.doc', file_area::accept_attribute_to_accepted_types('.doc'));
        $this->assertSame(array('.doc', '.txt'), file_area::accept_attribute_to_accepted_types('.doc,.txt'));

        // Map to groups.
        $this->assertSame('audio', file_area::accept_attribute_to_accepted_types('audio/*'));
        $this->assertSame('video', file_area::accept_attribute_to_accepted_types('video/*'));
        $this->assertSame('image', file_area::accept_attribute_to_accepted_types('image/*'));

        // Mime types.
        $this->assertSame('text/plain', file_area::accept_attribute_to_accepted_types('text/plain'));
    }

    public function test_is_accepted_file() {
        $this->assertTrue(file_area::is_accepted_file('*', 'xxx.doc', 'application/msword'));
        $this->assertTrue(file_area::is_accepted_file('.doc,.mp4', 'xxx.doc', 'application/msword'));
        $this->assertTrue(file_area::is_accepted_file('.doc,.mp4', 'xxx.mp4', 'video/mp4'));

        $this->assertTrue(file_area::is_accepted_file('.doc', 'xxx.doc', 'application/msword'));
        $this->assertTrue(file_area::is_accepted_file('document', 'xxx.doc', 'application/msword'));
        $this->assertTrue(file_area::is_accepted_file('application/msword', 'xxx.doc', 'application/msword'));
        $this->assertTrue(file_area::is_accepted_file('.doc', 'xxx.doc', 'application/xxxxx'));
        $this->assertTrue(file_area::is_accepted_file('.doc', 'xxx.docx', 'application/msword'));
        $this->assertFalse(file_area::is_accepted_file('document', 'xxx.xxx', 'application/msword'));

        $this->assertTrue(file_area::is_accepted_file('.mp4', 'xxx.mp4', 'video/mp4'));
        $this->assertTrue(file_area::is_accepted_file('video', 'xxx.mp4', 'video/mp4'));
        $this->assertTrue(file_area::is_accepted_file('web_video', 'xxx.mp4', 'video/mp4'));
        $this->assertTrue(file_area::is_accepted_file('video/*', 'xxx.mp4', 'video/mp4'));
        $this->assertTrue(file_area::is_accepted_file('video/mp4', 'xxx.mp4', 'video/mp4'));
        $this->assertTrue(file_area::is_accepted_file('.mp4', 'xxx.xxx', 'video/mp4'));
        $this->assertFalse(file_area::is_accepted_file('video', 'xxx.xxx', 'video/mp4'));
        $this->assertFalse(file_area::is_accepted_file('web_video', 'xxx.xxx', 'video/mp4'));
    }

    public function test_rewrite_links_to_draftarea() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontextid = \context_user::instance($user->id)->id;

        $text = "xxx @@PLUGINFILE@@/somefile.txt <br /> @@PLUGINFILE@@/otherfile.doc\nzzzz";
        $expected = "xxx https://www.example.com/moodle/draftfile.php/$usercontextid/user/draft/666/somefile.txt <br /> https://www.example.com/moodle/draftfile.php/$usercontextid/user/draft/666/otherfile.doc\nzzzz";

        $this->assertSame($expected, file_area::rewrite_links_to_draftarea($text, 666));
    }

    public function test_create_draft_area() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = \context_user::instance($user->id);
        $syscontext = context_system::instance();
        $fs = get_file_storage();

        $filearea = new file_area($syscontext, 'totara_core', 'testarea', null);
        $draftitemid = $filearea->create_draft_area();
        $this->assertIsNumeric($draftitemid);
        $this->assertFalse($fs->file_exists($usercontext->id, 'user', 'draft', $draftitemid, '/', '.'));

        $filearea = new file_area($syscontext, 'totara_core', 'testarea', 0);
        $draftitemid = $filearea->create_draft_area();
        $this->assertIsNumeric($draftitemid);
        $this->assertFalse($fs->file_exists($usercontext->id, 'user', 'draft', $draftitemid, '/', '.'));

        $file = $fs->create_file_from_string(['contextid' => $syscontext->id, 'component' => 'totara_core', 'filearea' => 'testarea', 'itemid' => 0, 'filepath' => '/', 'filename' => 'test.jpg'], 'abc');
        $filearea = new file_area($syscontext, 'totara_core', 'testarea', 0);
        $draftitemid = $filearea->create_draft_area();
        $this->assertIsNumeric($draftitemid);
        $this->assertTrue($fs->file_exists($usercontext->id, 'user', 'draft', $draftitemid, '/', '.'));
        $this->assertTrue($fs->file_exists($usercontext->id, 'user', 'draft', $draftitemid, '/', 'test.jpg'));
    }

    public function test_update_file_area() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = \context_user::instance($user->id);
        $syscontext = context_system::instance();
        $fs = get_file_storage();

        $filearea = new file_area($syscontext, 'totara_core', 'testarea', null);
        $draftitemid = $filearea->create_draft_area();
        $this->assertIsNumeric($draftitemid);
        $this->assertFalse($fs->file_exists($usercontext->id, 'user', 'draft', $draftitemid, '/', '.'));
        $file = $fs->create_file_from_string(['contextid' => $usercontext->id, 'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftitemid, 'filepath' => '/', 'filename' => 'test.jpg'], 'abc');
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid);
        $this->assertCount(2, $files);

        $filearea->update_file_area($files, ['subdirs' => true], $syscontext, 3);
        $this->assertTrue($fs->file_exists($syscontext->id, 'totara_core', 'testarea', 3, '/', '.'));
        $this->assertTrue($fs->file_exists($syscontext->id, 'totara_core', 'testarea', 3, '/', 'test.jpg'));

        $filearea = new file_area($syscontext, 'totara_core', 'testarea', 3);
        $draftitemid = $filearea->create_draft_area();
        $file = $fs->create_file_from_string(['contextid' => $usercontext->id, 'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftitemid, 'filepath' => '/', 'filename' => 'test2.jpg'], 'xyz');
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid);
        $this->assertCount(3, $files);

        $filearea->update_file_area($files, ['subdirs' => true], $syscontext, 3);
        $this->assertTrue($fs->file_exists($syscontext->id, 'totara_core', 'testarea', 3, '/', '.'));
        $this->assertTrue($fs->file_exists($syscontext->id, 'totara_core', 'testarea', 3, '/', 'test.jpg'));
        $this->assertTrue($fs->file_exists($syscontext->id, 'totara_core', 'testarea', 3, '/', 'test2.jpg'));
    }
}
