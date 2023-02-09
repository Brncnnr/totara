<?php
/*
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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test for modifications in file_storage class.
 */
class totara_core_file_storage_testcase extends advanced_testcase {
    /**
     * Added cache reset param.
     */
    public function test_content_exists() {
        global $CFG;

        remove_dir($CFG->dataroot . '/filedir', true);
        clearstatcache(true);

        $fs = get_file_storage();
        $content = 'test_content_exists';
        $contenthash = sha1($content);

        $fs->add_string_to_pool($content);
        $this->assertTrue($fs->content_exists($contenthash));
        $this->assertTrue($fs->content_exists($contenthash), true);
        $this->assertTrue($fs->content_exists($contenthash), false);
    }

    /**
     * Added method.
     */
    public function test_validate_content() {
        global $CFG;

        remove_dir($CFG->dataroot . '/filedir', true);
        clearstatcache(true);

        $fs = get_file_storage();
        $content = 'test_validate_content';
        $contenthash = sha1($content);

        $fs->add_string_to_pool($content);
        $this->assertTrue($fs->validate_content($contenthash));
        $this->assertTrue($fs->content_exists($contenthash), true);
        $this->assertTrue($fs->validate_content($contenthash, true));
        $this->assertTrue($fs->content_exists($contenthash), true);
        $this->assertTrue($fs->validate_content($contenthash, false));
        $this->assertTrue($fs->content_exists($contenthash), true);

        $content = 'test_validate_content nonexistent ';
        $contenthash = sha1($content);
        $this->assertFalse($fs->validate_content($contenthash));
        $this->assertFalse($fs->content_exists($contenthash), true);

        $content = 'test_validate_content invalid ';
        $contenthash = sha1($content);
        $fs->add_string_to_pool($content);
        $filepath = $CFG->dataroot . '/filedir/' . $contenthash[0].$contenthash[1] . '/' . $contenthash[2].$contenthash[3] . '/' . $contenthash;
        $this->assertFileExists($filepath);
        $this->assertTrue($fs->content_exists($contenthash), true);
        file_put_contents($filepath, 'grrr');
        $this->assertTrue($fs->content_exists($contenthash));
        $this->assertFalse($fs->validate_content($contenthash));
        $this->assertTrue($fs->content_exists($contenthash));
        $this->assertFalse($fs->validate_content($contenthash, false));
        $this->assertTrue($fs->content_exists($contenthash));
        $this->assertFalse($fs->validate_content($contenthash, true));
        $this->assertFalse($fs->content_exists($contenthash));
        $this->assertFileDoesNotExist($filepath);
    }

    /**
     * Parameter can be a content hash too.
     */
    public function test_try_content_recovery() {
        global $CFG;

        remove_dir($CFG->dataroot . '/filedir', true);
        clearstatcache(true);

        $fs = get_file_storage();
        $content = 'test_validate_content';
        $contenthash = sha1($content);
        $fs->add_string_to_pool($content);
        $this->assertTrue($fs->content_exists($contenthash));
        $fs->deleted_file_cleanup($contenthash);
        $this->assertFalse($fs->content_exists($contenthash));

        $this->assertTrue($fs->try_content_recovery($contenthash));
        $this->assertTrue($fs->content_exists($contenthash));
    }

    public function test_get_content_length() {
        global $CFG;

        remove_dir($CFG->dataroot . '/filedir', true);
        clearstatcache(true);

        $fs = get_file_storage();
        $content = 'test_validate_content';
        $contenthash = sha1($content);
        $fs->add_string_to_pool($content);
        $this->assertTrue($fs->content_exists($contenthash));

        $this->assertSame(strlen($content), $fs->get_content_length($contenthash));
    }

    public function test_get_content_stream() {
        global $CFG;

        remove_dir($CFG->dataroot . '/filedir', true);
        clearstatcache(true);

        $fs = get_file_storage();
        $content = 'test_validate_content';
        $contenthash = sha1($content);
        $fs->add_string_to_pool($content);
        $this->assertTrue($fs->content_exists($contenthash));

        $handle = $fs->get_content_stream($contenthash);
        $result = stream_get_contents($handle);
        fclose($handle);

        $this->assertSame($result, $content);
    }
}
